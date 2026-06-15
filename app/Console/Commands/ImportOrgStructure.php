<?php

namespace App\Console\Commands;

use App\Enums\OrgStatus;
use App\Jobs\SyncOrgStructure;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Support\PositionResolver;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class ImportOrgStructure extends Command
{
    protected $signature = 'org:import
        {--file=storage/app/tj_structure.json : Path to the API JSON dump}
        {--api : Fetch the structure live from the Tojiktelecom API instead of reading --file}
        {--fresh : Wipe existing branches/departments/employees/vacancies/rotations before importing}
        {--sync : Run the import inline instead of dispatching it to the background queue}';

    protected $description = 'Import the Tojiktelecom org structure (company → departments → employees) from the API JSON dump, 1:1 with the source tree';

    public function handle(): int
    {
        if (! $this->option('sync')) {
            SyncOrgStructure::dispatch(
                api: (bool) $this->option('api'),
                file: (string) $this->option('file'),
                fresh: (bool) $this->option('fresh'),
            );

            $this->info('Org structure sync queued.');

            return self::SUCCESS;
        }

        $json = $this->option('api') ? $this->fetchFromApi() : $this->readFromFile();
        if ($json === null) {
            return self::FAILURE;
        }

        // Записи верхнего уровня — это компании (businessUnits). Каждая владеет
        // рекурсивным деревом подразделений. Всё дерево одной компании держим в
        // одном филиале (branch), чтобы оно оставалось связным ровно как в
        // источнике, и записываем собственный businessUnit каждого узла как
        // метаданные.
        $companies = $json['data'];

        // Разворачиваем дерево каждой компании в id => node (pre-order: родители
        // первыми), помечая каждый узел владеющей компанией и глобальным
        // sort_order, отражающим порядок в источнике.
        $nodes = [];
        $order = 0;
        foreach ($companies as $company) {
            $this->flatten($company['departments'] ?? [], null, (int) $company['id'], $nodes, $order);
        }

        // Чиним границу компания↔филиал до того, как что-либо строится: API
        // помечает узел-заголовок каждого филиала unit'ом родительской компании,
        // что иначе оставило бы корневое подразделение филиала и его сотрудников
        // в неправильном филиале. См. normalizeBusinessUnits().
        $promoted = $this->normalizeBusinessUnits($nodes);
        $this->info('Companies: '.count($companies).' / Source nodes: '.count($nodes).' / Filial headers re-tagged: '.$promoted);

        $positions = new PositionResolver;

        // Логирование активности подавлено, чтобы не плодить сотни записей лога.
        activity()->withoutLogs(function () use ($companies, $nodes, $positions) {
            DB::transaction(function () use ($companies, $nodes, $positions) {
                if ($this->option('fresh')) {
                    $this->warn('--fresh: clearing employees, departments, vacancies, rotations and branches...');
                    DB::table('rotations')->delete();
                    DB::table('vacancies')->delete();
                    DB::table('employees')->delete(); // departments.manager_id -> SET NULL
                    // parent_id — это RESTRICT, поэтому удаляем дерево от листьев
                    // (обнуление parent_id конфликтовало бы с частичным unique-
                    // индексом по именам корневых подразделений).
                    do {
                        $removed = DB::table('departments')
                            ->whereNotExists(function ($q) {
                                $q->select(DB::raw(1))
                                    ->from('departments as child')
                                    ->whereColumn('child.parent_id', 'departments.id');
                            })
                            ->delete();
                    } while ($removed > 0);
                    DB::table('branches')->delete(); // users.branch_id -> SET NULL
                }

                // 1. Филиалы (branches): по одному на каждый отдельный
                //    businessUnit (компания плюс каждый региональный филиал).
                //    Записи компаний верхнего уровня несут полные метаданные
                //    (legalName/tin/CEO); unit'ы филиалов имеют только имя,
                //    взятое из businessUnitName их узлов.
                $companyMeta = [];
                foreach ($companies as $company) {
                    $companyMeta[(int) $company['id']] = $company;
                }

                $buNames = [];
                foreach ($nodes as $n) {
                    $bu = $this->businessUnit($n);
                    if (! isset($buNames[$bu])) {
                        $buNames[$bu] = $n['businessUnitName'] ?? ('Филиал '.$bu);
                    }
                }

                $branchMap = [];
                foreach ($buNames as $buId => $buName) {
                    $meta = $companyMeta[$buId] ?? null;
                    $branch = Branch::updateOrCreate(
                        ['external_id' => $buId],
                        [
                            'name' => $meta['name'] ?? $buName,
                            'legal_name' => $meta['legalName'] ?? null,
                            'tin' => $meta['tin'] ?? null,
                            'ceo_external_id' => $meta['ceoId'] ?? null,
                            'head_company_external_id' => $meta['headCompanyId'] ?? null,
                            'status' => OrgStatus::tryFrom($meta['status'] ?? '') ?? OrgStatus::ACTIVE,
                            'code' => 'BU'.$buId,
                        ],
                    );
                    $branchMap[$buId] = $branch->id;
                }
                $this->info('Branches: '.count($branchMap));

                // 2. Подразделения (departments): в рамках своего businessUnit.
                //    Узел сохраняет родителя только если тот в том же филиале;
                //    иначе становится корнем собственного филиала (чтобы каждый
                //    филиал рисовался как самодостаточное дерево).
                $deptMap = [];
                foreach ($nodes as $n) {
                    $bu = $this->businessUnit($n);
                    $parentDeptId = null;
                    $parentId = $n['parentId'];
                    if ($parentId !== null
                        && isset($nodes[$parentId])
                        && $this->businessUnit($nodes[$parentId]) === $bu
                        && isset($deptMap[$parentId])) {
                        $parentDeptId = $deptMap[$parentId];
                    }

                    $code = $n['code'] !== null && $n['code'] !== '' ? mb_substr((string) $n['code'], 0, 20) : null;

                    $dept = Department::updateOrCreate(
                        ['external_id' => $n['id']],
                        [
                            'branch_id' => $branchMap[$bu],
                            'parent_id' => $parentDeptId,
                            'name' => $n['name'],
                            'short_name' => $n['shortName'],
                            'code' => $code,
                            'status' => OrgStatus::tryFrom($n['status'] ?? ''),
                            'sort_order' => $n['sortOrder'],
                        ],
                    );
                    $deptMap[$n['id']] = $dept->id;
                }
                $this->info('Departments: '.count($deptMap));

                // 3. Сотрудники.
                $personMap = []; // personId источника    => employee.id
                $empById = [];   // id сотрудника источника => employee.id
                $empOrder = 0;
                $empCount = 0;
                $seenEmails = []; // точное значение email => true, чтобы соблюсти unique-индекс
                foreach ($nodes as $n) {
                    $branchId = $branchMap[$this->businessUnit($n)];
                    $deptId = $deptMap[$n['id']] ?? null;

                    foreach ($n['employees'] as $e) {
                        // Email уникален на сотрудника (частичный unique-индекс,
                        // NULL исключён). В источнике один и тот же адрес-заглушка
                        // (например, net@info.tj) встречается у множества людей,
                        // поэтому оставляем первое вхождение, а остальным ставим
                        // NULL вместо падения.
                        $email = isset($e['email']) && is_string($e['email']) && trim($e['email']) !== ''
                            ? trim($e['email'])
                            : null;
                        if ($email !== null) {
                            if (isset($seenEmails[$email])) {
                                $email = null;
                            } else {
                                $seenEmails[$email] = true;
                            }
                        }

                        $emp = Employee::updateOrCreate(
                            ['external_id' => $e['id']],
                            [
                                'person_id' => $e['personId'] ?? null,
                                'branch_id' => $branchId,
                                'department_id' => $deptId,
                                'position_id' => $positions->resolve($e['jobTitleId'] ?? null, $e['jobTitleName'] ?? null),
                                'full_name' => $e['name'] ?? 'Номаълум',
                                'phone_number' => $e['phone'] ?? null,
                                'email' => $email,
                                'status' => OrgStatus::tryFrom($e['status'] ?? ''),
                                'sort_order' => $empOrder++,
                                'gender' => null,
                                'hire_date' => null,
                            ],
                        );
                        if (! empty($e['personId'])) {
                            $personMap[$e['personId']] = $emp->id;
                        }
                        $empById[$e['id']] = $emp->id;
                        $empCount++;
                    }
                }
                $this->info('Employees: '.$empCount);

                // 4. Связи с руководителями: managerId источника — это id
                //    сотрудника (personId оставлен лишь как устаревший запасной
                //    вариант). Назначаем руководителя подразделения и указываем
                //    его каждому участнику.
                $deptLinked = 0;
                $empLinked = 0;
                foreach ($nodes as $n) {
                    if ($n['managerId'] === null) {
                        continue;
                    }
                    $managerEmpId = $empById[$n['managerId']] ?? $personMap[$n['managerId']] ?? null;
                    if ($managerEmpId === null) {
                        continue;
                    }

                    if (isset($deptMap[$n['id']])) {
                        DB::table('departments')->where('id', $deptMap[$n['id']])
                            ->update(['manager_id' => $managerEmpId]);
                        $deptLinked++;
                    }

                    foreach ($n['employees'] as $e) {
                        $empId = $empById[$e['id']] ?? null;
                        if ($empId !== null && $empId !== $managerEmpId) {
                            DB::table('employees')->where('id', $empId)->update(['manager_id' => $managerEmpId]);
                            $empLinked++;
                        }
                    }
                }
                $this->info("Manager links: departments={$deptLinked}, employees={$empLinked}");
            });
        });

        $this->info('Import finished.');

        return self::SUCCESS;
    }

    /**
     * Читает JSON структуры из локального дампа --file.
     *
     * @return array<string,mixed>|null null при любой ошибке (уже сообщена)
     */
    private function readFromFile(): ?array
    {
        $path = base_path((string) $this->option('file'));
        if (! is_file($path)) {
            $this->error("File not found: {$path}");

            return null;
        }

        $json = json_decode((string) file_get_contents($path), true);
        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($json) || empty($json['data'])) {
            $this->error('Invalid or empty JSON payload.');

            return null;
        }

        return $json;
    }

    /**
     * Получает структуру вживую из API оргструктуры Tojiktelecom.
     *
     * Версионированный эндпоинт (/api/v1/organization/structure) аутентифицируется
     * статичным bearer-токеном, поэтому один GET возвращает всё дерево компания →
     * подразделения → сотрудники. Маппинг updateOrCreate ниже затем сверяет наши
     * таблицы с источником — везде по ключу external_id, так что повторные запуски
     * обновляют существующие строки на месте, а не дублируют их.
     *
     * @return array<string,mixed>|null null при любой ошибке (уже сообщена)
     */
    private function fetchFromApi(): ?array
    {
        $token = (string) config('services.tojiktelecom.token');

        if ($token === '') {
            $this->error('Set TOJIKTELECOM_TOKEN in .env to sync from the API.');

            return null;
        }

        try {
            $url = (string) config('services.tojiktelecom.structure_url');
            $response = Http::acceptJson()
                ->withToken($token)
                ->timeout(60)
                ->retry(2, 2000)
                ->get($url, ['with_employees' => 'true']);
        } catch (\Throwable $e) {
            $this->error('API request failed: '.$e->getMessage());

            return null;
        }

        if (! $response->successful()) {
            $this->error("API returned HTTP {$response->status()}: ".mb_substr($response->body(), 0, 200));

            return null;
        }

        $json = $response->json();
        if (! is_array($json) || empty($json['data'])) {
            $this->error('Structure response was not valid JSON (empty "data") — the session may have expired.');

            return null;
        }

        return $json;
    }

    /**
     * Разворачивает дерево (вложенность через "departments" и/или "children") в
     * pre-order карту по id узла, фиксируя id родителя, владеющую компанию и
     * монотонно возрастающий sort_order для каждого узла.
     *
     * @param  array<int,array<string,mixed>>  $nodes
     * @param  array<int,array<string,mixed>>  $out
     */
    private function flatten(array $nodes, ?int $parentId, int $companyId, array &$out, int &$order): void
    {
        foreach ($nodes as $n) {
            $id = (int) $n['id'];
            $out[$id] = [
                'id' => $id,
                'name' => $n['name'] ?? ('Воҳид '.$id),
                'shortName' => $n['shortName'] ?? null,
                'code' => $n['code'] ?? null,
                'status' => $n['status'] ?? null,
                'businessUnitId' => $n['businessUnitId'] ?? null,
                'businessUnitName' => $n['businessUnitName'] ?? null,
                'companyId' => $companyId,
                'parentId' => $parentId,
                'managerId' => $n['managerId'] ?? null,
                'sortOrder' => $order++,
                'employees' => $n['employees'] ?? [],
            ];

            $kids = [];
            foreach (['departments', 'children'] as $k) {
                if (! empty($n[$k]) && is_array($n[$k])) {
                    $kids = array_merge($kids, $n[$k]);
                }
            }
            $this->flatten($kids, $id, $companyId, $out, $order);
        }
    }

    /**
     * Business unit, которому принадлежит развёрнутый узел: его собственный
     * businessUnitId, если он проставлен, иначе id владеющей компании (всегда есть).
     *
     * @param  array<string,mixed>  $node
     */
    private function businessUnit(array $node): int
    {
        return $node['businessUnitId'] ?? $node['companyId'];
    }

    /**
     * Чинит границу business-unit компания↔филиал в данных источника.
     *
     * Источник вкладывает всю оргструктуру как одно дерево родитель→потомок и так
     * же её отображает; теги businessUnit — ненадёжный второй слой. Филиал вводится
     * узлом-заголовком «Филиал …» (региональные офисы и центральный филиал);
     * настоящие подразделения никогда не начинаются с этого слова. API помечает
     * каждый заголовок филиала unit'ом *компании* и непоследователен в отношении
     * подразделений под ним:
     *
     *  - Региональные филиалы (Суғд, Хатлон, …): каждое подразделение несёт
     *    собственный unit филиала, поэтому unit очевиден из поддерева.
     *  - Центральный филиал (#137): все его подразделения, кроме одного, ошибочно
     *    помечены как компания, оставляя единственный корректно помеченный узел,
     *    раскрывающий настоящий unit — этого достаточно.
     *
     * Поэтому для каждого заголовка филиала берём доминирующий *не-компанийный*
     * unit, найденный где-либо в его поддереве, и перемечаем им заголовок и всё
     * его поддерево. Это перестраивает каждый филиал как один связный филиал,
     * соответствующий дереву источника, вместо того чтобы оставить заголовок (и
     * большинство его подразделений) в компании, пока одинокий потомок образует
     * филиал из одного подразделения.
     *
     * @param  array<int,array<string,mixed>>  $nodes  id => node, по ссылке
     * @return int количество перемеченных узлов
     */
    private function normalizeBusinessUnits(array &$nodes): int
    {
        // parentId => [childId, ...]
        $childrenOf = [];
        foreach ($nodes as $id => $n) {
            if ($n['parentId'] !== null) {
                $childrenOf[$n['parentId']][] = $id;
            }
        }

        $isFilialHeader = static function (array $n): bool {
            return str_starts_with(mb_strtolower(trim((string) ($n['name'] ?? ''))), 'филиал');
        };

        // Первое businessUnitName, встреченное для каждого unit, чтобы
        // перемеченное поддерево могло нести отображаемое имя филиала.
        $buName = [];
        foreach ($nodes as $n) {
            $bu = $n['businessUnitId'] ?? null;
            if ($bu !== null && ! isset($buName[$bu]) && ! empty($n['businessUnitName'])) {
                $buName[$bu] = $n['businessUnitName'];
            }
        }

        // Собирает потомков узла, не пересекая вложенный заголовок филиала.
        $descendants = function (int $rootId) use ($childrenOf, $isFilialHeader, $nodes): array {
            $out = [];
            $stack = $childrenOf[$rootId] ?? [];
            while ($stack) {
                $id = array_pop($stack);
                $out[] = $id;
                if (! $isFilialHeader($nodes[$id])) {
                    foreach (($childrenOf[$id] ?? []) as $child) {
                        $stack[] = $child;
                    }
                }
            }

            return $out;
        };

        $promoted = 0;
        foreach ($nodes as $id => $n) {
            if (! $isFilialHeader($n)) {
                continue;
            }

            $company = $n['companyId'];
            $subtree = array_merge([$id], $descendants($id));

            // Доминирующий не-компанийный unit по заголовку и его поддереву.
            $tally = [];
            foreach ($subtree as $nid) {
                $bu = $nodes[$nid]['businessUnitId'] ?? $company;
                if ($bu !== $company) {
                    $tally[$bu] = ($tally[$bu] ?? 0) + 1;
                }
            }
            if (! $tally) {
                continue; // узел уровня компании, просто названный «Филиал…» — оставляем
            }
            arsort($tally);
            $filialBu = array_key_first($tally);
            $filialName = $buName[$filialBu] ?? ($n['businessUnitName'] ?? null);

            foreach ($subtree as $nid) {
                if (($nodes[$nid]['businessUnitId'] ?? null) !== $filialBu) {
                    $nodes[$nid]['businessUnitId'] = $filialBu;
                    $nodes[$nid]['businessUnitName'] = $filialName;
                    $promoted++;
                }
            }
        }

        return $promoted;
    }
}

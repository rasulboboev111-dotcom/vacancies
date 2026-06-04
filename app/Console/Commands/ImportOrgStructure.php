<?php

namespace App\Console\Commands;

use App\Enums\OrgStatus;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class ImportOrgStructure extends Command
{
    protected $signature = 'org:import
        {--file=storage/app/tj_structure.json : Path to the API JSON dump}
        {--api : Fetch the structure live from the Tojiktelecom API instead of reading --file}
        {--fresh : Wipe existing branches/departments/employees/vacancies/rotations before importing}';

    protected $description = 'Import the Tojiktelecom org structure (company → departments → employees) from the API JSON dump, 1:1 with the source tree';

    /** @var array<string,int> position external_id (jobTitleId) => positions.id */
    private array $positionByExternal = [];

    /** @var array<string,int> position name (lowercased) => positions.id */
    private array $positionByName = [];

    public function handle(): int
    {
        $json = $this->option('api') ? $this->fetchFromApi() : $this->readFromFile();
        if ($json === null) {
            return self::FAILURE;
        }

        // The top-level entries are the companies (businessUnits). Each owns a
        // recursive department tree. We keep the whole tree of one company in a
        // single branch so it stays connected exactly like the source, and we
        // record each node's own businessUnit as metadata.
        $companies = $json['data'];

        // Flatten every company's tree into id => node (pre-order: parents
        // first), tagging each node with its owning company and a global
        // sort_order that mirrors the source ordering.
        $nodes = [];
        $order = 0;
        foreach ($companies as $company) {
            $this->flatten($company['departments'] ?? [], null, (int) $company['id'], $nodes, $order);
        }

        // Repair the company↔filial boundary before anything is built: the API
        // tags each filial's header node with the parent company's unit, which
        // would otherwise strand the filial's root department and its staff in
        // the wrong branch. See normalizeBusinessUnits().
        $promoted = $this->normalizeBusinessUnits($nodes);
        $this->info('Companies: '.count($companies).' / Source nodes: '.count($nodes).' / Filial headers re-tagged: '.$promoted);

        // Activity logging is suppressed so we do not emit hundreds of log rows.
        activity()->withoutLogs(function () use ($companies, $nodes) {
            DB::transaction(function () use ($companies, $nodes) {
                if ($this->option('fresh')) {
                    $this->warn('--fresh: clearing employees, departments, vacancies, rotations and branches...');
                    DB::table('rotations')->delete();
                    DB::table('vacancies')->delete();
                    DB::table('employees')->delete(); // departments.manager_id -> SET NULL
                    // parent_id is RESTRICT, so delete the tree leaves-first
                    // (nulling parent_id would clash with the partial unique
                    // index on root department names).
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

                // 1. Branches: one per distinct businessUnit (the company plus
                //    each regional filial). Top-level company entries carry the
                //    rich metadata (legalName/tin/CEO); filial units only have a
                //    name, taken from businessUnitName on their nodes.
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

                // 2. Departments: scoped per businessUnit. A node keeps its
                //    headOffice as parent only when that parent is in the same
                //    branch; otherwise it becomes a root of its own filial
                //    branch (so each filial renders as a self-contained tree).
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

                // 3. Employees.
                $personMap = []; // source personId    => employee.id
                $empById = [];   // source employee id => employee.id
                $empOrder = 0;
                $empCount = 0;
                $seenEmails = []; // exact email value => true, to honour the unique index
                foreach ($nodes as $n) {
                    $branchId = $branchMap[$this->businessUnit($n)];
                    $deptId = $deptMap[$n['id']] ?? null;

                    foreach ($n['employees'] as $e) {
                        // Email is unique per employee (partial unique index, NULL
                        // exempt). The source data shares placeholder addresses
                        // (e.g. net@info.tj) across many people, so keep the first
                        // occurrence and NULL the rest instead of crashing.
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
                                'position_id' => $this->resolvePosition($e['jobTitleId'] ?? null, $e['jobTitleName'] ?? null),
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

                // 4. Manager links: the source managerId is an employee id (a
                //    personId is kept only as a legacy fallback). Set the
                //    department's manager and point each member at that manager.
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
     * Read the structure JSON from the local --file dump.
     *
     * @return array<string,mixed>|null null on any error (already reported)
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
     * Fetch the structure live from the Tojiktelecom API.
     *
     * Access is gated by the company SSO (auth.tojiktelecom.tj, OAuth code
     * flow), so there is no static API token: we sign in with service-account
     * credentials, let a shared cookie jar carry the session across the
     * authorize→callback redirect chain, then call the structure endpoint. This
     * is the path the daily scheduled sync takes; the updateOrCreate mapping
     * below then reconciles our tables with the source.
     *
     * @return array<string,mixed>|null null on any error (already reported)
     */
    private function fetchFromApi(): ?array
    {
        $email = (string) config('services.tojiktelecom.email');
        $password = (string) config('services.tojiktelecom.password');

        if ($email === '' || $password === '') {
            $this->error('Set TOJIKTELECOM_EMAIL and TOJIKTELECOM_PASSWORD in .env to sync from the API.');

            return null;
        }

        // One cookie jar shared across both domains for the whole flow — the
        // SSO sets JWT cookies on auth.* and the callback sets the app session
        // on org.*, exactly like a browser would.
        $jar = new CookieJar;
        $http = Http::withOptions([
            'cookies' => $jar,
            'allow_redirects' => ['max' => 10, 'referer' => true],
        ])->timeout(60)->withHeaders([
            'User-Agent' => 'vacancies-sync/1.0',
        ]);

        try {
            // 1) Load the login page; it redirects to the SSO authorize form,
            //    which carries the OAuth return URL we must echo back ("rd").
            $loginUrl = (string) config('services.tojiktelecom.login_url');
            $page = $http->get($loginUrl);
            if (! preg_match('/name="rd"\s+value="([^"]+)"/i', $page->body(), $m)) {
                $this->error('Could not locate the SSO return URL (rd) on the login page.');

                return null;
            }
            $rd = html_entity_decode($m[1]);

            // 2) Submit credentials. On success the SSO returns JSON with the
            //    authorize URL to follow (not an HTTP redirect).
            $signinUrl = (string) config('services.tojiktelecom.signin_url');
            $signin = $http->asForm()->acceptJson()->post($signinUrl, [
                'email' => $email,
                'password' => $password,
                'rd' => $rd,
            ]);
            $redirect = $signin->json('redirect');
            if (! is_string($redirect) || $redirect === '') {
                $this->error('SSO sign-in failed (no redirect returned) — check the credentials.');

                return null;
            }

            // 3) Follow authorize → callback → app: this is where the org
            //    session cookie gets established in the jar.
            $http->get($redirect);

            // 4) Now authenticated, pull the structure with employees.
            $url = (string) config('services.tojiktelecom.structure_url');
            $response = $http->acceptJson()
                ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
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
     * Flatten the tree (nests via "departments" and/or "children") into a
     * pre-order map keyed by node id, capturing the parent id, owning company
     * and a monotonically increasing sort_order for each node.
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
     * The business unit a flattened node belongs to: its own businessUnitId
     * when tagged, otherwise the owning company id (always present).
     *
     * @param  array<string,mixed>  $node
     */
    private function businessUnit(array $node): int
    {
        return $node['businessUnitId'] ?? $node['companyId'];
    }

    /**
     * Repair the company↔filial business-unit boundary in the source data.
     *
     * The API tags each regional filial's *header* node (e.g. "Филиал дар
     * вилояти Суғд") with the parent company's businessUnit, while every
     * department beneath it carries the filial's own businessUnit. Left as-is
     * that strands the header — and the staff attached to it, including the
     * filial director — in the parent company, and leaves the filial branch as
     * a headless forest of root departments.
     *
     * We promote a header to its children's unit, but only when ALL of its
     * children unanimously belong to a single *other* unit, so a lone stray tag
     * can never drag an unrelated subtree across branches. Parents whose
     * children genuinely span several units are reported, not guessed.
     *
     * One pass suffices: the source mis-tags exactly the header level, and the
     * child-unit map is read from the original tags before any change applies.
     *
     * @param  array<int,array<string,mixed>>  $nodes  id => node, by reference
     * @return int number of header nodes re-tagged
     */
    private function normalizeBusinessUnits(array &$nodes): int
    {
        // Collapse each parent's child units into a single shared unit, or
        // false when the children span more than one unit.
        $childUnit = [];     // parentId => businessUnitId | false (mixed)
        $childUnitName = []; // parentId => businessUnitName of that unit
        foreach ($nodes as $n) {
            $parentId = $n['parentId'];
            if ($parentId === null) {
                continue;
            }
            $bu = $this->businessUnit($n);
            if (! array_key_exists($parentId, $childUnit)) {
                $childUnit[$parentId] = $bu;
                $childUnitName[$parentId] = $n['businessUnitName'] ?? null;
            } elseif ($childUnit[$parentId] !== $bu) {
                $childUnit[$parentId] = false;
            }
        }

        $promoted = 0;
        foreach ($nodes as $id => &$n) {
            $kidUnit = $childUnit[$id] ?? null;

            if ($kidUnit === false) {
                $this->warn("Department #{$id} ({$n['name']}) has children across multiple business units; left as-is.");

                continue;
            }

            $bu = $this->businessUnit($n);
            if ($kidUnit !== null && $kidUnit !== $bu) {
                $n['businessUnitId'] = $kidUnit;
                $n['businessUnitName'] = $childUnitName[$id] ?? $n['businessUnitName'];
                $promoted++;
            }
        }
        unset($n);

        return $promoted;
    }

    /**
     * Resolve a job title to a Position id. The title NAME is the local
     * identity (positions enforce a case-insensitive unique name), so we match
     * by name first and merely record the source jobTitleId on that row. This
     * is required because the source legitimately reuses the same title text
     * under several distinct jobTitleIds (e.g. "Муҳандиси пешбар" = 193 & 196).
     */
    private function resolvePosition(?int $externalId, ?string $name): ?int
    {
        $name = trim((string) $name);

        if ($name !== '') {
            $key = mb_strtolower($name);
            if (isset($this->positionByName[$key])) {
                return $this->positionByName[$key];
            }

            $position = Position::whereRaw('LOWER(TRIM(name)) = LOWER(?)', [$name])->first();
            if ($position !== null) {
                // Stamp the source id onto a row that does not have one yet.
                if ($externalId !== null && $position->external_id === null) {
                    $position->update(['external_id' => $externalId]);
                }
            } else {
                $position = Position::create([
                    'external_id' => $externalId,
                    'name' => $name,
                ]);
            }

            return $this->positionByName[$key] = $position->id;
        }

        if ($externalId === null) {
            return null;
        }
        if (isset($this->positionByExternal[$externalId])) {
            return $this->positionByExternal[$externalId];
        }
        $position = Position::firstOrCreate(
            ['external_id' => $externalId],
            ['name' => 'Вазифа '.$externalId],
        );

        return $this->positionByExternal[$externalId] = $position->id;
    }
}

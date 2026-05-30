# Миграции базы данных — разбор

Документация по всем миграциям проекта **HR-платформы**. Объясняет, что делает
каждая миграция и какие приёмы/инструменты в ней использованы.

> Это справочный документ. Он ничего не меняет в схеме — только описывает уже
> существующие миграции в `database/migrations`.

---

## Стек и инструменты

| Что | Значение |
|---|---|
| Фреймворк | Laravel (миграции на `Illuminate\Database\Migrations`) |
| СУБД | PostgreSQL (`pgsql`) |
| Права/роли | библиотека **spatie/laravel-permission** |
| Журнал действий | библиотека **spatie/laravel-activitylog** |
| Конструктор схемы | `Schema::create` / `Schema::table` + `Blueprint` |
| Сырой SQL | `DB::statement(...)` — только там, где у Laravel нет builder-API |

Все миграции пишутся штатными методами Laravel. Сырой SQL применяется **только**
для того, что конструктор схемы не умеет: частичные индексы (`WHERE ...`),
индексы по выражению (`LOWER(TRIM(...))`) и `CHECK`-ограничения.

---

## Используемые приёмы (краткий справочник)

| Приём | Что делает |
|---|---|
| `$table->id()` | Первичный ключ `bigint` auto-increment |
| `$table->foreignId('x')->constrained()` | FK-колонка + внешний ключ на таблицу `xs` |
| `->nullOnDelete()` | При удалении родителя — выставить `NULL` |
| `->cascadeOnDelete()` | При удалении родителя — удалить и потомка |
| `->restrictOnDelete()` | Запретить удаление, если есть потомки |
| `$table->softDeletes()` | Колонка `deleted_at` — «мягкое» удаление |
| `$table->timestamps()` | `created_at` / `updated_at` |
| `$table->index(...)` | Обычный индекс (ускоряет фильтры/JOIN) |
| `$table->unique(...)` | Уникальный индекс |
| `$table->nullableMorphs(...)` | Полиморфная связь (`*_id` + `*_type`) |
| `DB::table()->insertOrIgnore()` | Вставка с пропуском дублей (`ON CONFLICT DO NOTHING`) |
| `DB::statement('CREATE UNIQUE INDEX ... WHERE ...')` | Частичный/выражающий индекс (нет в builder) |
| `DB::statement('ALTER TABLE ... ADD CONSTRAINT ... CHECK')` | Ограничение значений на уровне БД |

Каждая миграция содержит `up()` (применить) и `down()` (откатить) — откат
полностью обратим.

---

## Полный список (в порядке выполнения)

| # | Файл | Назначение |
|---|---|---|
| 1 | `0001_01_01_000000_create_users_table` | Пользователи, сброс пароля, сессии |
| 2 | `0001_01_01_000001_create_cache_table` | Кэш и блокировки кэша |
| 3 | `0001_01_01_000002_create_jobs_table` | Очереди задач |
| 4 | `2026_05_24_000001_create_branches_table` | Справочник филиалов |
| 5 | `2026_05_24_000001_create_categories_table` | Справочник категорий |
| 6 | `2026_05_24_000001_create_positions_table` | Справочник должностей |
| 7 | `2026_05_24_000001_create_structures_table` | Справочник структур |
| 8 | `2026_05_24_000002_create_employees_table` | Таблица сотрудников |
| 9 | `2026_05_24_000003_add_branch_id_to_users_table` | Привязка пользователя к филиалу |
| 10 | `2026_05_24_000004_create_rotations_table` | История перемещений (ротаций) |
| 11 | `2026_05_24_042617_create_permission_tables` | Таблицы ролей и прав (Spatie) |
| 12 | `2026_05_24_042618_create_activity_log_table` | Журнал действий (Spatie) |
| 13 | `2026_05_24_042619_add_event_column_to_activity_log_table` | Колонка `event` в журнал |
| 14 | `2026_05_24_042620_add_batch_uuid_column_to_activity_log_table` | Колонка `batch_uuid` в журнал |
| 15 | `2026_05_26_054526_add_soft_deletes_to_tables` | `deleted_at` для employees/branches/users |
| 16 | `2026_05_26_055500_add_indexes_to_tables` | Индексы на ключевые колонки |
| 17 | `2026_05_26_061122_add_case_insensitive_unique_index_to_positions_table` | Регистронезависимый unique на должности |
| 18 | `2026_05_26_064008_make_email_case_insensitive_unique_on_users_table` | Регистронезависимый unique на email |
| 19 | `2026_05_29_000001_create_departments_table` | Дерево отделов (шуъба) |
| 20 | `2026_05_29_000002_add_department_permissions` | Права на отделы |
| 21 | `2026_05_30_000001_create_vacancies_table` | Таблица вакансий |
| 22 | `2026_05_30_000002_add_vacancy_permissions` | Права на вакансии |
| 23 | `2026_05_30_000003_normalize_employee_gender` | Нормализация поля «пол» + CHECK |
| 24 | `2026_05_30_000004_add_department_id_to_employees` | FK `department_id` у сотрудника |
| 25 | `2026_05_30_000005_normalize_employee_lookups` | Справочники: миллат/маълумот/ихтисос/зодгоҳ |
| 26 | `2026_05_30_000006_add_employment_type_check` | CHECK на тип занятости |
| 27 | `2026_05_30_000007_add_normalization_indexes` | Индексы под новые FK |
| 28 | `2026_05_30_000008_add_case_insensitive_unique_to_lookups` | Регистронезависимый unique на справочники |

---

## Базовые миграции Laravel

### 1. `create_users_table`
Создаёт три таблицы из коробки Laravel:
- **`users`** — `id`, `name`, `email` (unique), `email_verified_at`, `password`,
  `remember_token`, таймстемпы.
- **`password_reset_tokens`** — токены сброса пароля (PK по `email`).
- **`sessions`** — серверные сессии (PK `id`, индекс `user_id` и `last_activity`).

Приёмы: `Schema::create`, `$table->id()`, `->unique()`, `rememberToken()`.

### 2. `create_cache_table`
Таблицы `cache` и `cache_locks` для драйвера кэша «database». Ключ — первичный,
`expiration` индексируется. Стандарт Laravel.

### 3. `create_jobs_table`
Таблицы для очередей: `jobs`, `job_batches`, `failed_jobs`. Нужны, если фоновые
задачи идут через драйвер «database». Стандарт Laravel.

---

## Справочники

### 4. `create_branches_table` — филиалы
`id`, `name`, `code` (unique), `address` (nullable), таймстемпы.
Базовая опорная таблица: на филиал ссылаются пользователи, сотрудники, отделы,
вакансии.

### 5–7. `categories` / `positions` / `structures`
Три одинаковых по структуре справочника: `id`, `name` (unique), таймстемпы.
- **categories** — категории сотрудников (Руководство, Специалисты…).
- **positions** — должности.
- **structures** — структурные единицы.

Это первый шаг нормализации: повторяющиеся текстовые значения вынесены в
отдельные таблицы, а сотрудник ссылается на них по FK.

---

## Сотрудники и перемещения

### 8. `create_employees_table` — сотрудники
Центральная таблица. Содержит:
- **FK-связи:** `branch_id` (`cascadeOnDelete`), `category_id`/`position_id`/
  `structure_id` (`nullOnDelete`), `manager_id` — самоссылка на руководителя
  (`nullOnDelete`).
- **Личные поля:** `full_name`, `gender`, даты (`hire_date`, `dismissal_date`,
  `birth_date`), `nationality`, паспортные данные, `inn`, `sin`, `address`,
  `phone_number`, `birth_place`, `education`, `specialty`, `employment_start_date`.
- **Индексы** на `full_name` и `dismissal_date`.

Приёмы: `foreignId()->constrained()` с разными стратегиями удаления, самоссылка
(`constrained('employees')`).

> Поля `nationality`, `education`, `specialty`, `birth_place` позже вынесены в
> справочники миграцией **#25**.

### 9. `add_branch_id_to_users_table`
Добавляет `users.branch_id` (nullable, `nullOnDelete`). Это основа разграничения
доступа: `branch_id = NULL` + не Admin → доступ закрыт; иначе пользователь видит
свой филиал. Приём: `Schema::table` + `foreignId()->constrained()`.

### 10. `create_rotations_table` — история перемещений
Лог ротаций сотрудника: пары «старое/новое» для филиала, должности и структуры
(`old_*` / `new_*`), `rotation_date`, `reason`. `employee_id` и `new_branch_id`
с `cascadeOnDelete`, остальные FK — `nullOnDelete`. Индекс на `rotation_date`.

---

## Spatie: права и журнал

### 11. `create_permission_tables` — роли и права
Стандартная миграция **spatie/laravel-permission**. Создаёт:
`permissions`, `roles`, `model_has_permissions`, `model_has_roles`,
`role_has_permissions`. Имена таблиц/колонок берутся из `config/permission.php`.
На ней держатся роли **Admin** и **User** и все права `view/create/edit/delete …`.

### 12. `create_activity_log_table` — журнал действий
Стандартная миграция **spatie/laravel-activitylog**. Таблица `activity_log`:
`log_name`, `description`, полиморфные `subject_*` и `causer_*`
(`nullableMorphs`), `properties` (JSON со снимком изменений), таймстемпы.

### 13. `add_event_column_to_activity_log_table`
Добавляет `event` (created/updated/deleted) — тип события для фильтрации журнала.

### 14. `add_batch_uuid_column_to_activity_log_table`
Добавляет `batch_uuid` — группировка нескольких записей журнала в одну операцию.

---

## Качество данных и производительность

### 15. `add_soft_deletes_to_tables`
Добавляет `deleted_at` (`softDeletes()`) к `employees`, `branches`, `users`.
Включает «мягкое» удаление — записи скрываются, а не стираются (отсюда «Корзина»).

### 16. `add_indexes_to_tables`
Явные индексы под частые фильтры и JOIN:
- `employees`: `deleted_at`, все FK (`branch_id`, `category_id`, `position_id`,
  `structure_id`, `manager_id`), `employment_type`, `inn`, `sin`.
- `users`: `deleted_at`, `branch_id`.
- `branches`: `deleted_at`.
- `rotations`: все FK-колонки.

> Важно: в PostgreSQL FK **не индексируются автоматически**, поэтому индексы
> заводятся вручную.

### 17. `add_case_insensitive_unique_index_to_positions_table`
Делает уникальность должности **регистронезависимой**:
1. Снимает обычный unique (`dropUnique('positions_name_unique')`).
2. `DB::statement('CREATE UNIQUE INDEX ... ON positions (LOWER(TRIM(name)))')`.

Так «Бухгалтер» и «бухгалтер» считаются одним значением. Builder так не умеет —
нужен сырой SQL по выражению.

### 18. `make_email_case_insensitive_unique_on_users_table`
То же для email, но с нюансами:
1. Сначала чистит данные: `UPDATE users SET email = LOWER(TRIM(email))`.
2. Снимает старый unique.
3. Создаёт **частичный** уникальный индекс
   `... (LOWER(TRIM(email))) WHERE deleted_at IS NULL` — уникальность только
   среди не удалённых, чтобы «мягко удалённый» email можно было переиспользовать.

---

## Отделы (шуъба)

### 19. `create_departments_table` — дерево отделов
- `branch_id` (`cascadeOnDelete`), `parent_id` — самоссылка для иерархии
  (`restrictOnDelete` — нельзя удалить отдел с подотделами).
- `name`, `code`, таймстемпы, `softDeletes`, индекс `(branch_id, parent_id)`.
- **Два частичных уникальных индекса** через `DB::statement`:
  - корни уникальны в рамках филиала: `(branch_id, name) WHERE parent_id IS NULL`;
  - подотделы уникальны внутри родителя: `(branch_id, parent_id, name) WHERE parent_id IS NOT NULL`.
  - Оба `WHERE deleted_at IS NULL` — учитывают мягкое удаление.

### 20. `add_department_permissions`
Не меняет схему — наполняет данные. Создаёт права
`view/create/edit/delete departments` и выдаёт их ролям **Admin** и **User**.
Сбрасывает кэш прав Spatie до и после. `down()` отзывает и удаляет права.

---

## Вакансии

### 21. `create_vacancies_table` — вакансии
- FK: `branch_id` (`cascadeOnDelete`), `department_id`/`position_id`/
  `structure_id` (`nullOnDelete`), `created_by` → `users` (`nullOnDelete`).
- Поля: `title`, `employment_type`, `requirements`, `schedule`, `salary`,
  `description`, `status` (по умолчанию `open`), `opened_at`, `closed_at`.
- `softDeletes`; индексы `(branch_id, status)` и `department_id`.

Покрывает поля формы вакансии: должность, структура, требования, график, оклад.

### 22. `add_vacancy_permissions`
Аналог #20 для вакансий: права `view/create/edit/delete vacancies` ролям
Admin и User. Только данные, схему не трогает.

---

## Нормализация (миграции #23–#28)

Серия приведения БД к нормальной форме: повторяющийся текст → справочники,
свободные значения → ограничения, новые FK → индексы.

### 23. `normalize_employee_gender`
1. Приводит разнобой («Мужской», «Male», «M», «м» …) к двум каноническим
   значениям `мужской` / `женский` через `DB::table()->update()`.
2. `ALTER TABLE employees ADD CONSTRAINT ... CHECK (gender IN ('мужской','женский'))`
   — фиксирует допустимые значения на уровне БД.

### 24. `add_department_id_to_employees`
Добавляет `employees.department_id` (nullable, `nullOnDelete`, `after('branch_id')`)
— привязка сотрудника к отделу из нового дерева отделов.

### 25. `normalize_employee_lookups` ⭐
Выносит 4 свободных текстовых поля сотрудника в справочники. Шаги в `up()`:
1. Создаёт таблицы `nationalities`, `educations`, `specialties`, `birth_places`
   (`id`, `name` unique, таймстемпы).
2. Переносит уникальные значения из employees в справочники
   (`distinct` → `insertOrIgnore`).
3. Добавляет FK-колонки `nationality_id`, `education_id`, `specialty_id`,
   `birth_place_id` (`constrained()->nullOnDelete()`).
4. Заполняет их по совпадению имени:
   `UPDATE employees e SET nationality_id = l.id FROM nationalities l WHERE e.nationality = l.name`.
5. Удаляет старые текстовые колонки.

`down()` полностью обратим: восстанавливает текстовые колонки, переливает имена
обратно из справочников, удаляет FK и сами справочники. **Откат проверен —
данные восстанавливаются без потерь.**

### 26. `add_employment_type_check`
1. Нормализует значения типа занятости к `штатный` / `контракт`.
2. **Catch-all:** любое неизвестное значение приводит к `штатный`
   (`whereNotIn(...)->update(...)`) — чтобы CHECK гарантированно применился на
   любых данных.
3. `ADD CONSTRAINT employees_employment_type_check CHECK (employment_type IN ('штатный','контракт'))`.

### 27. `add_normalization_indexes`
Индексы под производительность:
- FK новых справочников: `nationality_id`, `education_id`, `specialty_id`,
  `birth_place_id`;
- ранее не проиндексированный `department_id`;
- составной `(branch_id, dismissal_date)` — под основной запрос списка
  сотрудников (фильтр по филиалу + активные/уволенные).

Причина — PostgreSQL не индексирует FK сам.

### 28. `add_case_insensitive_unique_to_lookups`
Делает уникальность 4 новых справочников регистронезависимой (как у positions
в #17). Для каждой таблицы: снять обычный unique → создать
`CREATE UNIQUE INDEX ..._name_lower_unique ON ... (LOWER(TRIM(name)))`.
Так «Тоҷик» и «тоҷик» — одно значение (контроллер при добавлении сотрудника тоже
ищет по `LOWER(TRIM(name))`).

---

## Запуск и откат

```bash
php artisan migrate            # применить все новые миграции
php artisan migrate:status     # статус (Ran / Pending) и номер батча
php artisan migrate:rollback   # откатить последний батч
php artisan migrate:rollback --step=4   # откатить N последних миграций
php artisan migrate:fresh --seed        # пересоздать БД и засеять (dev)
```

> Откат серии нормализации (#25–#28) протестирован: `rollback` → данные
> возвращаются в текстовые колонки без потерь, `migrate` обратно → снимок данных
> совпадает с исходным.

---

## Итог по нормализации

После миграций #23–#28 в БД не осталось дублирующегося свободного текста по
сотрудникам:

| Было (текст в `employees`) | Стало |
|---|---|
| `gender` свободный | CHECK на 2 значения |
| `employment_type` свободный | CHECK на 2 значения |
| `nationality` | справочник `nationalities` + FK |
| `education` | справочник `educations` + FK |
| `specialty` | справочник `specialties` + FK |
| `birth_place` | справочник `birth_places` + FK |

Плюс индексы под все FK и регистронезависимая уникальность справочников.

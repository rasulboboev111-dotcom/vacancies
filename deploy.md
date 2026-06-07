# Руководство по развёртыванию и запуску — HR-портал

Приложение для HR/вакансий на Laravel 12 + Inertia/Vue 3. Это руководство охватывает
чистую установку (локально или на проде), базу данных, **сидеры**, **импорт
оргструктуры**, планировщик, очередь и продакшен-хардеринг.

---

## 1. Требования

| Компонент | Версия |
|-----------|---------|
| PHP | **8.2+** (расширения: `pdo_pgsql`, `mbstring`, `openssl`, `ctype`, `json`, `bcmath`, `fileinfo`) |
| **PostgreSQL** | **13+** — *обязательно* (миграции используют частичные уникальные индексы, CHECK-ограничения, `ALTER COLUMN … USING`; на SQLite/MySQL миграции не пройдут) |
| Composer | 2.x |
| Node.js | 18+ (сборка фронтенда через Vite) |
| Веб-сервер | Nginx/Apache с корнем на `public/`, HTTPS в продакшене |

---

## 2. Получить код и окружение

```bash
git clone <repo-url> hr-portal && cd hr-portal
cp .env.example .env
```

Отредактируйте `.env`:

- **Продакшен:** `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://your-domain.tj`
- **База данных:** задайте `DB_HOST/DB_PORT/DB_DATABASE/DB_USERNAME/DB_PASSWORD` (сначала создайте БД Postgres, например `createdb vacancies`).
- **Синхронизация оргструктуры:** задайте `TOJIKTELECOM_TOKEN=<bearer-token>` (оставьте пустым, чтобы отключить живую синхронизацию через API).
- `APP_DISPLAY_TIMEZONE=Asia/Dushanbe` (уже по умолчанию) — время в интерфейсе показывается по Душанбе; в хранилище остаётся UTC.

---

## 3. Установить зависимости и собрать ассеты

```bash
# Бэкенд
composer install --no-dev --optimize-autoloader     # для разработки: composer install

# Ключ приложения (только если APP_KEY пуст)
php artisan key:generate

# Фронтенд (ассеты в .gitignore — их нужно собирать на сервере)
npm ci
npm run build                                        # для разработки: npm run dev
```

---

## 4. База данных: миграции

```bash
php artisan migrate --force
```

Это создаёт все таблицы (users, branches, departments, employees, vacancies,
rotations, positions, справочники, таблицы Spatie permission, activity_log),
включая индекс `activity_log.created_at`.

> Полная пересборка (УНИЧТОЖАЕТ данные): `php artisan migrate:fresh --force`

---

## 5. Сидеры (учётка администратора + роли/права)

```bash
php artisan db:seed --force
```

Что создают сидеры (`DatabaseSeeder` → `RoleAndPermissionSeeder`):

- **Администратор** `admin@hr.local`
  - **local/testing:** пароль `password`
  - **продакшен:** пароль **случайный** (известный пароль никогда не поставляется) — задайте свой после установки, см. ниже.
- **Роли:** `Admin` (все права) и `User` (в пределах своего филиала: view/create/edit/delete для employees, departments, vacancies; view branches).
- **Права:** полный набор `view/create/edit/delete branches|employees|departments|vacancies` + `view audit logs`.

> Филиалы, отделы и сотрудники **НЕ** засеиваются — они приходят из импорта
> оргструктуры (следующий шаг).

### Задать продакшен-пароль администратора

```bash
php artisan tinker --execute "\$u=App\Models\User::where('email','admin@hr.local')->first(); \$u->password=bcrypt('CHANGE_ME_STRONG'); \$u->save(); echo 'ok';"
```

(Либо войдите со случайным паролем, сбросив его тем же способом.)

---

## 6. Импорт оргструктуры (филиалы → отделы → сотрудники)

Реальные организационные данные загружаются командой `org:import`.

```bash
# Живая синхронизация из v1 API Tojiktelecom (нужен TOJIKTELECOM_TOKEN в .env)
php artisan org:import --api

# Сначала очистить данные оргструктуры, затем загрузить чистую копию (также чистит vacancies/rotations)
php artisan org:import --api --fresh

# Офлайн: импорт из сохранённого JSON-дампа вместо API
php artisan org:import --file=storage/app/tj_structure.json
```

Примечания:

- Идемпотентно: записи апсёртятся по `external_id`, поэтому повторный запуск **не дублирует**.
- `--fresh` удаляет employees/departments/branches **и vacancies/rotations** перед перезагрузкой — используйте только для полного сброса.
- Один Branch на businessUnit; дерево отделов остаётся связным в пределах филиала; порядок сохраняется через `departments.sort_order`.

---

## 7. Планировщик (ежедневная авто-синхронизация)

`routes/console.php` планирует `org:import --api` ежедневно в **03:00** (время сервера/UTC)
с `withoutOverlapping()`. Включите планировщик Laravel одним системным cron:

```cron
* * * * * cd /var/www/hr-portal && php artisan schedule:run >> /dev/null 2>&1
```

> 03:00 — в таймзоне приложения (UTC) → 08:00 по Asia/Dushanbe. Если токен живого
> API отсутствует/истёк, запуск пишет ошибку в лог, а БД просто остаётся в
> последнем состоянии (без падения).

---

## 8. Воркер очереди (опционально)

`QUEUE_CONNECTION=database`. Если включаете почту/уведомления через очередь, запустите воркер
(через Supervisor/systemd):

```bash
php artisan queue:work --tries=3 --timeout=90
```

---

## 9. Продакшен-оптимизация и кеш

Запускать после каждого деплоя (и повторно при изменении конфигов/маршрутов):

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

> Если меняете `.env`, выполните `php artisan config:clear` (или пересоберите кеш) —
> закешированный конфиг игнорирует `.env` во время выполнения.

Symlink для хранилища (загрузки/публичные файлы):

```bash
php artisan storage:link
```

Права (Linux): `storage/` и `bootstrap/cache/` должны быть доступны на запись веб-пользователю.

---

## 10. Веб-сервер

Укажите корень документа на **`public/`**. Пример Nginx:

```nginx
server {
    listen 443 ssl;
    server_name your-domain.tj;
    root /var/www/hr-portal/public;
    index index.php;

    location / { try_files $uri $uri/ /index.php?$query_string; }
    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
    # ssl_certificate / ssl_certificate_key ...
}
```

---

## 11. Скрипт деплоя в один заход (copy/paste)

```bash
set -e
cd /var/www/hr-portal
git pull
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan db:seed --force                 # безопасно: firstOrCreate, не дублирует
php artisan org:import --api                # обновить данные оргструктуры (пропустить, если нет токена)
php artisan storage:link || true
php artisan config:cache route:cache view:cache event:cache
# при необходимости перезапустить php-fpm / воркер очереди
```

Только при первом запуске: `cp .env.example .env && php artisan key:generate` перед вышеуказанным.

---

## 12. Локальная разработка (быстрый старт)

```bash
cp .env.example .env
composer install
php artisan key:generate
# укажите DB_* в .env на локальный Postgres, затем:
php artisan migrate --seed       # admin@hr.local / password
npm install
composer run dev                 # запускает PHP + очередь + Vite вместе
# опционально: php artisan org:import --file=storage/app/tj_structure.json
```

---

## 13. Чек-лист перед деплоем и известные проблемы

- [ ] `.env`: `APP_ENV=production`, `APP_DEBUG=false`, реальный `APP_KEY`, креды БД, `TOJIKTELECOM_TOKEN`.
- [ ] База Postgres создана и доступна.
- [ ] `npm run build` выполнен на сервере (ассеты в .gitignore).
- [ ] Установлен системный cron для `schedule:run` (для ежедневного импорта).
- [ ] Пароль администратора изменён со случайного/засеянного значения.
- [ ] Настроен HTTPS; `storage/` и `bootstrap/cache/` доступны на запись.

**Известно (не блокеры, но имейте в виду):**

- **Тесты в .gitignore** (`/tests` в `.gitignore`) — их нет в репозитории, поэтому CI/серверу нечего запускать. Локально набор ~146 проходит с 4 известными падениями (`ImportOrgStructureTest` ×2, пагинация `VacancyTest` ×2).
- **PHPStan** (level 5) сообщает об одной существующей ошибке в `ProfileController` (`$user->branch?->name`), которой нет в baseline — решите, исправить или внести в baseline, прежде чем подключать в CI-гейтинг.
- Синхронизация оргструктуры **не обрабатывает истечение bearer-токена автоматически**: если токен отозван, ночная синхронизация падает молча (пишет ошибку в лог). Рассмотрите алерт о сбое и сервисный (не персональный) токен.

---

## 14. Справочник команд

| Задача | Команда |
|------|---------|
| Миграции | `php artisan migrate --force` |
| Сидинг (админ + роли) | `php artisan db:seed --force` |
| Живой импорт оргструктуры | `php artisan org:import --api` |
| Импорт с полным сбросом | `php artisan org:import --api --fresh` |
| Импорт из файла | `php artisan org:import --file=storage/app/tj_structure.json` |
| Запустить планировщик один раз | `php artisan schedule:run` |
| Очистить кеши | `php artisan optimize:clear` |
| Кеш для прода | `php artisan config:cache route:cache view:cache event:cache` |

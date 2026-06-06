# Deployment & Run Guide — HR Portal

Laravel 12 + Inertia/Vue 3 HR/vacancies app. This guide covers a fresh setup
(local or production), database, **seeders**, the **org-structure import**, the
scheduler, queue, and production hardening.

---

## 1. Requirements

| Component | Version |
|-----------|---------|
| PHP | **8.2+** (extensions: `pdo_pgsql`, `mbstring`, `openssl`, `ctype`, `json`, `bcmath`, `fileinfo`) |
| **PostgreSQL** | **13+** — *required* (migrations use partial unique indexes, CHECK constraints, `ALTER COLUMN … USING`; SQLite/MySQL will not migrate) |
| Composer | 2.x |
| Node.js | 18+ (build the frontend with Vite) |
| Web server | Nginx/Apache pointing at `public/`, HTTPS in production |

---

## 2. Get the code & environment

```bash
git clone <repo-url> hr-portal && cd hr-portal
cp .env.example .env
```

Edit `.env`:

- **Production:** `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://your-domain.tj`
- **Database:** set `DB_HOST/DB_PORT/DB_DATABASE/DB_USERNAME/DB_PASSWORD` (create the Postgres DB first, e.g. `createdb vacancies`).
- **Org sync:** set `TOJIKTELECOM_TOKEN=<bearer-token>` (leave empty to disable the live API sync).
- `APP_DISPLAY_TIMEZONE=Asia/Dushanbe` (already default) — UI times are shown in Dushanbe; storage stays UTC.

---

## 3. Install dependencies & build assets

```bash
# Backend
composer install --no-dev --optimize-autoloader     # dev: composer install

# App key (only if APP_KEY is empty)
php artisan key:generate

# Frontend (assets are git-ignored — must be built on the server)
npm ci
npm run build                                        # dev: npm run dev
```

---

## 4. Database: migrate

```bash
php artisan migrate --force
```

This builds all tables (users, branches, departments, employees, vacancies,
rotations, positions, lookups, Spatie permission tables, activity_log) including
the `activity_log.created_at` index.

> Fresh rebuild (DESTROYS data): `php artisan migrate:fresh --force`

---

## 5. Seeders (admin account + roles/permissions)

```bash
php artisan db:seed --force
```

What the seeders create (`DatabaseSeeder` → `RoleAndPermissionSeeder`):

- **Admin user** `admin@hr.local`
  - **local/testing:** password is `password`
  - **production:** password is **random** (a known password is never shipped) — set your own afterwards, see below.
- **Roles:** `Admin` (all permissions) and `User` (branch-scoped: view/create/edit/delete employees, departments, vacancies; view branches).
- **Permissions:** the full `view/create/edit/delete branches|employees|departments|vacancies` + `view audit logs` set.

> Branches, departments and employees are **NOT** seeded — they come from the
> org-structure import (next step).

### Set the production admin password

```bash
php artisan tinker --execute "\$u=App\Models\User::where('email','admin@hr.local')->first(); \$u->password=bcrypt('CHANGE_ME_STRONG'); \$u->save(); echo 'ok';"
```

(Or sign in with the random password by resetting it the same way.)

---

## 6. Org-structure import (branches → departments → employees)

The real organisational data is loaded by the `org:import` command.

```bash
# Live sync from the Tojiktelecom v1 API (needs TOJIKTELECOM_TOKEN in .env)
php artisan org:import --api

# Wipe org data first, then load a clean live copy (also clears vacancies/rotations)
php artisan org:import --api --fresh

# Offline: import from a saved JSON dump instead of the API
php artisan org:import --file=storage/app/tj_structure.json
```

Notes:

- Idempotent: records upsert on `external_id`, so re-running **does not duplicate**.
- `--fresh` deletes employees/departments/branches **and vacancies/rotations** before reload — use only for a full reset.
- One Branch per businessUnit; the department tree stays connected per branch; ordering preserved via `departments.sort_order`.

---

## 7. Scheduler (daily auto-sync)

`routes/console.php` schedules `org:import --api` daily at **03:00** (server/UTC time)
with `withoutOverlapping()`. Enable Laravel's scheduler with a single system cron:

```cron
* * * * * cd /var/www/hr-portal && php artisan schedule:run >> /dev/null 2>&1
```

> The 03:00 is in the app timezone (UTC) → 08:00 Asia/Dushanbe. If the live API
> token is missing/expired the run logs an error and the DB simply stays at its
> last state (no crash).

---

## 8. Queue worker (optional)

`QUEUE_CONNECTION=database`. If you enable queued mail/notifications, run a worker
(via Supervisor/systemd):

```bash
php artisan queue:work --tries=3 --timeout=90
```

---

## 9. Production optimisation & cache

Run after every deploy (and re-run on config/route changes):

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

> If you change `.env`, run `php artisan config:clear` (or re-cache) — cached
> config ignores `.env` at runtime.

Storage symlink (uploads/public files):

```bash
php artisan storage:link
```

Permissions (Linux): `storage/` and `bootstrap/cache/` must be writable by the web user.

---

## 10. Web server

Point the document root at **`public/`**. Example Nginx:

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

## 11. One-shot deploy script (copy/paste)

```bash
set -e
cd /var/www/hr-portal
git pull
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan db:seed --force                 # safe: firstOrCreate, won't duplicate
php artisan org:import --api                # refresh org data (skip if no token)
php artisan storage:link || true
php artisan config:cache route:cache view:cache event:cache
# restart php-fpm / queue worker as needed
```

First-time only: `cp .env.example .env && php artisan key:generate` before the above.

---

## 12. Local development (quick start)

```bash
cp .env.example .env
composer install
php artisan key:generate
# point .env DB_* at a local Postgres, then:
php artisan migrate --seed       # admin@hr.local / password
npm install
composer run dev                 # serves PHP + queue + Vite together
# optional: php artisan org:import --file=storage/app/tj_structure.json
```

---

## 13. Pre-deploy checklist & known issues

- [ ] `.env`: `APP_ENV=production`, `APP_DEBUG=false`, real `APP_KEY`, DB creds, `TOJIKTELECOM_TOKEN`.
- [ ] Postgres database created and reachable.
- [ ] `npm run build` ran on the server (assets are git-ignored).
- [ ] System cron for `schedule:run` installed (for the daily import).
- [ ] Admin password changed from the random/seeded value.
- [ ] HTTPS configured; `storage/` & `bootstrap/cache/` writable.

**Known (not blockers, but be aware):**

- **Tests are git-ignored** (`/tests` in `.gitignore`) — they are not in the repo, so CI/server has nothing to run. Locally the suite is ~146 passing with 4 known failures (`ImportOrgStructureTest` ×2, `VacancyTest` pagination ×2).
- **PHPStan** (level 5) reports one pre-existing error in `ProfileController` (`$user->branch?->name`) that is not in the baseline — decide whether to fix or baseline before wiring it into CI gating.
- The org-sync **bearer token does not auto-expire-handle**: if it is revoked, the nightly sync fails silently (logs an error). Consider a failure alert and a service (non-personal) token.

---

## 14. Command reference

| Task | Command |
|------|---------|
| Migrate | `php artisan migrate --force` |
| Seed (admin + roles) | `php artisan db:seed --force` |
| Live org import | `php artisan org:import --api` |
| Full reset import | `php artisan org:import --api --fresh` |
| Import from file | `php artisan org:import --file=storage/app/tj_structure.json` |
| Run scheduler once | `php artisan schedule:run` |
| Clear caches | `php artisan optimize:clear` |
| Cache for prod | `php artisan config:cache route:cache view:cache event:cache` |

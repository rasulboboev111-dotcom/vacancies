# Настройка на проде: отправка откликов из бота на сайт

Как настроить продакшн так, чтобы отклики на вакансии из Telegram-бота (`hrbot`)
автоматически попадали на сайт (Laravel-приложение `vacancies`).

## Как это работает

Модель **push**: бот — HTTP-клиент. При создании отклика (и повторно после
завершения анкеты) бот шлёт `POST` multipart на endpoint сайта. Сайт
аутентифицирует запрос по заголовку `X-Api-Key`, создаёт/обновляет заявку
(идемпотентно по `external_id`) и сохраняет резюме через media library.

Каждый входящий отклик привязывается к **филиалу по умолчанию** (ҶСК
«Тоҷиктелеком», businessUnit code `BU51`, задаётся `INTAKE_DEFAULT_BRANCH_CODE`).
Вакансия ищется по названию должности **в пределах этого филиала**; если филиал
по умолчанию не найден (оргструктура не импортирована), `branch_id`/`vacancy_id`
остаются пустыми для ручной разборки.

```
hrbot (Docker)  ──POST /api/applications (X-Api-Key)──►  сайт (Laravel)
   SITE_API_KEY        ровно совпадает                   INTAKE_API_KEY
```

Ключевое условие: **`SITE_API_KEY` бота === `INTAKE_API_KEY` сайта**.

---

## Часть 1. Сайт (Laravel)

### 1.1. Переменные окружения (`.env`)

```dotenv
APP_ENV=production
APP_DEBUG=false                # ОБЯЗАТЕЛЬНО на проде: иначе ошибки могут раскрыть .env
APP_URL=https://your-site.tj

# Приём откликов из бота
INTAKE_API_KEY=<длинный случайный ключ>
INTAKE_DISK=local                   # приватный диск для резюме (НЕ public)
INTAKE_DEFAULT_BRANCH_CODE=BU51     # businessUnit code филиала, к которому привязываются все отклики (ҶСК «Тоҷиктелеком»)
```

Сгенерировать стойкий ключ:

```bash
php -r "echo bin2hex(random_bytes(32)).PHP_EOL;"
# или
openssl rand -hex 32
```

Этот же ключ пропишите боту в `SITE_API_KEY` (см. часть 2).

### 1.2. Диск для резюме

Резюме не должны быть публично доступны по прямой ссылке. `INTAKE_DISK`
указывает на приватный диск из `config/filesystems.php` (по умолчанию `local` —
`storage/app/private`, вне `public/`). Скачивание идёт через защищённый маршрут
с проверкой прав, а не по прямому URL.

Если используете S3/совместимое хранилище — заведите отдельный приватный диск и
укажите его имя в `INTAKE_DISK`.

### 1.3. Эндпоинт (справочно)

| Параметр | Значение |
|----------|----------|
| URL | `POST /api/applications` |
| Аутентификация | заголовок `X-Api-Key: <INTAKE_API_KEY>` |
| Тело | `multipart/form-data` |
| Поле `payload` | JSON-строка (обязательно) |
| Поле `resume` | файл, необязательно |
| Лимит частоты | `throttle:120,1` (120 запросов/мин) |
| Резюме: типы | `pdf, doc, docx, rtf, odt` |
| Резюме: размер | до 10 МБ (`10240` КБ) |

Структура `payload`:

```json
{
  "external_id": 123,
  "name": "Имя кандидата",
  "email": "a@b.tj",
  "phone": "+992...",
  "vacancy": "Название должности",
  "source": "telegram",
  "summary": "...",
  "survey": { "full_name": "...", "phone": "..." },
  "created_at": "2026-06-15T10:00:00"
}
```

Ответы: `200 {"id": <id заявки>}` — успех; `401` — неверный/пустой ключ;
`422` — ошибка валидации; `429` — превышен лимит частоты.

### 1.4. Веб-сервер (nginx)

Загрузка резюме до 10 МБ — поднимите лимит тела запроса (по умолчанию nginx
режет на 1 МБ → ошибка `413`):

```nginx
# в server { } или location /api/ { }
client_max_body_size 12m;
```

И согласуйте с PHP (`php.ini`): `upload_max_filesize = 12M`,
`post_max_size = 12M`.

### 1.5. Команды деплоя сайта

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan storage:link            # если ещё не сделано
php artisan config:cache            # ОБЯЗАТЕЛЬНО после правки .env
php artisan route:cache
npm ci && npm run build
```

> После любого изменения `.env` на проде запускайте `php artisan config:cache`
> заново — иначе старые значения останутся в кэше конфигурации.

---

## Часть 2. Бот (`hrbot`)

### 2.1. Переменные окружения (`.env` бота)

```dotenv
SITE_PUSH_ENABLED=true                               # включить отправку
SITE_PROVIDER=http                                   # http = реальная отправка (mock = заглушка!)
SITE_API_URL=https://your-site.tj/api/applications   # публичный HTTPS-URL сайта
SITE_API_KEY=<тот же ключ, что INTAKE_API_KEY сайта>
SITE_PUSH_TIMEOUT=10
```

Критичные ошибки конфигурации:
- `SITE_PROVIDER=mock` → бот ничего не шлёт, только пишет в лог (это для локалки/тестов).
- `SITE_PUSH_ENABLED=false` → отправка полностью отключена.
- `SITE_API_URL` с `host.docker.internal` — это **только для локали** (доступ к
  хосту из контейнера). На проде укажите реальный публичный URL сайта.
- `SITE_API_KEY` не совпадает с `INTAKE_API_KEY` → сайт вернёт `401`.

### 2.2. Деплой бота

```bash
cd /path/to/hrbot
docker compose up -d --build
```

Миграции БД бота применяются автоматически при старте (`bot/main.py` вызывает
`alembic upgrade head`) — в т.ч. таблица `application_site`, где хранится статус
доставки на сайт (для идемпотентности и ретраев).

При старте бот также дошлёт ранее не доставленные отклики со статусом
`failed`/`none` (`resend_failed_pushes`).

---

## Часть 3. Проверка

### 3.1. Прямой тест эндпоинта (с любой машины)

```bash
curl -i -X POST https://your-site.tj/api/applications \
  -H "X-Api-Key: <INTAKE_API_KEY>" \
  -F 'payload={"external_id":999,"name":"Проверка","source":"telegram"}'
# Ожидаем: HTTP 200 {"id": ...}
```

Неверный ключ должен вернуть `HTTP 401`.

### 3.2. Со стороны бота

- Логи: `docker logs <имя_контейнера_бота>` — строки `[SITE] app=… status=sent`.
- Статус доставки в БД бота (таблица `application_site`): `site_status='sent'`,
  заполнен `remote_id` (= id заявки на сайте), `last_error=NULL`.

### 3.3. Со стороны сайта

- Новые отклики видны на странице **«Идоракунии аризаҳо»** (Управление заявками).
- Каждое создание/изменение/удаление заявки пишется в **журнал действий**
  (`activity_log`).

---

## Часть 4. Траблшутинг

| Симптом | Причина | Решение |
|---------|---------|---------|
| `401 Unauthorized` | ключи не совпадают / пустой `INTAKE_API_KEY` | сверьте `SITE_API_KEY` и `INTAKE_API_KEY`, после правки — `config:cache` |
| Connection refused / timeout | сайт недоступен по `SITE_API_URL` | проверьте URL, HTTPS, файрвол; на локали — bind сервера на `0.0.0.0` |
| `413 Request Entity Too Large` | резюме больше лимита nginx | поднимите `client_max_body_size` (см. 1.4) |
| `422` валидация | в `payload` нет `external_id` или он не число | проверьте формат `payload` (см. 1.3) |
| Отклики не уходят, ошибок нет | `SITE_PROVIDER=mock` или `SITE_PUSH_ENABLED=false` | выставьте `http` / `true`, пересоберите контейнер |
| В контейнере старый код | образ собран до фичи отправки | `docker compose up -d --build` |
| `429 Too Many Requests` | всплеск > 120 запросов/мин | временно; повтор позже (бот ретраит) |

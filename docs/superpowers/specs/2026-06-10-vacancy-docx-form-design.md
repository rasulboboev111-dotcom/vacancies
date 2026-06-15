# Заявка на подбор персонала: форма и печать 1:1 с docx

Дата: 2026-06-10. Источник: «Заявка_на_подбор_персонала_Финал.docx» (Приложение № 1 к СОП).

## Цель

1. Форма создания/редактирования вакансии повторяет бланк docx: те же 6 секций, те же
   поля и варианты-чекбоксы.
2. Печатная страница `vacancies/{id}/print` визуально совпадает с docx (A4, Times New
   Roman, тёмно-синие бары секций #0F3D5C, лейблы #E8F1F8, значения #F3F7FB, шапка
   «УТВЕРЖДАЮ», блок подписей, футер).
3. БД нормализована: дубликат «вазифа» (`vacancies.title` против `position_id`) убран —
   остаётся только `position_id` → справочник `positions`. Языки — отдельная таблица.

## Схема данных

### Удаляется
- `vacancies.title` — дубликат должности. Перед удалением данные переливаются:
  `title` без `position_id` → find-or-create в `positions` (case-insensitive).
- `vacancies.schedule` (string) → заменяется `schedule_type` + `schedule_other`.
- `vacancies.description` → переименование в `responsibilities` («Основные обязанности»).

### Изменяется
- `employment_type`: значения `штатный|контракт` → `полная|частичная|проектная`
  (миграция данных: штатный→полная, контракт→проектная; check-constraint обновляется).
  Для сотрудников остаётся старый `App\Enums\EmploymentType`; вакансии получают свой
  `VacancyEmploymentType`.

### Добавляется в `vacancies` (всё nullable, check-constraint на каждое enum-поле)
| Колонка | Тип | Поле бланка | Enum |
|---|---|---|---|
| location | string | Место деятельности / локация | — |
| supervisor | string | Непосредственный руководитель | — |
| education | string | Образование | Education: высшее, среднее специальное, не имеет значения |
| experience | string | Опыт работы | Experience: без опыта, от 1 года, от 3 лет и более |
| skills | text | Ключевые навыки и знание программ | — |
| schedule_type | string | График работы | ScheduleType: 5/2, иной |
| schedule_other | string | «Иной — укажите» | — |
| work_format | string | Формат работы | WorkFormat: офис, удалённо, гибрид |
| probation | string | Испытательный срок | Probation: нет, 1 месяц, 3 месяца, иное |
| probation_other | string | «Иное» | — |
| opening_reason | string | Причина открытия позиции | OpeningReason: расширение штата, новая позиция, замена уволенного сотрудника, декретная ставка / временное замещение |
| priority | string | Приоритет / срочность | VacancyPriority: низкая, средняя, высокая |
| deadline | date | Планируемая дата закрытия | — |

`opened_at` = «Дата подачи заявки», `requirements` = «Дополнительные требования»,
`salary` = «Предполагаемый уровень дохода», `openings` = «Количество вакансий».

### Новая таблица `vacancy_languages` («Знание языков», multi-select)
`id, vacancy_id FK cascade, name string(100)`, unique(vacancy_id, name).
Известные значения чекбоксов: Таджикский, Русский, Английский; всё прочее — «Другой».

## Backend

- Enum'ы в `App\Enums` со строковыми русскими значениями (печатаются на бланке как есть)
  и `label()` с текстом из docx («5/2, 08:00–17:00», «1 мес.», «Проектная / срочная»).
- `VacancyService`: остаётся resolvePosition; языки синхронизируются в транзакции
  (delete + createMany при наличии ключа `languages`). Логи используют имя должности.
- `Store/UpdateVacancyRequest`: правила на новые поля, `title` удалён.
- `VacancyData`: новые поля + `*_label`, `languages: string[]`.
- `VacancyController@print` + роут `GET vacancies/{id}/print` (auth, Gate 'view').
- Тоггл статуса с фронта шлёт только `status` (частичный update безопасен: absent-поля
  не попадают в validated()).

## Frontend

- `VacancyFormDialog.vue`: 6 секций как в бланке (навигационно — те же навбары
  «1 ИНФОРМАЦИЯ О ВАКАНСИИ»…). Одиночный выбор — кликабельные чекбоксы
  (повторный клик снимает), языки — мультивыбор + поле «Другой», график/испытательный
  срок — вариант + текстовое поле. Лейблы на русском, как в docx.
- `VacancyTable/ViewDialog/Index`: `title` заменяется на `position.name`, график — из
  `schedule_type/schedule_other`.
- Печать: Blade `resources/views/vacancies/print.blade.php` — самодостаточный HTML,
  `@page A4, поля 19мм`, Times New Roman ~10pt, выбранные варианты — ☑, пустые — ☐,
  панель «Печать» скрыта в `@media print`. Кнопка-принтер в таблице открывает в новой
  вкладке.

## Тесты

Существующий `VacancyTest` переводится с `title` на `position`; новые тесты: маппинг
данных миграции, языки, enum-валидация, печатная страница (200 своя / 403 чужой филиал).

// Хелперы отображения для двухколоночных групп выбора «Заявка на подбор
// персонала»: предустановленный вариант показывает свою подпись, вариант
// «иной/иное» показывает введённый пользователем свободный текст (с откатом на
// подпись варианта).

// Backing-значения вариантов «иной/иное» — копия App\Enums\ScheduleType::OTHER
// и App\Enums\Probation::OTHER. Держим их одной константой, чтобы строка не
// разъезжалась по компонентам.
export const SCHEDULE_OTHER = 'иной';
export const PROBATION_OTHER = 'иное';

// Общая логика «иной»-группы: при выбранном «ином» варианте показываем свободный
// текст (с откатом на подпись), иначе — подпись предустановленного варианта.
function otherableText(value, otherValue, freeText, label) {
    if (!value)
        return null;
    return value === otherValue ? (freeText || label) : label;
}

export function scheduleText(vacancy) {
    return otherableText(
        vacancy?.schedule_type,
        SCHEDULE_OTHER,
        vacancy?.schedule_other,
        vacancy?.schedule_type_label,
    );
}

export function probationText(vacancy) {
    return otherableText(
        vacancy?.probation,
        PROBATION_OTHER,
        vacancy?.probation_other,
        vacancy?.probation_label,
    );
}

// Единое форматирование дохода для списков и карточек, чтобы число не
// форматировалось по-разному в разных местах.
export function salaryText(salary, suffix = 'сомонӣ') {
    if (salary == null)
        return null;
    return `${salary.toLocaleString('ru-RU')} ${suffix}`;
}

// Разбивает сохранённые языки вакансии на предустановленные чекбоксы и свободное
// поле «Другой». Каждый язык, не входящий в предустановленные, сохраняется (через
// запятую), так что редактирование вакансии никогда не теряет молча
// пользовательские языки, кроме первого.
export function splitLanguages(stored, known) {
    const list = stored ?? [];
    const presets = known ?? [];
    return {
        selected: list.filter(name => presets.includes(name)),
        other: list.filter(name => !presets.includes(name)).join(', '),
    };
}

// Снова объединяет выбор чекбоксов с текстом «Другой» через запятую в плоский
// список языков, который ожидает бэкенд. Дедупим без учёта регистра (как
// VacancyService::pullLanguages), чтобы «Русский» + «русский» не упёрлись в
// серверное правило distinct:ignore_case и не дали 422.
export function mergeLanguages(selected, otherText) {
    const extras = (otherText ?? '')
        .split(',')
        .map(name => name.trim())
        .filter(Boolean);

    const seen = new Map();
    for (const name of [...(selected ?? []), ...extras]) {
        const key = name.toLocaleLowerCase('ru-RU');
        if (!seen.has(key))
            seen.set(key, name);
    }
    return [...seen.values()];
}

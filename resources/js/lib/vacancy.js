// Хелперы отображения для двухколоночных групп выбора «Заявка на подбор
// персонала»: предустановленный вариант показывает свою подпись, вариант
// «иной/иное» показывает введённый пользователем свободный текст (с откатом на
// подпись варианта).

export function scheduleText(vacancy) {
    if (!vacancy?.schedule_type)
        return null;
    return vacancy.schedule_type === 'иной'
        ? (vacancy.schedule_other || vacancy.schedule_type_label)
        : vacancy.schedule_type_label;
}

export function probationText(vacancy) {
    if (!vacancy?.probation)
        return null;
    return vacancy.probation === 'иное'
        ? (vacancy.probation_other || vacancy.probation_label)
        : vacancy.probation_label;
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
// список языков, который ожидает бэкенд.
export function mergeLanguages(selected, otherText) {
    const extras = (otherText ?? '')
        .split(',')
        .map(name => name.trim())
        .filter(Boolean);
    return [...(selected ?? []), ...extras];
}

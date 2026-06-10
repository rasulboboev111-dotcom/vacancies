// Display helpers for the «Заявка на подбор персонала» two-column choice
// groups: the preset option shows its label, the «иной/иное» option shows the
// free text the user typed (falling back to the option label).

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

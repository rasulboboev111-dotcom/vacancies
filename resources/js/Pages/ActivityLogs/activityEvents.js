// Shared presentation helpers for activity-log events.

export function getEventColor(event) {
    switch (event) {
        case 'created': return 'success';
        case 'updated': return 'indigo';
        case 'deleted': return 'error';
        default: return 'grey';
    }
}

export function getEventText(event) {
    switch (event) {
        case 'created': return 'Эҷодшуда';
        case 'updated': return 'Навсозӣшуда';
        case 'deleted': return 'Несткардашуда';
        default: return event;
    }
}

// Tajik label for the subject type shown in each log row (class basename).
export function getSubjectText(subjectType) {
    switch (subjectType) {
        case 'Employee': return 'Корманд';
        case 'Vacancy': return 'Вакансия';
        case 'Branch': return 'Филиал';
        case 'Department': return 'Шуъба';
        case 'Position': return 'Вазифа';
        case 'User': return 'Корбар';
        case 'Rotation': return 'Ротатсия';
        default: return subjectType || 'Низом';
    }
}

// Хелперы отображения событий журнала активности.

export function getEventText(event) {
    switch (event) {
        case 'created': return 'Эҷодшуда';
        case 'updated': return 'Навсозӣшуда';
        case 'deleted': return 'Несткардашуда';
        default: return event;
    }
}

// Таджикская подпись типа субъекта в строке журнала (basename класса).
export function getSubjectText(subjectType) {
    switch (subjectType) {
        case 'Employee': return 'Корманд';
        case 'Vacancy': return 'Вакансия';
        case 'Branch': return 'Филиал';
        case 'Department': return 'Шуъба';
        case 'Position': return 'Вазифа';
        case 'User': return 'Корбар';
        case 'Rotation': return 'Ротатсия';
        case 'Application': return 'Ариза';
        default: return subjectType || 'Низом';
    }
}

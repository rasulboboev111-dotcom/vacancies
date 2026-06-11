// Единый источник истины для форматирования дат во всём приложении.
import dayjs from 'dayjs';

// `new Date('YYYY-MM-DD')` парсит как полночь UTC (сдвигая день в отрицательных
// смещениях); dayjs парсит строки-даты в ЛОКАЛЬНОМ времени, поэтому обходной
// путь, нужный старому самописному форматтеру, больше не требуется.

/**
 * Форматирует строку-дату как dd.mm.yyyy. Терпим к уже локализованным строкам
 * и пустым значениям (возвращает '-'); строки, не являющиеся датой, проходят без изменений.
 */
export function formatDate(dateStr) {
    if (!dateStr)
        return '-';
    const date = dayjs(dateStr);
    return date.isValid() ? date.format('DD.MM.YYYY') : dateStr;
}

/**
 * Форматирует строку-дату как dd.mm.yyyy HH:MM. Пустые значения возвращают '-';
 * строки, не являющиеся датой, проходят без изменений.
 */
export function formatDateTime(dateStr) {
    if (!dateStr)
        return '-';
    const date = dayjs(dateStr);
    return date.isValid() ? date.format('DD.MM.YYYY HH:mm') : dateStr;
}

/**
 * Сегодняшняя локальная дата в виде строки `YYYY-MM-DD`, готовой для полей формы/даты.
 */
export function today() {
    return dayjs().format('YYYY-MM-DD');
}

// Единый источник истины для форматирования дат во всём приложении.
import dayjs from 'dayjs';
import timezone from 'dayjs/plugin/timezone';
import utc from 'dayjs/plugin/utc';

dayjs.extend(utc);
dayjs.extend(timezone);

// Приложение однозонное (UTC+5). Сырые Eloquent-модели сериализуют Carbon как
// UTC-инстант (суффикс Z / смещение), поэтому без фиксированной зоны dayjs
// отрисовал бы их в зоне БРАУЗЕРА (сдвиг на часы/день у не-UTC+5 клиентов).
// Наивные строки (DTO форматируют дату на сервере, поля формы) зоны не несут —
// их оставляем как есть.
const APP_TZ = 'Asia/Dushanbe';
const HAS_ZONE = /z$|[+-]\d{2}:?\d{2}$/i;

function parse(dateStr) {
    return HAS_ZONE.test(String(dateStr))
        ? dayjs(dateStr).tz(APP_TZ)
        : dayjs(dateStr);
}

/**
 * Форматирует строку-дату как dd.mm.yyyy. Терпим к уже локализованным строкам
 * и пустым значениям (возвращает '-'); строки, не являющиеся датой, проходят без изменений.
 */
export function formatDate(dateStr) {
    if (!dateStr)
        return '-';
    const date = parse(dateStr);
    return date.isValid() ? date.format('DD.MM.YYYY') : dateStr;
}

/**
 * Форматирует строку-дату как dd.mm.yyyy HH:MM. Пустые значения возвращают '-';
 * строки, не являющиеся датой, проходят без изменений.
 */
export function formatDateTime(dateStr) {
    if (!dateStr)
        return '-';
    const date = parse(dateStr);
    return date.isValid() ? date.format('DD.MM.YYYY HH:mm') : dateStr;
}

/**
 * Сегодняшняя дата (в зоне приложения) как строка `YYYY-MM-DD`, готовая для полей формы/даты.
 */
export function today() {
    return dayjs().tz(APP_TZ).format('YYYY-MM-DD');
}

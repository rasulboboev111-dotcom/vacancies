// Общий хелпер для вывода ошибок валидации Inertia одной строкой.

/**
 * Возвращает первое человекочитаемое сообщение из набора ошибок Inertia или
 * значение fallback. Значения ошибок Inertia обычно строки, но могут быть
 * массивами — здесь разворачиваем в строку, чтобы вызывающий код мог сразу
 * подставить её в alert.
 */
export function firstError(errors, fallback = '') {
    const first = Object.values(errors ?? {})[0];
    const value = Array.isArray(first) ? first[0] : first;
    return value != null ? String(value) : fallback;
}

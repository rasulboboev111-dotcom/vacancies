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

/**
 * Переносит серверные ошибки валидации (от Inertia) в поля vee-validate —
 * общий для form-диалогов цикл setFieldError по известным полям формы.
 *
 * @param {Record<string, unknown>} serverErrors — ошибки из onError Inertia
 * @param {string[]} fields — имена полей формы
 * @param {(field: string, message: unknown) => void} setFieldError — из useForm vee-validate
 */
export function applyServerErrors(serverErrors, fields, setFieldError) {
    for (const field of fields) {
        if (serverErrors[field]) {
            setFieldError(field, serverErrors[field]);
        }
    }
}

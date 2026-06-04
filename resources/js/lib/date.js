// Single source of truth for date formatting across the app.
import dayjs from 'dayjs';

// `new Date('YYYY-MM-DD')` parses as UTC midnight (shifting the day in negative
// offsets); dayjs parses date-only strings in LOCAL time, so the workaround the
// old hand-rolled formatter needed is no longer necessary.

/**
 * Format a date string as dd.mm.yyyy. Tolerant of already-localised strings
 * and empty values (returns '-'); non-date strings pass through unchanged.
 */
export function formatDate(dateStr) {
    if (!dateStr)
        return '-';
    const date = dayjs(dateStr);
    return date.isValid() ? date.format('DD.MM.YYYY') : dateStr;
}

/**
 * Format a date string as dd.mm.yyyy HH:MM. Empty values return '-';
 * non-date strings pass through unchanged.
 */
export function formatDateTime(dateStr) {
    if (!dateStr)
        return '-';
    const date = dayjs(dateStr);
    return date.isValid() ? date.format('DD.MM.YYYY HH:mm') : dateStr;
}

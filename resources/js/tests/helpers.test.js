import { describe, expect, it } from 'vitest';
import { formatDate, formatDateTime } from '@/lib/date';
import { getEventText, getSubjectText } from '@/Pages/ActivityLogs/activityEvents';

describe('activityEvents', () => {
    it('translates known events to Tajik', () => {
        expect(getEventText('created')).toBe('Эҷодшуда');
        expect(getEventText('updated')).toBe('Навсозӣшуда');
        expect(getEventText('deleted')).toBe('Несткардашуда');
    });

    it('returns the raw value for an unknown event', () => {
        expect(getEventText('whatever')).toBe('whatever');
    });

    it('maps subject types to Tajik with a system fallback', () => {
        expect(getSubjectText('Employee')).toBe('Корманд');
        expect(getSubjectText('Vacancy')).toBe('Вакансия');
        expect(getSubjectText('Branch')).toBe('Филиал');
        expect(getSubjectText('Department')).toBe('Шуъба');
        expect(getSubjectText('User')).toBe('Корбар');
        expect(getSubjectText('')).toBe('Низом');
        expect(getSubjectText('Unknown')).toBe('Unknown');
    });
});

describe('formatDate', () => {
    it('returns a dash for empty values', () => {
        expect(formatDate(null)).toBe('-');
        expect(formatDate('')).toBe('-');
        expect(formatDate(undefined)).toBe('-');
    });

    it('formats an ISO date as dd.mm.yyyy', () => {
        expect(formatDate('2024-01-15')).toMatch(/^\d{2}\.\d{2}\.2024$/);
    });

    it('passes through non-date strings unchanged', () => {
        expect(formatDate('не сана')).toBe('не сана');
    });
});

describe('formatDateTime', () => {
    it('returns a dash for empty values', () => {
        expect(formatDateTime(null)).toBe('-');
    });

    it('includes a time component', () => {
        expect(formatDateTime('2024-01-15T09:30:00')).toMatch(/\d{2}:\d{2}/);
    });
});

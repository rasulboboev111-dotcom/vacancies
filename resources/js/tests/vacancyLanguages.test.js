import { describe, expect, it } from 'vitest';
import { mergeLanguages, probationText, salaryText, scheduleText, splitLanguages } from '@/lib/vacancy';

const known = ['Таджикский', 'Русский', 'Английский'];

describe('vacancy language round-trip', () => {
    it('splits stored languages into presets and a comma-joined «other» slot', () => {
        const result = splitLanguages(['Таджикский', 'Немецкий', 'Французский'], known);

        expect(result.selected).toEqual(['Таджикский']);
        // Every custom language is kept — not just the first.
        expect(result.other).toBe('Немецкий, Французский');
    });

    it('preserves every custom language across a split → merge round-trip', () => {
        const stored = ['Русский', 'Немецкий', 'Французский'];

        const { selected, other } = splitLanguages(stored, known);

        expect(mergeLanguages(selected, other)).toEqual(stored);
    });

    it('handles empty / missing input without throwing', () => {
        expect(splitLanguages(null, known)).toEqual({ selected: [], other: '' });
        expect(mergeLanguages(null, '')).toEqual([]);
        expect(mergeLanguages(['Русский'], '   ')).toEqual(['Русский']);
    });

    it('trims and drops blank entries in the «other» slot', () => {
        expect(mergeLanguages([], 'Немецкий, , Французский ,')).toEqual(['Немецкий', 'Французский']);
    });

    it('deduplicates case-insensitively so the server distinct rule never 422s', () => {
        // Чекбокс «Русский» + ручной ввод «русский» → один язык, а не дубль.
        expect(mergeLanguages(['Русский'], 'русский')).toEqual(['Русский']);
        // Первое вхождение (из чекбоксов) сохраняет свой регистр.
        expect(mergeLanguages(['Английский'], 'английский, Немецкий')).toEqual(['Английский', 'Немецкий']);
        // Дубли внутри самого поля «Другой» тоже схлопываются.
        expect(mergeLanguages([], 'Немецкий, немецкий')).toEqual(['Немецкий']);
    });
});

describe('vacancy display helpers', () => {
    it('scheduleText shows free text for «иной», otherwise the preset label', () => {
        expect(scheduleText({ schedule_type: 'иной', schedule_other: 'сменный 2/2', schedule_type_label: 'Иной' }))
            .toBe('сменный 2/2');
        expect(scheduleText({ schedule_type: '5/2', schedule_type_label: '5/2, 08:00–17:00' }))
            .toBe('5/2, 08:00–17:00');
        expect(scheduleText({})).toBeNull();
    });

    it('probationText shows free text for «иное», otherwise the preset label', () => {
        expect(probationText({ probation: 'иное', probation_other: 'полгода', probation_label: 'Иное' }))
            .toBe('полгода');
        expect(probationText({ probation: '3 месяца', probation_label: '3 мес.' })).toBe('3 мес.');
        expect(probationText({})).toBeNull();
    });

    it('salaryText formats numbers and treats null as «not set»', () => {
        // toLocaleString('ru-RU') разделяет разряды неразрывным пробелом — нормализуем.
        const norm = s => s?.replace(/\s/g, ' ');
        expect(norm(salaryText(5000))).toBe('5 000 сомонӣ');
        expect(norm(salaryText(5000, 'сом.'))).toBe('5 000 сом.');
        expect(salaryText(null)).toBeNull();
        expect(salaryText(undefined)).toBeNull();
    });
});

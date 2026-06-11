import { describe, expect, it } from 'vitest';
import { mergeLanguages, splitLanguages } from '@/lib/vacancy';

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
});

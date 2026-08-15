import { getInitials, severityColor } from '@/lib/utils';
import { describe, expect, it } from 'vitest';

describe('severityColor', () => {
    // Ketujuh nilai yang benar-benar dipakai seeder master-data
    it.each([
        ['primary', 'primary'],
        ['secondary', 'secondary'],
        ['success', 'success'],
        ['info', 'info'],
        ['warn', 'warning'],
        ['danger', 'error'],
        ['contrast', 'neutral'],
    ] as const)('memetakan %s ke %s', (input, expected) => {
        expect(severityColor(input)).toBe(expected);
    });

    it('jatuh ke neutral untuk nilai kosong', () => {
        expect(severityColor(null)).toBe('neutral');
        expect(severityColor(undefined)).toBe('neutral');
    });
});

describe('getInitials', () => {
    it('mengambil maksimal dua huruf pertama', () => {
        expect(getInitials('Dhenistian Dickie')).toBe('DD');
        expect(getInitials('Ana')).toBe('A');
        expect(getInitials('Ana Wijaya Putri')).toBe('AW');
    });
});

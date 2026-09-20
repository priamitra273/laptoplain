import { describe, expect, it } from 'vitest';
import { dueDateTone, formatDate, formatDateRange, formatDateShort, formatRelativeDay } from './date';

describe('formatDate', () => {
    it('formats a date-only value', () => {
        expect(formatDate('2026-06-24')).toBe('24 Jun 2026');
    });

    it('keeps the calendar day for a leap day', () => {
        expect(formatDate('2028-02-29')).toBe('29 Feb 2028');
    });

    it('falls back for empty, invalid, and timestamp values', () => {
        expect(formatDate(null)).toBe('—');
        expect(formatDate('')).toBe('—');
        expect(formatDate('invalid')).toBe('—');
        expect(formatDate('2026-13-45')).toBe('—');
        expect(formatDate('2026-06-24T00:00:00.000Z')).toBe('—');
    });
});

describe('formatDateShort', () => {
    it('drops the year', () => {
        expect(formatDateShort('2026-06-24')).toBe('24 Jun');
    });

    it('falls back for empty and invalid values', () => {
        expect(formatDateShort(null)).toBe('—');
        expect(formatDateShort('invalid')).toBe('—');
    });
});

describe('formatDateRange', () => {
    it('writes the year once when both dates share it', () => {
        expect(formatDateRange('2026-06-24', '2026-08-06')).toBe('24 Jun – 6 Aug 2026');
    });

    it('keeps the month on both sides within the same month', () => {
        expect(formatDateRange('2026-06-20', '2026-06-30')).toBe('20 Jun – 30 Jun 2026');
    });

    it('writes both years when the range crosses a year', () => {
        expect(formatDateRange('2026-12-20', '2027-01-05')).toBe('20 Dec 2026 – 5 Jan 2027');
    });

    it('marks the missing side of an incomplete range', () => {
        expect(formatDateRange('2026-06-24', null)).toBe('24 Jun 2026 – —');
        expect(formatDateRange(null, '2026-06-24')).toBe('— – 24 Jun 2026');
    });

    it('falls back when both sides are empty or invalid', () => {
        expect(formatDateRange(null, null)).toBe('—');
        expect(formatDateRange('invalid', 'invalid')).toBe('—');
    });
});

describe('formatRelativeDay', () => {
    /** Tanggal acuan tetap supaya hasilnya tidak berubah seiring waktu. */
    const reference = new Date(2026, 8, 14);

    it('names the three days around the reference instead of counting them', () => {
        expect(formatRelativeDay('2026-09-14', reference)).toBe('today');
        expect(formatRelativeDay('2026-09-15', reference)).toBe('tomorrow');
        expect(formatRelativeDay('2026-09-13', reference)).toBe('yesterday');
    });

    it('counts in days on both sides of the reference', () => {
        expect(formatRelativeDay('2026-09-17', reference)).toBe('in 3 days');
        expect(formatRelativeDay('2026-09-08', reference)).toBe('6 days ago');
    });

    it('switches to weeks once a week has passed', () => {
        expect(formatRelativeDay('2026-09-28', reference)).toBe('in 2 weeks');
        expect(formatRelativeDay('2026-08-17', reference)).toBe('4 weeks ago');
    });

    it('switches to months beyond a month', () => {
        expect(formatRelativeDay('2026-06-30', reference)).toBe('2 months ago');
        expect(formatRelativeDay('2026-12-20', reference)).toBe('in 3 months');
    });

    it('switches to years beyond a year', () => {
        expect(formatRelativeDay('2025-09-14', reference)).toBe('last year');
        expect(formatRelativeDay('2024-03-01', reference)).toBe('3 years ago');
    });

    it('falls back for empty, invalid, and timestamp values', () => {
        expect(formatRelativeDay(null, reference)).toBe('—');
        expect(formatRelativeDay('', reference)).toBe('—');
        expect(formatRelativeDay('invalid', reference)).toBe('—');
        expect(formatRelativeDay('2026-06-24T00:00:00.000Z', reference)).toBe('—');
    });
});

describe('dueDateTone', () => {
    /** Tanggal acuan tetap supaya hasilnya tidak berubah seiring waktu. */
    const reference = new Date(2026, 8, 14);

    it('has no tone without a usable date', () => {
        expect(dueDateTone(null, false, reference)).toBe('none');
        expect(dueDateTone('', false, reference)).toBe('none');
        expect(dueDateTone('invalid', false, reference)).toBe('none');
    });

    /** Keterlambatan dinilai server, termasuk untuk task selesai yang dibandingkan ke tanggal selesainya. */
    it('trusts the server flag over the calendar', () => {
        expect(dueDateTone('2026-09-10', true, reference)).toBe('overdue');
        expect(dueDateTone('2026-09-20', true, reference)).toBe('overdue');
    });

    it('warns from three days out, counting today', () => {
        expect(dueDateTone('2026-09-14', false, reference)).toBe('soon');
        expect(dueDateTone('2026-09-17', false, reference)).toBe('soon');
    });

    it('stays neutral beyond three days', () => {
        expect(dueDateTone('2026-09-18', false, reference)).toBe('normal');
    });

    /** Selesai tepat waktu: tanggalnya lampau tapi tidak terlambat, jadi tidak boleh ikut menguning. */
    it('does not warn for a past date the server did not flag', () => {
        expect(dueDateTone('2026-09-01', false, reference)).toBe('normal');
    });
});

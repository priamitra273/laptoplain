import { describe, expect, it } from 'vitest';
import { dueInfo, startOfToday } from './dueDate';

const today = startOfToday(new Date(2026, 7, 28));

const at = (offsetInDays: number): string => {
    const date = new Date(2026, 7, 28 + offsetInDays);

    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
};

describe('dueInfo', () => {
    it('reports rows without a due date as neutral', () => {
        expect(dueInfo(null, today)).toEqual({ label: 'No due date', tone: 'neutral', days: null });
    });

    it('counts how late an overdue row is', () => {
        expect(dueInfo(at(-5), today)).toMatchObject({ label: '5 days overdue', tone: 'error' });
    });

    it('keeps the singular form one day past the due date', () => {
        expect(dueInfo(at(-1), today)).toMatchObject({ label: '1 day overdue', tone: 'error' });
    });

    it('treats today as due soon rather than overdue, matching the counters', () => {
        expect(dueInfo(at(0), today)).toMatchObject({ label: 'Due today', tone: 'warning', days: 0 });
    });

    it('names tomorrow instead of counting it', () => {
        expect(dueInfo(at(1), today)).toMatchObject({ label: 'Due tomorrow', tone: 'warning' });
    });

    it('counts down the rest of the week', () => {
        expect(dueInfo(at(7), today)).toMatchObject({ label: 'Due in 7 days', tone: 'warning' });
    });

    it('falls back to an absolute date beyond a week', () => {
        expect(dueInfo(at(8), today)).toMatchObject({ label: '05 Sept 2026', tone: 'neutral' });
    });

    it('ignores the time part so timezones cannot shift the day', () => {
        expect(dueInfo('2026-08-28T23:30:00.000000Z', today)).toMatchObject({ days: 0 });
    });
});

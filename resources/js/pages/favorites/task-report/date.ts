import { CalendarDate, parseDate } from '@internationalized/date';

const formatter = new Intl.DateTimeFormat('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });

/** Kolomnya bertipe date di DB, jadi bagian jam pada payload diabaikan. */
export const toCalendarDate = (value?: string | null): CalendarDate | null => {
    if (!value) {
        return null;
    }

    try {
        return parseDate(value.slice(0, 10));
    } catch {
        return null;
    }
};

export const formatCalendarDate = (date: CalendarDate | null): string | null =>
    date ? formatter.format(new Date(date.year, date.month - 1, date.day)) : null;

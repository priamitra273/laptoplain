export type DueTone = 'error' | 'warning' | 'neutral';

export interface DueInfo {
    label: string;
    tone: DueTone;
    /** Whole days from today; negative when overdue, null when the row has no due date. */
    days: number | null;
}

const dayInMs = 86_400_000;

const absolute = new Intl.DateTimeFormat('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });

/**
 * Due dates arrive as calendar days, so they are compared at local midnight. Parsing the
 * ISO string with `new Date()` would read it as UTC and shift the day west of Greenwich.
 */
export const startOfLocalDay = (value: string): number => {
    const [year, month, day] = value.slice(0, 10).split('-').map(Number);

    return new Date(year, month - 1, day).getTime();
};

export const startOfToday = (now: Date = new Date()): number => new Date(now.getFullYear(), now.getMonth(), now.getDate()).getTime();

/**
 * Buckets mirror the counters the controller sends: overdue is strictly before today,
 * and "due soon" covers today through the next seven days.
 */
export function dueInfo(value?: string | null, today: number = startOfToday()): DueInfo {
    if (!value) {
        return { label: 'No due date', tone: 'neutral', days: null };
    }

    const due = startOfLocalDay(value);
    const days = Math.round((due - today) / dayInMs);

    if (days < 0) {
        const late = Math.abs(days);

        return { label: late === 1 ? '1 day overdue' : `${late} days overdue`, tone: 'error', days };
    }

    if (days === 0) {
        return { label: 'Due today', tone: 'warning', days };
    }

    if (days === 1) {
        return { label: 'Due tomorrow', tone: 'warning', days };
    }

    if (days <= 7) {
        return { label: `Due in ${days} days`, tone: 'warning', days };
    }

    return { label: absolute.format(new Date(due)), tone: 'neutral', days };
}

export const dueToneClass = (tone: DueTone): string => {
    if (tone === 'error') {
        return 'text-error';
    }

    return tone === 'warning' ? 'text-warning' : 'text-muted';
};

export const dueDotClass = (tone: DueTone): string => {
    if (tone === 'error') {
        return 'bg-error';
    }

    return tone === 'warning' ? 'bg-warning' : 'bg-accented';
};

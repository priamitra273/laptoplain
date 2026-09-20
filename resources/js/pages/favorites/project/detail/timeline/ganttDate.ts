// Sengaja tidak reuse parseDateOnly dari resources/js/lib/date.ts — kontraknya beda:
// itu menerima string|null|undefined dan mengembalikan Date|null setelah validasi
// format (menolak timestamp, menolak format rusak). Versi Gantt ini selalu dipanggil
// dengan string yang sudah dipastikan truthy oleh pemanggilnya (lihat hasBar), tidak
// memvalidasi apa pun, dan membiarkan input rusak jadi Invalid Date yang merambat
// sebagai NaN di style bar — itu perilaku lama, bukan yang diperbaiki di ekstraksi ini.
export const parseDateOnly = (value: string): Date => {
    const [year, month, day] = value.split('-').map(Number);
    return new Date(year, month - 1, day);
};

export const diffInDays = (a: Date, b: Date): number => Math.round((a.getTime() - b.getTime()) / 86_400_000);

export const isSameDay = (a: Date, b: Date): boolean => a.toDateString() === b.toDateString();

export const startOfWeek = (date: Date): Date => {
    const result = new Date(date);
    const day = (result.getDay() + 6) % 7; // Senin = 0
    result.setDate(result.getDate() - day);
    return result;
};

export const isoWeekNumber = (date: Date): number => {
    const target = new Date(date.getFullYear(), date.getMonth(), date.getDate());
    const dayNumber = (target.getDay() + 6) % 7;
    target.setDate(target.getDate() - dayNumber + 3);
    const firstThursday = new Date(target.getFullYear(), 0, 4);
    const diff = target.getTime() - firstThursday.getTime();
    return 1 + Math.round(diff / (7 * 86_400_000));
};

interface GanttDateRangeInput {
    start_date: string | null;
    due_date: string | null;
    sub_task_recursive?: GanttDateRangeInput[];
}

// ~3 bulan. Hari ini cuma ikut "menarik" ujung rentang kalau jaraknya masih
// dekat dari task terdekat — kalau project-nya udah lama selesai (atau belum
// mulai jauh di masa depan), hari ini diabaikan biar nggak ada ruang kosong panjang.
const INCLUDE_TODAY_THRESHOLD_DAYS = 90;

export const computeTaskDateRange = (tasks: GanttDateRangeInput[], today: Date): { min: Date; max: Date } => {
    const dates: Date[] = [];

    const walk = (list: GanttDateRangeInput[]) => {
        for (const task of list) {
            if (task.start_date) dates.push(parseDateOnly(task.start_date));
            if (task.due_date) dates.push(parseDateOnly(task.due_date));

            if (task.sub_task_recursive?.length) walk(task.sub_task_recursive);
        }
    };

    walk(tasks);

    if (dates.length === 0) {
        const min = new Date(today);
        const max = new Date(today);
        min.setDate(min.getDate() - 7);
        max.setDate(max.getDate() + 7);
        return { min, max };
    }

    let min = new Date(Math.min(...dates.map((d) => d.getTime())));
    let max = new Date(Math.max(...dates.map((d) => d.getTime())));

    if (today.getTime() > max.getTime() && diffInDays(today, max) <= INCLUDE_TODAY_THRESHOLD_DAYS) {
        max = new Date(today);
    } else if (today.getTime() < min.getTime() && diffInDays(min, today) <= INCLUDE_TODAY_THRESHOLD_DAYS) {
        min = new Date(today);
    }

    min.setDate(min.getDate() - 7);
    max.setDate(max.getDate() + 7);

    return { min, max };
};

export interface GanttDayColumn {
    date: Date;
    isWeekend: boolean;
    isWeekEnd: boolean;
    isToday: boolean;
}

export const computeDayColumns = (min: Date, max: Date, today: Date): GanttDayColumn[] => {
    const totalDays = diffInDays(max, min) + 1;

    return Array.from({ length: totalDays }, (_, i) => {
        const date = new Date(min);
        date.setDate(date.getDate() + i);
        const dayOfWeek = date.getDay();

        return {
            date,
            isWeekend: dayOfWeek === 0 || dayOfWeek === 6,
            isWeekEnd: dayOfWeek === 0, // Minggu = akhir minggu (Senin-Minggu)
            isToday: isSameDay(date, today),
        };
    });
};

export interface GanttGroup {
    label: string;
    span: number;
    isCurrent: boolean;
}

export const computeMonthGroups = (columns: GanttDayColumn[], today: Date): GanttGroup[] => {
    const groups: GanttGroup[] = [];
    let currentMonthKey: string | null = null;
    const todayMonthKey = `${today.getFullYear()}-${today.getMonth()}`;

    for (const { date } of columns) {
        const monthKey = `${date.getFullYear()}-${date.getMonth()}`;

        if (monthKey !== currentMonthKey) {
            groups.push({ label: date.toLocaleDateString('en-US', { month: 'long' }), span: 1, isCurrent: monthKey === todayMonthKey });
            currentMonthKey = monthKey;
        } else {
            groups[groups.length - 1].span++;
        }
    }

    return groups;
};

export const computeQuarterGroups = (columns: GanttDayColumn[], today: Date): GanttGroup[] => {
    const groups: GanttGroup[] = [];
    let currentQuarterKey: string | null = null;
    const todayQuarterKey = `${today.getFullYear()}-${Math.floor(today.getMonth() / 3)}`;

    for (const { date } of columns) {
        const quarterIndex = Math.floor(date.getMonth() / 3);
        const quarterKey = `${date.getFullYear()}-${quarterIndex}`;

        if (quarterKey !== currentQuarterKey) {
            groups.push({ label: `Q${quarterIndex + 1}`, span: 1, isCurrent: quarterKey === todayQuarterKey });
            currentQuarterKey = quarterKey;
        } else {
            groups[groups.length - 1].span++;
        }
    }

    return groups;
};

export const computeWeekGroups = (columns: GanttDayColumn[], today: Date): GanttGroup[] => {
    const groups: GanttGroup[] = [];
    let currentWeekStart: Date | null = null;
    const todayWeekStart = startOfWeek(today).getTime();

    for (const { date } of columns) {
        const weekStart = startOfWeek(date);

        if (!currentWeekStart || weekStart.getTime() !== currentWeekStart.getTime()) {
            groups.push({ label: `W${isoWeekNumber(weekStart)}`, span: 1, isCurrent: weekStart.getTime() === todayWeekStart });
            currentWeekStart = weekStart;
        } else {
            groups[groups.length - 1].span++;
        }
    }

    return groups;
};

interface GanttBarTask {
    start_date: string | null;
    due_date: string | null;
}

export const hasBar = (task: GanttBarTask): boolean => !!task.start_date;

export const barDates = (task: GanttBarTask): { start: Date; end: Date } => {
    const start = parseDateOnly(task.start_date!);
    const hasRange = !!task.due_date && task.due_date !== task.start_date;
    const end = hasRange ? parseDateOnly(task.due_date!) : start;

    return { start, end };
};

export const computeBarPosition = (task: GanttBarTask, windowStart: Date, dayWidth: number): { left: string; width: string } => {
    const { start, end } = barDates(task);

    const left = diffInDays(start, windowStart) * dayWidth;
    const width = (diffInDays(end, start) + 1) * dayWidth;

    return { left: `${left}px`, width: `${width}px` };
};

export const formatDayLabel = (date: Date): string => date.toLocaleDateString('en-US', { weekday: 'short' });

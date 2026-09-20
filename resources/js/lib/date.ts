const DATE_ONLY_PATTERN = /^\d{4}-\d{2}-\d{2}$/;

const EMPTY_LABEL = '—';

const MILLISECONDS_PER_DAY = 86_400_000;

/** Rata-rata panjang bulan dan tahun, supaya jarak 76 hari jatuh di "2 months", bukan "3 months". */
const DAYS_PER_MONTH = 30.44;
const DAYS_PER_YEAR = 365.25;

const dayMonthYearFormatter = new Intl.DateTimeFormat('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
const dayMonthFormatter = new Intl.DateTimeFormat('en-GB', { day: 'numeric', month: 'short' });
const relativeFormatter = new Intl.RelativeTimeFormat('en', { numeric: 'auto' });

/**
 * Hanya menerima tanggal tanpa jam. Timestamp ditolak agar informasi zona waktunya
 * tidak terbuang diam-diam; caller yang punya timestamp harus memotongnya sendiri.
 * Diparse sebagai tengah malam lokal supaya "2026-06-24" tidak mundur sehari di
 * zona waktu yang lebih barat dari UTC.
 */
export function parseDateOnly(value: string | null | undefined): Date | null {
    if (!value || !DATE_ONLY_PATTERN.test(value)) {
        return null;
    }

    const date = new Date(`${value}T00:00:00`);

    return Number.isNaN(date.getTime()) ? null : date;
}

export function formatDate(value: string | null | undefined): string {
    const date = parseDateOnly(value);

    return date ? dayMonthYearFormatter.format(date) : EMPTY_LABEL;
}

export function formatDateShort(value: string | null | undefined): string {
    const date = parseDateOnly(value);

    return date ? dayMonthFormatter.format(date) : EMPTY_LABEL;
}

const startOfDay = (date: Date): Date => new Date(date.getFullYear(), date.getMonth(), date.getDate());

/**
 * Selisih hari kalender: negatif untuk yang sudah lewat, 0 untuk hari ini. `null` kalau
 * nilainya tidak bisa diparse — supaya caller bisa membedakannya dari "hari ini".
 *
 * `reference` bisa diisi agar hasilnya bisa diuji tanpa bergantung pada tanggal hari ini.
 */
export function daysUntil(value: string | null | undefined, reference: Date = new Date()): number | null {
    const date = parseDateOnly(value);

    if (!date) {
        return null;
    }

    return Math.round((startOfDay(date).getTime() - startOfDay(reference).getTime()) / MILLISECONDS_PER_DAY);
}

/**
 * Jarak hari dalam bahasa manusia: "today", "yesterday", "in 3 days", "2 months ago".
 * Satuannya naik bertahap supaya selisih besar tidak dibaca sebagai ratusan hari.
 */
export function formatRelativeDay(value: string | null | undefined, reference: Date = new Date()): string {
    const days = daysUntil(value, reference);

    if (days === null) {
        return EMPTY_LABEL;
    }

    const distance = Math.abs(days);

    if (distance < 7) {
        return relativeFormatter.format(days, 'day');
    }

    if (distance < 31) {
        return relativeFormatter.format(Math.round(days / 7), 'week');
    }

       if (distance < 365) {
        return relativeFormatter.format(Math.round(days / DAYS_PER_MONTH), 'month');
    }

    return relativeFormatter.format(Math.round(days / DAYS_PER_YEAR), 'year');
}

export type DueDateTone = 'none' | 'overdue' | 'soon' | 'normal';

/**
 * Tiga tingkat kemendesakan due date untuk kartu board. `soon` menyalin ambang v1: tiga hari
 * atau kurang. `isOverdue` datang dari server, bukan dihitung ulang di sini, karena task yang
 * sudah selesai dinilai terhadap tanggal selesainya — bukan terhadap hari ini.
 */
export function dueDateTone(value: string | null | undefined, isOverdue: boolean, reference: Date = new Date()): DueDateTone {
    const days = daysUntil(value, reference);

    if (days === null) {
        return 'none';
    }

    if (isOverdue) {
        return 'overdue';
    }

    /** Batas bawahnya perlu: task yang selesai tepat waktu punya due date lampau tanpa flag overdue. */
    return days >= 0 && days <= 3 ? 'soon' : 'normal';
}

export function formatDateRange(start: string | null | undefined, end: string | null | undefined): string {
const startDate = parseDateOnly(start);
    const endDate = parseDateOnly(end);

    if (!startDate && !endDate) {
        return EMPTY_LABEL;
    }

    const sameYear = startDate !== null && endDate !== null && startDate.getFullYear() === endDate.getFullYear();

    const startLabel = startDate ? (sameYear ? dayMonthFormatter : dayMonthYearFormatter).format(startDate) : EMPTY_LABEL;
    const endLabel = endDate ? dayMonthYearFormatter.format(endDate) : EMPTY_LABEL;

    return `${startLabel} – ${endLabel}`;
}

import type { PrimeSeverity } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { clsx, type ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';

/**
 * Tailwind merge
 *
 * @param inputs
 * @returns string
 */
export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

/**
 * Check if user has permission
 *
 * @param permission
 * @returns boolean
 */
export function can(permission: string): boolean {
    // usePage() dipanggil di dalam fungsi, bukan di module scope: modul ini diimpor
    // sebelum app ter-mount (dan di SSR), dan usePage() butuh instance yang aktif.
    return usePage().props.auth.permissions.includes(permission);
}

/**
 * Severity di DB memakai kosakata PrimeVue; Nuxt UI memakai kosakata sendiri.
 * Hanya tiga nilai yang berbeda, sisanya identik.
 */
export function severityColor(
    severity: PrimeSeverity | null | undefined,
): 'primary' | 'secondary' | 'success' | 'info' | 'warning' | 'error' | 'neutral' {
    switch (severity) {
        case 'warn':
            return 'warning';
        case 'danger':
            return 'error';
        case 'contrast':
            return 'neutral';
        case 'primary':
        case 'secondary':
        case 'success':
        case 'info':
            return severity;
        default:
            return 'neutral';
    }
}

/**
 * Get initials from name
 *
 * @param name
 * @returns string
 */
export function getInitials(name: string): string {
    return name
        .split(' ')
        .map((w) => w[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);
}

export function randomHexColor(): string {
    return '#' + Math.floor(Math.random() * 16777215).toString(16);
}

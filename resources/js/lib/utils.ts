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

export function isSuperAdmin(roles: string[]): boolean {
    return roles.some((role) => role.startsWith('super-admin-'));
}

export function hasPermission(permissions: string[], roles: string[], permission: string): boolean {
    return isSuperAdmin(roles) || permissions.includes(permission);
}

export function can(permission: string): boolean {
    const auth = usePage().props.auth;

    return hasPermission(auth.permissions ?? [], auth.roles ?? [], permission);
}


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

export function severityDotClass(severity: PrimeSeverity | null | undefined): string {
    const color = severityColor(severity);

    return color === 'neutral' || color === 'secondary' ? 'bg-neutral-400' : `bg-${color}`;
}


export function severityDotStyle(severity?: PrimeSeverity | null): { backgroundColor: string } {
    return { backgroundColor: `var(--ui-color-${severityColor(severity)}-500)` };
}


export function severityBoxStyle(severity?: PrimeSeverity | null): { backgroundColor: string; borderColor: string } {
    const color = severityColor(severity);

    return {
        backgroundColor: `color-mix(in srgb, var(--ui-color-${color}-500) 6%, transparent)`,
        borderColor: `color-mix(in srgb, var(--ui-color-${color}-500) 25%, transparent)`,
    };
}

const PRIORITY_ICONS: Record<string, string> = {
    Low: 'i-lucide-signal-low',
    Medium: 'i-lucide-signal-medium',
    High: 'i-lucide-signal-high',
    Critical: 'i-lucide-signal',
};


export function priorityIcon(name: string | null | undefined): string {
    return PRIORITY_ICONS[name ?? ''] ?? 'i-lucide-signal-low';
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


export function plural(count: number, word: string): string {
    return `${count} ${word}${count === 1 ? '' : 's'}`;
}


const DONE_STATUS_NAMES = new Set(['COMPLETED', 'FINISHED', 'DONE']);

/** Dibandingkan case-insensitive — perilaku ini sebelumnya cuma ada di ringkasan List. */
export function isTaskStatusDone(statusName: string | null | undefined): boolean {
    return !!statusName && DONE_STATUS_NAMES.has(statusName.toUpperCase());
}

export function randomHexColor(): string {
    return '#' + Math.floor(Math.random() * 16777215).toString(16);
}

const getCsrfToken = (): string => {
    const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/);
    return match ? decodeURIComponent(match[1]) : '';
};

export class FetchJsonError extends Error {
    constructor(
        message: string,
        public status: number,
        public data: unknown,
    ) {
        super(message);
    }
}

/**
 * Plain-JSON request helper for endpoints that branch on `expectsJson()`. Inertia's router
 * (and `useHttp`) always send `Accept: text/html`, so those endpoints take their redirect
 * branch instead of returning JSON when called through Inertia — this bypasses Inertia
 * entirely for that one request, mirroring how axios called the same endpoints pre-migration.
 */
export async function fetchJson<T = unknown>(url: string, method: 'POST' | 'PUT' | 'PATCH' | 'DELETE', body?: Record<string, unknown>): Promise<T> {
    const response = await fetch(url, {
        method,
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-XSRF-TOKEN': getCsrfToken(),
        },
        credentials: 'same-origin',
        body: body ? JSON.stringify(body) : undefined,
    });

    const data = await response.json().catch(() => null);

    if (!response.ok) {
        throw new FetchJsonError((data as { message?: string })?.message ?? 'Request failed', response.status, data);
    }

    return data as T;
}

export function formErrorFor(errors: Record<string, string | string[] | undefined>, ...prefixes: string[]): string | undefined {
    const key = Object.keys(errors).find((name) => prefixes.some((prefix) => name === prefix || name.startsWith(`${prefix}.`)));

    if (!key) {
        return undefined;
    }

    const value = errors[key];

    return Array.isArray(value) ? value[0] : value;
}

/** Ukuran file dalam satuan yang enak dibaca. */
export function formatFileSize(bytes: number): string {
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;

    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

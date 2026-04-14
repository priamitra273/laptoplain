import { usePage } from '@inertiajs/vue3';
import { clsx, type ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';

const page = usePage();

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
    return page.props.auth.permissions.includes(permission);
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

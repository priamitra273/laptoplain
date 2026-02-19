import { usePage } from '@inertiajs/vue3';
import { clsx, type ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';

const page = usePage();

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function can(permission: string): boolean {
    return page.props.auth.permissions.includes(permission);
}

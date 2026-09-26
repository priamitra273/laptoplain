import type { route as routeFn } from 'ziggy-js';

declare global {
    const route: typeof routeFn;
    const __LUCIDE_ICON_NAMES__: string[];
}

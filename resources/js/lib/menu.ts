import type { SidebarMenuItem } from '@/types';
import type { NavigationMenuItem } from '@nuxt/ui';

/**
 * Nama ikon di DB (MenuSeeder) berupa PascalCase Lucide — `LayoutDashboard`.
 * Nuxt UI / Iconify butuh `i-lucide-layout-dashboard`.
 */
export function toLucideIcon(name: string | null | undefined): string | undefined {
    if (!name) {
        return undefined;
    }

    const kebab = name
        .replace(/([a-z0-9])([A-Z])/g, '$1-$2')
        .replace(/([A-Z])([A-Z][a-z])/g, '$1-$2')
        .toLowerCase();

    return `i-lucide-${kebab}`;
}

/**
 * Ubah pohon menu dari server jadi item `UNavigationMenu`.
 *
 * `resolveHref` diinjeksi (bukan memanggil `route()` global) supaya modul ini bisa
 * diuji tanpa Ziggy, dan supaya route name yang tidak terdaftar tidak melempar.
 *
 * @param items pohon `{ label, icon, to, items }` dari MenuService::getSidebarMenu()
 * @param resolveHref penerjemah route name → URL
 */
export function buildNavigationItems(items: SidebarMenuItem[] | undefined, resolveHref: (routeName: string) => string): NavigationMenuItem[] {
    return (items ?? []).map((item) => {
        const children = buildNavigationItems(item.items as SidebarMenuItem[] | undefined, resolveHref);

        return {
            label: item.label,
            icon: toLucideIcon(item.icon),
            ...(item.to ? { to: resolveHref(item.to) } : {}),
            ...(children.length ? { children, defaultOpen: true } : {}),
        } satisfies NavigationMenuItem;
    });
}

/**
 * Ratakan pohon jadi daftar datar berisi item yang benar-benar bisa dituju,
 * untuk dipakai `UDashboardSearch`.
 */
export function flattenNavigationItems(items: NavigationMenuItem[]): NavigationMenuItem[] {
    return items.flatMap((item) => [...(item.to ? [item] : []), ...flattenNavigationItems((item.children as NavigationMenuItem[]) ?? [])]);
}

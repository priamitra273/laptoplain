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

export function isNavItemActive(href: string | undefined, currentUrl: string | undefined): boolean {
    if (!href || currentUrl === undefined) {
        return false;
    }

    const normalize = (value: string) => value.split('?')[0].split('#')[0].replace(/\/+$/, '') || '/';

    const target = normalize(href);
    const current = normalize(currentUrl);

    if (target === '/') {
        return current === '/';
    }

    return current === target || current.startsWith(`${target}/`);
}

/**
 * Ubah pohon menu dari server jadi item `UNavigationMenu`.
 *
 * `resolveHref` diinjeksi (bukan memanggil `route()` global) supaya modul ini bisa
 * diuji tanpa Ziggy, dan supaya route name yang tidak terdaftar tidak melempar.
 *
 * `active` dihitung di sini, bukan diserahkan ke `ULink`: pencocokan bawaannya
 * memakai `page.url.startsWith(href)` yang membuat `/task-status` ikut menyalakan
 * `/task`. Tanpa `currentUrl` kunci `active` tidak ditulis sama sekali.
 *
 * @param items pohon `{ label, icon, to, items }` dari MenuService::getSidebarMenu()
 * @param resolveHref penerjemah route name → URL
 * @param currentUrl URL halaman yang sedang terbuka (`page.url` Inertia)
 */
export function buildNavigationItems(
    items: SidebarMenuItem[] | undefined,
    resolveHref: (routeName: string) => string,
    currentUrl?: string,
): NavigationMenuItem[] {
    return (items ?? []).map((item) => {
        const children = buildNavigationItems(item.items as SidebarMenuItem[] | undefined, resolveHref, currentUrl);
        const to = item.to ? resolveHref(item.to) : undefined;
        // induk ikut aktif agar ikonnya tetap tersorot saat sidebar menciut jadi rail
        const active = isNavItemActive(to, currentUrl) || children.some((child) => child.active);

        return {
            label: item.label,
            icon: toLucideIcon(item.icon),
            ...(to ? { to } : {}),
            ...(children.length ? { children, defaultOpen: true } : {}),
            ...(active ? { active: true } : {}),
        } satisfies NavigationMenuItem;
    });
}

/**
 * Ubah pohon jadi grup datar ala ui-thing: induk tanpa route jadi label seksi,
 * anaknya naik jadi item level atas. Node yang memang bisa dituju berdiri
 * sendiri sebagai grup satu item.
 *
 * `UNavigationMenu` merender tiap sub-array sebagai `<ul>` terpisah, dan
 * menyembunyikan item `type: 'label'` sendiri saat sidebar collapsed.
 */
export function toNavigationGroups(items: NavigationMenuItem[]): NavigationMenuItem[][] {
    return items.map((item) =>
        item.children
            ? // ikon induk dibuang: label seksi ui-thing berupa teks polos
              [{ type: 'label' as const, label: item.label }, ...(item.children as NavigationMenuItem[])]
            : [item],
    );
}

/**
 * Ratakan pohon jadi daftar datar berisi item yang benar-benar bisa dituju,
 * untuk dipakai `UDashboardSearch`.
 */
export function flattenNavigationItems(items: NavigationMenuItem[]): NavigationMenuItem[] {
    return items.flatMap((item) => [...(item.to ? [item] : []), ...flattenNavigationItems((item.children as NavigationMenuItem[]) ?? [])]);
}

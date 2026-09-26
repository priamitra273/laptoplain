import { buildNavigationItems, flattenNavigationItems, isNavItemActive, toLucideIcon, toNavigationGroups } from '@/lib/menu';
import type { SidebarMenuItem } from '@/types';
import type { NavigationMenuItem } from '@nuxt/ui';
import { describe, expect, it } from 'vitest';

describe('toLucideIcon', () => {
    // Seluruh ikon yang benar-benar ada di database/seeders/MenuSeeder.php
    it.each([
        ['Activity', 'i-lucide-activity'],
        ['BookCheck', 'i-lucide-book-check'],
        ['Building2', 'i-lucide-building-2'],
        ['Clock10', 'i-lucide-clock-10'],
        ['Compass', 'i-lucide-compass'],
        ['FolderCog', 'i-lucide-folder-cog'],
        ['Grid2x2', 'i-lucide-grid-2x2'],
        ['Inbox', 'i-lucide-inbox'],
        ['Layers', 'i-lucide-layers'],
        ['LayoutDashboard', 'i-lucide-layout-dashboard'],
        ['Network', 'i-lucide-network'],
        ['ScrollText', 'i-lucide-scroll-text'],
        ['Settings', 'i-lucide-settings'],
        ['Shapes', 'i-lucide-shapes'],
        ['Star', 'i-lucide-star'],
        ['UserCog', 'i-lucide-user-cog'],
    ])('mengubah %s menjadi %s', (input, expected) => {
        expect(toLucideIcon(input)).toBe(expected);
    });

    it.each(['i-lucide-folder-cog', 'i-lucide-building-2'])('melewatkan %s apa adanya', (icon) => {
        expect(toLucideIcon(icon)).toBe(icon);
    });

    it('memecah rentetan huruf kapital di batas kata', () => {
        expect(toLucideIcon('SquareCheckBig')).toBe('i-lucide-square-check-big');
        expect(toLucideIcon('QRCode')).toBe('i-lucide-qr-code');
    });

    it('mengembalikan undefined untuk nilai kosong', () => {
        expect(toLucideIcon(null)).toBeUndefined();
        expect(toLucideIcon('')).toBeUndefined();
    });
});

describe('buildNavigationItems', () => {
    const resolveHref = (name: string) => `/${name.replace(/\./g, '/')}`;

    const menu: SidebarMenuItem[] = [
        { label: 'Dashboard', icon: 'LayoutDashboard', to: 'dashboard', items: null },
        {
            label: 'Master',
            icon: 'FolderCog',
            items: [{ label: 'Tag', icon: 'Shapes', to: 'tag.index', items: null }],
        },
    ];

    it('memetakan ikon dan menerjemahkan route name jadi URL', () => {
        expect(buildNavigationItems(menu, resolveHref)).toEqual([
            { label: 'Dashboard', icon: 'i-lucide-layout-dashboard', to: '/dashboard' },
            {
                label: 'Master',
                icon: 'i-lucide-folder-cog',
                children: [{ label: 'Tag', icon: 'i-lucide-shapes', to: '/tag/index' }],
                defaultOpen: true,
            },
        ]);
    });

    it('tidak memberi `to` pada induk yang tidak punya route', () => {
        expect(buildNavigationItems(menu, resolveHref)[1]).not.toHaveProperty('to');
    });

    it('menerima menu kosong atau undefined', () => {
        expect(buildNavigationItems([], resolveHref)).toEqual([]);
        expect(buildNavigationItems(undefined, resolveHref)).toEqual([]);
    });

    it('tidak menulis kunci `active` ketika URL tidak diberikan', () => {
        expect(buildNavigationItems(menu, resolveHref)[0]).not.toHaveProperty('active');
    });

    it('menandai item yang cocok dengan URL sekarang', () => {
        const [dashboard, master] = buildNavigationItems(menu, resolveHref, '/dashboard');

        expect(dashboard.active).toBe(true);
        expect(master).not.toHaveProperty('active');
    });

    it('ikut menandai induk ketika salah satu anaknya aktif', () => {
        const [dashboard, master] = buildNavigationItems(menu, resolveHref, '/tag/index');

        expect(master.active).toBe(true);
        expect((master.children as NavigationMenuItem[])[0].active).toBe(true);
        expect(dashboard).not.toHaveProperty('active');
    });
});

describe('isNavItemActive', () => {
    it('cocok persis dan pada rute bersarang', () => {
        expect(isNavItemActive('/task', '/task')).toBe(true);
        expect(isNavItemActive('/task', '/task/47OCdz06')).toBe(true);
    });

    it('tidak ikut aktif pada path yang cuma berawalan sama', () => {
        expect(isNavItemActive('/task', '/task-status')).toBe(false);
        expect(isNavItemActive('/project', '/project-role')).toBe(false);
    });

    it('mengabaikan query, hash, dan garis miring di ujung', () => {
        expect(isNavItemActive('/task', '/task?status=open')).toBe(true);
        expect(isNavItemActive('/task', '/task#top')).toBe(true);
        expect(isNavItemActive('/task/', '/task')).toBe(true);
    });

    it('memperlakukan beranda sebagai kecocokan persis', () => {
        expect(isNavItemActive('/', '/')).toBe(true);
        expect(isNavItemActive('/', '/dashboard')).toBe(false);
    });

    it('mengembalikan false untuk href atau URL yang tidak ada', () => {
        expect(isNavItemActive(undefined, '/task')).toBe(false);
        expect(isNavItemActive('/task', undefined)).toBe(false);
    });
});

describe('toNavigationGroups', () => {
    const items = buildNavigationItems(
        [
            { label: 'Dashboard', icon: 'LayoutDashboard', to: 'dashboard', items: null },
            { label: 'Master', icon: 'FolderCog', items: [{ label: 'Tag', icon: 'Shapes', to: 'tag.index', items: null }] },
        ],
        (name) => `/${name}`,
    );

    it('mengubah induk tanpa route jadi label seksi dan menaikkan anaknya', () => {
        expect(toNavigationGroups(items)[1]).toEqual([
            { type: 'label', label: 'Master' },
            { label: 'Tag', icon: 'i-lucide-shapes', to: '/tag.index' },
        ]);
    });

    it('tidak memberi ikon pada label seksi', () => {
        expect(toNavigationGroups(items)[1][0]).not.toHaveProperty('icon');
    });

    it('membuat grup satu item untuk menu yang bisa dituju', () => {
        expect(toNavigationGroups(items)[0]).toEqual([{ label: 'Dashboard', icon: 'i-lucide-layout-dashboard', to: '/dashboard' }]);
    });

    it('menerima daftar kosong', () => {
        expect(toNavigationGroups([])).toEqual([]);
    });
});

describe('flattenNavigationItems', () => {
    it('hanya menyisakan item yang bisa dituju', () => {
        const items = buildNavigationItems(
            [
                { label: 'Dashboard', icon: 'LayoutDashboard', to: 'dashboard', items: null },
                { label: 'Master', icon: 'FolderCog', items: [{ label: 'Tag', icon: 'Shapes', to: 'tag.index', items: null }] },
            ],
            (name) => `/${name}`,
        );

        expect(flattenNavigationItems(items).map((item) => item.label)).toEqual(['Dashboard', 'Tag']);
    });
});

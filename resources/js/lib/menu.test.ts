import { buildNavigationItems, flattenNavigationItems, toLucideIcon } from '@/lib/menu';
import type { SidebarMenuItem } from '@/types';
import { describe, expect, it } from 'vitest';

describe('toLucideIcon', () => {
    // Seluruh ikon yang benar-benar ada di database/seeders/MenuSeeder.php
    it.each([
        ['Activity', 'i-lucide-activity'],
        ['BookCheck', 'i-lucide-book-check'],
        ['Compass', 'i-lucide-compass'],
        ['FolderCog', 'i-lucide-folder-cog'],
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

import type { DropdownMenuItem } from '@nuxt/ui';
import { describe, expect, it, vi } from 'vitest';

import type { CascadeNode, CascadeOption, CascadeSelectProps } from './types';
import { buildCascadeNodes, findCascadePath, readField, SELECTED_ITEM_CLASS, toMenuItems } from './utils';

const wilayah = [
    {
        nama: 'Bali',
        kota: [
            { nama: 'Denpasar', kecamatan: [{ nama: 'Denpasar Barat', kode: 'DPS-BR' }] },
            // grup tanpa anak: jadi leaf yang mati, bukan submenu kosong
            { nama: 'Klungkung', kecamatan: [] },
        ],
    },
    {
        nama: 'Jawa Barat',
        kota: [{ nama: 'Bandung', kecamatan: [{ nama: 'Coblong', kode: 'BDO-CB' }] }],
    },
] satisfies CascadeOption[];

const config: CascadeSelectProps = {
    options: wilayah,
    optionLabel: 'nama',
    optionValue: 'kode',
    optionGroupLabel: 'nama',
    optionGroupChildren: ['kota', 'kecamatan'],
    emptyMessage: 'Tidak ada pilihan',
};

/** `children` UDropdownMenu boleh berupa array bersarang, jadi aksesnya dipersempit. */
const childAt = (item: DropdownMenuItem | undefined, index: number) => {
    const children = item?.children;
    return Array.isArray(children) ? (children[index] as DropdownMenuItem | undefined) : undefined;
};

describe('readField', () => {
    it('membaca dot-path dan getter', () => {
        expect(readField({ meta: { kode: 'A' } }, 'meta.kode')).toBe('A');
        expect(readField({ kode: 'B' }, (option) => option.kode)).toBe('B');
        expect(readField({}, 'meta.kode')).toBeUndefined();
    });
});

describe('buildCascadeNodes', () => {
    const nodes = buildCascadeNodes(wilayah, config);

    it('menormalisasi tiap level dengan accessor-nya sendiri', () => {
        expect(nodes.map((node) => node.label)).toEqual(['Bali', 'Jawa Barat']);
        const leaf = nodes[0]?.children?.[0]?.children?.[0];
        expect(leaf).toMatchObject({ label: 'Denpasar Barat', value: 'DPS-BR', level: 2, key: '0.0.0' });
        expect(leaf?.children).toBeNull();
    });

    it('menjadikan grup berdaftar-anak-kosong sebagai leaf yang mati', () => {
        expect(nodes[0]?.children?.[1]).toMatchObject({ label: 'Klungkung', disabled: true });
        expect(nodes[0]?.children?.[1]?.children).toBeNull();
    });

    it('memakai option itu sendiri sebagai nilai bila optionValue tidak diisi', () => {
        const tanpaValue = buildCascadeNodes(wilayah, { ...config, optionValue: undefined });
        expect(tanpaValue[0]?.children?.[0]?.children?.[0]?.value).toBe(wilayah[0]?.kota[0]?.kecamatan[0]);
    });
});

describe('findCascadePath', () => {
    const nodes = buildCascadeNodes(wilayah, config);

    it('mengembalikan jalur akar sampai leaf', () => {
        expect(findCascadePath(nodes, 'BDO-CB').map((node) => node.label)).toEqual(['Jawa Barat', 'Bandung', 'Coblong']);
    });

    it('kosong untuk nilai tak dikenal maupun null', () => {
        expect(findCascadePath(nodes, 'TIDAK-ADA')).toEqual([]);
        expect(findCascadePath(nodes, null)).toEqual([]);
    });
});

describe('toMenuItems', () => {
    it('menandai leaf terpilih, membuka jalurnya, dan meneruskan pilihan', () => {
        const nodes = buildCascadeNodes(wilayah, config);
        const path = findCascadePath(nodes, 'BDO-CB');
        const pathKeys = new Set(path.slice(0, -1).map((node) => node.key));
        const onSelect = vi.fn<(node: CascadeNode) => void>();

        const items = toMenuItems(nodes, path[path.length - 1]?.key, pathKeys, onSelect);

        // seluruh jalur — grup akar sampai leaf — ikut disorot
        const provinsi = items[1];
        expect(provinsi).toMatchObject({
            label: 'Jawa Barat',
            defaultOpen: true,
            class: SELECTED_ITEM_CLASS,
        });
        // grup di luar jalur terpilih tetap tertutup dan tanpa sorotan
        expect(items[0]).toMatchObject({ defaultOpen: false, class: undefined });

        const leaf = childAt(childAt(provinsi, 0), 0);
        expect(childAt(provinsi, 0)).toMatchObject({ label: 'Bandung', class: SELECTED_ITEM_CLASS });
        expect(leaf).toMatchObject({
            label: 'Coblong',
            type: 'checkbox',
            checked: true,
            class: SELECTED_ITEM_CLASS,
        });

        leaf?.onSelect?.(new Event('select'));
        expect(onSelect).toHaveBeenCalledWith(expect.objectContaining({ value: 'BDO-CB' }));
    });
});

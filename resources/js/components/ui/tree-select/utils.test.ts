import { describe, expect, it } from 'vitest';

import type { TreeSelectNode } from './types';
import { branchKeys, buildNodeMap, buildParentMap, filterTree, pathToKeys, resolveFilterFields, toKeyArray } from './utils';

const tree: TreeSelectNode[] = [
    {
        key: 'bali',
        label: 'Bali',
        children: [
            { key: 'denpasar', label: 'Denpasar', data: { kode: 'DPS' } },
            { key: 'badung', label: 'Badung', data: { kode: 'BDG' } },
        ],
    },
    {
        key: 'jawa',
        label: 'Jawa',
        children: [{ key: 'jakarta', label: 'Jakarta', data: { kode: 'JKT' } }],
    },
];

describe('buildNodeMap', () => {
    it('mendatarkan seluruh pohon jadi peta key → node', () => {
        expect(Object.keys(buildNodeMap(tree)).sort()).toEqual(['badung', 'bali', 'denpasar', 'jakarta', 'jawa']);
    });

    it('mengembalikan peta kosong untuk pohon kosong', () => {
        expect(buildNodeMap(undefined)).toEqual({});
    });
});

describe('buildParentMap / pathToKeys', () => {
    it('memetakan node akar ke null', () => {
        expect(buildParentMap(tree).bali).toBeNull();
    });

    it('mengembalikan rantai leluhur, akar lebih dulu', () => {
        expect(pathToKeys('denpasar', buildParentMap(tree))).toEqual(['bali']);
        expect(pathToKeys('bali', buildParentMap(tree))).toEqual([]);
    });

    it('tidak menggantung ketika pohon menunjuk balik ke leluhurnya sendiri', () => {
        const cyclic = { a: 'b', b: 'a' };

        expect(pathToKeys('a', cyclic)).toEqual(['a', 'b']);
    });
});

describe('branchKeys', () => {
    it('hanya mengembalikan key yang punya anak', () => {
        expect(branchKeys(tree)).toEqual(['bali', 'jawa']);
    });
});

describe('toKeyArray', () => {
    it('menormalkan kedua bentuk modelValue', () => {
        expect(toKeyArray(undefined)).toEqual([]);
        expect(toKeyArray('bali')).toEqual(['bali']);
        expect(toKeyArray(['bali', 'jawa'])).toEqual(['bali', 'jawa']);
    });
});

describe('resolveFilterFields', () => {
    it('default ke label, termasuk saat diberi array kosong', () => {
        expect(resolveFilterFields(undefined)).toEqual(['label']);
        expect(resolveFilterFields([])).toEqual(['label']);
        expect(resolveFilterFields('data.kode')).toEqual(['data.kode']);
    });
});

describe('filterTree', () => {
    const config = { text: '', fields: ['label' as const] };

    it('mengembalikan pohon apa adanya saat teks kosong', () => {
        expect(filterTree(tree, config)).toBe(tree);
    });

    it('mode lenient membawa serta seluruh subtree dari node yang cocok', () => {
        const result = filterTree(tree, { ...config, text: 'bali' });

        expect(result).toHaveLength(1);
        expect(result[0].children).toHaveLength(2);
    });

    it('mode strict memangkas anak yang tidak cocok', () => {
        const result = filterTree(tree, { ...config, text: 'denpasar', strict: true });

        expect(result).toHaveLength(1);
        expect(result[0].children?.map((child) => child.key)).toEqual(['denpasar']);
    });

    it('membuang `children` pada node yang cocok tanpa keturunan yang bertahan', () => {
        const result = filterTree(tree, { ...config, text: 'bali', strict: true });

        // `[]` bernilai truthy — reka menurunkan hasChildren dari !!children, jadi harus undefined
        expect(result[0].children).toBeUndefined();
    });

    it('tidak memutasi options milik pemanggil', () => {
        const snapshot = JSON.stringify(tree);
        filterTree(tree, { ...config, text: 'denpasar', strict: true });

        expect(JSON.stringify(tree)).toBe(snapshot);
    });

    it('mencocokkan lewat field bersarang', () => {
        const result = filterTree(tree, { text: 'jkt', fields: ['data.kode'] });

        expect(result[0].children?.map((child) => child.key)).toEqual(['jakarta']);
    });

    it('field kosong tidak ikut cocok dengan "und"', () => {
        expect(filterTree(tree, { text: 'und', fields: ['data.kode'] })).toEqual([]);
    });
});

import type { DropdownMenuItem } from '@nuxt/ui';
import { twMerge } from 'tailwind-merge';
import type { CascadeNode, CascadeOption, CascadeOptionAccessor, CascadeSelectProps } from './types';

/** Bagian props yang dibutuhkan untuk menormalisasi option. */
export type CascadeConfig = Pick<CascadeSelectProps, 'optionLabel' | 'optionValue' | 'optionDisabled' | 'optionGroupLabel' | 'optionGroupChildren'>;

/** Baca satu field option: lewat getter, atau nama properti dengan dukungan dot-path. */
export function readField(option: CascadeOption, accessor: CascadeOptionAccessor): unknown {
    if (typeof accessor === 'function') return accessor(option);
    return accessor.split('.').reduce<unknown>((value, key) => (value as CascadeOption | undefined)?.[key], option);
}

const toLabel = (value: unknown) => (value === null || value === undefined ? '' : String(value));

/** Anak mentah sebuah option pada level tertentu — belum dipastikan berbentuk array. */
function rawChildren(option: CascadeOption, config: CascadeConfig, level: number): unknown {
    const accessor = config.optionGroupChildren;
    if (accessor === undefined) return undefined;
    if (typeof accessor === 'function') return accessor(option);
    if (Array.isArray(accessor)) {
        const field = accessor[level];
        return field === undefined ? undefined : readField(option, field);
    }
    return readField(option, accessor);
}

/** Normalisasi `options` bertingkat jadi pohon `CascadeNode`. */
export function buildCascadeNodes(options: CascadeOption[], config: CascadeConfig, level = 0, parentKey = ''): CascadeNode[] {
    return options.map((option, index) => {
        const raw = rawChildren(option, config, level);
        const children = Array.isArray(raw) && raw.length > 0 ? (raw as CascadeOption[]) : null;
        const key = parentKey === '' ? String(index) : `${parentKey}.${index}`;
        const labelAccessor = (children ? config.optionGroupLabel : undefined) ?? config.optionLabel;

        return {
            option,
            label: toLabel(labelAccessor === undefined ? option : readField(option, labelAccessor)),
            value: children ? undefined : config.optionValue === undefined ? option : readField(option, config.optionValue),
            // grup yang daftar anaknya kosong jadi leaf mati, bukan submenu kosong
            disabled:
                (config.optionDisabled !== undefined && Boolean(readField(option, config.optionDisabled))) ||
                (Array.isArray(raw) && raw.length === 0),
            level,
            key,
            children: children ? buildCascadeNodes(children, config, level + 1, key) : null,
        };
    });
}

/**
 * Jalur akar → leaf yang nilainya sama dengan `value`.
 *
 * ponytail: pembandingnya `===`. Kalau nilainya objek yang tidak lagi satu referensi
 * dengan isi `options` (mis. hasil round-trip API), isi `optionValue` dengan properti
 * primitif — atau ganti pembandingnya dengan perbandingan mendalam.
 */
export function findCascadePath(nodes: CascadeNode[], value: unknown): CascadeNode[] {
    if (value === null || value === undefined) return [];

    for (const node of nodes) {
        if (node.children) {
            const path = findCascadePath(node.children, value);
            if (path.length > 0) return [node, ...path];
        } else if (node.value === value) {
            return [node];
        }
    }
    return [];
}

/**
 * Penanda item yang ada di jalur nilai terpilih, dari grup akar sampai leaf-nya.
 *
 * Meniru variant `active` tema dropdown-menu (`text-highlighted before:bg-elevated`).
 * Modifier `data-highlighted` dan `data-[state=open]` ikut disebut karena tema memberi
 * keduanya `bg-elevated/50` — tanpa ini baris terpilih justru memudar saat disorot.
 */
export const SELECTED_ITEM_CLASS = twMerge(
    'text-highlighted before:bg-primary-100/70 dark:before:bg-primary-300/30',
    'data-highlighted:before:bg-primary-100/70 dark:data-highlighted:before:bg-primary-300/30',
    'data-[state=open]:before:bg-primary-100/70 dark:data-[state=open]:before:bg-primary-300/30',
);

/** Pohon `CascadeNode` → item `UDropdownMenu`; grup jadi submenu, leaf jadi checkbox. */
export function toMenuItems(
    nodes: CascadeNode[],
    selectedKey: string | undefined,
    /** Kunci grup di jalur nilai terpilih — dipakai untuk membuka sekaligus menyorotnya. */
    pathKeys: Set<string>,
    onSelect: (node: CascadeNode) => void,
): DropdownMenuItem[] {
    return nodes.map((node) =>
        node.children
            ? {
                  label: node.label,
                  disabled: node.disabled,
                  // jalur nilai terpilih sudah terbuka begitu overlay muncul
                  defaultOpen: pathKeys.has(node.key),
                  class: pathKeys.has(node.key) ? SELECTED_ITEM_CLASS : undefined,
                  children: toMenuItems(node.children, selectedKey, pathKeys, onSelect),
              }
            : {
                  label: node.label,
                  disabled: node.disabled,
                  // tipe checkbox: UDropdownMenu yang merender ikon centangnya
                  type: 'checkbox' as const,
                  checked: node.key === selectedKey,
                  class: node.key === selectedKey ? SELECTED_ITEM_CLASS : undefined,
                  onSelect: () => onSelect(node),
              },
    );
}

import { parentOptionTree } from '@/lib/taskTree';
import { computed, ref, toValue, type MaybeRefOrGetter } from 'vue';

export interface ParentPickerOption {
    id: string;
    title: string;
    parent_id?: string | null;
}

/**
 * Opsi induk sebagai pohon yang bisa dibuka-tutup di dalam dropdown. Semua cabang tertutup
 * di awal, jadi daftar dimulai dari task teratas saja.
 *
 * Saat kotak pencarian terisi, seluruh cabang dibuka sementara: filter bawaan USelectMenu hanya
 * melihat item yang dikirim, jadi tanpa ini hasil yang cocok di dalam cabang tertutup tidak pernah muncul.
 */
export function useParentPicker<T extends ParentPickerOption>(options: MaybeRefOrGetter<T[]>, searchTerm: MaybeRefOrGetter<string>) {
    const expandedIds = ref(new Set<string>());

    const tree = computed(() => parentOptionTree(toValue(options)));

    /** Sebuah node punya anak kalau node sesudahnya pada urutan depth-first lebih dalam. */
    const branchIds = computed(() => {
        const ids = new Set<string>();

        tree.value.forEach((item, index) => {
            const next = tree.value[index + 1];
            if (next && next.depth > item.depth) ids.add(item.id);
        });

        return ids;
    });

    const items = computed(() => {
        if (toValue(searchTerm).trim()) return tree.value;

        const visible: typeof tree.value = [];
        let collapsedDepth: number | null = null;

        for (const item of tree.value) {
            if (collapsedDepth !== null) {
                if (item.depth > collapsedDepth) continue;
                collapsedDepth = null;
            }

            visible.push(item);

            if (!expandedIds.value.has(item.id)) collapsedDepth = item.depth;
        }

        return visible;
    });

    const toggle = (id: string) => {
        const next = new Set(expandedIds.value);
        if (!next.delete(id)) next.add(id);
        expandedIds.value = next;
    };

    return {
        items,
        toggle,
        isBranch: (id: string) => branchIds.value.has(id),
        isExpanded: (id: string) => expandedIds.value.has(id),
    };
}

/**
 * Urutkan opsi induk seperti pohon — anak tepat di bawah induknya — dan bawa `depth` untuk indentasi.
 * Opsi yang induknya tidak ada di daftar (mis. task yang sedang diedit dibuang dari pilihan)
 * ikut diperlakukan sebagai akar supaya tidak hilang dari dropdown.
 */
export function parentOptionTree<T extends { id: string; parent_id?: string | null }>(options: T[]): (T & { depth: number })[] {
    const ids = new Set(options.map((option) => option.id));
    const childrenOf = new Map<string, T[]>();
    const roots: T[] = [];

    for (const option of options) {
        const parentId = option.parent_id ?? null;

        if (parentId === null || !ids.has(parentId)) {
            roots.push(option);
            continue;
        }

        const siblings = childrenOf.get(parentId);
        if (siblings) siblings.push(option);
        else childrenOf.set(parentId, [option]);
    }

    const walk = (nodes: T[], depth: number): (T & { depth: number })[] =>
        nodes.flatMap((node) => [{ ...node, depth }, ...walk(childrenOf.get(node.id) ?? [], depth + 1)]);

    return walk(roots, 0);
}

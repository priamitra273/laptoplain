import type { Menu } from '@/types';

export interface MenuRow extends Menu {
    depth: number;
    previousSequence?: number;
    nextSequence?: number;
}

const bySequence = (a: Menu, b: Menu) => a.sequence_number - b.sequence_number;

const withSiblingTargets = (siblings: Menu[], depth: number): MenuRow[] =>
    siblings.map((menu, index) => ({
        ...menu,
        depth,
        previousSequence: index > 0 ? siblings[index - 1].sequence_number : undefined,
        nextSequence: index < siblings.length - 1 ? siblings[index + 1].sequence_number : undefined,
    }));

export function buildMenuTree(data: Menu[]): MenuRow[] {
    const roots = [...data.filter((menu) => !menu.parent_uuid)].sort(bySequence);

    return withSiblingTargets(roots, 0).flatMap((root) => {
        const children = [...data.filter((menu) => menu.parent_uuid === root.uuid)].sort(bySequence);

        return [root, ...withSiblingTargets(children, 1)];
    });
}


export function filterMenuTree(rows: MenuRow[], query: string): MenuRow[] {
    const keyword = query.trim().toLowerCase();

    if (!keyword) {
        return rows;
    }

    const matches = (menu: MenuRow) => menu.label.toLowerCase().includes(keyword) || (menu.route ?? '').toLowerCase().includes(keyword);

    const visible = new Set<string>();

    for (const row of rows) {
        if (!matches(row)) {
            continue;
        }

        visible.add(row.uuid);

        if (row.parent_uuid) {
            visible.add(row.parent_uuid);
        } else {
            rows.filter((child) => child.parent_uuid === row.uuid).forEach((child) => visible.add(child.uuid));
        }
    }

    return rows.filter((row) => visible.has(row.uuid));
}

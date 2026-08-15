import type { TreeFilterField, TreeSelectNode } from './types';

/** Read a nested field through a dot-path, e.g. `"data.country"`. */
export function readField(node: TreeSelectNode, path: string): unknown {
    return path.split('.').reduce<unknown>((value, key) => (value as Record<string, unknown> | undefined)?.[key], node);
}

/** Flat `key → node` map of the whole tree. */
export function buildNodeMap(nodes?: TreeSelectNode[]): Record<string, TreeSelectNode> {
    const map: Record<string, TreeSelectNode> = {};

    const walk = (list: TreeSelectNode[]) => {
        for (const node of list) {
            map[node.key] = node;
            if (node.children?.length) walk(node.children);
        }
    };

    walk(nodes ?? []);
    return map;
}

/** `key → parent key` map. Root nodes map to `null`. */
export function buildParentMap(nodes?: TreeSelectNode[]): Record<string, string | null> {
    const map: Record<string, string | null> = {};

    const walk = (list: TreeSelectNode[], parent: string | null) => {
        for (const node of list) {
            map[node.key] = parent;
            if (node.children?.length) walk(node.children, node.key);
        }
    };

    walk(nodes ?? [], null);
    return map;
}

/** Ancestor keys of `key`, root first. Used to open the path to a selected node. */
export function pathToKeys(key: string, parentMap: Record<string, string | null>): string[] {
    const path: string[] = [];
    // A malformed tree can point a node at one of its own ancestors; without this the loop hangs.
    const visited = new Set<string>();

    let parent = parentMap[key] ?? null;
    while (parent !== null && !visited.has(parent)) {
        visited.add(parent);
        path.unshift(parent);
        parent = parentMap[parent] ?? null;
    }

    return path;
}

/** Every key that has children — used to open all branches of a filtered tree. */
export function branchKeys(nodes: TreeSelectNode[]): string[] {
    return nodes.flatMap((node) => (node.children?.length ? [node.key, ...branchKeys(node.children)] : []));
}

/** `modelValue` in either shape → a plain list of keys. */
export function toKeyArray(value: string | string[] | undefined): string[] {
    if (value === undefined) return [];
    return Array.isArray(value) ? value : [value];
}

export interface TreeFilterConfig {
    text: string;
    /** Compared fields, from `resolveFilterFields`. */
    fields: TreeFilterField[];
    /** `true` for `filterMode="strict"`. */
    strict?: boolean;
    locale?: string;
}

export function resolveFilterFields(filterBy?: TreeFilterField | TreeFilterField[]): TreeFilterField[] {
    if (filterBy === undefined) return ['label'];
    const fields = Array.isArray(filterBy) ? filterBy : [filterBy];
    return fields.length > 0 ? fields : ['label'];
}

function fieldText(node: TreeSelectNode, field: TreeFilterField, locale?: string): string {
    const raw = typeof field === 'function' ? field(node) : readField(node, field);
    // Empty fields become '' rather than "undefined" — otherwise typing "und" matches everything.
    if (raw === null || raw === undefined) return '';
    return String(raw).toLocaleLowerCase(locale);
}

function matchesSelf(node: TreeSelectNode, config: TreeFilterConfig, text: string): boolean {
    return config.fields.some((field) => fieldText(node, field, config.locale).includes(text));
}

function filterNodes(nodes: TreeSelectNode[], config: TreeFilterConfig, text: string): TreeSelectNode[] {
    const result: TreeSelectNode[] = [];

    for (const node of nodes) {
        const matched = matchesSelf(node, config, text);

        // Lenient: a node matching on its own brings its whole subtree along untouched.
        if (matched && !config.strict) {
            result.push(node);
            continue;
        }

        const children = node.children?.length ? filterNodes(node.children, config, text) : [];

        if (children.length > 0) result.push({ ...node, children });
        // A self-match with no surviving descendant is kept, but reads as a leaf. `children` must
        // be dropped, not emptied: reka derives `hasChildren` from `!!children`, and `[]` is truthy.
        else if (matched) result.push({ ...node, children: undefined });
    }

    return result;
}

/**
 * Filtered tree. The caller's `options` is never mutated — surviving branches are rebuilt
 * as shallow copies.
 *
 * ponytail: the result is a pruned tree, so checkbox propagation while a filter is active
 * only reaches the visible children. Run the propagation against the full `options` if that
 * ever matters.
 */
export function filterTree(nodes: TreeSelectNode[] | undefined, config: TreeFilterConfig): TreeSelectNode[] {
    const text = config.text.trim().toLocaleLowerCase(config.locale);
    if (!text) return nodes ?? [];
    return filterNodes(nodes ?? [], config, text);
}

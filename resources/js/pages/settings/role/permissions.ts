/**
 * Logika matriks permission role.
 *
 * Sumber kebenaran cuma satu: himpunan `"<menu uuid>:<action>"`. State induk
 * tidak disimpan, tapi diturunkan dari anak-anaknya — jadi tidak ada propagasi
 * naik yang perlu dipelihara.
 */

export const PERMISSION_ACTIONS = ['read', 'create', 'update', 'delete'] as const;

export type PermissionAction = (typeof PERMISSION_ACTIONS)[number];

export type CheckState = boolean | 'indeterminate';

export interface MenuNode {
    key: string;
    data: {
        label: string;
        icon: string;
        route?: string | null;
        [key: string]: unknown;
    };
    children?: MenuNode[];
    // TreeTable/UTable menuntut baris berupa Record<string, unknown>.
    [key: string]: unknown;
}

export interface MenuPermission {
    uuid: string;
    label: string;
    route_name?: string | null;
    name: string;
}

const selectionKey = (menuUuid: string, action: PermissionAction): string => `${menuUuid}:${action}`;

const isPermissionAction = (value: string | undefined): value is PermissionAction => PERMISSION_ACTIONS.includes(value as PermissionAction);

export function flattenMenu(nodes: MenuNode[]): MenuNode[] {
    return nodes.flatMap((node) => [node, ...flattenMenu(node.children ?? [])]);
}

function combine(states: CheckState[]): CheckState {
    if (states.length === 0) {
        return false;
    }

    if (states.every((state) => state === true)) {
        return true;
    }

    return states.some((state) => state !== false) ? 'indeterminate' : false;
}

export function nodeState(node: MenuNode, action: PermissionAction, selected: Set<string>): CheckState {
    if (!node.children?.length) {
        return selected.has(selectionKey(node.key, action));
    }

    return combine(node.children.map((child) => nodeState(child, action, selected)));
}

export function columnState(nodes: MenuNode[], action: PermissionAction, selected: Set<string>): CheckState {
    return combine(nodes.map((node) => nodeState(node, action, selected)));
}

export function rowState(node: MenuNode, selected: Set<string>): CheckState {
    return combine(PERMISSION_ACTIONS.map((action) => nodeState(node, action, selected)));
}

export function matrixState(nodes: MenuNode[], selected: Set<string>): CheckState {
    return combine(PERMISSION_ACTIONS.map((action) => columnState(nodes, action, selected)));
}

function apply(nodes: MenuNode[], action: PermissionAction, on: boolean, selected: Set<string>): void {
    for (const node of flattenMenu(nodes)) {
        if (on) {
            selected.add(selectionKey(node.key, action));
        } else {
            selected.delete(selectionKey(node.key, action));
        }
    }
}

/**
 * Read adalah syarat aksi lain: mematikan read ikut mematikan create/update/delete,
 * menyalakan salah satu aksi lain ikut menyalakan read.
 */
export function toggleAction(nodes: MenuNode[], action: PermissionAction, on: boolean, selected: Set<string>): void {
    apply(nodes, action, on, selected);

    if (action === 'read' && !on) {
        for (const other of PERMISSION_ACTIONS) {
            if (other !== 'read') {
                apply(nodes, other, false, selected);
            }
        }
    }

    if (action !== 'read' && on) {
        apply(nodes, 'read', true, selected);
    }
}

export function toggleAll(nodes: MenuNode[], on: boolean, selected: Set<string>): void {
    for (const action of PERMISSION_ACTIONS) {
        apply(nodes, action, on, selected);
    }
}

function permissionIndex(menuPermissions: MenuPermission[]): Map<string, string> {
    const index = new Map<string, string>();

    for (const permission of menuPermissions) {
        const action = permission.name.split('.').pop();

        if (isPermissionAction(action)) {
            index.set(selectionKey(permission.uuid, action), permission.name);
        }
    }

    return index;
}

/**
 * Menu induk ikut terkirim saat sebagian anaknya tercentang: tanpa permission
 * induk, seluruh cabang hilang dari sidebar.
 */
export function toPermissionNames(nodes: MenuNode[], selected: Set<string>, menuPermissions: MenuPermission[]): string[] {
    const index = permissionIndex(menuPermissions);

    return flattenMenu(nodes).flatMap((node) =>
        PERMISSION_ACTIONS.filter((action) => nodeState(node, action, selected) !== false)
            .map((action) => index.get(selectionKey(node.key, action)))
            .filter((name): name is string => Boolean(name)),
    );
}

export function fromPermissionNames(names: string[], menuPermissions: MenuPermission[]): Set<string> {
    const menuByPermission = new Map(menuPermissions.map((permission) => [permission.name, permission.uuid]));
    const selected = new Set<string>();

    for (const name of names) {
        const uuid = menuByPermission.get(name);
        const action = name.split('.').pop();

        if (uuid && isPermissionAction(action)) {
            selected.add(selectionKey(uuid, action));
        }
    }

    return selected;
}

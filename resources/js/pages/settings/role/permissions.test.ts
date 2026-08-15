import { describe, expect, it } from 'vitest';
import {
    columnState,
    fromPermissionNames,
    matrixState,
    nodeState,
    rowState,
    toPermissionNames,
    toggleAction,
    toggleAll,
    type MenuNode,
    type MenuPermission,
} from './permissions';

const team: MenuNode = { key: 'uuid-team', data: { label: 'Team', icon: 'Network' } };
const role: MenuNode = { key: 'uuid-role', data: { label: 'Role', icon: 'Shapes' } };
const settings: MenuNode = { key: 'uuid-settings', data: { label: 'Settings', icon: 'Settings' }, children: [team, role] };

const menu: MenuNode[] = [settings];

const menuPermissions: MenuPermission[] = [
    ['uuid-settings', 'setting'],
    ['uuid-team', 'team'],
    ['uuid-role', 'role'],
].flatMap(([uuid, prefix]) =>
    ['read', 'create', 'update', 'delete'].map((action) => ({ uuid, label: prefix, name: `${prefix}.${action}` })),
);

describe('permission matrix', () => {
    it('marks the parent indeterminate when only one child is checked', () => {
        const selected = new Set<string>();

        toggleAction([team], 'read', true, selected);

        expect(nodeState(team, 'read', selected)).toBe(true);
        expect(nodeState(role, 'read', selected)).toBe(false);
        expect(nodeState(settings, 'read', selected)).toBe('indeterminate');
        expect(columnState(menu, 'read', selected)).toBe('indeterminate');
    });

    it('turns read on together with any other action', () => {
        const selected = new Set<string>();

        toggleAction([team], 'update', true, selected);

        expect(nodeState(team, 'read', selected)).toBe(true);
    });

    it('turns every other action off when read goes off', () => {
        const selected = new Set<string>();

        toggleAll([team], true, selected);
        toggleAction([team], 'read', false, selected);

        expect(rowState(team, selected)).toBe(false);
    });

    it('cascades a parent toggle down to its children', () => {
        const selected = new Set<string>();

        toggleAction(menu, 'read', true, selected);

        expect(nodeState(team, 'read', selected)).toBe(true);
        expect(nodeState(role, 'read', selected)).toBe(true);
        expect(matrixState(menu, selected)).toBe('indeterminate');

        toggleAll(menu, true, selected);

        expect(matrixState(menu, selected)).toBe(true);
    });

    it('submits the parent permission whenever a child is checked', () => {
        const selected = new Set<string>();

        toggleAction([team], 'read', true, selected);

        expect(toPermissionNames(menu, selected, menuPermissions).sort()).toEqual(['setting.read', 'team.read']);
    });

    it('restores the checked state from saved permission names', () => {
        const selected = fromPermissionNames(['setting.read', 'setting.create', 'team.read', 'team.create'], menuPermissions);

        expect(nodeState(team, 'create', selected)).toBe(true);
        expect(nodeState(role, 'read', selected)).toBe(false);
        expect(toPermissionNames(menu, selected, menuPermissions).sort()).toEqual([
            'setting.create',
            'setting.read',
            'team.create',
            'team.read',
        ]);
    });

    it('drops permission names that no longer belong to a menu', () => {
        const selected = fromPermissionNames(['ghost.read'], menuPermissions);

        expect(toPermissionNames(menu, selected, menuPermissions)).toEqual([]);
    });
});

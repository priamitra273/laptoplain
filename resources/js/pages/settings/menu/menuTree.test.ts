import type { Menu } from '@/types';
import { describe, expect, it } from 'vitest';
import { buildMenuTree, filterMenuTree } from './menuTree';

const makeMenu = (overrides: Partial<Menu> & Pick<Menu, 'uuid' | 'label' | 'sequence_number'>): Menu => ({
    icon: 'Circle',
    is_active: true,
    created_at: '2026-01-01T00:00:00Z',
    updated_at: '2026-01-01T00:00:00Z',
    ...overrides,
});

const settings = makeMenu({ uuid: 'settings', label: 'Settings', sequence_number: 2 });
const dashboard = makeMenu({ uuid: 'dashboard', label: 'Dashboard', sequence_number: 1, route: 'dashboard' });
const team = makeMenu({ uuid: 'team', label: 'Team', sequence_number: 1, parent_uuid: 'settings', route: 'team.index' });
const role = makeMenu({ uuid: 'role', label: 'Role', sequence_number: 5, parent_uuid: 'settings', route: 'role.index' });
const user = makeMenu({ uuid: 'user', label: 'User', sequence_number: 9, parent_uuid: 'settings', route: 'user.index' });

const menu: Menu[] = [role, settings, user, dashboard, team];

const labelsOf = (rows: { label: string }[]) => rows.map((row) => row.label);

describe('buildMenuTree', () => {
    it('orders roots by sequence and nests children directly beneath their parent', () => {
        expect(labelsOf(buildMenuTree(menu))).toEqual(['Dashboard', 'Settings', 'Team', 'Role', 'User']);
    });

    it('marks depth so children can be indented', () => {
        const byLabel = Object.fromEntries(buildMenuTree(menu).map((row) => [row.label, row.depth]));

        expect(byLabel).toEqual({ Dashboard: 0, Settings: 0, Team: 1, Role: 1, User: 1 });
    });

    it('targets the neighbouring sibling sequence rather than plus or minus one', () => {
        const rows = buildMenuTree(menu);
        const roleRow = rows.find((row) => row.uuid === 'role')!;

        expect(roleRow.previousSequence).toBe(team.sequence_number);
        expect(roleRow.nextSequence).toBe(user.sequence_number);
    });

    it('leaves the first and last sibling without a move target', () => {
        const rows = buildMenuTree(menu);

        expect(rows.find((row) => row.uuid === 'team')!.previousSequence).toBeUndefined();
        expect(rows.find((row) => row.uuid === 'user')!.nextSequence).toBeUndefined();
    });

    it('scopes move targets per parent so the last child never points at a root', () => {
        const rows = buildMenuTree(menu);

        expect(rows.find((row) => row.uuid === 'dashboard')!.nextSequence).toBe(settings.sequence_number);
        expect(rows.find((row) => row.uuid === 'settings')!.nextSequence).toBeUndefined();
    });

    it('drops children whose parent is missing instead of rendering them as roots', () => {
        expect(labelsOf(buildMenuTree([dashboard, team]))).toEqual(['Dashboard']);
    });

    it('returns nothing for an empty menu', () => {
        expect(buildMenuTree([])).toEqual([]);
    });
});

describe('filterMenuTree', () => {
    const rows = buildMenuTree(menu);

    it('returns every row when the query is blank', () => {
        expect(filterMenuTree(rows, '   ')).toHaveLength(rows.length);
    });

    it('keeps the parent as context when only a child matches', () => {
        expect(labelsOf(filterMenuTree(rows, 'role'))).toEqual(['Settings', 'Role']);
    });

    it('keeps every child when the parent matches', () => {
        expect(labelsOf(filterMenuTree(rows, 'settings'))).toEqual(['Settings', 'Team', 'Role', 'User']);
    });

    it('matches on route name as well as label', () => {
        expect(labelsOf(filterMenuTree(rows, 'user.index'))).toEqual(['Settings', 'User']);
    });

    it('ignores case and surrounding whitespace', () => {
        expect(labelsOf(filterMenuTree(rows, '  TEAM '))).toEqual(['Settings', 'Team']);
    });

    it('returns nothing when no row matches', () => {
        expect(filterMenuTree(rows, 'invoice')).toEqual([]);
    });
});

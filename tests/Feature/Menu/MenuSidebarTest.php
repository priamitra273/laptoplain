<?php

use App\Models\Menu;
use App\Models\Role;
use App\Models\User;
use App\Services\MenuService;
use Spatie\Permission\Models\Permission;

function createMenu(array $attributes): Menu
{
    return Menu::create(array_merge([
        'parent_id' => null,
        'icon' => 'icon',
        'route_name' => null,
        'is_active' => true,
    ], $attributes));
}

beforeEach(function () {
    $this->user = User::factory()->create();

    $this->role = Role::create([
        'name' => 'tester',
        'guard_name' => 'web',
        'label' => 'Tester',
        'team_id' => 1,
        'is_active' => true,
    ]);

    $this->user->assignRole($this->role);

    $this->service = app(MenuService::class);
});

it('builds the nested sidebar tree of menus the user can access', function () {
    $allowedParent = Permission::findOrCreate('dashboard.read', 'web');
    $allowedChild = Permission::findOrCreate('reports.read', 'web');
    $denied = Permission::findOrCreate('admin.read', 'web');

    $this->role->givePermissionTo($allowedParent, $allowedChild);

    $parent = createMenu(['label' => 'Dashboard', 'icon' => 'home', 'route_name' => 'dashboard.index', 'sequence_number' => 1]);
    $parent->givePermissionTo($allowedParent);

    $child = createMenu(['parent_id' => $parent->id, 'label' => 'Reports', 'icon' => 'chart', 'route_name' => 'reports.index', 'sequence_number' => 1]);
    $child->givePermissionTo($allowedChild);

    $hiddenChild = createMenu(['parent_id' => $parent->id, 'label' => 'Secret', 'route_name' => 'secret.index', 'sequence_number' => 2]);
    $hiddenChild->givePermissionTo($denied);

    $hiddenParent = createMenu(['label' => 'Admin', 'route_name' => 'admin.index', 'sequence_number' => 2]);
    $hiddenParent->givePermissionTo($denied);

    $tree = $this->service->getSidebarMenu($this->user->id);

    expect($tree)->toEqual([
        [
            'label' => 'Dashboard',
            'icon' => 'home',
            'to' => 'dashboard.index',
            'items' => [
                [
                    'label' => 'Reports',
                    'icon' => 'chart',
                    'to' => 'reports.index',
                    'items' => null,
                ],
            ],
        ],
    ]);
});

it('orders top-level menus by sequence_number', function () {
    $permission = Permission::findOrCreate('any.read', 'web');
    $this->role->givePermissionTo($permission);

    $second = createMenu(['label' => 'Second', 'route_name' => 'second.index', 'sequence_number' => 2]);
    $second->givePermissionTo($permission);

    $first = createMenu(['label' => 'First', 'route_name' => 'first.index', 'sequence_number' => 1]);
    $first->givePermissionTo($permission);

    $tree = $this->service->getSidebarMenu($this->user->id);

    expect(array_column($tree, 'label'))->toBe(['First', 'Second']);
});

it('excludes soft-deleted menus', function () {
    $permission = Permission::findOrCreate('any.read', 'web');
    $this->role->givePermissionTo($permission);

    $visible = createMenu(['label' => 'Visible', 'route_name' => 'visible.index', 'sequence_number' => 1]);
    $visible->givePermissionTo($permission);

    $trashed = createMenu(['label' => 'Trashed', 'route_name' => 'trashed.index', 'sequence_number' => 2]);
    $trashed->givePermissionTo($permission);
    $trashed->delete();

    $tree = $this->service->getSidebarMenu($this->user->id);

    expect(array_column($tree, 'label'))->toBe(['Visible']);
});

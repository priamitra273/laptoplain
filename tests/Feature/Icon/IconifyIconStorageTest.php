<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->user = User::factory()->create(['id' => 1]);

    Role::create([
        'name' => 'super-admin-test',
        'guard_name' => 'web',
        'label' => 'Super Admin Test',
        'team_id' => 1,
        'is_active' => true,
    ]);

    $this->user->assignRole('super-admin-test');
});

function iconifyStorageMigration(): Migration
{
    $migrationFiles = glob(database_path('migrations/*_convert_icons_to_iconify_names.php'));

    return require $migrationFiles[0];
}

it('converts legacy menu and task category icons and keeps custom values', function () {
    $categories = [
        ['name' => 'Prime Bolt', 'icon' => 'pi pi-bolt'],
        ['name' => 'Circle Alert', 'icon' => 'CircleAlert'],
        ['name' => 'Building', 'icon' => 'Building2'],
        ['name' => 'Grid', 'icon' => 'Grid2x2'],
        ['name' => 'Iconify', 'icon' => 'i-lucide-zap'],
        ['name' => 'Custom', 'icon' => 'custom icon'],
        ['name' => 'Soft Deleted', 'icon' => 'Grid2x2'],
    ];

    foreach ($categories as $category) {
        DB::table('task_categories')->insert([
            ...$category,
            'severity' => 'info',
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => $category['name'] === 'Soft Deleted' ? now() : null,
        ]);
    }

    DB::table('menus')->insert([
        'uuid' => (string) Str::uuid(),
        'label' => 'Master Data',
        'icon' => 'FolderCog',
        'sequence_number' => 1,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    iconifyStorageMigration()->up();

    expect(DB::table('task_categories')->where('name', 'Prime Bolt')->value('icon'))->toBe('i-lucide-zap')
        ->and(DB::table('task_categories')->where('name', 'Circle Alert')->value('icon'))->toBe('i-lucide-circle-alert')
        ->and(DB::table('task_categories')->where('name', 'Building')->value('icon'))->toBe('i-lucide-building-2')
        ->and(DB::table('task_categories')->where('name', 'Grid')->value('icon'))->toBe('i-lucide-grid-2x2')
        ->and(DB::table('task_categories')->where('name', 'Iconify')->value('icon'))->toBe('i-lucide-zap')
        ->and(DB::table('task_categories')->where('name', 'Custom')->value('icon'))->toBe('custom icon')
        ->and(DB::table('task_categories')->where('name', 'Soft Deleted')->value('icon'))->toBe('i-lucide-grid-2x2')
        ->and(DB::table('menus')->where('label', 'Master Data')->value('icon'))->toBe('i-lucide-folder-cog');
});

it('rolls Iconify icon names back to PascalCase', function () {
    DB::table('task_categories')->insert([
        ['name' => 'Bookmark', 'icon' => 'i-lucide-bookmark', 'severity' => 'info', 'created_at' => now(), 'updated_at' => now()],
        ['name' => 'Folder Cog', 'icon' => 'i-lucide-folder-cog', 'severity' => 'info', 'created_at' => now(), 'updated_at' => now()],
    ]);

    iconifyStorageMigration()->down();

    expect(DB::table('task_categories')->where('name', 'Bookmark')->value('icon'))->toBe('Bookmark')
        ->and(DB::table('task_categories')->where('name', 'Folder Cog')->value('icon'))->toBe('FolderCog');
});

it('validates Iconify names when storing menu and task category icons', function () {
    $this->actingAs($this->user)
        ->post(route('task-category.store'), ['name' => 'Valid category', 'icon' => 'i-lucide-zap', 'severity' => 'contrast'])
        ->assertSessionHasNoErrors();

    foreach (['Zap', 'pi pi-bolt', 'i-heroicons-bolt'] as $icon) {
        $this->actingAs($this->user)
            ->post(route('task-category.store'), ['name' => "Invalid $icon", 'icon' => $icon, 'severity' => 'contrast'])
            ->assertSessionHasErrors('icon');
    }

    $this->actingAs($this->user)
        ->post(route('menu.store'), [
            'label' => 'Invalid menu icon',
            'icon' => 'FolderCog',
            'sequence_number' => 1,
            'is_active' => true,
        ])
        ->assertSessionHasErrors('icon');
});

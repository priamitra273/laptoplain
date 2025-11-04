<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Services\MenuService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\ValidatedInput;

class MenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $service = new MenuService();

        // Favorites Menu
        $service->store(new ValidatedInput([
            'label' => 'Favorites',
            'icon' => 'Star',
            'sequence_number' => 1,
            'is_active' => true
        ]));

        // Master Data
        $service->store(new ValidatedInput([
            'label' => 'Master Data',
            'icon' => 'FolderCog',
            'sequence_number' => 2,
            'is_active' => true
        ]));

        // Settings
        $service->store(new ValidatedInput([
            'label' => 'Settings',
            'icon' => 'Settings',
            'sequence_number' => 3,
            'is_active' => true
        ]));

        $favorites = Menu::find(1);
        $master_data = Menu::find(2);
        $settings = Menu::find(3);

        // Dashboard
        $service->store(new ValidatedInput([
            'label' => 'Dashboard',
            'parent_uuid' => $favorites->uuid,
            'icon' => 'LayoutDashboard',
            'route_name' => 'dashboard',
            'sequence_number' => 1,
            'is_active' => true
        ]));

        // user
        $service->store(new ValidatedInput([
            'label' => 'User',
            'parent_uuid' => $settings->uuid,
            'icon' => 'UserCog',
            'route_name' => 'user.index',
            'sequence_number' => 1,
            'is_active' => true
        ]));

        // menu
        $service->store(new ValidatedInput([
            'label' => 'Menu',
            'parent_uuid' => $settings->uuid,
            'icon' => 'Compass',
            'route_name' => 'menu.index',
            'sequence_number' => 2,
            'is_active' => true
        ]));

        // team
        $service->store(new ValidatedInput([
            'label' => 'Team',
            'parent_uuid' => $settings->uuid,
            'icon' => 'Network',
            'route_name' => 'team.index',
            'sequence_number' => 3,
            'is_active' => true
        ]));

        // role
        $service->store(new ValidatedInput([
            'label' => 'Role',
            'parent_uuid' => $settings->uuid,
            'icon' => 'Shapes',
            'route_name' => 'role.index',
            'sequence_number' => 4,
            'is_active' => true
        ]));

        // project role
        $service->store(new ValidatedInput([
            'label' => 'Project Role',
            'parent_uuid' => $master_data->uuid,
            'icon' => 'BookCheck',
            'route_name' => 'ms_project_role.index',
            'sequence_number' => 1,
            'is_active' => true
        ]));

        // project status
        $service->store(new ValidatedInput([
            'label' => 'Project Status',
            'parent_uuid' => $master_data->uuid,
            'icon' => 'BookCheck',
            'route_name' => 'ms_project_status.index',
            'sequence_number' => 2,
            'is_active' => true
        ]));

        // project priority
        $service->store(new ValidatedInput([
            'label' => 'Project Priority',
            'parent_uuid' => $master_data->uuid,
            'icon' => 'BookCheck',
            'route_name' => 'ms_project_priority.index',
            'sequence_number' => 3,
            'is_active' => true
        ]));

        // task status
        $service->store(new ValidatedInput([
            'label' => 'Task Status',
            'parent_uuid' => $master_data->uuid,
            'icon' => 'BookCheck',
            'route_name' => 'ms_task_status.index',
            'sequence_number' => 4,
            'is_active' => true
        ]));

        // task priority
        $service->store(new ValidatedInput([
            'label' => 'Task Priority',
            'parent_uuid' => $master_data->uuid,
            'icon' => 'BookCheck',
            'route_name' => 'ms_task_priority.index',
            'sequence_number' => 5,
            'is_active' => true
        ]));

        // task type
        $service->store(new ValidatedInput([
            'label' => 'Task Type',
            'parent_uuid' => $master_data->uuid,
            'icon' => 'BookCheck',
            'route_name' => 'ms_task_type.index',
            'sequence_number' => 6,
            'is_active' => true
        ]));
    }
}

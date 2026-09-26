<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Services\MenuService;
use Illuminate\Database\Seeder;
use Illuminate\Support\ValidatedInput;

class MenuSeeder extends Seeder
{
    public function __construct(protected MenuService $service) {}

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Favorites Menu
        $this->service->store(new ValidatedInput([
            'label' => 'Favorites',
            'icon' => 'i-lucide-star',
            'sequence_number' => 1,
            'is_active' => true,
        ]));

        // Master Data
        $this->service->store(new ValidatedInput([
            'label' => 'Master Data',
            'icon' => 'i-lucide-folder-cog',
            'sequence_number' => 2,
            'is_active' => true,
        ]));

        // Settings
        $this->service->store(new ValidatedInput([
            'label' => 'Settings',
            'icon' => 'i-lucide-settings',
            'sequence_number' => 3,
            'is_active' => true,
        ]));

        $favorites = Menu::find(1);
        $master_data = Menu::find(2);
        $settings = Menu::find(3);

        // Dashboard
        $this->service->store(new ValidatedInput([
            'label' => 'Dashboard',
            'parent_uuid' => $favorites->uuid,
            'icon' => 'i-lucide-layout-dashboard',
            'route_name' => 'dashboard',
            'sequence_number' => 1,
            'is_active' => true,
        ]));

        // Project
        $this->service->store(new ValidatedInput([
            'label' => 'Project',
            'parent_uuid' => $favorites->uuid,
            'icon' => 'i-lucide-layers',
            'route_name' => 'project.index',
            'sequence_number' => 2,
            'is_active' => true,
        ]));

        // My Task
        $this->service->store(new ValidatedInput([
            'label' => 'My Task',
            'parent_uuid' => $favorites->uuid,
            'icon' => 'i-lucide-scroll-text',
            'route_name' => 'task.index',
            'sequence_number' => 3,
            'is_active' => true,
        ]));

        // Task Report
        $this->service->store(new ValidatedInput([
            'label' => 'Task Report',
            'parent_uuid' => $favorites->uuid,
            'icon' => 'i-lucide-inbox',
            'route_name' => 'reports.tasks.index',
            'sequence_number' => 4,
            'is_active' => true,
        ]));

        // Workload
        $this->service->store(new ValidatedInput([
            'label' => 'Workload',
            'parent_uuid' => $favorites->uuid,
            'icon' => 'i-lucide-activity',
            'route_name' => 'workload-users.index',
            'sequence_number' => 5,
            'is_active' => true,
        ]));

        // user
        $this->service->store(new ValidatedInput([
            'label' => 'User',
            'parent_uuid' => $settings->uuid,
            'icon' => 'i-lucide-user-cog',
            'route_name' => 'user.index',
            'sequence_number' => 1,
            'is_active' => true,
        ]));

        // menu
        $this->service->store(new ValidatedInput([
            'label' => 'Menu',
            'parent_uuid' => $settings->uuid,
            'icon' => 'i-lucide-compass',
            'route_name' => 'menu.index',
            'sequence_number' => 2,
            'is_active' => true,
        ]));

        // team
        $this->service->store(new ValidatedInput([
            'label' => 'Team',
            'parent_uuid' => $settings->uuid,
            'icon' => 'i-lucide-network',
            'route_name' => 'team.index',
            'sequence_number' => 3,
            'is_active' => true,
        ]));

        // role
        $this->service->store(new ValidatedInput([
            'label' => 'Role',
            'parent_uuid' => $settings->uuid,
            'icon' => 'i-lucide-shapes',
            'route_name' => 'role.index',
            'sequence_number' => 4,
            'is_active' => true,
        ]));

        // project role
        $this->service->store(new ValidatedInput([
            'label' => 'Project Role',
            'parent_uuid' => $master_data->uuid,
            'icon' => 'i-lucide-book-check',
            'route_name' => 'project-role.index',
            'sequence_number' => 1,
            'is_active' => true,
        ]));

        // project status
        $this->service->store(new ValidatedInput([
            'label' => 'Project Status',
            'parent_uuid' => $master_data->uuid,
            'icon' => 'i-lucide-book-check',
            'route_name' => 'project-status.index',
            'sequence_number' => 2,
            'is_active' => true,
        ]));

        // project priority
        $this->service->store(new ValidatedInput([
            'label' => 'Project Priority',
            'parent_uuid' => $master_data->uuid,
            'icon' => 'i-lucide-book-check',
            'route_name' => 'project-priority.index',
            'sequence_number' => 3,
            'is_active' => true,
        ]));

        // task status
        $this->service->store(new ValidatedInput([
            'label' => 'Task Status',
            'parent_uuid' => $master_data->uuid,
            'icon' => 'i-lucide-book-check',
            'route_name' => 'task-status.index',
            'sequence_number' => 4,
            'is_active' => true,
        ]));

        // task priority
        $this->service->store(new ValidatedInput([
            'label' => 'Task Priority',
            'parent_uuid' => $master_data->uuid,
            'icon' => 'i-lucide-book-check',
            'route_name' => 'task-priority.index',
            'sequence_number' => 5,
            'is_active' => true,
        ]));

        // task type
        $this->service->store(new ValidatedInput([
            'label' => 'Task Type',
            'parent_uuid' => $master_data->uuid,
            'icon' => 'i-lucide-book-check',
            'route_name' => 'task-type.index',
            'sequence_number' => 6,
            'is_active' => true,
        ]));
    }
}

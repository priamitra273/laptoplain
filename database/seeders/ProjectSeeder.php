<?php

namespace Database\Seeders;

use App\Models\MsProjectRole;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();
        $ownerRole = MsProjectRole::where('name', 'Owner')->first();
        $memberRole = MsProjectRole::where('name', 'Member')->first();

        ProjectFactory::new()
            ->count(5)
            ->create()
            ->each(function (Project $project) use ($users, $ownerRole, $memberRole) {
                ProjectMember::create([
                    'project_id' => $project->id,
                    'user_id' => $project->owner_id,
                    'project_role_id' => $ownerRole?->id,
                    'owned_id' => $project->owner_id,
                ]);

                foreach ($users as $user) {
                    if ($user->id === $project->owner_id) {
                        continue;
                    }

                    ProjectMember::create([
                        'project_id' => $project->id,
                        'user_id' => $user->id,
                        'project_role_id' => $memberRole?->id,
                        'owned_id' => $project->owner_id,
                    ]);
                }
            });
    }
}

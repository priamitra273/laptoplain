<?php

namespace Database\Factories;

use App\Models\MsTaskPriority;
use App\Models\MsTaskStatus;
use App\Models\MsTaskType;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Task>
 */
class TaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * Mirrors how App\Actions\Task\CreateTaskAction builds a task: project, creator,
     * a status-derived initial progress, and an incrementing sequence number. Master
     * data (status/priority/type/category) and the project are seeder-driven in this
     * application, so foreign keys default to existing rows and stay overridable.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $status = MsTaskStatus::query()->inRandomOrder()->first();

        $startDate = fake()->dateTimeBetween('-1 week', '+1 week');

        return [
            'project_id' => Project::query()->value('id'),
            'parent_id' => null,
            'status_id' => $status?->id,
            'priority_id' => MsTaskPriority::query()->inRandomOrder()->value('id'),
            'type_id' => MsTaskType::query()->inRandomOrder()->value('id'),
            'task_category_id' => TaskCategory::query()->inRandomOrder()->value('id'),
            'created_by' => User::query()->value('id') ?? User::factory(),
            'updated_by' => null,
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'start_date' => $startDate->format('Y-m-d'),
            'due_date' => fake()->dateTimeBetween($startDate, '+1 month')->format('Y-m-d'),
            'progress' => $this->initialProgress($status),
            'story_points' => fake()->randomElement([1, 2, 3, 5, 8, 13]),
            'sequence_number' => fake()->unique()->numberBetween(1, 100_000),
            'is_archived' => false,
        ];
    }

    /**
     * Resolve the initial progress from the status score, like CreateTaskAction::calculateInitialProgress().
     */
    private function initialProgress(?MsTaskStatus $status): float
    {
        return $status ? (float) $status->score : 0;
    }

    /**
     * Attach the task to a specific project.
     */
    public function forProject(Project $project): static
    {
        return $this->state(fn (array $attributes): array => [
            'project_id' => $project->id,
        ]);
    }

    /**
     * Nest the task under a parent, inheriting the parent's project.
     */
    public function childOf(Task $parent): static
    {
        return $this->state(fn (array $attributes): array => [
            'parent_id' => $parent->id,
            'project_id' => $parent->project_id,
        ]);
    }

    /**
     * Mark the task as completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'completed_at' => now(),
            'progress' => 100,
        ]);
    }

    /**
     * Mark the task as archived.
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_archived' => true,
        ]);
    }

    /**
     * Assign users to the task after creation (mirrors CreateTaskAction::syncRelationships()).
     *
     * @param  \App\Models\User|array<int, \App\Models\User|int>  $users
     */
    public function assignUsers(User|array $users): static
    {
        $userIds = collect(is_array($users) ? $users : [$users])
            ->map(fn (User|int $user): int => $user instanceof User ? $user->id : $user)
            ->all();

        return $this->afterCreating(function (Task $task) use ($userIds): void {
            if (! empty($userIds)) {
                $task->users()->syncWithoutDetaching($userIds);
            }
        });
    }
}

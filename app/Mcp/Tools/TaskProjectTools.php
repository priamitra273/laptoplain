<?php

namespace App\Mcp\Tools;

use App\Facades\Sqids;
use App\Models\Project;
use App\Repositories\ProjectRepository;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('get-project-tasks')]
#[Title('Get Project Tasks')]
#[Description('Fetch the recursive task tree for a given project.')]
#[IsReadOnly]
#[IsIdempotent]
class TaskProjectTools extends Tool
{
    public function __construct(
        protected ProjectRepository $repository,
    ) {}

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'project' => ['required', 'string'],
        ], [
            'project.required' => 'You must provide a project id. Use the encoded `id` returned by the get-user-projects tool.',
        ]);

        try {
            $projectId = Sqids::decode($validated['project']);
        } catch (\Throwable $e) {
            return Response::error('Invalid project id. Pass the encoded id returned by get-user-projects.');
        }

        // Project::visibleFor scopes to the user's projects (members, plus super-admin/watcher
        // roles), so a non-member resolves to null and is reported as "not found".
        $project = Project::visibleFor($request->user())->find($projectId);

        if ($project === null) {
            return Response::error('Project not found.');
        }

        $tasks = $this->repository->getTaskListTree($project->id)->toArray();

        return Response::json([
            'data' => Sqids::rec_encode_ids_in_list($tasks),
        ]);
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'project' => $schema->string()
                ->description('The encoded project id (the `id` field returned by the get-user-projects tool).')
                ->required(),
        ];
    }

    /**
     * Get the tool's output schema.
     *
     * @return array<string, Type>
     */
    public function outputSchema(JsonSchema $schema): array
    {
        return [
            'data' => $schema->array()
                ->items($this->taskShape($schema))
                ->description('Recursive task tree of the project (root tasks first; children nested under sub_task_recursive).')
                ->required(),
        ];
    }

    /**
     * One node of the recursive task tree, mirroring App\Data\Project\Lazy\TaskListItemData.
     *
     * `sub_task_recursive` carries child nodes of this same shape. It is described as a
     * generic array because the JsonSchema builder cannot express a self-referencing schema.
     */
    private function taskShape(JsonSchema $schema): Type
    {
        return $schema->object([
            'id' => $schema->string(),
            'parent_id' => $schema->string()->description('Encoded id of the parent task, or null for roots.'),
            'sequence_number' => $schema->integer(),
            'title' => $schema->string(),
            'progress' => $schema->number(),
            'start_date' => $schema->string(),
            'due_date' => $schema->string(),
            'completed_at' => $schema->string(),
            'is_overdue' => $schema->boolean(),
            'created_at' => $schema->string(),
            'updated_at' => $schema->string(),
            'status' => $schema->object([
                'id' => $schema->string(),
                'name' => $schema->string(),
                'severity' => $schema->string(),
                'score' => $schema->number(),
            ]),
            'type' => $schema->object([
                'id' => $schema->string(),
                'name' => $schema->string(),
                'severity' => $schema->string(),
            ]),
            'category' => $schema->object([
                'id' => $schema->string(),
                'name' => $schema->string(),
                'icon' => $schema->string(),
                'severity' => $schema->string(),
            ]),
            'users' => $schema->array()
                ->items($schema->object([
                    'id' => $schema->string(),
                    'name' => $schema->string(),
                    'email' => $schema->string(),
                    'avatar_url' => $schema->string(),
                ])),
            'sub_task_recursive' => $schema->array()
                ->description('Nested child tasks, each with the same shape as this object.'),
        ]);
    }
}

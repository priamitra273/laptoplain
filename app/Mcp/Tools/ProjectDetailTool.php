<?php

namespace App\Mcp\Tools;

use App\Facades\Sqids;
use App\Models\Project;
use App\Repositories\ProjectRepository;
use App\Services\ProjectLazyService;
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

#[Name('get-project-detail')]
#[Title('Get Project Detail')]
#[Description('Fetch a project detail shell: header, members, membership flag, and the current user\'s permission policy. Statuses and priorities are excluded (use get-task-options for option lists).')]
#[IsReadOnly]
#[IsIdempotent]
class ProjectDetailTool extends Tool
{
    public function __construct(
        protected ProjectLazyService $service,
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
        if (! Project::visibleFor($request->user())->whereKey($projectId)->exists()) {
            return Response::error('Project not found.');
        }

        // Reuse the app's shell pipeline (cached, fully eager-loaded) exactly like
        // ProjectTabController::renderTab, then drop the master option lists per this tool's
        // contract (statuses/priorities are served by get-task-options instead).
        $project = $this->repository->findShell($validated['project']);

        $data = $this->service->shellData($project);

        unset($data['statuses'], $data['priorities']);

        return Response::json([
            'data' => Sqids::rec_encode_ids_in_list($data),
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
            'data' => $schema->object([
                'project' => $schema->object([
                    'id' => $schema->string(),
                    'project_no' => $schema->string(),
                    'title' => $schema->string(),
                    'emoji' => $schema->string(),
                    'progress' => $schema->number(),
                    'start_date' => $schema->string(),
                    'due_date' => $schema->string(),
                    'status_id' => $schema->string(),
                    'priority_id' => $schema->string(),
                    'status' => $schema->object([
                        'id' => $schema->string(),
                        'name' => $schema->string(),
                        'severity' => $schema->string(),
                    ]),
                    'priority' => $schema->object([
                        'id' => $schema->string(),
                        'name' => $schema->string(),
                        'severity' => $schema->string(),
                    ]),
                ])->description('Project header/shell.'),
                'members' => $schema->array()
                    ->items($schema->object([
                        'id' => $schema->string(),
                        'user' => $schema->object([
                            'id' => $schema->string(),
                            'name' => $schema->string(),
                            'email' => $schema->string(),
                            'avatar_url' => $schema->string(),
                        ]),
                    ]))
                    ->description('Active project members.'),
                'isMember' => $schema->boolean()
                    ->description('Whether the current user is a member of the project.'),
                'policy' => $schema->object([
                    'task' => $schema->array(),
                    'sprint' => $schema->array(),
                    'project_member' => $schema->array(),
                    'allow_task_status' => $schema->array()->description('Empty array means all statuses are allowed.'),
                    'allow_update_task_fields' => $schema->array()->description('Empty array means all fields are allowed.'),
                ])->description('The current user\'s permission policy for this project (null if none).'),
            ])->description('Project detail shell, excluding the statuses/priorities option lists.')
                ->required(),
        ];
    }
}

<?php

namespace App\Mcp\Tools;

use App\Facades\Sqids;
use App\Http\Requests\Project\ProjectUpdateRequest;
use App\Models\Project;
use App\Services\ProjectService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[Name('update-project')]
#[Title('Update Project')]
#[Description('Update an existing project. Provide the encoded project id plus only the fields to change. Use get-project-options for valid encoded status_id/priority_id.')]
#[IsDestructive]
class UpdateProjectTool extends Tool
{
    /**
     * Fields that may be updated through this tool.
     *
     * @var array<int, string>
     */
    private const FIELDS = ['title', 'description', 'emoji', 'start_date', 'due_date', 'status_id', 'priority_id'];

    public function __construct(
        protected ProjectService $service,
    ) {}

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $this->authorize($request);

        $project = $this->resolveProject($request);

        $validated = $this->validate($request, $project);

        $project = $this->service->updateProject($project, $validated);

        return $this->present($project);
    }

    /**
     * Authorize the request, mirroring CheckRoutePermission for the project.update route:
     * a `super-admin-*` role bypasses, otherwise the `project.update` permission is required.
     *
     * @throws AuthenticationException
     * @throws AuthorizationException
     */
    protected function authorize(Request $request): void
    {
        $user = $request->user();

        if ($user === null) {
            throw new AuthenticationException('Authentication is required to update a project.');
        }

        $canUpdate = $user->getRoleNames()->contains(
            fn (string $role) => str_starts_with($role, 'super-admin-')
        ) || $user->can('project.update');

        if (! $canUpdate) {
            throw new AuthorizationException('You do not have permission to update a project.');
        }
    }

    /**
     * Resolve the target project from the encoded `project` argument.
     *
     * @throws ValidationException
     */
    protected function resolveProject(Request $request): Project
    {
        $encoded = $request->get('project');

        if (! is_string($encoded) || $encoded === '') {
            throw ValidationException::withMessages([
                'project' => 'The encoded project id is required. Use the `id` returned by get-user-projects.',
            ]);
        }

        try {
            return Project::findOrFail(Sqids::decode($encoded));
        } catch (\Throwable $e) {
            throw ValidationException::withMessages([
                'project' => 'Project not found. Provide a valid encoded project id from get-user-projects.',
            ]);
        }
    }

    /**
     * Validate the provided fields against ProjectUpdateRequest's rules. Only the fields actually
     * supplied are validated (partial update), with the sqid status/priority ids decoded first.
     *
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    protected function validate(Request $request, Project $project): array
    {
        $input = $request->all(self::FIELDS);

        foreach (['status_id', 'priority_id'] as $key) {
            if (array_key_exists($key, $input)) {
                $input[$key] = $this->decodeId($input[$key]);
            }
        }

        $validator = Validator::make(
            $input,
            (new ProjectUpdateRequest)->rules(),
            [
                'status_id.exists' => 'The selected status is invalid. Call get-project-options to see the available statuses.',
                'priority_id.exists' => 'The selected priority is invalid. Call get-project-options to see the available priorities.',
            ],
        );

        // Mirror ProjectUpdateRequest::withValidator: when no due date is supplied, statuses that
        // demand one (ids 1 and 2) require it — checked against the incoming or existing status.
        $validator->after(function ($validator) use ($input, $project): void {
            if (array_key_exists('due_date', $input)) {
                return;
            }

            $statusId = $input['status_id'] ?? $project->status_id;

            if (in_array((int) $statusId, [1, 2], true)) {
                $validator->errors()->add('due_date', 'Due date is required for the selected status.');
            }
        });

        return $validator->validate();
    }

    /**
     * Build the success response for the updated project (ids sqid-encoded).
     */
    protected function present(Project $project): Response
    {
        return Response::json([
            'data' => Sqids::rec_encode_ids_in_list($project->only([
                'id', 'project_no', 'title', 'emoji', 'description',
                'start_date', 'due_date', 'progress', 'status_id', 'priority_id',
            ])),
        ]);
    }

    /**
     * Mirror ProjectUpdateRequest::prepareForValidation — decode a sqid id to its integer.
     * An undecodable value is returned unchanged so the `exists` rule reports it clearly.
     */
    private function decodeId(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        try {
            return Sqids::decode($value);
        } catch (\Throwable $e) {
            return $value;
        }
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
                ->description('The encoded project id to update (the `id` returned by get-user-projects).')
                ->required(),
            'title' => $schema->string()
                ->description('New project title.'),
            'description' => $schema->string()
                ->description('New project description as an HTML rich-text string.'),
            'emoji' => $schema->string()
                ->description('New emoji representing the project.'),
            'start_date' => $schema->string()
                ->description('New start date in YYYY-MM-DD format.'),
            'due_date' => $schema->string()
                ->description('New due date in YYYY-MM-DD format; must be on or after start_date.'),
            'status_id' => $schema->string()
                ->description('Encoded project status id obtained from the get-project-options tool.'),
            'priority_id' => $schema->string()
                ->description('Encoded project priority id obtained from the get-project-options tool.'),
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
                'id' => $schema->string(),
                'project_no' => $schema->string(),
                'title' => $schema->string(),
                'emoji' => $schema->string(),
                'description' => $schema->string(),
                'start_date' => $schema->string(),
                'due_date' => $schema->string(),
                'progress' => $schema->number(),
                'status_id' => $schema->string(),
                'priority_id' => $schema->string(),
            ])->description('The updated project (ids are sqid-encoded).')
                ->required(),
        ];
    }
}

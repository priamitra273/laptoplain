<?php

namespace App\Mcp\Tools;

use App\Facades\Sqids;
use App\Http\Requests\Project\ProjectStoreRequest;
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

#[Name('create-project')]
#[Title('Create Project')]
#[Description('Create a new project. First call get-project-options to obtain valid encoded status_id and priority_id. The current user is attached as the project Owner.')]
#[IsDestructive(false)]
class CreateProjectTool extends Tool
{
    public function __construct(
        protected ProjectService $service,
    ) {}

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $this->authorize($request);

        $validated = $this->validate($request);

        $project = $this->service->createProject($validated);

        return $this->present($project);
    }

    /**
     * Authorize the request, mirroring CheckRoutePermission for the project.store route:
     * a `super-admin-*` role bypasses, otherwise the `project.create` permission is required.
     *
     * @throws AuthenticationException
     * @throws AuthorizationException
     */
    protected function authorize(Request $request): void
    {
        $user = $request->user();

        if ($user === null) {
            throw new AuthenticationException('Authentication is required to create a project.');
        }

        $canCreate = $user->getRoleNames()->contains(
            fn (string $role) => str_starts_with($role, 'super-admin-')
        ) || $user->can('project.create');

        if (! $canCreate) {
            throw new AuthorizationException('You do not have permission to create a project.');
        }
    }

    /**
     * Validate the tool arguments against ProjectStoreRequest's rules and messages, decoding the
     * sqid status/priority ids first (mirroring ProjectStoreRequest::prepareForValidation).
     *
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    protected function validate(Request $request): array
    {
        $storeRequest = new ProjectStoreRequest;

        return Validator::make(
            [
                'title' => $request->get('title'),
                'description' => $request->get('description'),
                'emoji' => $request->get('emoji'),
                'start_date' => $request->get('start_date'),
                'due_date' => $request->get('due_date'),
                'status_id' => $this->decodeId($request->get('status_id')),
                'priority_id' => $this->decodeId($request->get('priority_id')),
            ],
            $storeRequest->rules(),
            array_merge($storeRequest->messages(), [
                'status_id.exists' => 'The selected status is invalid. Call get-project-options to see the available statuses.',
                'priority_id.exists' => 'The selected priority is invalid. Call get-project-options to see the available priorities.',
            ]),
        )->validate();
    }

    /**
     * Build the success response for a newly created project (ids sqid-encoded).
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
     * Mirror ProjectStoreRequest::prepareForValidation — decode a sqid id to its integer.
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
            'title' => $schema->string()
                ->description('Project title.')
                ->required(),
            'description' => $schema->string()
                ->description('Project description as an HTML rich-text string (e.g. "<p>Goal: <strong>ship</strong></p>").')
                ->required(),
            'emoji' => $schema->string()
                ->description('A single emoji representing the project.')
                ->required(),
            'start_date' => $schema->string()
                ->description('Start date in YYYY-MM-DD format.')
                ->required(),
            'due_date' => $schema->string()
                ->description('Optional due date in YYYY-MM-DD format; must be on or after start_date.'),
            'status_id' => $schema->string()
                ->description('Encoded project status id obtained from the get-project-options tool.')
                ->required(),
            'priority_id' => $schema->string()
                ->description('Encoded project priority id obtained from the get-project-options tool.')
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
            ])->description('The newly created project (ids are sqid-encoded).')
                ->required(),
        ];
    }
}

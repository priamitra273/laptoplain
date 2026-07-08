<?php

namespace App\Mcp\Tools;

use App\Facades\Sqids;
use App\Models\MsTaskStatus;
use App\Models\Task;
use App\Rules\SqidExists;
use App\Services\TaskService;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[Name('update-task-status')]
#[Title('Update Task Status')]
#[Description('Update the status of a task. Provide the encoded task id (from get-project-tasks) and the encoded status_id (from get-task-options). Optionally provide a due_date (YYYY-MM-DD); statuses other than "To Do"/"Blocked" require one when the task has no due date yet. Setting the status to "Completed" marks the task complete and recalculates progress.')]
#[IsDestructive]
class UpdateTaskStatusTool extends Tool
{
    public function __construct(
        protected TaskService $service,
    ) {}

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $task = $this->resolveTask($request);

        $this->authorize($request, $task);

        $this->validate($request, $task);

        $status = MsTaskStatus::find(Sqids::decode($request->get('status_id')));

        $this->service->updateStatus(
            task: $task,
            status: $status,
            due_date: $request->has('due_date') ? $request->get('due_date') : $task->due_date,
        );

        return $this->present($task->fresh());
    }

    /**
     * Resolve the target task from the encoded `task` argument.
     *
     * @throws ValidationException
     */
    protected function resolveTask(Request $request): Task
    {
        $encoded = $request->get('task');

        if (! is_string($encoded) || $encoded === '') {
            throw ValidationException::withMessages([
                'task' => 'The encoded task id is required. Use the `id` returned by get-project-tasks.',
            ]);
        }

        try {
            return Task::findOrFail(Sqids::decode($encoded));
        } catch (\Throwable $e) {
            throw ValidationException::withMessages([
                'task' => 'Task not found. Provide a valid encoded task id from get-project-tasks.',
            ]);
        }
    }

    /**
     * Authorize the request, mirroring TaskController::updateStatus which gates on the `update`
     * task policy (super-admin/admin roles bypass; otherwise task.update permission plus task
     * membership or project ownership).
     *
     * @throws AuthenticationException
     * @throws AuthorizationException
     */
    protected function authorize(Request $request, Task $task): void
    {
        $user = $request->user();

        if ($user === null) {
            throw new AuthenticationException('Authentication is required to update a task status.');
        }

        if ($user->cannot('update', $task)) {
            throw new AuthorizationException('You do not have permission to update this task.');
        }
    }

    /**
     * Validate the provided status_id and due_date, mirroring TaskUpdateStatusRequest against the
     * resolved task (implicit requiredIf, start-date guard, and the product-owner status restriction).
     *
     * @throws ValidationException
     */
    protected function validate(Request $request, Task $task): void
    {
        $input = ['status_id' => $request->get('status_id')];

        if ($request->has('due_date')) {
            $input['due_date'] = $request->get('due_date');
        }

        $validator = Validator::make($input, [
            'status_id' => [
                'required',
                'string',
                new SqidExists(MsTaskStatus::class),
            ],
            'due_date' => [
                'nullable',
                'date',
                Rule::requiredIf(function () use ($input, $task): bool {
                    try {
                        $status = MsTaskStatus::findOrFail(Sqids::decode($input['status_id']));
                    } catch (\Throwable $th) {
                        return false;
                    }

                    $exclude_status = ['To Do', 'Blocked'];

                    return ! in_array($status->name, $exclude_status) && ! $task->due_date;
                }),
                function ($attribute, $value, $fail) use ($task): void {
                    if ($value && $due_date = Carbon::parse($value)) {
                        $start_date = Carbon::parse($task->start_date);

                        if ($start_date && $due_date->lt($start_date)) {
                            $fail('Due date cannot be earlier than the start date.');
                        }
                    }
                },
            ],
        ], [
            'status_id.required' => 'You must provide a status id. Use the encoded id returned by get-task-options.',
            'due_date.required' => 'Due date is required for this status.',
            'due_date.date' => 'Due date must be a valid date.',
        ]);

        // Mirror TaskUpdateStatusRequest::withValidator: a product-owner may only move a task to
        // "To Do", "Complete(d)", or "Block(ed)".
        $validator->after(function ($validator) use ($request): void {
            $user = $request->user();

            if ($user === null) {
                return;
            }

            $isProductOwner = $user->getRoleNames()->contains(
                fn ($role) => str_starts_with($role, 'product-owner-')
            );

            if (! $isProductOwner) {
                return;
            }

            try {
                $statusId = Sqids::decode((string) $request->get('status_id'));
            } catch (\Throwable $th) {
                return;
            }

            $statusName = (string) optional(MsTaskStatus::find($statusId))->name;
            $normalized = mb_strtolower(trim($statusName));
            $allowed = ['to do', 'complete', 'completed', 'block', 'blocked'];

            if (! in_array($normalized, $allowed, true)) {
                $validator->errors()->add(
                    'status_id',
                    'Product Owner can only set status to To Do, Complete(d), or Block.'
                );
            }
        });

        $validator->validate();
    }

    /**
     * Build the success response for the updated task (ids sqid-encoded).
     */
    protected function present(Task $task): Response
    {
        return Response::json([
            'data' => Sqids::rec_encode_ids_in_list($task->only([
                'id', 'project_id', 'parent_id', 'title',
                'status_id', 'due_date', 'progress', 'completed_at',
            ])),
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
            'task' => $schema->string()
                ->description('The encoded task id to update (the `id` field returned by the get-project-tasks tool).')
                ->required(),
            'status_id' => $schema->string()
                ->description('Encoded task status id obtained from the get-task-options tool (taskStatuses).')
                ->required(),
            'due_date' => $schema->string()
                ->description('Optional due date in YYYY-MM-DD format; required for statuses other than "To Do"/"Blocked" when the task has no due date, and must be on or after the task start date.'),
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
                'project_id' => $schema->string(),
                'parent_id' => $schema->string(),
                'title' => $schema->string(),
                'status_id' => $schema->string(),
                'due_date' => $schema->string(),
                'progress' => $schema->number(),
                'completed_at' => $schema->string(),
            ])->description('The updated task (ids are sqid-encoded).')
                ->required(),
        ];
    }
}

<?php

namespace App\Http\Requests\Task;

use App\Enums\TaskStatusEnum;
use App\Facades\Sqids;
use App\Models\MsTaskStatus;
use App\Rules\FileOrMedia;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TaskUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $status = MsTaskStatus::find($this->status_id);
        $datesRequired = $this->requiresDates($status?->name);

        return [
            'project_id' => 'sometimes|exists:projects,id',
            'parent_id' => 'sometimes|nullable|exists:tasks,id',

            'status_id' => 'required|exists:ms_task_statuses,id',
            'priority_id' => 'sometimes|nullable|exists:ms_task_priorities,id',
            'type_id' => 'sometimes|nullable|exists:ms_task_types,id',
            'task_category_id' => 'sometimes|nullable|exists:task_categories,id',

            'owned_id' => 'sometimes|exists:users,id',
            'emoji' => 'sometimes|nullable|string|max:100',
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|nullable|string',

            'start_date' => [
                Rule::requiredIf($datesRequired),
                'nullable',
                'date',
            ],

            'due_date' => [
                Rule::requiredIf($datesRequired),
                'nullable',
                'date',
                'after_or_equal:start_date',
            ],

            'sequence_number' => 'sometimes|nullable|integer',
            'is_archived' => 'sometimes|boolean',

            'assign_users' => 'sometimes|array',
            'assign_users.*' => 'exists:users,id',

            'unassign_users' => 'sometimes|array',
            'unassign_users.*' => 'exists:users,id',

            'add_tag' => 'sometimes|array',
            'add_tag.exists' => 'sometimes|array',
            'add_tag.exists.*' => 'exists:tags,id',

            'add_tag.new' => 'sometimes|array',
            'add_tag.new.*.name' => 'required_with:add_tag.new|string|max:255',
            'add_tag.new.*.severity' => 'nullable|string|max:50',

            'remove_tag' => 'sometimes|array',
            'remove_tag.*' => 'exists:tags,id',

            'attachments' => 'sometimes|nullable|array',
            'attachments.*' => [
                'nullable',
                new FileOrMedia(
                    extensions: 'jpg,jpeg,png,gif,svg,pdf,mp4,webm,ogg,m4a,wav,flac,aac,mp3,m4v,mov,avi,wmv,flv,3gp,doc,docx,xls,xlsx,ppt,pptx,csv,txt,zip,rar,7z,tar,gz,bz2',
                    maxSize: 20 * 1024,
                ),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'The title field is required.',
            'status_id.exists' => 'The selected status is invalid.',
            'priority_id.exists' => 'The selected priority is invalid.',
            'type_id.exists' => 'The selected type is invalid.',
            'task_category_id.exists' => 'The selected category is invalid.',
            'assign_users.*.exists' => 'One of the selected members is invalid.',
            'add_tag.exists.*.exists' => 'One of the existing tags is invalid.',
            'add_tag.new.*.name.required' => 'Each new tag must have a name.',
            'add_tag.new.*.name.max' => 'New tag name may not exceed 255 characters.',
            'add_tag.new.*.severity.max' => 'The severity value may not exceed 50 characters.',
            'remove_tag.*.exists' => 'One of the tags to remove is invalid.',
            'due_date.after_or_equal' => 'The due date must be after or equal to the start date.',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $taskRouteParam = $this->route('taskEncoded') ?? $this->route('task');
            $taskId = is_string($taskRouteParam) ? Sqids::decode($taskRouteParam) : $taskRouteParam;

            if (! $taskId) {
                return;
            }

            $statusId = $this->status_id
                ?? DB::table('tasks')->where('id', $taskId)->value('status_id');

            if ($statusId) {
                $status = \App\Models\MsTaskStatus::find($statusId);

                if ($status && $status->name === 'In Progress' && ! $this->filled('due_date')) {
                    $existingDueDate = DB::table('tasks')
                        ->where('id', $taskId)
                        ->value('due_date');

                    if (! $existingDueDate) {
                        $validator->errors()->add(
                            'due_date',
                            'Due date is required when status is In Progress.'
                        );
                    }
                }
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $statusId = $this->status_id;
        $priorityId = $this->priority_id;
        $typeId = $this->type_id;
        $categoryId = $this->task_category_id;
        $projectId = $this->project_id;
        $ownedId = $this->owned_id;
        $parentId = $this->parent_id;

        $assignUsersEncoded = $this->input('assign_users', []);
        $unassignUsersEncoded = $this->input('unassign_users', []);
        $addTagEncoded = $this->input('add_tag.exists', []);
        $removeTagEncoded = $this->input('remove_tag', []);

        $assignUsers = [];
        $unassignUsers = [];
        $addTag = [];
        $removeTag = [];

        if (is_array($assignUsersEncoded)) {
            foreach ($assignUsersEncoded as $user) {
                $assignUsers[] = Sqids::decode($user);
            }
        }

        if (is_array($unassignUsersEncoded)) {
            foreach ($unassignUsersEncoded as $user) {
                $unassignUsers[] = Sqids::decode($user);
            }
        }

        if (is_array($addTagEncoded)) {
            foreach ($addTagEncoded as $encoded) {
                $addTag[] = Sqids::decode($encoded);
            }
        }

        if (is_array($removeTagEncoded)) {
            foreach ($removeTagEncoded as $encoded) {
                $removeTag[] = Sqids::decode($encoded);
            }
        }

        $merged = [
            'assign_users' => $assignUsers,
            'unassign_users' => $unassignUsers,
            'add_tag' => [
                'exists' => $addTag,
                'new' => $this->input('add_tag.new', []),
            ],
            'remove_tag' => $removeTag,
            'progress' => $this->progress_value,
        ];

        if ($categoryId) {
            $merged['task_category_id'] = Sqids::decode($categoryId);
        }

        if ($statusId) {
            $merged['status_id'] = Sqids::decode($statusId);
        }
        if ($priorityId) {
            $merged['priority_id'] = Sqids::decode($priorityId);
        }
        if ($typeId) {
            $merged['type_id'] = Sqids::decode($typeId);
        }
        if ($projectId) {
            $merged['project_id'] = Sqids::decode($projectId);
        }
        if ($ownedId) {
            $merged['owned_id'] = Sqids::decode($ownedId);
        }
        if ($parentId) {
            $merged['parent_id'] = Sqids::decode($parentId);
        }

        if ($statusId && (int) Sqids::decode($statusId) === 1) {
            $merged['due_date'] = null;
        }

        $this->merge($merged);
    }

    protected function doesNotRequireDateStatus(): array
    {
        return [
            $this->normalizeStatusName(TaskStatusEnum::TO_DO->value),
            $this->normalizeStatusName(TaskStatusEnum::BLOCKED->value),
        ];
    }

    private function requiresDates(?string $statusName): bool
    {
        if (! $statusName) {
            return true;
        }

        return ! in_array($this->normalizeStatusName($statusName), $this->doesNotRequireDateStatus(), true);
    }

    private function normalizeStatusName(string $statusName): string
    {
        return str_replace(' ', '', strtolower($statusName));
    }
}

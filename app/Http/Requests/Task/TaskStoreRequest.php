<?php

namespace App\Http\Requests\Task;

use App\Enums\TaskStatusEnum;
use App\Facades\Sqids;
use App\Models\MsTaskStatus;
use App\Rules\FileOrMedia;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class TaskStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isCreate = $this->isMethod('post');
        $status = MsTaskStatus::find($this->status_id);

        return [
            'project_id' => $isCreate
                ? 'required|exists:projects,id'
                : 'sometimes|exists:projects,id',

            'parent_id' => 'sometimes|nullable|exists:tasks,id',
            'status_id' => 'required|exists:ms_task_statuses,id',
            'priority_id' => 'required|exists:ms_task_priorities,id',
            'type_id' => 'required|exists:ms_task_types,id',
            'task_category_id' => 'nullable|exists:task_categories,id',
            'sprint_id' => 'nullable|exists:project_sprints,id',
            'owned_id' => 'sometimes|exists:users,id',

            'emoji' => 'nullable|string|max:100',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',

            'start_date' => [
                Rule::requiredIf(! in_array($status->name, $this->doesNotRequireDateStatus())),
                'date',
            ],

            'due_date' => [
                Rule::requiredIf(! in_array($status->name, $this->doesNotRequireDateStatus())),
                'date',
                'after_or_equal:start_date',
            ],

            'sequence_number' => 'nullable|integer',
            'is_archived' => 'boolean',

            'assign_users' => 'nullable|array',
            'assign_users.*' => 'exists:users,id',

            'unassign_users' => 'sometimes|array',
            'unassign_users.*' => 'exists:users,id',

            'add_tag' => 'sometimes|array',
            'add_tag.exists' => 'sometimes|array',
            'add_tag.exists.*' => 'exists:tags,id',
            'add_tag.new' => 'sometimes|array',
            'add_tag.new.*.name' => 'required|string|max:255',
            'add_tag.new.*.severity' => 'nullable|string|max:50',

            'remove_tag' => 'sometimes|array',
            'remove_tag.*' => 'exists:tags,id',

            'attachments' => 'sometimes|nullable|array',
            'attachments.*' => [
                'required',
                new FileOrMedia(
                    extensions: 'jpg,jpeg,png,gif,svg,pdf,mp4,webm,ogg,m4a,wav,flac,aac,mp3,m4v,mov,avi,wmv,flv,3gp,doc,docx,xls,xlsx,ppt,pptx,csv,txt,zip,rar,7z,tar,gz,bz2',
                    maxSize: 20 * 1024,
                ),
            ],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // due_date required hanya jika status "In Progress"
            if ($this->status_id) {
                $status = \App\Models\MsTaskStatus::find($this->status_id);
                if ($status && strtolower($status->name) === 'in progress' && ! $this->filled('due_date')) {
                    $validator->errors()->add('due_date', 'Due date is required when status is In Progress.');
                }
            }

            // Epic tidak boleh masuk sprint (cek di SprintController, bukan di sini)
            // tapi validasi hierarki: Epic tidak boleh punya parent
            if ($this->task_category_id && $this->parent_id) {
                $category = \App\Models\TaskCategory::find($this->task_category_id);
                if ($category && strtolower($category->name) === 'epic') {
                    $validator->errors()->add('parent_id', 'Epic tidak boleh memiliki parent task.');
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'title.required' => 'The title field is required.',
            'emoji.max' => 'The emoji may not be greater than 100 characters.',
            'status_id.exists' => 'The selected status is invalid.',
            'priority_id.exists' => 'The selected priority is invalid.',
            'type_id.exists' => 'The selected type is invalid.',
            'task_category_id.exists' => 'The selected category is invalid.',
            'assign_users.*.exists' => 'One of the selected members is invalid.',
            'owned_id.exists' => 'The selected owner is invalid.',
            'progress.numeric' => 'The progress must be a number.',
            'add_tag.exists.*.exists' => 'One of the existing tags is invalid.',
            'add_tag.new.*.name.required' => 'Each new tag must have a name.',
            'add_tag.new.*.name.max' => 'New tag name may not exceed 255 characters.',
            'add_tag.new.*.severity.max' => 'The severity value may not exceed 50 characters.',
            'remove_tag.*.exists' => 'One of the tags to remove is invalid.',
            'due_date.after_or_equal' => 'The due date must be after or equal to the start date.',
            'due_date.required' => 'Due date is required when status is In Progress.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('owned_id')) {
            $this->merge(['owned_id' => Auth::id()]);
        }

        $decode = fn ($val) => is_string($val) && $val !== '' ? Sqids::decode($val) : $val;

        $assignUsers = [];
        foreach ((array) $this->input('assign_users', []) as $id) {
            $decoded = $decode($id);
            if ($decoded) {
                $assignUsers[] = $decoded;
            }
        }

        $unassignUsers = [];
        foreach ((array) $this->input('unassign_users', []) as $id) {
            $decoded = $decode($id);
            if ($decoded) {
                $unassignUsers[] = $decoded;
            }
        }

        $addTagExists = [];
        foreach ((array) $this->input('add_tag.exists', []) as $id) {
            $decoded = $decode($id);
            if ($decoded) {
                $addTagExists[] = $decoded;
            }
        }

        $removeTag = [];
        foreach ((array) $this->input('remove_tag', []) as $id) {
            $decoded = $decode($id);
            if ($decoded) {
                $removeTag[] = $decoded;
            }
        }

        // ✅ FIX: task_category_id fallback ke $categoryId (bukan null) jika sudah integer
        $categoryId = $this->task_category_id;
        $sprintId = $this->sprint_id;

        $this->merge([
            'status_id' => $decode($this->status_id),
            'priority_id' => $decode($this->priority_id),
            'type_id' => $decode($this->type_id),
            'task_category_id' => $categoryId !== null && $categoryId !== '' ? $decode($categoryId) : null,
            'sprint_id' => $sprintId !== null && $sprintId !== '' ? $decode($sprintId) : null,
            'project_id' => $decode($this->project_id),
            'owned_id' => $decode($this->owned_id),
            'parent_id' => $this->parent_id !== null ? $decode($this->parent_id) : null,
            'progress' => $this->progress_value,
            'assign_users' => $assignUsers,
            'unassign_users' => $unassignUsers,
            'add_tag' => [
                'exists' => $addTagExists,
                'new' => $this->input('add_tag.new', []),
            ],
            'remove_tag' => $removeTag,
        ]);
    }

    public function validationData(): array
    {
        return $this->all();
    }

    protected function doesNotRequireDateStatus(): array
    {
        return [TaskStatusEnum::TO_DO->value, TaskStatusEnum::BLOCKED->value];
    }
}

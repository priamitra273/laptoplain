<?php

namespace App\Http\Requests\Task;

use App\Facades\Sqids;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Http\FormRequest;

class TaskUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'project_id'  => 'sometimes|exists:projects,id',
            'parent_id'   => 'sometimes|nullable|exists:tasks,id',

            'status_id'   => 'sometimes|required|exists:ms_task_statuses,id',
            'priority_id' => 'sometimes|required|exists:ms_task_priorities,id',
            'type_id'     => 'sometimes|required|exists:ms_task_types,id',

            'owned_id'    => 'sometimes|exists:users,id',
            'emoji'       => 'sometimes|nullable|string|max:100',
            'title'       => 'sometimes|required|string|max:255',
            'description' => 'sometimes|nullable|string',

            'start_date'  => 'sometimes|nullable|date',
            'due_date'    => 'sometimes|nullable|date|after_or_equal:start_date',

            'sequence_number' => 'sometimes|nullable|integer',
            'is_archived'     => 'sometimes|boolean',

            'assign_users'   => 'sometimes|array',
            'assign_users.*' => 'exists:users,id',

            'unassign_users'   => 'sometimes|array',
            'unassign_users.*' => 'exists:users,id',

            'add_tag'          => 'sometimes|array',
            'add_tag.exists'   => 'sometimes|array',
            'add_tag.exists.*' => 'exists:tags,id',

            'add_tag.new'            => 'sometimes|array',
            'add_tag.new.*.name'     => 'required_with:add_tag.new|string|max:255',
            'add_tag.new.*.severity' => 'nullable|string|max:50',

            'remove_tag'   => 'sometimes|array',
            'remove_tag.*' => 'exists:tags,id',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required'              => 'The title field is required.',
            'status_id.required'          => 'The status field is required.',
            'status_id.exists'            => 'The selected status is invalid.',
            'priority_id.required'        => 'The priority field is required.',
            'priority_id.exists'          => 'The selected priority is invalid.',
            'type_id.required'            => 'The type field is required.',
            'type_id.exists'              => 'The selected type is invalid.',
            'assign_users.*.exists'       => 'One of the selected members is invalid.',
            'add_tag.exists.*.exists'     => 'One of the existing tags is invalid.',
            'add_tag.new.*.name.required' => 'Each new tag must have a name.',
            'add_tag.new.*.name.max'      => 'New tag name may not exceed 255 characters.',
            'add_tag.new.*.severity.max'  => 'The severity value may not exceed 50 characters.',
            'remove_tag.*.exists'         => 'One of the tags to remove is invalid.',
            'due_date.after_or_equal'     => 'The due date must be after or equal to the start date.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $statusId   = $this->status_id;
        $priorityId = $this->priority_id;
        $typeId     = $this->type_id;
        $projectId  = $this->project_id;
        $ownedId    = $this->owned_id;
        $parentId   = $this->parent_id;

        $assignUsersEncoded  = $this->input('assign_users', []);
        $unassignUsersEncoded = $this->input('unassign_users', []);
        $addTagEncoded       = $this->input('add_tag.exists', []);
        $removeTagEncoded    = $this->input('remove_tag', []);

        $assignUsers  = [];
        $unassignUsers = [];
        $addTag       = [];
        $removeTag    = [];

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
            'assign_users'  => $assignUsers,
            'unassign_users' => $unassignUsers,
            'add_tag' => [
                'exists' => $addTag,
                'new'    => $this->input('add_tag.new', []),
            ],
            'remove_tag'   => $removeTag,
            'progress'     => $this->progress_value,
        ];

        if ($statusId)   $merged['status_id']   = Sqids::decode($statusId);
        if ($priorityId) $merged['priority_id'] = Sqids::decode($priorityId);
        if ($typeId)     $merged['type_id']      = Sqids::decode($typeId);
        if ($projectId)  $merged['project_id']   = Sqids::decode($projectId);
        if ($ownedId)    $merged['owned_id']      = Sqids::decode($ownedId);
        if ($parentId)   $merged['parent_id']     = Sqids::decode($parentId);

        $this->merge($merged);
    }
}

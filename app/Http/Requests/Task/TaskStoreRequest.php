<?php

namespace App\Http\Requests\Task;

use App\Facades\Sqids;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Http\FormRequest;

class TaskStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */

    public function rules(): array
    {
        $isCreate = $this->isMethod('post');

        return [
            'project_id' => $isCreate
                ? 'required|exists:projects,id'
                : 'sometimes|exists:projects,id',

            'parent_id' => 'sometimes|nullable|exists:tasks,id',

            'status_id'   => 'required|exists:ms_task_statuses,id',
            'priority_id' => 'required|exists:ms_task_priorities,id',
            'type_id'     => 'required|exists:ms_task_types,id',

            'owned_id' => 'sometimes|exists:users,id',

            'emoji' => 'nullable|string|max:100',
            'title' => 'required|string|max:255',

            'description' => 'nullable|string',

            'start_date' => 'nullable|date',
            'due_date'   => 'nullable|date|after_or_equal:start_date',

            'progress' => 'nullable|numeric|min:0|max:100',

            'sequence_number' => 'nullable|integer',
            'is_archived'     => 'boolean',

            'assign_users' => $isCreate
                ? 'required|array|min:1'
                : 'sometimes|array',

            'assign_users.*' => 'exists:users,id',

            'unassign_users'   => 'sometimes|array',
            'unassign_users.*' => 'exists:users,id',

            'add_tag'          => 'sometimes|array',
            'add_tag.exists'   => 'sometimes|array',
            'add_tag.exists.*' => 'exists:tags,id',

            'add_tag.new' => 'sometimes|array',
            'add_tag.new.*.name'     => 'required|string|max:255',
            'add_tag.new.*.severity' => 'nullable|string|max:50',

            'remove_tag'   => 'sometimes|array',
            'remove_tag.*' => 'exists:tags,id',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'The title field is required.',
            'emoji.max' => 'The emoji may not be greater than 100 characters.',
            'status_id.required' => 'The status field is required.',
            'status_id.exists' => 'The selected status is invalid.',
            'priority_id.required' => 'The priority field is required.',
            'priority_id.exists' => 'The selected priority is invalid.',
            'type_id.required' => 'The type field is required.',
            'type_id.exists' => 'The selected type is invalid.',
            'assign_users.required' => 'At least one member must be assigned.',
            'assign_users.min' => 'At least one member must be assigned.',
            'assign_users.*.exists' => 'One of the selected members is invalid.',

            'owned_id.exists' => 'The selected owner is invalid.',
            'progress.numeric' => 'The progress must be a number.',
            'add_tag.exists.*.required' => 'Existing tag ID is required.',
            'add_tag.exists.*.exists'   => 'One of the existing tags is invalid.',
            'add_tag.new.*.name.required' => 'Each new tag must have a name.',
            'add_tag.new.*.name.max'      => 'New tag name may not exceed 255 characters.',
            'add_tag.new.*.severity.max'  => 'The severity value may not exceed 50 characters.',
            'remove_tag.*.exists' => 'One of the tags to remove is invalid.',
        ];
    }


    /**
     * Prepare the data for validation.
     *
     * @return void
     */

    protected function prepareForValidation()
    {
        if (!$this->has('owned_id')) {
            $this->merge([
                'owned_id' => Auth::id(),
            ]);
        }

        $statusId = $this->status_id;
        $priorityId = $this->priority_id;
        $typeId = $this->type_id;
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

        $this->merge([
            'status_id' => is_string($statusId) ? Sqids::decode($statusId) : $statusId,
            'priority_id' => is_string($priorityId) ? Sqids::decode($priorityId) : $priorityId,
            'type_id' => is_string($typeId) ? Sqids::decode($typeId) : $typeId,
            'project_id' => is_string($projectId) ? Sqids::decode($projectId) : $projectId,
            'owned_id' => is_string($ownedId) ? Sqids::decode($ownedId) : $ownedId,
            'parent_id' => is_string($parentId) ? Sqids::decode($parentId) : $parentId,
            'progress' => $this->progress_value,
            'assign_users' => $assignUsers,
            'unassign_users' => $unassignUsers,
            'add_tag' => [
                'exists' => $addTag,
                'new'    => $this->input('add_tag.new', [])
            ],
            'remove_tag' => $removeTag,
        ]);
    }

    /**
     * Get the data that should be validated.
     *
     * @return array
     */
    public function validationData()
    {
        return $this->all();
    }
}

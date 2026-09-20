<?php

namespace App\Http\Requests\Project;

use App\Facades\Sqids;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProjectUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|nullable|string',
            'emoji' => 'sometimes|string|max:10',
            'start_date' => 'sometimes|date',
            'due_date' => 'sometimes|date|after_or_equal:start_date',
            'status_id' => 'sometimes|exists:ms_project_statuses,id',
            'priority_id' => 'sometimes|exists:ms_project_priority,id',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $project = $this->currentProject();

            if (! $project) {
                return;
            }

            $this->validateDateOrder($validator, $project);

            if ($this->has('due_date')) {
                return;
            }

            $statusId = $this->status_id ?? $project->status_id;

            if (in_array((int) $statusId, [1, 2]) && ! $project->due_date) {
                $validator->errors()->add(
                    'due_date',
                    'Due date is required when status is set to "Status"'
                );
            }
        });
    }

    private function validateDateOrder($validator, object $project): void
    {
        if ($this->has('start_date') && $this->has('due_date')) {
            return;
        }

        if ($validator->errors()->hasAny(['start_date', 'due_date'])) {
            return;
        }

        $startDate = $this->date('start_date') ?: ($project->start_date ? Carbon::parse($project->start_date) : null);
        $dueDate = $this->date('due_date') ?: ($project->due_date ? Carbon::parse($project->due_date) : null);

        if (! $startDate || ! $dueDate || $dueDate->startOfDay() >= $startDate->startOfDay()) {
            return;
        }

        $validator->errors()->add(
            $this->has('start_date') ? 'start_date' : 'due_date',
            'Due date must be on or after the start date.'
        );
    }

    private function currentProject(): ?object
    {
        $projectRouteParam = $this->route('project');
        $projectId = is_string($projectRouteParam) ? Sqids::decode($projectRouteParam) : $projectRouteParam;

        if (! $projectId) {
            return null;
        }

        return DB::table('projects')->where('id', $projectId)->first();
    }

    protected function prepareForValidation()
    {
        if (! $this->has('owned_id')) {
            $this->merge([
                'owned_id' => Auth::id(),
            ]);
        }

        if ($this->status_id) {
            $this->merge([
                'status_id' => Sqids::decode($this->status_id),
            ]);
        }

        if ($this->priority_id) {
            $this->merge([
                'priority_id' => Sqids::decode($this->priority_id),
            ]);
        }
    }
}

<?php

namespace App\Services;

use App\Data\Project\ProjectData;
use App\Data\Project\ProjectPriorityData;
use App\Data\Project\ProjectStatusData;
use App\Facades\Sqids;
use App\Models\MsProjectPriority;
use App\Models\MsProjectStatus;
use App\Models\Project;
use Illuminate\Support\Facades\Auth;
use Spatie\LaravelData\DataCollection;

class ProjectService
{
    public function findByEncodedId(string $encodedId)
    {
        $id = Sqids::decodeOrFail($encodedId);

        return Project::findOrFail($id);
    }

    public function getIndexData(): array
    {
        $user = Auth::user();

        $projects = Project::with([
            'status:id,name,severity',
            'priority:id,name,severity',
        ])
            ->visibleFor($user)
            ->orderByDesc('id')
            ->get();

        $statuses = MsProjectStatus::select('id', 'name', 'severity')->get();
        $priorities = MsProjectPriority::select('id', 'name', 'severity')->get();

        return [
            'projects' => ProjectData::collect($projects, DataCollection::class)->toArray(),
            'statuses' => ProjectStatusData::collect($statuses, DataCollection::class)->toArray(),
            'priorities' => ProjectPriorityData::collect($priorities, DataCollection::class)->toArray(),
        ];
    }
}

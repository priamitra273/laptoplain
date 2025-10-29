<?php

namespace App\Services;

use App\Enums\SiteStatus as EnumsSiteStatus;
use App\Enums\StreamingStatusEnum;
use App\Models\Site;
use App\Models\SiteHistory;
use App\Models\SiteStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ValidatedInput;

class InstallationService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function builder(): Builder
    {
        return Site::joinRelationship('project')
            ->joinRelationship('status')
            ->joinRelationship('regency')
            ->leftJoinRelationshipUsingAlias('replacement', 'sr')
            ->select([
                'sites.uuid',
                'sites.site_id',
                'sites.site_name',
                'sites.latitude',
                'sites.longitude',
                DB::raw('projects.uuid as project_uuid'),
                DB::raw('projects.name as project_name'),
                DB::raw('sr.site_id as replacement_site'),
                DB::raw('sr.uuid as replacement_to'),
                DB::raw('site_statuses.uuid as status_uuid'),
                DB::raw('site_statuses.name as status'),
                DB::raw('regencies.uuid as regency_uuid'),
                DB::raw('regencies.name as regency_name'),
                'sites.created_at',
                'sites.updated_at'
            ]);
    }

    public function filter(array|null $filter): QueryBuilder
    {
        $sites = DB::query()->fromSub($this->builder(), 'dt');

        if (!empty($filter) && !empty($filter['sortField']) && !empty($filter['sortOrder'])) {
            $direction = $filter['sortOrder'] == 1 ? 'asc' : 'desc';

            $sites = $sites->orderBy($filter['sortField'], $direction);
        }

        if (!empty($filter) && !empty($filter['regency_uuid'])) {
            if ($filter['regency_uuid']['matchMode'] === 'in') {
                $sites = $sites->whereIn('regency_uuid', $filter['regency_uuid']['value']);
            }
        }

        if (!empty($filter) && !empty($filter['q'])) {
            $query = $filter['q'];

            $sites = $sites->where('site_id', 'ILIKE', "%$query%")
                ->orWhere('site_name', 'ILIKE', "%$query%")
                ->orWhere('latitude', 'ILIKE', "%$query%")
                ->orWhere('longitude', 'ILIKE', "%$query%")
                ->orWhere('longitude', 'ILIKE', "%$query%")
                ->orWhere('project_name', 'ILIKE', "%$query%")
                ->orWhere('replacement_site', 'ILIKE', "%$query%");
        }

        return $sites;
    }

    /**
     * Handle store site history
     * 
     * @return void
     */
    public function store(ValidatedInput $input, Site $site): void
    {
        $site_status = SiteStatus::findByUuid($input->site_status_uuid);

        DB::beginTransaction();

        try {
            $site->update([
                'site_status_id' => $site_status->id
            ]);
    
            if ($site_status->id === EnumsSiteStatus::COMPLETE->value) {
                $site->cctv->each->update(['streaming_status_id' => StreamingStatusEnum::OPEN]);
            }
            
            $history = SiteHistory::create([
                'site_id' => $site->id,
                'site_status_id' => $site_status->id,
                'remark' => $input->remark,
            ]);
    
            if ($input->attachments) {
                foreach ($input->attachments as $file) {
                    $history->addMedia($file)->toMediaCollection('attachments', 'public');
                }
            }

            DB::commit();
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            DB::rollBack();

            abort(500);
        }
    }
}

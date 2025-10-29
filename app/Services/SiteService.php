<?php

namespace App\Services;

use App\Enums\SiteStatus;
use App\Enums\StreamingStatusEnum;
use App\Models\Cctv;
use App\Models\Device;
use App\Models\Project;
use App\Models\Regency;
use App\Models\Site;
use App\Models\SiteHistory;
use App\Models\SiteStatus as ModelsSiteStatus;
use Illuminate\Auth\Events\Validated;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ValidatedInput;

class SiteService
{
    /**
     * Get site builder
     * 
     */
    public function getBuilder(): Builder
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
            ])
            ->withCount('cctv');
    }

    /**
     * Get pagination list of resource 
     * 
     */
    public function getPaginateData($rows = 25, $filter = null) 
    {
        $sites = DB::query()->fromSub($this->getBuilder(), 'dt');

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

        return $sites->paginate($rows);
    }

    /**
     * Store site resource using db transaction
     * - insert the new site first
     * - insert into site history
     * - handle attachment if any
     * - insert new cctv if any device inputed (mac address)
     * 
     * @param ValidatedInput $input
     * @return void
     */
    public function store(ValidatedInput $input): void
    {
        DB::beginTransaction();

        try {
            $site = Site::create([
                'site_status_id' => SiteStatus::OPEN,
                'project_id' => Project::findByUuid($input->project_id)->id,
                'regency_id' => Regency::findByUuid($input->regency_id)->id,
                'site_id' => $input->site_id,
                'site_name' => $input->site_name,
                'latitude' => $input->latitude,
                'longitude' => $input->longitude,
                'replacement_to' => $input->replacement_to ? Site::findByUuid($input->replacement_to)->id : null
            ]);

            $site->histories()->create([
                'site_status_id' => SiteStatus::OPEN,
                'remark' => 'Created new site'
            ]);

            // $history = SiteHistory::create([
            //     'site_id' => $site->id,
            //     'site_status_id' => $site_status->id,
            //     'remark' => $input->remark,
            // ]);

            // if ($input->attachments) {
            //     $history->addMediaFromRequest('attachments')->toMediaCollection('attachments', 'public');
            // }

            // foreach ($input->cctv as $camera) {
            //     if ($camera['device_id']) {
            //         Cctv::create([
            //             'name' => $camera['cctv_name'],
            //             'site_id' => $site->id,
            //             'device_id' => Device::findByUuid($camera['device_id'])->id,
            //             'streaming_status_id' => $site_status->id === SiteStatus::COMPLETE ? StreamingStatusEnum::OPEN : null,
            //             'is_active' => true
            //         ]);
            //     }
            // }

            DB::commit();

        } catch (\Throwable $th) {
            Log::error($th->getMessage());

            DB::rollBack();

            abort(500, 'Error creating site');
        }
    }

    public function update(ValidatedInput $input, Site $site): void
    {
        DB::beginTransaction();

        try {
            // $site_status = ModelsSiteStatus::findByUuid($input->site_status_id);

            $site->update([
                'project_id' => Project::findByUuid($input->project_id)->id,
                // 'site_status_id' => $site_status->id,
                'regency_id' => Regency::findByUuid($input->regency_id)->id,
                'site_id' => $input->site_id,
                'site_name' => $input->site_name,
                'latitude' => $input->latitude,
                'longitude' => $input->longitude,
                'replacement_to' => $input->replacement_to ? Site::findByUuid($input->replacement_to)->id : null
            ]);

            $site->histories()->create([
                'site_status_id' => $site->site_status_id,
                'remark' => 'Update data site'
            ]);

            //  $history = SiteHistory::create([
            //     'site_id' => $site->id,
            //     'site_status_id' => $site_status->id,
            //     'remark' => $input->remark,
            // ]);

            // if ($input->attachments) {
            //     $history->addMediaFromRequest('attachments')->toMediaCollection('attachments', 'public');
            // }

            // foreach ($input->cctv as $camera) {
            //     if (!empty($camera['uuid'])) {
            //         Cctv::findByUuid($camera['uuid'])->update([
            //             'name' => $camera['cctv_name'],
            //             'device_id' => Device::findByUuid($camera['device_id'])->id,
            //             'streaming_status_id' => $site_status->id === SiteStatus::COMPLETE ? StreamingStatusEnum::OPEN : null,
            //         ]);
            //     }
            //     else {
            //         Cctv::create([
            //             'name' => $camera['cctv_name'],
            //             'site_id' => $site->id,
            //             'device_id' => Device::findByUuid($camera['device_id'])->id,
            //             'streaming_status_id' => $site_status->id === SiteStatus::COMPLETE ? StreamingStatusEnum::OPEN : null,
            //             'is_active' => true
            //         ]);
            //     }
            // }

            DB::commit();
        } catch (\Throwable $th) {
            Log::error($th->getMessage());

            DB::rollBack();

            abort(500, 'Error creating site');
        }
    }
}
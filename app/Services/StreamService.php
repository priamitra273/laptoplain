<?php

namespace App\Services;

use App\Enums\AnalyticStatusEnum;
use App\Enums\SiteStatus;
use App\Enums\StreamingStatusEnum;
use App\Models\Cctv;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ValidatedInput;

class StreamService
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
        return Cctv::joinRelationship('site.status')
            ->joinRelationship('site.project')
            ->joinRelationship('site.regency')
            ->leftJoinRelationship('device')
            ->leftJoinRelationship('streaming_status')
            ->select(
                'cctvs.uuid', 
                'cctvs.name', 
                'cctvs.created_at', 
                'cctvs.updated_at', 
                'ip_flussonic', 
                'link_rtsp', 
                'link_embed', 
                'link_embed_nonrelay', 
                'updated_streaming_at',
                'sites.site_id',
                'sites.site_name',
                'sites.latitude',
                'sites.longitude',
                'devices.mac_address',
                'devices.ip_dhcp',
                'devices.ip_static'
            )
            ->selectRaw('projects.name as project_name')
            ->selectRaw('site_statuses.name as status_site')
            ->selectRaw('regencies.name as regency_name')
            ->selectRaw('streaming_statuses.uuid as status_stream_uuid')
            ->selectRaw('streaming_statuses.name as status_stream_name')
            ->whereNotNull('streaming_status_id');
    }

    public function get($rows = 25, array|null $filter = null)
    {
        $strams = DB::query()->fromSub($this->builder(), 'dt');

        if (!empty($filter) && !empty($filter['sortField']) && !empty($filter['sortOrder'])) {
            $direction = $filter['sortOrder'] == 1 ? 'asc' : 'desc';

            $strams = $strams->orderBy($filter['sortField'], $direction);
        }

        if (!empty($filter) && !empty($filter['q'])) {
            $query = $filter['q'];

            $strams = $strams->where('site_id', 'ILIKE', "%$query%")
                ->orWhere('site_name', 'ILIKE', "%$query%")
                ->orWhere('latitude', 'ILIKE', "%$query%")
                ->orWhere('longitude', 'ILIKE', "%$query%")
                ->orWhere('longitude', 'ILIKE', "%$query%")
                ->orWhere('project_name', 'ILIKE', "%$query%");
        }

        return $strams->paginate($rows);
    }

    public function update(ValidatedInput $input, Cctv $cctv): void
    {
        DB::beginTransaction();

        try {
            $cctv->device->update([
                'ip_static' => $input->ip_static
            ]);

            $update_data = [
                'cctv_name' => $input->cctv_name,
                'ip_flussonic' => $input->ip_flussonic,
                'link_embed' => $input->link_embed,
                'link_embed_nonrelay' => $input->link_embed_nonrelay,
                'link_rtsp' => $input->link_rtsp,
                'updated_streaming_at' => now(),
                'streaming_status_id' => StreamingStatusEnum::COMPLETE,
                
            ];

            if (empty($cctv->analytic_status_id)) {
                $update_data['analytic_status_id'] = AnalyticStatusEnum::OPEN;
            }

            $cctv->update($update_data);
            
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();

            Log::error($th->getMessage());
            
            abort(500);
        }
    }
}

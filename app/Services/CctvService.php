<?php

namespace App\Services;

use App\Models\Cctv;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class CctvService
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
        return Cctv::leftJoinRelationship('analytic_status')
            ->joinRelationship('site.project')
            ->joinRelationship('site.regency')
            ->joinRelationship('site.status')
            ->leftJoinRelationship('device')
            ->leftJoinRelationship('department')
            ->leftJoinRelationship('analytic_server')
            ->leftJoinRelationship('analytic_category')
            ->leftJoinRelationship('analytic_status')
            ->whereNotNull('analytic_status_id')
            ->select(
                'cctvs.uuid',
                'cctvs.name',
                'cctvs.created_at',
                'cctvs.updated_at',
                'ip_flussonic',
                'link_rtsp',
                'link_embed',
                'link_embed_nonrelay',
                'link_embed_bb',
                'sites.site_id',
                'sites.site_name',
                'sites.latitude',
                'sites.longitude',
                'devices.ip_static',
                'updated_analytic_at',
                'polygon',
                'threshold'
            )
            ->selectRaw('site_statuses.name as status_site')
            ->selectRaw('projects.name as project_name')
            ->selectRaw('regencies.name as regency_name')
            ->selectRaw('departments.uuid as department_uuid')
            ->selectRaw('departments.name as department_name')
            ->selectRaw('analytic_categories.uuid as category_uuid')
            ->selectRaw('analytic_categories.name as category_name')
            ->selectRaw('analytic_servers.uuid as server_uuid')
            ->selectRaw('analytic_servers.ip_address as ip_server')
            ->selectRaw('analytic_statuses.uuid as analytic_status_uuid')
            ->selectRaw('analytic_statuses.name as analytic_status_name');
    }

    public function filter(array|null $filter = null): \Illuminate\Database\Query\Builder
    {
        $cctv = DB::query()->fromSub($this->builder(), 'dt');

        if (!empty($filter) && !empty($filter['sortField']) && !empty($filter['sortOrder'])) {
            $direction = $filter['sortOrder'] == 1 ? 'asc' : 'desc';

            $cctv = $cctv->orderBy($filter['sortField'], $direction);
        }

        if (!empty($filter) && !empty($filter['q'])) {
            $query = $filter['q'];

            $cctv = $cctv->where('site_id', 'ILIKE', "%$query%")
                ->orWhere('site_name', 'ILIKE', "%$query%")
                ->orWhere('latitude', 'ILIKE', "%$query%")
                ->orWhere('longitude', 'ILIKE', "%$query%")
                ->orWhere('longitude', 'ILIKE', "%$query%")
                ->orWhere('project_name', 'ILIKE', "%$query%");
        }

        return $cctv;
    }
}

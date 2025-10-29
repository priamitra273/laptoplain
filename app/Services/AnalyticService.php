<?php

namespace App\Services;

use App\Enums\AnalyticStatusEnum;
use App\Models\AnalyticCategory;
use App\Models\AnalyticServer;
use App\Models\Cctv;
use App\Models\Department;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ValidatedInput;

class AnalyticService
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
        return Cctv::joinRelationship('analytic_status')
            ->joinRelationship('site.project')
            ->joinRelationship('site.regency')
            ->joinRelationship('site.status')
            ->joinRelationship('device')
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

    public function get($rows = 25, array|null $filter = null): LengthAwarePaginator
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
        $category = AnalyticCategory::findByUuid($input->category_uuid);
        
        $has_config = $category->has_polygon || $category->has_threshold ? 0 : 2;

        $status = count(array_filter($input->toArray(), fn ($item) => !empty($item))) === (count($input->toArray()) - $has_config)
            ? AnalyticStatusEnum::COMPLETE
            : AnalyticStatusEnum::DRAFT;

        $data = [
            'analytic_category_id' => $category->id,
            'department_id' => Department::findByUuid($input->department_uuid)->id,
            'link_embed_bb' => $input->link_embed_bb,
            'analytic_server_id' => AnalyticServer::findByUuid($input->server_uuid)->id,
            'polygon' => $input->polygon,
            'threshold' => $input->threshold,
            'updated_analytic_at' => now(),
            'analytic_status_id' => $status
        ];

        $cctv->update($data);
    }

    public function getThumbnail(Cctv $cctv)
    {
        $timestamp = now()->subMinutes(30)->format('U');

        try {
            return $this->flussonicDownload($cctv, $timestamp, true);
        }
        catch (\Exception $e) {
            Log::error("CCTV ID: " . $cctv->uuid);
            Log::error($e->getMessage());
        }

        try {
            return $this->flussonicDownload($cctv, $timestamp);
        }
        catch (\Exception $e) {
            // Handle the exception
            Log::error("CCTV ID: " . $cctv->uuid);
            Log::error($e->getMessage());
        }

        try {
            return $this->flussonicDownload($cctv, $timestamp);
        }
        catch (\Exception $e) {
            // Handle the exception
            Log::error("Failed second attempt");
            Log::error($e->getMessage());
        }

        $directory = storage_path("app/private/thumbnails");

        if (!File::isDirectory($directory)) {
            File::makeDirectory($directory);
        }
        
        $filename   = "$directory/{$cctv->uuid}.jpg";
        $filename   = str_replace("\\", "/", $filename);
        $input      = $cctv->link_rtsp ?? $cctv->link_embed;
        
        if ($input && $input != 'nan') {
            if (str_contains($input, 'embed.html')) {
                $input = str_replace('embed.html', 'tracks-v1/index.fmp4.m3u8', $input);
            }

            try {
                $ffmpeg_path = env('FFMPEG_PATH', '');
                $result = Process::timeout(30)->run("{$ffmpeg_path}ffmpeg -i $input -vframes 1 -q:v 2 $filename -y");
                Log::error($result->errorOutput());
                Log::info("ffmpeg -i $input -vframes 1 -q:v 2 $filename -y");
                
                return storage_path("app/private/thumbnails/{$cctv->uuid}.jpg");
            } catch (\Exception $e) {
                Log::error("CCTV ID: " . $cctv->uuid);
                Log::error($e->getMessage());
            }
        }

        return '';
    }

    protected function flussonicDownload(Cctv $cctv, $timestamp, $using_url = false): string
    {
        if ($using_url && $cctv->link_embed_nonrelay) {
            $url = str_replace('embed.html', "$timestamp-preview.jpg", $cctv->link_embed_nonrelay);
            $image = file_get_contents($url);

            if ($image) {
                Storage::disk('local')->put("thumbnails/{$cctv->uuid}.jpg", $image);
    
                return storage_path("app/private/thumbnails/{$cctv->uuid}.jpg");
            }
        }

        if ($cctv->link_embed_nonrelay && $cctv->ip_flussonic) {
            $stream_name = explode('/', $cctv->link_embed_nonrelay)[3];
            $url = "http://" . $cctv->ip_flussonic ."/". $stream_name . "/$timestamp-preview.jpg";

            $image = file_get_contents($url);

            if ($image) {
                Storage::disk('local')->put("thumbnails/{$cctv->uuid}.jpg", $image);
    
                return storage_path("app/private/thumbnails/{$cctv->uuid}.jpg");
            }
        }

        return '';
    }
}

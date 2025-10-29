<?php

namespace App\Http\Resources\CCTV;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnalyticResource extends JsonResource
{
    public static $wrap = null;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,

            'project_name' => $this->project_name,
            'regency_name' => $this->regency_name,

            'site_id' => $this->site_id,
            'site_name' => $this->site_name,
            'status_site' => $this->status_site,
            'latitude' => (float) $this->latitude,
            'longitude' => (float) $this->longitude,
            
            'department_uuid' => $this->department_uuid,
            'department_name' => $this->department_name,
            
            'ip_static' => $this->ip_static,
            'ip_flussonic' => $this->ip_flussonic,
            'server_uuid' => $this->server_uuid,
            'ip_server' => $this->ip_server,
            
            'link_rtsp' => $this->link_rtsp,
            'link_embed' => $this->link_embed,
            'link_embed_nonrelay' => $this->link_embed_nonrelay,
            'link_embed_bb' => $this->link_embed_bb,

            // analytic section
            'category_uuid' => $this->category_uuid,
            'category_name' => $this->category_name,
            'analytic_status_uuid' => $this->analytic_status_uuid,
            'analytic_status_name' => $this->analytic_status_name,
            'polygon' => json_decode($this->polygon),
            'threshold' => json_decode($this->threshold),

            'updated_analytic_at' => $this->updated_analytic_at ? Carbon::parse($this->updated_analytic_at)->toISOString() : null,
            'created_at' => Carbon::parse($this->created_at)->toISOString(),
            'updated_at' => Carbon::parse($this->updated_at)->toISOString(),
        ];
    }
}

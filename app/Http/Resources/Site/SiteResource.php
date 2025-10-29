<?php

namespace App\Http\Resources\Site;

use App\Http\Resources\CCTV\PreconfigCameraResource;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SiteResource extends JsonResource
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
            'site_id' => $this->site_id,
            'site_name' => $this->site_name,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            
            'project_uuid' => $this->project_uuid ?? $this->project->uuid,
            'project_name' => $this->project_name ?? $this->project->name,
            
            'replacement_site' => empty($this->replacement) ? $this->replacement_site : $this->replacement?->site_id,
            'replacement_to' => empty($this->replacement) ? $this->replacement_to : $this->replacement->site_uuid,

            'status_uuid' => $this->status->uuid ?? $this->status_uuid,
            'status' => $this->status->name ?? $this->status,

            'regency_uuid' => empty($this->regency) ? $this->regency_uuid : $this->regency->uuid,
            'regency_name' => empty($this->regency) ? $this->regency_name : $this->regency->name,

            'has_preconfig' => (bool) $this->cctv_count,

            $this->mergeWhen(!empty($this->cctv), [
                'cctv' => PreconfigCameraResource::collection($this->cctv ?? [])->resolve()
            ]),

            'created_at' => Carbon::parse($this->created_at)->toISOString(),
            'updated_at' => Carbon::parse($this->updated_at)->toISOString()
        ];
    }
}

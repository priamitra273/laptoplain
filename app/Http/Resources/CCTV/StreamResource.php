<?php

namespace App\Http\Resources\CCTV;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StreamResource extends JsonResource
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
            'site_id' => $this->site_id,
            'site_name' => $this->site_name,
            'latitude' => (float) $this->latitude,
            'longitude' => (float) $this->longitude,
            'project_name' => $this->project_name,
            'status_site' => $this->status_site,
            'regency_name' => $this->regency_name,
            'created_at' => Carbon::parse($this->created_at)->toISOString(),
            'updated_at' => Carbon::parse($this->updated_at)->toISOString(),
            'status_stream_uuid' => $this->status_stream_uuid,
            'status_stream_name' => $this->status_stream_name,
            'mac_address' => $this->mac_address,
            'ip_dhcp' => $this->ip_dhcp,
            'ip_static' => $this->ip_static,
            'ip_flussonic' => $this->ip_flussonic,
            'link_rtsp' => $this->link_rtsp,
            'link_embed' => $this->link_embed,
            'link_embed_nonrelay' => $this->link_embed_nonrelay,
            'updated_streaming_at' => Carbon::parse($this->updated_streaming_at)->toISOString(),
        ];
    }
}

<?php

namespace App\Http\Resources\Device;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeviceResource extends JsonResource
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
            'mac_address' => $this->mac_address,
            'ip_dhcp' => $this->ip_dhcp,
            'ip_static' => $this->ip_static,
            'site_id' => $this->cctv?->site?->site_id,
            'cctv_name' => $this->cctv?->name,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}

<?php

namespace App\Http\Resources\CCTV;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PreconfigCameraResource extends JsonResource
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
            'cctv_name' => $this->name,
            'device_id' => $this->device->uuid,
            'mac_address' => $this->device->mac_address,
            'ip_dhcp' => $this->device->ip_dhcp
        ];
    }
}

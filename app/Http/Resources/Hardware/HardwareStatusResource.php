<?php

namespace App\Http\Resources\Hardware;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HardwareStatusResource extends JsonResource
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
            'level' => $this->level,
            'name' => $this->name,
            'description' => $this->description
        ];
    }
}

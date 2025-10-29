<?php

namespace App\Http\Resources\Site;

use App\Http\Resources\SimpleMediaResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SiteHistoryResource extends JsonResource
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
            'status' => $this->status->name,
            'remark' => $this->remark,
            'attachments' => SimpleMediaResource::collection($this->getMedia('attachments'))->resolve(),
            'created_at' => $this->created_at,
            'created_by' => $this->created_by_user->name
        ];
    }
}

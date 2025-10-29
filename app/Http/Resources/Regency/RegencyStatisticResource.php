<?php

namespace App\Http\Resources\Regency;

use App\Http\Resources\FeatureCollection;
use App\Http\Resources\FeatureResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RegencyStatisticResource extends JsonResource
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
            'success' => true,
            'message' => 'Success get geo spasial data',
            'data' => [
                'geojson' => [
                    "type" => "FeatureCollection",
                    "crs" => [
                        "type" => "name",
                        "properties" => [
                            "name" => "DKI Jakarta"
                        ]
                    ],
                    "features" => FeatureResource::collection($this->resource)
                ],
    
                'data' => $this->resource->map(function ($item) {
                    return [$item->geojson['properties']['code'], $item->cctv_count];
                })
            ]
        ];
    }
}

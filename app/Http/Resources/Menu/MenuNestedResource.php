<?php

namespace App\Http\Resources\Menu;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class MenuNestedResource extends JsonResource
{
    // public function __construct($resource)
    // {
    //     self::withoutWrapping();
    //     parent::__construct($resource);
    // }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'key' => $this->uuid,
            'data' => [
                'label' => $this->label,
                'parent' => $this->parent?->label,
                'parent_uuid' => $this->parent?->uuid,
                'icon' => $this->icon,
                'route' => $this->route_name,
                'sequence_number' => $this->sequence_number,
                'is_active' => $this->is_active,
                'permission_prefix' => (string) !empty($this->route_name) ? str($this->route_name)->before('.') : str($this->label)->singular()->slug(),
                'created_at' => $this->created_at,
                'updated_at' => $this->updated_at
            ],

            // using "resolve" to avoid wrapping
            'children' => MenuNestedResource::collection($this->children()->orderBy('sequence_number')->get())->resolve(),

        ];
    }
}

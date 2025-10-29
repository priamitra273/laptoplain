<?php

namespace App\Http\Resources\Menu;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class MenuSidebarResource extends JsonResource
{
    public static $wrap = null;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $children = $this->children()
            ->whereRelation('permissions.roles.users', 'id', Auth::id())
            ->orderBy('sequence_number')->get();

        return [
            'label' => $this->label,
            'icon' => $this->icon,
            'to' => $this->route_name,
            'items' => $children->count() ? MenuSidebarResource::collection($children)->resolve() : null
        ];
    }
}

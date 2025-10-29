<?php

namespace App\Http\Resources\Project;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectStatisticResource extends JsonResource
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
            'plan_site' => $this->plan_site,
            'plan_cctv' => $this->plan_cctv,
            'start_date' => $this->start_date,
            'finish_date' => $this->finish_date,
            'progress' => [
                'total_site' => $this->total_site,
                'total_preconfig' => $this->total_preconfig,
                'installation' => [
                    'done' => $this->total_done_installation,
                    'pending' => $this->total_pending_installation,
                    'total' => $this->total_done_installation + $this->total_pending_installation
                ],
                'stream_config' => [
                    'done' => $this->total_done_streaming,
                    'pending' => $this->total_pending_streaming,
                    'total' => $this->total_config_streaming
                ],
                'analytic_config' => [
                    'done' => $this->total_done_analytic,
                    'pending' => $this->total_pending_analytic,
                    'total' => $this->total_config_analytic
                ]
            ],
        ];
    }
}

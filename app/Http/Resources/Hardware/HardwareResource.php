<?php

namespace App\Http\Resources\Hardware;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HardwareResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'po_number' => $this->po_number,
            'serial_number' => $this->serial_number,
            'category' => $this->component->code ?? $this->category,
            'category_uuid' => $this->component->uuid,

            'brand' => $this->whenLoaded('brand', function () {
                return (new BrandResource($this->brand))->resolve();
            }),

            'status' => (new HardwareStatusResource($this->status))->resolve(),

            'remarks' => $this->remarks,
            'used_by_server_ips' => $this->getServerIps(),
            'model' => $this->model,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    protected function getServerIps(): ?string
    {
        return match ($this->category ?? strtolower($this->component->name)) {
            'cpu' => $this->serversAsCpu->first()?->ip_address,
            'gpu' => $this->serversAsGpu->first()?->ip_address,
            'ram' => $this->serversAsRam->first()?->ip_address,
            'ssd' => $this->serversAsSsd->first()?->ip_address,
            'mobo' => $this->serversAsMotherboard->first()?->ip_address,
            'nic' => $this->serversAsNetworkCard->first()?->ip_address,
            'psu' => $this->serversAsPowerSupply->first()?->ip_address,
            'lc'  => $this->serversAsLiquidCooling->first()?->ip_address,
            default => null,
        };
    }
}

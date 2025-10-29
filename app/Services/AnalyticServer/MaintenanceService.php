<?php

namespace App\Services\AnalyticServer;

use App\Models\AnalyticServer;
use App\Models\Hardware;
use App\Models\Hardware\HardwareStatus;
use Illuminate\Support\Facades\DB;

class MaintenanceService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handling update maintenace of analytic server resource
     */
    public function handle(array $replacements, AnalyticServer $analyticServer): void
    {
        $replacements = $this->filter($replacements);

        DB::transaction(function () use ($replacements, $analyticServer) {
            $update_data = [];

            $analyticServer->maintenance_logs()->create([
                'cpu_id' => $analyticServer->cpu_id,
                'gpu_id' => $analyticServer->gpu_id,
                'ram_id' => $analyticServer->ram_id,
                'ssd_id' => $analyticServer->ssd_id,
                'mobo_id' => $analyticServer->mobo_id,
                'nic_id' => $analyticServer->nic_id,
                'psu_id' => $analyticServer->psu_id,
                'lc_id' => $analyticServer->lc_id
            ]);

            foreach ($replacements as $key => $component) {
                if ($component['current_uuid']) {
                    $hardware = Hardware::findByUuid($component['current_uuid']);
                    $hardware->status_id = HardwareStatus::findByUuid($component['status_uuid'])->id;
                    $hardware->save();
                }

                $update_data[$key . '_id'] = Hardware::findByUuid($component['replacement_uuid'])->id;
            }

            $analyticServer->update($update_data);
        });
    }

    /**
     * Filtering only selected item
     */
    protected function filter(array $replacements): array
    {
        return array_filter($replacements, fn($item) => $item['status_uuid']);
    }
}

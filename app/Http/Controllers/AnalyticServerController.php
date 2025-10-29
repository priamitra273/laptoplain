<?php

namespace App\Http\Controllers;

use App\Exports\ServerExport;
use App\Http\Requests\AnalyticServer\MaintenanceServerRequest;
use App\Http\Requests\AnalyticServer\ServerImportRequest;
use App\Models\AnalyticServer;
use App\Models\Hardware;
use Illuminate\Http\Request;
use App\Http\Requests\AnalyticServer\StoreServerRequest;
use App\Http\Requests\AnalyticServer\UpdateServerRequest;
use App\Http\Resources\Hardware\HardwareResource;
use App\Http\Resources\Hardware\HardwareStatusResource;
use App\Imports\ServerStoreImport;
use App\Models\Hardware\HardwareStatus;
use App\Models\HardwareComponent;
use App\Services\AnalyticServer\MaintenanceService;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class AnalyticServerController extends Controller
{
    public function __construct(
        protected MaintenanceService $maintenanceService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $servers = AnalyticServer::with(['cpu', 'gpu', 'ram', 'ssd', 'motherboard', 'nic', 'psu', 'lc'])->get();

        // $hardware = Hardware::unused()->whereRelation('status', 'level', '<', 3)->get();

        $hardware = Hardware::with([
            'serversAsCpu',
            'serversAsGpu',
            'serversAsRam',
            'serversAsSsd',
            'serversAsMotherboard',
            'serversAsNetworkCard',
            'serversAsPowerSupply',
            'serversAsLiquidCooling',
            'component',
            'brand',
            'status'
        ])
            ->unused()
            ->whereRelation('status', 'level', '<', 3)
            ->latest()
            ->get();

        // dd($servers);

        $components = HardwareComponent::select('uuid', 'code', 'name')->get();
        $hardware_status = HardwareStatus::whereIn('id', [3, 12])->get();

        return Inertia::render('analyticserver/AnalyticServer', [
            'servers' => $servers,
            'hardware' => HardwareResource::collection($hardware)->resolve(),
            'components' => $components,
            'hardware_status' => HardwareStatusResource::collection($hardware_status)->resolve()
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreServerRequest $request)
    {
        $data = $request->getSafeDataWithConvertedIds();
        AnalyticServer::create($data);

        return to_route('analytic-server.index');
    }

    public function verify_import(Request $request)
    {
        $request->validate([
            'type' => ['required', 'string', 'in:INSERT,UPDATE'],
            'file' => ['required', 'file', 'mimes:xls,xlsx,csv']
        ]);

        $import = new ServerStoreImport();

        $header = $import->getHeader();
        $data = $import->toArray($request->file('file'));
        $data = $import->getValidatedData($data[0]);

        return Inertia::render('analyticserver/ServerVerifyImport', [
            'servers' => $data,
            'header' => $header
        ]);
    }

    public function import(ServerImportRequest $request)
    {
        foreach ($request->safe()->servers as $server) {
            $serverData = [
                'ip_address' => $server['ip_address'],
                'lisence' => $server['lisence'],
                'is_alive' => $server['is_alive'],
                'is_active' => $server['is_active'],

                'cpu_id' => $server['cpu_id'] ?? null,
                'gpu_id' => $server['gpu_id'] ?? null,
                'ram_id' => $server['ram_id'] ?? null,
                'ssd_id' => $server['ssd_id'] ?? null,
                'mobo_id' => $server['mobo_id'] ?? null,
                'nic_id' => $server['nic_id'] ?? null,
                'psu_id' => $server['psu_id'] ?? null,
                'lc_id' => $server['lc_id'] ?? null,
            ];

            AnalyticServer::create($serverData);
        }

        return to_route('analytic-server.index');
    }

    /**
     * Export the resource
     */
    public function export()
    {
        return Excel::download(new ServerExport, 'servers-' . now()->format('Y-m-d') . '.xlsx');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateServerRequest $request, AnalyticServer $analyticServer)
    {
        $data = $request->getSafeDataWithConvertedIds();
        $analyticServer->update($data);

        return to_route('analytic-server.index');
    }

    public function maintenance(MaintenanceServerRequest $request, AnalyticServer $analytic_server)
    {
        $this->maintenanceService->handle($request->validated('replacements'), $analytic_server);

        return to_route('analytic-server.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AnalyticServer $analyticServer)
    {
        $analyticServer->delete();

        return to_route('analytic-server.index');
    }
}

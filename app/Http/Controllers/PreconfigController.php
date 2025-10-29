<?php

namespace App\Http\Controllers;

use App\Exports\PreconfigExport;
use App\Http\Requests\Site\PreconfigImportRequest;
use App\Http\Requests\Site\PreconfigTableRequest;
use App\Http\Requests\Site\PreconfigUpdateRequest;
use App\Http\Resources\Site\PreconfigResource;
use App\Imports\PreconfigStoreImport;
use App\Models\Device;
use App\Models\Regency;
use App\Models\Site;
use App\Services\PreconfigService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class PreconfigController extends Controller
{
    public function __construct(
        protected PreconfigService $service
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $regencies = Regency::all('uuid', 'name');

        return Inertia::render('preconfig/Preconfig', [
            'regencies' => $regencies
        ]);
    }

    /**
     * Get listing of the resource for datatable.
     * 
     * @return response json
     */
    public function datatable(PreconfigTableRequest $request)
    {
        $data = $this->service->filter($request->validated())->paginate($request->rows ?? 25);

        return PreconfigResource::collection($data);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $uuid)
    {
        $data = Site::with(['cctv', 'project', 'status', 'regency', 'replacement'])
            ->withMax('cctv as last_update', 'updated_preconfig_at')
            ->withCount('cctv as total_cctv')
            ->whereUuid($uuid)
            ->firstOrFail();

        return new PreconfigResource($data);
    }

    public function verify_import(Request $request)
    {
        $request->validate([
            'type' => ['required', 'string', 'in:INSERT,UPDATE'],
            'file' => ['required', 'file', 'mimes:xls,xlsx,csv']
        ]);

        $import = new PreconfigStoreImport();
        $header = $import->getHeader();
        $data = $import->toArray($request->file('file'));
        $data = $import->getValidatedData($data[0]);

        return Inertia::render('preconfig/PreconfigVerifyImport', [
            'preconfigs' => $data,
            'header' => $header,
            'type' => $request->type
        ]);
    }

    public function import(PreconfigImportRequest $request)
    {

        foreach ($request->safe()->preconfigs as $preconfig) {
            if (
                empty($preconfig['site_uuid']) ||
                empty($preconfig['device_id']) ||
                empty($preconfig['device_uuid'])
            ) {
                continue;
            }

            $site = Site::where('uuid', $preconfig['site_uuid'])->first();
            if (!$site) {
                continue;
            }

            $site->update([
                'site_name' => $preconfig['site_name'],
                'latitude' => $preconfig['latitude'],
                'longitude' => $preconfig['longitude'],
                'project_id' => $preconfig['project_id'],
                'regency_id' => $preconfig['regency_id'],
            ]);

            $cctvData = [[
                'cctv_id' => $preconfig['cctv_id'] ?? null,
                'cctv_name' => $preconfig['cctv_name'],
                'device_id' => $preconfig['device_uuid'],
            ]];

            $this->service->updateOrCreate($cctvData, $site);
        }

        return to_route('preconfig.index');
    }

    public function export()
    {
        return Excel::download(new PreconfigExport, 'preconfigs-' . now()->format('Y-m-d') . '.xlsx');
    }


    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $uuid)
    {
        $site = Site::with('cctv')->where('uuid', $uuid)->firstOrFail();

        $devices = Device::select('uuid', 'mac_address', 'ip_dhcp', 'ip_static', 'created_at', 'updated_at')
            ->whereDoesntHave('cctv', fn(Builder $query) => $query->whereNot('site_id', $site->id))
            ->get();

        return Inertia::render('preconfig/PreconfigForm', [
            'devices' => $devices,
            'preconfig' => (new PreconfigResource($site))->resolve()
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PreconfigUpdateRequest $request, string $uuid)
    {
        $site = Site::where('uuid', $uuid)->firstOrFail();

        $this->service->updateOrCreate($request->cctv, $site);

        return to_route('preconfig.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}

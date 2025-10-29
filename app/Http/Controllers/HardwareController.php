<?php

namespace App\Http\Controllers;

use App\Exports\HardwareExport;
use App\Http\Requests\hardware\HardwareImportRequest as hardwareHardwareImportRequest;
use App\Http\Resources\Hardware\HardwareResource;
use App\Models\Hardware;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use App\Imports\HardwareStoreImport;
use App\Http\Requests\Hardware\HardwareImportRequest;
use App\Http\Requests\Hardware\HardwareStoreRequest;
use App\Http\Requests\Hardware\HardwareUpdateRequest;
use App\Http\Resources\Hardware\BrandResource;
use App\Http\Resources\Hardware\HardwareComponentResource;
use App\Models\Hardware\Brand;
use App\Models\HardwareComponent;
use Maatwebsite\Excel\Facades\Excel;

class HardwareController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
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
        ])->latest()->get();

        $components = HardwareComponent::all();
        $brands = Brand::all();

        $models = Hardware::select('b.uuid as brand_uuid', 'c.uuid as component_uuid', 'model')
            ->joinRelationshipUsingAlias('brand', 'b')
            ->joinRelationshipUsingAlias('component', 'c')
            ->whereNotNull('model')
            ->groupBy(['b.uuid', 'c.uuid', 'model'])
            ->get();

        return Inertia::render('hardware/Hardware', [
            'hardware' => HardwareResource::collection($hardware)->resolve(),
            'components' => HardwareComponentResource::collection($components)->resolve(),
            'brands' => BrandResource::collection($brands)->resolve(),
            'models' => $models
        ]);
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
    public function store(HardwareStoreRequest $request)
    {
        Hardware::create($this->getInputFromRequest($request));

        return to_route('hardware.index');
    }

    public function verify_import(Request $request)
    {
        $request->validate([
            'type' => ['required', 'string', 'in:INSERT,UPDATE'],
            'file' => ['required', 'file', 'mimes:xls,xlsx,csv']
        ]);

        $import = new HardwareStoreImport();

        $header = $import->getHeader();
        $data = $import->toArray($request->file('file'));
        $data = $import->getValidatedData($data[0]);

        return Inertia::render('hardware/HardwareVerifyImport', [
            'hardwares' => $data,
            'header' => $header
        ]);
    }

    public function import(HardwareImportRequest $request)
    {
        foreach ($request->safe()->hardwares as $hardware) {
            $brand = Brand::firstOrCreate(
                ['name' => $hardware['brand']],
                ['name' => $hardware['brand']]
            );

            $hardwareData = [
                'po_number' => $hardware['po_number'],
                'serial_number' => $hardware['serial_number'],
                'component_id' => HardwareComponent::where('code', $hardware['category'])->first()->id,
                'brand_id' => $brand->id,
                'model' => $hardware['model'],
                'remarks' => $hardware['remarks'] ?? null,
            ];

            Hardware::create($hardwareData);
        }

        return to_route('hardware.index');
    }

    public function export()
    {
        return Excel::download(new HardwareExport, 'hardwares-' . now()->format('Y-m-d') . '.xlsx');
    }

    /**
     * Display the specified resource.
     */
    public function show(Hardware $hardware)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Hardware $hardware)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(HardwareUpdateRequest $request, Hardware $hardware)
    {
        $hardware->update($this->getInputFromRequest($request));

        return to_route('hardware.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Hardware $hardware)
    {
        $hardware->delete();

        return to_route('hardware.index');
    }

    protected function getInputFromRequest(HardwareStoreRequest|HardwareUpdateRequest $request)
    {
        $brand = Brand::firstOrCreate(
            ['uuid' => $request->validated('brand')['uuid']],
            ['name' => $request->validated('brand')['name']]
        );

        return [
            'po_number' => $request->validated('po_number'),
            'serial_number' => $request->validated('serial_number'),
            'remarks' => $request->validated('remarks'),
            'component_id' => HardwareComponent::findByUuid($request->validated('category'))->id,
            'brand_id' => $brand->id,
            'model' => str($request->validated('model'))->title(),
        ];
    }
}

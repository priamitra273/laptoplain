<?php

namespace App\Http\Controllers;

use App\Exports\DeviceExport;
use App\Http\Requests\Device\DeviceImportRequest;
use App\Http\Resources\Device\DeviceResource;
use App\Imports\DeviceStoreImport;
use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class DeviceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $devices = Device::with('cctv.device')->latest()->get();

        // dd(DeviceResource::collection($devices)->resolve());

        return Inertia::render('device/Device', [
            'devices' => DeviceResource::collection($devices)->resolve()
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
    public function store(Request $request)
    {
        $request->validate([
            'mac_address' => 'required|mac_address|unique:App\Models\Device',
            'ip_dhcp' => 'required|ip',
            'ip_static' => 'nullable|ip'
        ]);

        Device::create([
            'mac_address' => $request->mac_address,
            'ip_dhcp' => $request->ip_dhcp,
            'ip_static' => $request->ip_static
        ]);

        return to_route('device.index');
    }

    public function verify_import(Request $request)
    {
        $request->validate([
            'type' => ['required', 'string', 'in:INSERT,UPDATE'],
            'file' => ['required', 'file', 'mimes:xls,xlsx,csv']
        ]);

        $import = new DeviceStoreImport();

        $header = $import->getHeader();
        $data = $import->toArray($request->file('file'));
        $data = $import->getValidatedData($data[0]);

        return Inertia::render('device/DeviceVerifyImport', [
            'devices' => $data,
            'header' => $header
        ]);
    }

    public function import(DeviceImportRequest $request)
    {
        foreach ($request->safe()->devices as $device) {
            $deviceData = [
                'mac_address' => $device['mac_address'],
                'ip_dhcp' => $device['ip_dhcp'],
                'ip_static' => $device['ip_static'] ?? null,
            ];

            Device::create($deviceData);
        }

        return to_route('device.index');
    }

    public function export()
    {
        return Excel::download(new DeviceExport, 'devices-' . now()->format('Y-m-d') . '.xlsx');
    }

    /**
     * Display the specified resource.
     */
    public function show(Device $device)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Device $device)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Device $device)
    {
        $request->validate([
            'mac_address' => ['required', 'mac_address', Rule::unique('devices')->ignore($device->id)->withoutTrashed()],
            'ip_dhcp' => 'required|ip',
            'ip_static' => 'nullable|ip'
        ]);

        $device->update([
            'mac_address' => $request->mac_address,
            'ip_dhcp' => $request->ip_dhcp,
            'ip_static' => $request->ip_static
        ]);

        return to_route('device.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Device $device)
    {
        $device->delete();

        return to_route('device.index');
    }
}

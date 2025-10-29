<?php

namespace App\Http\Controllers;

use App\Exports\AnalyticExport;
use App\Http\Requests\Analytic\AnalyticImportRequest;
use App\Http\Requests\Analytic\AnalyticTableRequest;
use App\Http\Requests\Analytic\AnalyticUpdateRequest;
use App\Http\Resources\CCTV\AnalyticResource;
use App\Imports\AnalyticStoreImport;
use App\Models\AnalyticCategory;
use App\Models\AnalyticServer;
use App\Models\Cctv;
use App\Models\Department;
use App\Services\AnalyticService;
use Illuminate\Http\Request;
use Illuminate\Support\ValidatedInput;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class AnalyticController extends Controller
{
    public function __construct(
        protected AnalyticService $service
    ) {}

    public function index()
    {
        return Inertia::render('analytic/Analytic');
    }

    public function datatable(AnalyticTableRequest $request)
    {
        $rows = $request->rows ?? 25;
        $data = $this->service->get($rows, $request->validated());

        return AnalyticResource::collection($data);
    }

    public function verify_import(Request $request)
    {
        $request->validate([
            'type' => ['required', 'string', 'in:INSERT,UPDATE'],
            'file' => ['required', 'file', 'mimes:xls,xlsx,csv']
        ]);

        $import = new AnalyticStoreImport();
        $header = $import->getHeader();
        $data = $import->toArray($request->file('file'));
        $data = $import->getValidatedData($data[0]);

        return Inertia::render('analytic/AnalyticVerifyImport', [
            'analytics' => $data,
            'header' => $header,
            'type' => $request->type
        ]);
    }

    public function import(AnalyticImportRequest $request)
    {
        foreach ($request->safe()->analytics as $analytic) {
            if (empty($analytic['cctv_id'])) {
                continue;
            }

            $cctv = Cctv::find($analytic['cctv_id']);
            if (!$cctv) {
                continue;
            }

            $category = AnalyticCategory::where('name', $analytic['categories_name'])->first();
            $department = Department::where('name', $analytic['department_name'])->first();
            $server = AnalyticServer::where('ip_address', $analytic['server'])->first();

            if (!$category || !$department || !$server) {
                continue;
            }

            $this->service->update(new ValidatedInput([
                'category_uuid' => $category->uuid,
                'department_uuid' => $department->uuid,
                'server_uuid' => $server->uuid,
                'link_embed_bb' => $analytic['link_embed_bb'] ?? null,
                'polygon' => $analytic['polygon'] ?? null,
                'threshold' => $analytic['threshold'] ?? null,
            ]), $cctv);
        }

        return to_route('analytic.index');
    }

    public function export()
    {
        return Excel::download(new AnalyticExport, 'analytics-' . now()->format('Y-m-d') . '.xlsx');
    }

    public function edit(string $uuid)
    {
        $cctv = $this->service->builder()->where('cctvs.uuid', $uuid)->firstOrFail();

        $departments = Department::select('uuid', 'name', 'created_at', 'updated_at')->get();
        $categories = AnalyticCategory::select('uuid', 'type', 'name', 'has_threshold', 'has_polygon', 'created_at', 'updated_at')->get();
        $servers = AnalyticServer::select('uuid', 'ip_address', 'lisence', 'is_alive', 'is_active')->get();

        return Inertia::render('analytic/AnalyticForm', [
            'analytic' => (new AnalyticResource($cctv))->resolve(),
            'departments' => $departments,
            'categories' => $categories,
            'servers' => $servers
        ]);
    }

    public function thumbnail(string $uuid)
    {
        $cctv = $this->service->builder()->where('cctvs.uuid', $uuid)->firstOrFail();

        $filepath = $this->service->getThumbnail($cctv);

        return response()->file($filepath);
    }

    public function update(AnalyticUpdateRequest $request, string $uuid)
    {
        $cctv = Cctv::whereNotNull('analytic_status_id')
            ->whereHas('analytic_status')
            ->whereHas('site.project')
            ->whereHas('site.regency')
            ->whereHas('site.status')
            ->whereHas('device')
            ->whereUuid($uuid)
            ->first();

        $this->service->update($request->safe(), $cctv);

        return to_route('analytic.index');
    }
}

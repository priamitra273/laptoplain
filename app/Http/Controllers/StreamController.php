<?php

namespace App\Http\Controllers;

use App\Exports\StreamExport;
use App\Http\Requests\Stream\StreamImportRequest;
use App\Http\Requests\Stream\StreamTableRequest;
use App\Http\Requests\Stream\StreamUpdateRequest;
use App\Http\Resources\CCTV\StreamResource;
use App\Imports\StreamStoreImport;
use App\Models\Cctv;
use App\Services\StreamService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\ValidatedInput;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class StreamController extends Controller
{
    public function __construct(
        protected StreamService $service
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('stream/Stream');
    }

    /**
     * Listing of stream resource.
     * 
     * @return response json
     */
    public function datatable(StreamTableRequest $request)
    {
        return StreamResource::collection($this->service->get($request->rows ?? 25, $request->validated()));
    }

    public function health(Request $request)
    {
        $request->validate([
            'ip_static' => 'nullable|ipv4'
        ]);

        $result = Process::run(env('PING_PATH', 'ping') . ' -c 1 ' . $request->ip_static);

        return response()->json([
            'success' => true,
            'message' => 'Success health checking',
            'results' => [
                'ip_static' => $result->successful()
            ]
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
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    public function verify_import(Request $request)
    {
        $request->validate([
            'type' => ['required', 'string', 'in:INSERT,UPDATE'],
            'file' => ['required', 'file', 'mimes:xls,xlsx,csv']
        ]);

        $import = new StreamStoreImport();
        $header = $import->getHeader();
        $data = $import->toArray($request->file('file'));
        $data = $import->getValidatedData($data[0]);

        return Inertia::render('stream/StreamVerifyImport', [
            'streams' => $data,
            'header' => $header,
            'type' => $request->type
        ]);
    }

    public function import(StreamImportRequest $request)
    {
        foreach ($request->safe()->streams as $stream) {
            if (empty($stream['cctv_id'])) {
                continue;
            }

            $cctv = Cctv::find($stream['cctv_id']);
            if (!$cctv) {
                continue;
            }

            $this->service->update(new ValidatedInput([
                'cctv_name' => $stream['cctv_name'],
                'ip_static' => $stream['ip_static'] ?? null,
                'ip_flussonic' => $stream['ip_flussonic'] ?? null,
                'link_embed' => $stream['link_embed'] ?? null,
                'link_embed_nonrelay' => $stream['link_embed_nonrelay'] ?? null,
                'link_rtsp' => $stream['link_rtsp'],
            ]), $cctv);
        }

        return to_route('stream.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $uuid)
    {
        $stream = $this->service->builder()->where('cctvs.uuid', $uuid)->firstOrFail();

        return Inertia::render('stream/StreamForm', [
            'stream' => (new StreamResource($stream))->resolve()
        ]);
    }

    public function export()
    {
        return Excel::download(new StreamExport, 'streams-' . now()->format('Y-m-d') . '.xlsx');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StreamUpdateRequest $request, string $uuid)
    {
        $stream = Cctv::whereUuid($uuid)
            ->whereNotNull('streaming_status_id')
            ->whereHas('device')
            ->whereHas('site.status')
            ->whereHas('site.regency')
            ->whereHas('site.project')
            ->firstOrFail();

        $this->service->update($request->safe(), $stream);

        return to_route('stream.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}

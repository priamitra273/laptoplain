<?php

namespace App\Http\Controllers;

use App\Enums\SiteStatus as EnumsSiteStatus;
use App\Http\Requests\Site\InstallationStoreRequest;
use App\Http\Requests\Site\InstallationTableRequest;
use App\Http\Resources\Site\InstallationResource;
use App\Http\Resources\Site\SiteHistoryResource;
use App\Models\Regency;
use App\Models\Site;
use App\Models\SiteStatus;
use App\Services\InstallationService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InstallationController extends Controller
{
    public function __construct(
        protected InstallationService $service
    ){}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $regencies = Regency::all('uuid', 'name');
        
        return Inertia::render('installation/Installation', [
            'regencies' => $regencies
        ]);
    }

    /**
     * Get listing of the resource for datatable.
     * 
     * @return response json
     */
    public function datatable(InstallationTableRequest $request)
    {
        $data = $this->service->filter($request->validated())->paginate($request->rows ?? 25);

        return InstallationResource::collection($data);
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

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $uuid)
    {
        $data = Site::with(['cctv', 'project', 'status', 'regency', 'replacement'])
            ->whereUuid($uuid)
            ->firstOrFail();

        $statuses = SiteStatus::select('uuid', 'name')
            ->whereNot('id', EnumsSiteStatus::OPEN)
            ->orderBy('id')
            ->get();

        return Inertia::render('installation/InstallationForm', [
            'statuses' => $statuses,
            'installation' => (new InstallationResource($data))->resolve()
        ]);
    }

    /**
     * Get listing of site's log.
     * 
     * @return response json
     */
    public function log(Request $request, Site $site)
    {
        $histories = $site->histories()->orderByDesc('created_at')->get();

        return response()->json([
            'success' => true,
            'message' => 'Success get data.',
            'data' => SiteHistoryResource::collection($histories)
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(InstallationStoreRequest $request, string $uuid)
    {
        $site = Site::whereUuid($uuid)->firstOrFail();

        $this->service->store($request->safe(), $site);

        return to_route('installation.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}

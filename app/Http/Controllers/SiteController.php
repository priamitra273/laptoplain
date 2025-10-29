<?php

namespace App\Http\Controllers;

use App\Enums\SiteStatus;
use App\Exports\SiteExport;
use App\Http\Requests\Site\SiteImportRequest;
use App\Http\Requests\Site\SiteStoreRequest;
use App\Http\Requests\Site\SiteTableRequest;
use App\Http\Requests\SiteUpdateRequest;
use App\Http\Resources\Site\SiteCollection;
use App\Http\Resources\Site\SiteHistoryResource;
use App\Http\Resources\Site\SiteResource;
use App\Imports\SiteStoreImport;
use App\Models\Device;
use App\Models\Project;
use App\Models\Regency;
use App\Models\Site;
use App\Models\SiteHistory;
use App\Models\SiteStatus as ModelsSiteStatus;
use App\Services\SiteService;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class SiteController extends Controller
{
    public function __construct(
        protected SiteService $service
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $regencies = Regency::all('uuid', 'name');

        return Inertia::render('site/Site', [
            'regencies' => $regencies
        ]);
    }

    /**
     * Get listing of the resource for datatable.
     * 
     * @return response json
     */
    public function datatable(SiteTableRequest $request)
    {
        return SiteResource::collection($this->service->getPaginateData($request->rows ?? 25, $request->validated()));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $projects = Project::select([
            'uuid',
            'name',
            'start_date',
            'finish_date',
            'plan_site',
            'plan_cctv',
            'created_at',
            'updated_at'
        ])
            ->orderBy('id')
            ->get();

        $regencies = Regency::select('uuid', 'name')->get();

        $dismantle_sites = $this->service->getBuilder()
            ->whereDoesntHave('replaced_by')
            ->where('sites.site_status_id', SiteStatus::DISMANTLE)
            ->get();

        // $devices = Device::select('uuid', 'mac_address', 'ip_dhcp', 'ip_static', 'created_at', 'updated_at')
        //     ->whereDoesntHave('cctv')
        //     ->get();

        // $status = ModelsSiteStatus::select('uuid', 'name')->whereIn('id', [
        //     SiteStatus::OPEN,
        //     SiteStatus::PROGRESS,
        //     SiteStatus::COMPLETE
        // ])->get();

        return Inertia::render('site/SiteFormNew', [
            'projects' => $projects,
            'dismantle_sites' => $dismantle_sites,
            'regencies' => $regencies,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(SiteStoreRequest $request)
    {
        $this->service->store($request->safe());

        return to_route('site.index');
    }

    /**
     * Store or update resource in storage
     */
    public function verify_import(Request $request)
    {
        $request->validate([
            'type' => ['required', 'string', 'in:INSERT,UPDATE'],
            'file' => ['required', 'file', 'mimes:xls,xlsx,csv']
        ]);

        $import = new SiteStoreImport();

        $header = $import->getHeader();
        $data = $import->toArray($request->file('file'));
        $data = $import->getValidatedData($data[0]);

        return Inertia::render('site/SiteVerifyImport', [
            'sites' => $data,
            'header' => $header
        ]);
    }

    public function import(SiteImportRequest $request)
    {
        foreach ($request->safe()->sites as $site) {
            $siteData = [
                'site_category' => $site['site_category'],
                'replacement_to' => $site['replacement_to'] ?? null,
                'regency_id' => $site['regency_id'],
                'project_id' => $site['project_id'],
                'site_id' => $site['site_id'],
                'site_name' => $site['site_name'],
                'latitude' => $site['latitude'],
                'longitude' => $site['longitude'],
                'site_status_id' => $site['site_status_id'],
            ];

            Site::create($siteData);
        }

        return to_route('site.index');
    }

    public function export()
    {
        return Excel::download(new SiteExport, 'sites-' . now()->format('Y-m-d') . '.xlsx');
    }

    /**
     * Display the specified resource.
     */
    public function show(Site $site)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Site $site)
    {
        $projects = Project::select([
            'uuid',
            'name',
            'start_date',
            'finish_date',
            'plan_site',
            'plan_cctv',
            'created_at',
            'updated_at'
        ])
            ->orderBy('id')
            ->get();

        $regencies = Regency::select('uuid', 'name')->get();

        $dismantle_sites = $this->service->getBuilder()
            ->where(function ($q) use ($site) {
                $q->whereDoesntHave('replaced_by')->orWhere('sr.id', $site->replacement_to);
            })
            ->where('sites.site_status_id', SiteStatus::DISMANTLE)
            ->get();

        // $devices = Device::select('uuid', 'mac_address', 'ip_dhcp', 'ip_static', 'created_at', 'updated_at')
        //     ->whereDoesntHave('cctv')
        //     ->orWhereRelation('cctv.site', 'sites.id', $site->id)
        //     ->get();

        // $status = ModelsSiteStatus::select('uuid', 'name')->whereNot('id', SiteStatus::OPEN)->orderBy('id')->get();

        $site->load(['project', 'regency', 'replacement', 'cctv']);

        // $histories = SiteHistory::where('site_id', $site->id)->with('status', 'created_by_user')->get();

        return Inertia::render('site/SiteFormNew', [
            'site' => (new SiteResource($site))->resolve(),
            'projects' => $projects,
            'dismantle_sites' => $dismantle_sites,
            'regencies' => $regencies,
            // 'devices' => $devices,
            // 'status' => $status,
            // 'histories' => SiteHistoryResource::collection($histories)->resolve(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(SiteUpdateRequest $request, Site $site)
    {
        $this->service->update($request->safe(), $site);

        return to_route('site.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Site $site)
    {
        $site->delete();

        return to_route('site.index');
    }
}

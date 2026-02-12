<?php

namespace App\Http\Controllers;

use App\Facades\Sqids;
use App\Http\Requests\MsProjectStatus\MsProjectStatusStoreRequest;
use App\Models\MsProjectStatus;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class MsProjectStatusController extends Controller
{

    public function index(): Response
    {
        $statuses = MsProjectStatus::select([
            'id',
            'name',
            'severity',
            'owned_id',
            'created_by',
            'updated_by',
            'deleted_by'
        ])->orderBy('id')->get();

        $statuses = Sqids::rec_encode_ids_in_list($statuses);

        return Inertia::render('ms_project_status/Index', [
            'statuses' => $statuses,
        ]);
    }


    public function store(MsProjectStatusStoreRequest $request): RedirectResponse
    {
        MsProjectStatus::create($request->validated());

        return redirect()
            ->route('project-status.index')
            ->with('success', 'Project Status has been successfully added.');
    }


    public function update(MsProjectStatusStoreRequest $request, string $encodedId): RedirectResponse
    {
        $id = Sqids::decode($encodedId);
        if (empty($id)) abort(404, 'ID tidak valid.');

        $statuses = MsProjectStatus::findOrFail($id);
        $statuses->update($request->validated());

        return redirect()
            ->route('project-status.index')
            ->with('success', 'Project Status has been successfully updated.');
    }


    public function destroy(string $encodedId): RedirectResponse
    {
        $id = Sqids::decode($encodedId);
        if (empty($id)) abort(404, 'ID tidak valid.');

        $statuses = MsProjectStatus::findOrFail($id);
        $statuses->delete();

        return redirect()
            ->route('project-status.index')
            ->with('success', 'Project Status has been successfully deleted.');
    }
}

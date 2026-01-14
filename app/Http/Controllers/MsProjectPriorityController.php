<?php

namespace App\Http\Controllers;

use App\Facades\Sqids;
use App\Http\Requests\MsProjectPriority\MsProjectPriorityRequest;
use App\Models\MsProjectPriority;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class MsProjectPriorityController extends Controller
{
    public function index(): Response
    {
        $priorities = MsProjectPriority::select([
            'id',
            'name',
            'severity',
            'owned_id',
            'created_by',
            'updated_by',
            'deleted_by'
        ])->orderBy('id')->get();

        $priorities = Sqids::rec_encode_ids_in_list($priorities);

        return Inertia::render('ms_project_priority/Index', [
            'project_priorities' => $priorities,
        ]);
    }

    public function store(MsProjectPriorityRequest $request): RedirectResponse
    {
        MsProjectPriority::create($request->validated());

        return redirect()
            ->route('project-priority.index')
            ->with('success', 'Project Priority has been successfully added.');
    }

    public function update(MsProjectPriorityRequest $request, string $encodedId): RedirectResponse
    {
        $id = Sqids::decode($encodedId);
        if (empty($id)) abort(404, 'ID tidak valid.');

        $priority = MsProjectPriority::findOrFail($id);
        $priority->update($request->validated());

        return redirect()
            ->route('project-priority.index')
            ->with('success', 'Project Priority has been successfully updated.');
    }

    public function destroy(string $encodedId): RedirectResponse
    {
        $id = Sqids::decode($encodedId);
        if (empty($id)) abort(404, 'ID tidak valid.');

        $priority = MsProjectPriority::findOrFail($id);
        $priority->delete();

        return redirect()
            ->route('project-priority.index')
            ->with('success', 'Project Priority has been successfully deleted.');
    }
}

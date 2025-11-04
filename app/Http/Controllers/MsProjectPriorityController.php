<?php

namespace App\Http\Controllers;

use App\Facades\Sqids;
use App\Http\Requests\MsProjectPriority\MsProjectPriorityRequest;
use App\Models\MsProjectPriority;
use Inertia\Inertia;

class MsProjectPriorityController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $msProjectPriorities = MsProjectPriority::select([
            'id',
            'name',
            'severity',
            'owned_id',
            'created_by',
            'updated_by',
            'deleted_by'
        ])->orderBy('id')->get();

        $msProjectPriorities = Sqids::rec_encode_ids_in_list($msProjectPriorities);

        return Inertia::render('ms_project_priority/Index', [
            'project_priorities' => $msProjectPriorities,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('ms_project_priority/ProjectPriorityCreate');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(MsProjectPriorityRequest $request)
    {
        MsProjectPriority::create($request->safe()->toArray());

        return to_route('ms_project_priority.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(MsProjectPriority $msProjectPriority)
    {
        return Inertia::render('ms_project_priority/ProjectPriorityShow', [
            'msProjectPriority' => $msProjectPriority,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(MsProjectPriority $msProjectPriority)
    {
        return Inertia::render('ms_project_priority/ProjectPriorityEdit', [
            'msProjectPriority' => $msProjectPriority,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(MsProjectPriorityRequest $request, MsProjectPriority $msProjectPriority)
    {
        $msProjectPriority->update($request->safe()->toArray());

        return to_route('ms_project_priority.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MsProjectPriority $msProjectPriority)
    {
        $msProjectPriority->delete();

        return to_route('ms_project_priority.index');
    }
}

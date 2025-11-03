<?php

namespace App\Http\Controllers;

use App\Http\Requests\MsTaskStatus\MsTaskStatusStoreRequest;
use App\Models\MsTaskStatus;
use Inertia\Inertia;

class MsTaskStatusController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    public function index()
    {
        $msTaskStatuses = MsTaskStatus::select([
            'id',
            'name',
            'severity',
            'owned_id',
            'created_by',
            'updated_by',
            'deleted_by'
        ])->orderBy('id')->get();

        return Inertia::render('ms_task_status/TaskStatus', [
            'task_statuses' => $msTaskStatuses,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('ms_task_status/TaskStatusCreate');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(MsTaskStatusStoreRequest $request)
    {
        MsTaskStatus::create($request->safe()->toArray());

        return to_route('ms_task_status.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(MsTaskStatus $msTaskStatus)
    {
        return Inertia::render('ms_task_status/TaskStatusShow', [
            'msTaskStatus' => $msTaskStatus,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(MsTaskStatus $msTaskStatus)
    {
        return Inertia::render('ms_task_status/TaskStatusEdit', [
            'msTaskStatus' => $msTaskStatus,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(MsTaskStatusStoreRequest $request, MsTaskStatus $msTaskStatus)
    {
        $msTaskStatus->update($request->safe()->toArray());

        return to_route('ms_task_status.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MsTaskStatus $msTaskStatus)
    {
        $msTaskStatus->delete();

        return to_route('ms_task_status.index');
    }
}

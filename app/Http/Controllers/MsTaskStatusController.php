<?php

namespace App\Http\Controllers;

use App\Http\Requests\MsTaskStatus\MsTaskStatusStoreRequest;
use App\Models\MsTaskStatus;
use Inertia\Inertia;

class MsTaskStatusController extends Controller
{
    

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

        return Inertia::render('ms_task_status/Index', [
            'task_statuses' => $msTaskStatuses,
        ]);
    }

    
    public function create()
    {
        return Inertia::render('ms_task_status/TaskStatusCreate');
    }

    
    public function store(MsTaskStatusStoreRequest $request)
    {
        MsTaskStatus::create($request->safe()->toArray());

        return to_route('ms_task_status.index');
    }

    public function show(MsTaskStatus $msTaskStatus)
    {
        return Inertia::render('ms_task_status/TaskStatusShow', [
            'msTaskStatus' => $msTaskStatus,
        ]);
    }

    
    public function edit(MsTaskStatus $msTaskStatus)
    {
        return Inertia::render('ms_task_status/TaskStatusEdit', [
            'msTaskStatus' => $msTaskStatus,
        ]);
    }

    public function update(MsTaskStatusStoreRequest $request, MsTaskStatus $msTaskStatus)
    {
        $msTaskStatus->update($request->safe()->toArray());

        return to_route('ms_task_status.index');
    }

    public function destroy(MsTaskStatus $msTaskStatus)
    {
        $msTaskStatus->delete();

        return to_route('ms_task_status.index');
    }
}

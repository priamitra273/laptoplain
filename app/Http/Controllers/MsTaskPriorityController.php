<?php

namespace App\Http\Controllers;

use App\Models\MsTaskPriority;
use App\Http\Requests\MsTaskPriority\MsTaskPriorityRequest;
use Inertia\Inertia;

class MsTaskPriorityController extends Controller
{
    public function index()
    {
        $msTaskPriorities = MsTaskPriority::select([
            'id',
            'name',
            'severity',
            'owned_id',
            'created_by',
            'updated_by',
            'deleted_by'
        ])->orderBy('id')->get();

        return Inertia::render('ms_task_priority/Index', [
            'task_priorities' => $msTaskPriorities,
        ]);
    }

    
    public function create()
    {
        //
    }

    
    public function store(MsTaskPriorityRequest $request)
    {
        MsTaskPriority::create($request->safe()->toArray());

        return to_route('ms_task_priority.index');
    }

    
    public function show(MsTaskPriority $msTaskPriority)
    {
        //
    }

    
    public function edit(MsTaskPriority $msTaskPriority)
    {
        //
    }

    public function update(MsTaskPriorityRequest $request, MsTaskPriority $msTaskPriority)
    {
        $msTaskPriority->update($request->safe()->toArray());

        return to_route('ms_task_priority.index');
    }

    public function destroy(MsTaskPriority $msTaskPriority)
    {
        $msTaskPriority->delete();

        return to_route('ms_task_priority.index');
    }
}

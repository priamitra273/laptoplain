<?php

namespace App\Http\Controllers;

use App\Models\MsTaskPriority;
use App\Http\Requests\MsTaskPriority\MsTaskPriorityRequest;
use Inertia\Inertia;

class MsTaskPriorityController extends Controller
{
    /**
     * Display a listing of the resource.
     */
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

        return Inertia::render('ms_task_priority/TaskPriority', [
            'task_priorities' => $msTaskPriorities,
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
    public function store(MsTaskPriorityRequest $request)
    {
        MsTaskPriority::create($request->safe()->toArray());

        return to_route('ms_task_priority.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(MsTaskPriority $msTaskPriority)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(MsTaskPriority $msTaskPriority)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(MsTaskPriorityRequest $request, MsTaskPriority $msTaskPriority)
    {
        $msTaskPriority->update($request->safe()->toArray());

        return to_route('ms_task_priority.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MsTaskPriority $msTaskPriority)
    {
        $msTaskPriority->delete();

        return to_route('ms_task_priority.index');
    }
}

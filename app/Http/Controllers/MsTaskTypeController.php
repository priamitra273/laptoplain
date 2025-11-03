<?php

namespace App\Http\Controllers;

use App\Http\Requests\MsTaskType\MsTaskTypeStoreRequest;
use App\Models\MsTaskType;
use Inertia\Inertia;

class MsTaskTypeController extends Controller
{
     /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $msTaskTypes = MsTaskType::select([
            'id',
            'name',
            'severity',
            'owned_id',
            'created_by',
            'updated_by',
            'deleted_by'
        ])->orderBy('id')->get();
        
        return Inertia::render('ms_task_type/TaskType', [
            'task_types' => $msTaskTypes,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('ms_task_type/TaskTypeCreate');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(MsTaskTypeStoreRequest $request)
    {
        MsTaskType::create($request->safe()->toArray());

        return to_route('ms_task_type.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(MsTaskType $msTaskType)
    {
        return Inertia::render('ms_task_type/TaskTypeShow', [
            'msTaskType' => $msTaskType,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(MsTaskType $msTaskType)
    {
        return Inertia::render('ms_task_type/TaskTypeEdit', [
            'msTaskType' => $msTaskType,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(MsTaskTypeStoreRequest $request, MsTaskType $msTaskType)
    {
        $msTaskType->update($request->safe()->toArray());

        return to_route('ms_task_type.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MsTaskType $msTaskType)
    {
        $msTaskType->delete();

        return to_route('ms_task_type.index');
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\MsTaskType\MsTaskTypeStoreRequest;
use App\Models\MsTaskType;
use Inertia\Inertia;

class MsTaskTypeController extends Controller
{
    
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
        
        return Inertia::render('ms_task_type/Index', [
            'task_types' => $msTaskTypes,
        ]);
    }

    
    public function create()
    {
        return Inertia::render('ms_task_type/TaskTypeCreate');
    }

   
    public function store(MsTaskTypeStoreRequest $request)
    {
        MsTaskType::create($request->safe()->toArray());

        return to_route('ms_task_type.index');
    }

    
    public function show(MsTaskType $msTaskType)
    {
        return Inertia::render('ms_task_type/TaskTypeShow', [
            'msTaskType' => $msTaskType,
        ]);
    }

   
    public function edit(MsTaskType $msTaskType)
    {
        return Inertia::render('ms_task_type/TaskTypeEdit', [
            'msTaskType' => $msTaskType,
        ]);
    }

    
    
    public function update(MsTaskTypeStoreRequest $request, MsTaskType $msTaskType)
    {
        $msTaskType->update($request->safe()->toArray());

        return to_route('ms_task_type.index');
    }

    
    public function destroy(MsTaskType $msTaskType)
    {
        $msTaskType->delete();

        return to_route('ms_task_type.index');
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\MsProjectPriority\MsProjectPriorityRequest;
use App\Models\MsProjectPriority;
use Inertia\Inertia;

class MsProjectPriorityController extends Controller
{
   
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
        
        return Inertia::render('ms_project_priority/Index', [
            'project_priorities' => $msProjectPriorities,
        ]);
    }

    
    public function create()
    {
        return Inertia::render('ms_project_priority/ProjectPriorityCreate');
    }

  
    public function store(MsProjectPriorityRequest $request)
    {
        MsProjectPriority::create($request->safe()->toArray());

        return to_route('ms_project_priority.index');
    }

   
    public function show(MsProjectPriority $msProjectPriority)
    {
        return Inertia::render('ms_project_priority/ProjectPriorityShow', [
            'msProjectPriority' => $msProjectPriority,
        ]);
    }

   
    public function edit(MsProjectPriority $msProjectPriority)
    {
        return Inertia::render('ms_project_priority/ProjectPriorityEdit', [
            'msProjectPriority' => $msProjectPriority,
        ]);
    }

    
    public function update(MsProjectPriorityRequest $request, MsProjectPriority $msProjectPriority)
    {
        $msProjectPriority->update($request->safe()->toArray());

        return to_route('ms_project_priority.index');
    }

    
    public function destroy(MsProjectPriority $msProjectPriority)
    {
        $msProjectPriority->delete();

        return to_route('ms_project_priority.index');
    }
}

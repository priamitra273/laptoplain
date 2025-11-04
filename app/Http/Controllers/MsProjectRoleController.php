<?php

namespace App\Http\Controllers;

use App\Models\MsProjectRole;
use App\Http\Requests\MsProjectRole\MsProjectRoleRequest;
use Inertia\Inertia;

class MsProjectRoleController extends Controller
{
    
    public function index()
    {
        $msProjectRoles = MsProjectRole::select([
            'id',
            'name',
            'owned_id',
            'created_by',
            'updated_by',
            'deleted_by'
        ])->orderBy('id')->get();

        return Inertia::render('ms_project_role/Index', [
            'project_roles' => $msProjectRoles,
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
    public function store(MsProjectRoleRequest $request)
    {
        MsProjectRole::create($request->safe()->toArray());

        return to_route('ms_project_role.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(MsProjectRole $msProjectRole)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(MsProjectRole $msProjectRole)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(MsProjectRoleRequest $request, MsProjectRole $msProjectRole)
    {
        $msProjectRole->update($request->safe()->toArray());

        return to_route('ms_project_role.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MsProjectRole $msProjectRole)
    {
        $msProjectRole->delete();

        return to_route('ms_project_role.index');
    }
}

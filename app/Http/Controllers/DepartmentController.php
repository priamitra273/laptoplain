<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;
use Ramsey\Uuid\Guid\Guid;

class DepartmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $departments = Department::select('uuid', 'name', 'created_at', 'created_by')->get();
        
        return inertia('department/Department', compact('departments'));
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
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255'
        ]);

        Department::create(['name' => $request->name]);

        return to_route('department.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(Department $department)
    {
        // dd($department);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Department $department)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Department $department)
    {
        $request->validate(['name' => 'required|string|max:255']);

        $department->update(['name' => $request->name]);

        return to_route('department.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Department $department)
    {
        $department->delete();

        return to_route('department.index');
    }
}

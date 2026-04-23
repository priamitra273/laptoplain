<?php

namespace App\Http\Controllers;

use App\Facades\Sqids;
use App\Models\TaskCategory;
use App\Http\Requests\TaskCategory\TaskCategoryRequest;
use Inertia\Inertia;

class TaskCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $taskCategories = TaskCategory::select([
            'id',
            'name',
            'icon',
            'severity',
            'created_by',
            'updated_by',
            'deleted_by'
        ])->orderBy('id')->get();

        $taskCategories = Sqids::rec_encode_ids_in_list($taskCategories);

        return Inertia::render('task_category/Index', [
            'task_categories' => $taskCategories,
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
    public function store(TaskCategoryRequest $request)
    {
        TaskCategory::create($request->validated());

        return redirect()
            ->route('task-category.index')
            ->with('success', 'Task Category has been successfully added.');
    }

    /**
     * Display the specified resource.
     */
    public function show(TaskCategory $taskCategory)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TaskCategory $taskCategory)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(TaskCategoryRequest $request, string $encodedId)
    {
        $id = Sqids::decode($encodedId);
        if (empty($id)) abort(404, 'ID tidak valid.');

        $taskCategory = TaskCategory::findOrFail($id);
        $taskCategory->update($request->validated());

        return redirect()
            ->route('task-category.index')
            ->with('success', 'Task Category has been successfully updated.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $encodedId)
    {
        $id = Sqids::decode($encodedId);
        if (empty($id)) abort(404, 'ID tidak valid.');

        $taskCategory = TaskCategory::findOrFail($id);
        $taskCategory->delete();

        return redirect()
            ->route('task-category.index')
            ->with('success', 'Task Category has been successfully deleted.');
    }
}

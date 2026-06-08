<?php

namespace App\Http\Controllers;

use App\Facades\Sqids;
use App\Http\Requests\MsTaskPriority\MsTaskPriorityRequest;
use App\Models\MsTaskPriority;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class MsTaskPriorityController extends Controller
{
    public function index(): Response
    {
        $msTaskPriorities = MsTaskPriority::orderBy('id')->get();

        $msTaskPriorities = Sqids::rec_encode_ids_in_list($msTaskPriorities);

        return Inertia::render('ms_task_priority/Index', [
            'task_priorities' => $msTaskPriorities,
        ]);
    }

    public function store(MsTaskPriorityRequest $request): RedirectResponse
    {
        MsTaskPriority::create($request->validated());

        return redirect()
            ->route('task-priority.index')
            ->with('success', 'Task Priority has been successfully added.');
    }

    public function update(MsTaskPriorityRequest $request, string $encodedId): RedirectResponse
    {
        $id = Sqids::decode($encodedId);
        if (empty($id)) {
            abort(404, 'ID tidak valid.');
        }

        $msTaskPriorities = MsTaskPriority::findOrFail($id);
        $msTaskPriorities->update($request->validated());

        return redirect()
            ->route('task-priority.index')
            ->with('success', 'Task Priority has been successfully updated.');
    }

    public function destroy(string $encodedId): RedirectResponse
    {
        $id = Sqids::decode($encodedId);
        if (empty($id)) {
            abort(404, 'ID tidak valid.');
        }

        $msTaskPriorities = MsTaskPriority::findOrFail($id);
        $msTaskPriorities->delete();

        return redirect()
            ->route('task-priority.index')
            ->with('success', 'Task Priority has been successfully deleted.');
    }
}

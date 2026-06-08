<?php

namespace App\Http\Controllers;

use App\Facades\Sqids;
use App\Http\Requests\MsTaskStatus\MsTaskStatusStoreRequest;
use App\Models\MsTaskStatus;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class MsTaskStatusController extends Controller
{
    public function index(): Response
    {
        $msTaskStatuses = MsTaskStatus::orderBy('id')->get();

        $msTaskStatuses = Sqids::rec_encode_ids_in_list($msTaskStatuses);

        return Inertia::render('ms_task_status/Index', [
            'task_statuses' => $msTaskStatuses,
        ]);
    }

    public function store(MsTaskStatusStoreRequest $request): RedirectResponse
    {
        MsTaskStatus::create($request->validated());

        return redirect()
            ->route('task-status.index')
            ->with('success', 'Task Status has been successfully added.');
    }

    public function update(MsTaskStatusStoreRequest $request, string $encodedId): RedirectResponse
    {
        $id = Sqids::decode($encodedId);
        if (empty($id)) {
            abort(404, 'ID tidak valid.');
        }

        $msTaskStatuses = MsTaskStatus::findOrFail($id);
        $msTaskStatuses->update($request->validated());

        return redirect()
            ->route('task-status.index')
            ->with('success', 'Task Status has been successfully updated.');
    }

    public function destroy(string $encodedId): RedirectResponse
    {
        $id = Sqids::decode($encodedId);
        if (empty($id)) {
            abort(404, 'ID tidak valid.');
        }

        $msTaskStatuses = MsTaskStatus::findOrFail($id);
        $msTaskStatuses->delete();

        return redirect()
            ->route('task-status.index')
            ->with('success', 'Task Status has been successfully deleted.');
    }
}

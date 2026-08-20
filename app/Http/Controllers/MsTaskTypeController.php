<?php

namespace App\Http\Controllers;

use App\Facades\Sqids;
use App\Http\Requests\MsTaskType\MsTaskTypeStoreRequest;
use App\Models\MsTaskType;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class MsTaskTypeController extends Controller
{
    public function index(): Response
    {
        $msTaskTypes = MsTaskType::select([
            'id',
            'name',
            'severity',
            'owned_id',
            'created_at',
            'created_by',
            'updated_by',
            'deleted_by',
        ])->orderBy('id')->get();

        $msTaskTypes = Sqids::rec_encode_ids_in_list($msTaskTypes);

        return Inertia::render('masterdata/ms_task_type/Index', [
            'task_types' => $msTaskTypes,
        ]);
    }

    public function store(MsTaskTypeStoreRequest $request): RedirectResponse
    {
        MsTaskType::create($request->validated());

        return redirect()
            ->route('task-type.index')
            ->with('success', 'Task Type has been successfully added.');
    }

    public function update(MsTaskTypeStoreRequest $request, string $encodedId): RedirectResponse
    {
        $id = Sqids::decode($encodedId);
        if (empty($id)) {
            abort(404, 'ID tidak valid.');
        }

        $msTaskTypes = MsTaskType::findOrFail($id);
        $msTaskTypes->update($request->validated());

        return redirect()
            ->route('task-type.index')
            ->with('success', 'Task Type has been successfully updated.');
    }

    public function destroy(string $encodedId): RedirectResponse
    {
        $id = Sqids::decode($encodedId);
        if (empty($id)) {
            abort(404, 'ID tidak valid.');
        }

        $msTaskTypes = MsTaskType::findOrFail($id);
        $msTaskTypes->delete();

        return redirect()
            ->route('task-type.index')
            ->with('success', 'Task Type has been successfully deleted.');
    }
}

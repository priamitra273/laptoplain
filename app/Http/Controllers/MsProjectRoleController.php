<?php

namespace App\Http\Controllers;

use App\Facades\Sqids;
use App\Http\Requests\MsProjectRole\MsProjectRoleRequest;
use App\Models\MsProjectRole;
use App\Models\MsTaskStatus;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class MsProjectRoleController extends Controller
{
    public function index(): Response
    {
        $roles = MsProjectRole::orderBy('id')->get();

        $roles = Sqids::rec_encode_ids_in_list($roles);

        return Inertia::render('ms_project_role/Index', [
            'project_roles' => $roles,
        ]);
    }

    public function create(): Response
    {
        $taskStatuses = MsTaskStatus::select(['id', 'name', 'severity'])
            ->orderBy('score')
            ->get();

        $taskStatuses = Sqids::rec_encode_ids_in_list($taskStatuses);

        return Inertia::render('ms_project_role/FormPage', [
            'task_statuses' => $taskStatuses,
        ]);
    }

    public function store(MsProjectRoleRequest $request): RedirectResponse
    {
        MsProjectRole::create($request->validated());

        return redirect()
            ->route('project-role.index')
            ->with('success', 'Project Role has been successfully added.');
    }

    public function edit(string $encodedId): Response
    {
        $id = Sqids::decode($encodedId);

        if (empty($id)) {
            abort(404, 'ID tidak valid.');
        }

        $projectRole = MsProjectRole::findOrFail($id);

        $taskStatuses = MsTaskStatus::select(['id', 'name', 'severity'])
            ->orderBy('score')
            ->get();

        $taskStatuses = Sqids::rec_encode_ids_in_list($taskStatuses);

        $projectRoleData = $projectRole->toArray();
        $projectRoleData['id'] = Sqids::encode($projectRole->id);

        if (! empty($projectRoleData['config']['allow_task_status'])) {
            $projectRoleData['config']['allow_task_status'] = array_map(fn ($item) => Sqids::encode($item), $projectRoleData['config']['allow_task_status']);
        }

        return Inertia::render('ms_project_role/FormPage', [
            'project_role' => $projectRoleData,
            'task_statuses' => $taskStatuses,
        ]);
    }

    public function update(MsProjectRoleRequest $request, string $encodedId): RedirectResponse
    {

        $id = Sqids::decode($encodedId);

        if (empty($id)) {
            abort(404, 'ID tidak valid.');
        }

        $msProjectRole = MsProjectRole::findOrFail($id);

        $data = $request->validated();

        if (! empty($data['config']['allow_task_status'])) {
            $data['config']['allow_task_status'] = array_map(fn ($item) => Sqids::decode($item), $data['config']['allow_task_status']);
        }

        $msProjectRole->update($data);

        return redirect()
            ->route('project-role.index')
            ->with('success', 'Project Role has been successfully updated.');
    }

    public function destroy(string $encodedId): RedirectResponse
    {
        $id = Sqids::decode($encodedId);
        if (empty($id)) {
            abort(404, 'ID tidak valid.');
        }

        $msProjectRole = MsProjectRole::findOrFail($id);
        $msProjectRole->delete();

        return redirect()
            ->route('project-role.index')
            ->with('success', 'Project Role has been successfully deleted.');
    }
}

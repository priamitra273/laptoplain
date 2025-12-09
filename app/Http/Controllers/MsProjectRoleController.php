<?php

namespace App\Http\Controllers;

use App\Facades\Sqids;
use App\Models\MsProjectRole;
use App\Http\Requests\MsProjectRole\MsProjectRoleRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class MsProjectRoleController extends Controller
{
    public function index(): Response
    {
        $roles = MsProjectRole::select([
            'id',
            'name',
            'owned_id',
            'created_by',
            'updated_by',
            'deleted_by'
        ])->orderBy('id')->get();

        $roles = Sqids::rec_encode_ids_in_list($roles);
        return Inertia::render('ms_project_role/Index', [
            'project_roles' => $roles,
        ]);
    }


    public function store(MsProjectRoleRequest $request): RedirectResponse
    {
        MsProjectRole::create($request->validated());

        return redirect()
            ->route('project-role.index')
            ->with('success', 'Project Role berhasil ditambahkan.');
    }


    public function update(MsProjectRoleRequest $request, string $encodedId): RedirectResponse
    {
        $id = Sqids::decode($encodedId);
        if (empty($id)) abort(404, 'ID tidak valid.');

        $msProjectRole = MsProjectRole::findOrFail($id);
        $msProjectRole->update($request->validated());

        return redirect()
            ->route('project-role.index')
            ->with('success', 'Project Role berhasil diperbarui.');
    }

    public function destroy(string $encodedId): RedirectResponse
    {
        $id = Sqids::decode($encodedId);
        if (empty($id)) abort(404, 'ID tidak valid.');

        $msProjectRole = MsProjectRole::findOrFail($id);
        $msProjectRole->delete();

        return redirect()
            ->route('project-role.index')
            ->with('success', 'Project Role berhasil dihapus.');
    }
}

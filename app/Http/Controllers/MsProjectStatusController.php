<?php

namespace App\Http\Controllers;

use App\Http\Requests\Project\MsProjectStatusStoreRequest;
use App\Models\MsProjectStatus;
use Inertia\Inertia;

class MsProjectStatusController extends Controller
{
    /**
     * Tampilkan daftar semua status project.
     */
    public function index()
    {
        $statuses = MsProjectStatus::query()
            ->with('owned:id,name')
            ->orderByDesc('created_at')
            ->get();

        return Inertia::render('ms_project_status/ProjectStatus', [
            'statuses' => $statuses,
        ]);
    }

    /**
     * Simpan data status project baru.
     */
    public function store(MsProjectStatusStoreRequest $request)
    {
        $validated = $request->validated();

        MsProjectStatus::create([
            'name' => $validated['name'],
            'severity' => $validated['severity'],
            'owned_id' => auth()->id(),
            'created_by' => auth()->id(),
        ]);

        return redirect()
            ->route('ms_project_status.index')
            ->with('success', 'Status project berhasil ditambahkan.');
    }

    /**
     * Perbarui status project.
     */
    public function update(MsProjectStatusStoreRequest $request, MsProjectStatus $ms_project_status)
    {
        $validated = $request->validated();

        $ms_project_status->update([
            'name' => $validated['name'],
            'severity' => $validated['severity'],
            'updated_by' => auth()->id(),
        ]);

        return redirect()
            ->route('ms_project_status.index')
            ->with('success', 'Status project berhasil diperbarui.');
    }

    /**
     * Hapus status project.
     */
    public function destroy(MsProjectStatus $ms_project_status)
    {
        $ms_project_status->update([
            'deleted_by' => auth()->id(),
        ]);

        $ms_project_status->delete();

        return redirect()
            ->route('ms_project_status.index')
            ->with('success', 'Status project berhasil dihapus.');
    }
}

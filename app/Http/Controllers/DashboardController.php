<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $projects = Project::select([
            'id',
            'emoji',
            'title',
            'description',
            'start_date',
            'due_date',
            'progress',
            'sequence_number',
            'created_by',
            'updated_by',
            'created_at',
            'updated_at'
        ])
            ->orderBy('id')
            ->get();

        return Inertia::render('Dashboard', [
            'projects' => $projects,
            'latestProject' => $projects->last(),
        ]);
    }
}

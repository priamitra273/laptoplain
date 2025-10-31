<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // Select fields that actually exist in the new table definition
        $projects = Project::select([
            'uuid',
            'title',
            'emoji',
            'description',
            'due_date',
            'progress',
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

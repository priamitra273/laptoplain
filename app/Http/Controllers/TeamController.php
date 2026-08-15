<?php

namespace App\Http\Controllers;

use App\Http\Requests\Team\TeamStoreRequest;
use App\Models\Team;
use Inertia\Inertia;

class TeamController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $team = Team::select('uuid', 'name', 'created_at', 'updated_at')
            ->filterByUserRole()
            ->get();

        return Inertia::render('settings/team/Team', [
            'teams' => $team,
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
    public function store(TeamStoreRequest $request)
    {
        Team::create(['name' => $request->name]);

        return redirect()->route('team.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(Team $team)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Team $team)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(TeamStoreRequest $request, Team $team)
    {
        $team->update(['name' => $request->name]);

        return redirect()->route('team.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Team $team)
    {
        abort_if($team->name == 'Admin', 404);

        $team->delete();

        return redirect()->route('team.index');
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\Role\RoleStoreRequest;
use App\Http\Resources\Menu\MenuNestedResource;
use App\Http\Resources\Role\RoleListResource;
use App\Http\Resources\Role\RoleResource;
use App\Models\Menu;
use App\Models\Role;
use App\Models\Team;
use App\Services\MenuService;
use Inertia\Inertia;

class RoleController extends Controller
{
    public function __construct(
        protected MenuService $menu_service
    ){}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $roles = Role::all();

        return Inertia::render('role/Role', [
            // using "resolve" to avoid wrapping
            'roles' => RoleListResource::collection($roles)->resolve()
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $teams = Team::select('uuid', 'name', 'created_at', 'updated_at')->get();
        $menu = Menu::whereNull('parent_id')->orderBy('sequence_number')->get();
        $total_menu = Menu::count();

        $menu_permissions = $this->menu_service->getMenuPermissions('menus.uuid', 'menus.label', 'p.name', 'route_name');

        return Inertia::render('role/RoleForm', [
            'teams' => $teams,

            // using "resolve" to avoid wrapping
            'menu' => MenuNestedResource::collection($menu)->resolve(),

            'menu_permissions' => $menu_permissions,
            'total_menu' => $total_menu
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(RoleStoreRequest $request)
    {
        $team = Team::findByUuid($request->team_uuid);

        $role = Role::create([
            'name' => str($request->label .'-'. $team->name)->slug(),
            'guard_name' => 'web',
            'label' => $request->label,
            'team_id' => $team->id,
            'is_active' => $request->is_active
        ]);

        $role->syncPermissions($request->permissions);

        return redirect()->route('role.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Role $role)
    {
        $data = new RoleResource($role);

        $teams = Team::select('uuid', 'name', 'created_at', 'updated_at')->get();
        $menu = Menu::whereNull('parent_id')->orderBy('sequence_number')->get();
        $total_menu = Menu::count();

        $menu_permissions = $this->menu_service->getMenuPermissions('menus.uuid', 'menus.label', 'p.name', 'route_name');
        
        return Inertia::render('role/RoleForm', [
            'pageTitle' => 'Edit Role',
            'role' => $data->resolve(),
            'teams' => $teams,

            // using "resolve" to avoid wrapping
            'menu' => MenuNestedResource::collection($menu)->resolve(),

            'menu_permissions' => $menu_permissions,
            'total_menu' => $total_menu
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(RoleStoreRequest $request, Role $role)
    {
        $team = Team::findByUuid($request->team_uuid);
        
        $role->update([
            'name' => str($request->label .'-'. $team->name)->slug(),
            'label' => $request->label,
            'team_id' => Team::findByUuid($request->team_uuid)->id,
            'is_active' => $request->is_active
        ]);

        $role->syncPermissions($request->permissions);

        return redirect()->route('role.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $role)
    {
        $role->delete();

        return redirect()->route('role.index');
    }
}

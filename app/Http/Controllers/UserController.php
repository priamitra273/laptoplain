<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\UserStoreRequest;
use App\Http\Requests\User\UserUpdateRequest;
use App\Http\Resources\Role\RoleResource;
use App\Http\Resources\User\UserListResource;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Ramsey\Uuid\Guid\Guid;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $users = User::with('roles.team')->whereRelation('roles.team', function ($q) {
            return $q->filterByUserRole();
        })->get();

        return Inertia::render('user/User', [
            'users' => UserListResource::collection($users)->resolve(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $teams = Team::select('uuid', 'name', 'created_at', 'updated_at')->filterByUserRole()->get();
        $roles = Role::whereRelation('team', fn ($q) => $q->filterByUserRole())->get();

        return Inertia::render('user/UserForm', [
            'teams' => $teams,
            'roles' => RoleResource::collection($roles)->resolve(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(UserStoreRequest $request)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'is_active' => $request->is_active,
        ]);

        $user->syncRoles($request->role_id);

        return to_route('user.index');
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
    public function edit(string $id)
    {
        $user = $this->getByUuid($id);
        $teams = Team::select('uuid', 'name', 'created_at', 'updated_at')->filterByUserRole()->get();
        $roles = Role::whereRelation('team', fn ($q) => $q->filterByUserRole())->get();

        return Inertia::render('user/UserForm', [
            'pageTitle' => 'Edit User',
            'user' => (new UserListResource($user))->resolve(),
            'teams' => $teams,
            'roles' => RoleResource::collection($roles)->resolve(),
        ]);

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UserUpdateRequest $request, string $id)
    {
        $user = $this->getByUuid($id);

        $user->name = $request->name;
        $user->email = $request->email;
        $user->is_active = $request->is_active;

        if ($request->password) {
            $user->password = Hash::make($request->password);
        }

        $user->save();
        $user->syncRoles($request->role_id);

        return to_route('user.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = $this->getByUuid($id);
        $user->delete();

        return to_route('user.index');
    }

    protected function getByUuid(string $uuid): User
    {
        if (! Guid::isValid($uuid)) {
            abort(404);
        }

        $user = User::whereUuid($uuid)->firstOrFail();

        return $user;
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Middleware\EnsureUuidIsValid;
use App\Http\Requests\Menu\MenuStoreRequest;
use App\Http\Requests\Menu\MenuUpdateRequest;
use App\Http\Resources\Menu\MenuListResource;
use App\Models\Menu;
use App\Services\MenuService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;
use Ramsey\Uuid\Guid\Guid;

class MenuController extends Controller implements HasMiddleware
{
    public function __construct(
        protected MenuService $service
    ){}

    /**
     * Get the middleware that should be assigned to the controller.
     */
    public static function middleware(): array
    {
        return [
            
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        $menu = Menu::orderByRaw('parent_id NULLS FIRST')->orderBy('sequence_number')->get();

        $parent_menu = Menu::whereNull('parent_id')->orderBy('sequence_number')->get();

        $routes = $this->service->getAvailableRoutes($menu->pluck('route_name'));
        
        return Inertia::render('menu/Menu', [
            'menu' => MenuListResource::collection($menu)->toArray(request()),
            'parent_menu' => MenuListResource::collection($parent_menu)->toArray(request()),
            'available_routes' => $routes->all()
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
    public function store(MenuStoreRequest $request)
    {
        $this->service->store($request->safe());

        return redirect()->route('menu.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $uuid)
    {
        // 
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Menu $menu)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(MenuUpdateRequest $request, Menu $menu)
    {
        $this->service->update($request->safe(), $menu);

        return redirect()->route('menu.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Menu $menu)
    {
        $menu->delete();

        return redirect()->route('menu.index');
    }
}

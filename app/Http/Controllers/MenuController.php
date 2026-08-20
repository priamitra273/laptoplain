<?php

namespace App\Http\Controllers;

use App\Http\Requests\Menu\MenuStoreRequest;
use App\Http\Requests\Menu\MenuUpdateRequest;
use App\Http\Resources\Menu\MenuListResource;
use App\Models\Menu;
use App\Services\MenuService;
use Illuminate\Routing\Controllers\HasMiddleware;
use Inertia\Inertia;
use Inertia\Response;

class MenuController extends Controller implements HasMiddleware
{
    public function __construct(
        protected MenuService $service
    ) {}

    public static function middleware(): array
    {
        return [

        ];
    }

    public function index(): Response
    {
        $menu = Menu::orderByRaw('parent_id NULLS FIRST')->orderBy('sequence_number')->get();

        $parent_menu = Menu::whereNull('parent_id')->orderBy('sequence_number')->get();

        $routes = $this->service->getAvailableRoutes($menu->pluck('route_name'));

        return Inertia::render('settings/menu/Menu', [
            'menu' => MenuListResource::collection($menu)->toArray(request()),
            'parent_menu' => MenuListResource::collection($parent_menu)->toArray(request()),
            'available_routes' => $routes->all(),
        ]);
    }

    public function create()
    {
        //
    }

    public function store(MenuStoreRequest $request)
    {
        $this->service->store($request->safe());

        return redirect()->route('menu.index');
    }

    public function show(string $uuid)
    {
        //
    }

    public function edit(Menu $menu)
    {
        //
    }

    public function update(MenuUpdateRequest $request, Menu $menu)
    {
        $this->service->update($request->safe(), $menu);

        return redirect()->route('menu.index');
    }

    public function destroy(Menu $menu)
    {
        $menu->delete();

        return redirect()->route('menu.index');
    }
}

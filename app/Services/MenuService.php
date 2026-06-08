<?php

namespace App\Services;

use App\Models\Menu;
use App\Models\User;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Support\ValidatedInput;
use Ramsey\Uuid\Guid\Guid;
use Spatie\Permission\Models\Permission;

class MenuService
{
    /**
     * Getting the available menu
     * Compare the route names with registered in database
     */
    public function getAvailableRoutes(array|Collection $existing_menu): Collection
    {
        $routes = collect(Route::getRoutes()->getRoutesByName())->filter(function (RoutingRoute $route) {
            return in_array('GET', $route->methods)
                && in_array('auth', $route->middleware())
                && (Str::contains($route->getName(), '.index') || Str::doesntContain($route->getName(), '.'));
        });

        $routes = $routes->map(function (RoutingRoute $route) {
            $parameters = $route->parameterNames();

            if (!empty($parameters)) {
                return null;
            }

            return [
                'uri' => route($route->getName()),
                'name' => $route->getName()
            ];
        })->filter()->whereNotIn('name', $existing_menu)->values();

        return $routes;
    }

    /**
     * Get resource by uuid
     */
    public function getByUuid(string $uuid): Menu
    {
        if (!Guid::isValid($uuid)) {
            abort(404);
        }

        return Menu::whereUuid($uuid)->firstOrFail();
    }

    /**
     * Creating the menu resource
     */
    public function store(ValidatedInput $input): void
    {
        $input->parent_id = $input->parent_uuid ? Menu::findByUuid($input->parent_uuid)->id : null;
        unset($input->parent_uuid);

        DB::beginTransaction();

        try {
            Menu::where('sequence_number', '>=', $input->sequence_number)
                ->where('parent_id', $input->parent_id)
                ->increment('sequence_number');

            $menu = Menu::create($input->toArray());

            if ($input->route_name) {
                $uri = Route::getRoutes()->getByName($input->route_name)->uri;
                $uri = Str::replace('/', '.', $uri);
            } else {
                $uri = (string) str($input->label)->singular()->slug();

                $count_uri = Menu::whereRaw("LOWER(REPLACE(label, ' ', '-')) = ?", Str::slug($input->label))->count();

                $uri = $count_uri > 1 ? $uri . "-" . $count_uri + 1 : $uri;
            }

            $actions = ['create', 'read', 'update', 'delete'];

            foreach ($actions as $action) {
                $permission = Permission::findOrCreate("$uri.$action");
                $menu->givePermissionTo($permission);
            }

            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();

            Log::error($th->getMessage());
        }
    }

    /**
     * Updating the menu resource
     */
    public function update(ValidatedInput $input, Menu $menu): void
    {
        $input->parent_id = $input->parent_uuid ? Menu::findByUuid($input->parent_uuid)->id : null;

        Menu::where('sequence_number', '>', $menu->sequence_number)
            ->where('parent_id', $input->parent_id)
            ->decrement('sequence_number');

        Menu::where('sequence_number', '>=', $input->sequence_number)
            ->where('parent_id', $input->parent_id)
            ->where('id', '!=', $menu->id)
            ->increment('sequence_number');

        $menu->update($input->toArray());
    }

    /**
     * Get the relationship menu permissions resources
     */
    public function getMenuPermissions(...$select): Collection
    {
        $menu = Menu::leftJoin('model_has_permissions as mhp', function (JoinClause $join) {
            $join->on('menus.id', '=', 'mhp.model_id')->where('mhp.model_type', '=', Menu::class);
        })
            ->join('permissions as p', 'p.id', '=', 'mhp.permission_id')
            ->select($select)
            ->get();

        return $menu;
    }
}

<?php

namespace App\Services;

use App\Models\Menu;
use App\Models\User;
use App\Repositories\MenuRepository;
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
    public function __construct(private MenuRepository $menuRepository) {}

    /**
     * Build the nested sidebar menu tree for the given user.
     *
     * Delegates the visibility-filtered fetch to a single recursive CTE in the
     * repository, then assembles the flat rows into the shape the sidebar
     * expects: { label, icon, to, items }.
     *
     * @return array<int, array<string, mixed>>
     */
    /**
     * Root menus are grouped under this key (menu ids are auto-increment >= 1,
     * so 0 can never collide with a real parent id).
     */
    private const ROOT_KEY = 0;

    public function getSidebarMenu(int $userId): array
    {
        $rows = $this->menuRepository->getVisibleMenuRowsForUser($userId);

        $childrenByParent = [];

        foreach ($rows as $row) {
            $parentKey = $row->parent_id !== null ? (int) $row->parent_id : self::ROOT_KEY;
            $childrenByParent[$parentKey][] = $row;
        }

        return $this->buildSidebarTree($childrenByParent, self::ROOT_KEY);
    }

    /**
     * Assemble the nested menu tree from rows pre-grouped by parent id.
     *
     * Each node is visited exactly once (O(N)); the depth/sequence ordering of
     * the source rows is preserved within every group.
     *
     * @param  array<int, array<int, object>>  $childrenByParent
     * @return array<int, array<string, mixed>>
     */
    private function buildSidebarTree(array $childrenByParent, int $parentKey): array
    {
        $branch = [];

        foreach ($childrenByParent[$parentKey] ?? [] as $row) {
            $children = $this->buildSidebarTree($childrenByParent, (int) $row->id);

            $branch[] = [
                'label' => $row->label,
                'icon' => $row->icon,
                'to' => $row->route_name,
                'items' => $children !== [] ? $children : null,
            ];
        }

        return $branch;
    }

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

            if (! empty($parameters)) {
                return null;
            }

            return [
                'uri' => route($route->getName()),
                'name' => $route->getName(),
            ];
        })->filter()->whereNotIn('name', $existing_menu)->values();

        return $routes;
    }

    /**
     * Get resource by uuid
     */
    public function getByUuid(string $uuid): Menu
    {
        if (! Guid::isValid($uuid)) {
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

                $uri = $count_uri > 1 ? $uri.'-'.$count_uri + 1 : $uri;
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

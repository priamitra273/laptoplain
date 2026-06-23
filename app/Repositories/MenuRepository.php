<?php

namespace App\Repositories;

use App\Models\Menu;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class MenuRepository
{
    /**
     * Fetch the flat, ordered rows of every menu visible to the given user.
     *
     * A single recursive CTE walks the parent/child tree and only keeps menus
     * the user can reach through its permissions -> roles -> users chain
     * (Spatie's model_has_permissions / role_has_permissions / model_has_roles).
     * Rows come back ordered by depth then sequence_number so the caller can
     * assemble the nested tree without re-sorting.
     *
     * @return array<int, object{id: int, parent_id: int|null, label: string, icon: string, route_name: string|null, sequence_number: int, depth: int}>
     */
    public function getVisibleMenuRowsForUser(int $userId): array
    {
        return DB::select($this->sidebarMenuTreeSql(), [
            'menuType' => Menu::class,
            'userType' => User::class,
            'userId' => $userId,
        ]);
    }

    /**
     * Recursive CTE returning the visibility-filtered menu tree rows.
     *
     * The non-recursive `visible_menus` CTE resolves the set of menu ids the
     * user can access once; `menu_tree` then walks parents to children, keeping
     * only those ids and excluding soft-deleted rows.
     */
    protected function sidebarMenuTreeSql(): string
    {
        return <<<'SQL'
            WITH RECURSIVE visible_menus AS (
                SELECT DISTINCT mhp.model_id AS menu_id
                FROM model_has_permissions mhp
                JOIN role_has_permissions rhp ON rhp.permission_id = mhp.permission_id
                JOIN model_has_roles mhr ON mhr.role_id = rhp.role_id
                WHERE mhp.model_type = :menuType
                  AND mhr.model_type = :userType
                  AND mhr.model_id = :userId
            ),
            menu_tree AS (
                SELECT m.id, m.parent_id, m.label, m.icon, m.route_name, m.sequence_number, 0 AS depth
                FROM menus m
                WHERE m.parent_id IS NULL
                  AND m.deleted_at IS NULL
                  AND m.id IN (SELECT menu_id FROM visible_menus)
                UNION ALL
                SELECT c.id, c.parent_id, c.label, c.icon, c.route_name, c.sequence_number, mt.depth + 1
                FROM menus c
                JOIN menu_tree mt ON c.parent_id = mt.id
                WHERE c.deleted_at IS NULL
                  AND c.id IN (SELECT menu_id FROM visible_menus)
            )
            SELECT id, parent_id, label, icon, route_name, sequence_number, depth
            FROM menu_tree
            ORDER BY depth, sequence_number, id
            SQL;
    }
}

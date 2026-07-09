<?php

namespace App\Services\DynamicQuery;

use App\Facades\Sqids;

class DynamicQueryService
{
    public function __construct(protected QueryRegistry $registry) {}

    /**
     * Validate a raw query against the registry and return a normalized query.
     *
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function validate(array $query): array
    {
        $defaults = $this->registry->defaults();

        $alias = $query['model'] ?? null;

        if (! is_string($alias) || $alias === '') {
            throw new QueryException('You must provide a `model`. Call the list-queryable-models tool to see available models.');
        }

        $def = $this->registry->get($alias);

        $joins = $this->normalizeJoins($query['joins'] ?? [], $alias, $def, $defaults);
        $with = array_values($query['with'] ?? []);
        $groupBy = $query['group_by'] ?? [];

        // Tables available for column/scope resolution: base + joined. Computed
        // before column/aggregate validation so every reference can be checked.
        $tables = array_merge([$def['table']], array_map(fn ($j) => $j['table'], $joins));
        $tableScope = [$def['table'] => $alias];
        foreach ($joins as $join) {
            $tableScope[$join['table']] = $join['alias'];
        }

        $aggregates = $this->normalizeAggregates($query['aggregates'] ?? [], $defaults, $tables, $tableScope);

        $mode = (! empty($joins) || ! empty($aggregates) || ! empty($groupBy)) ? 'join' : 'relation';

        if (! empty($with) && $mode === 'join') {
            throw new QueryException('`with` (relation loading) cannot be combined with joins/aggregates/group_by. Use either relation mode or join mode.');
        }

        $select = $this->normalizeSelect($query['select'] ?? [], $def, $mode, $tables, $tableScope);
        $withTargets = $this->normalizeWith($with, $def);
        $filters = $this->normalizeFilters($query['filters'] ?? [], $def, $mode, $tables, $tableScope, $defaults);
        $orderBy = $this->normalizeOrderBy($query['order_by'] ?? [], $def, $mode, $tables, $tableScope, $aggregates);
        $groupBy = $this->normalizeColumnList($groupBy, $def, $mode, $tables, $tableScope, 'group_by');

        if (! empty($aggregates)) {
            foreach ($select as $col) {
                if (! in_array($col, $groupBy, true)) {
                    throw new QueryException("When using aggregates, every selected column must appear in group_by. '{$col}' does not.");
                }
            }
        }

        $limit = (int) ($query['limit'] ?? $defaults['limit']);

        if ($limit < 1 || $limit > $defaults['max_limit']) {
            throw new QueryException("`limit` must be between 1 and {$defaults['max_limit']}.");
        }

        return [
            'mode' => $mode,
            'alias' => $alias,
            'table' => $def['table'],
            'model' => $def['model'],
            'soft_delete' => $def['soft_delete'],
            'select' => $select,
            'distinct' => (bool) ($query['distinct'] ?? false),
            'joins' => $joins,
            'with' => $with,
            'with_targets' => $withTargets,
            'filters' => $filters,
            'group_by' => $groupBy,
            'aggregates' => $aggregates,
            'order_by' => $orderBy,
            'limit' => $limit,
            'offset' => max(0, (int) ($query['offset'] ?? 0)),
            'with_trashed' => (bool) ($query['with_trashed'] ?? false),
            'tables' => $tables,
            'table_scope' => $tableScope,
        ];
    }

    /**
     * @param  array<int, mixed>  $joins
     * @param  array<string, mixed>  $def
     * @param  array<string, mixed>  $defaults
     * @return list<array<string, mixed>>
     */
    protected function normalizeJoins(array $joins, string $baseAlias, array $def, array $defaults): array
    {
        $availableScope = [$def['table'] => $baseAlias];
        $joinableFrom = $def['joinable'];
        $normalized = [];

        foreach ($joins as $join) {
            $targetAlias = $join['model'] ?? null;

            if (! is_string($targetAlias) || ! in_array($targetAlias, $joinableFrom, true)) {
                throw new QueryException("Cannot join model '".(is_string($targetAlias) ? $targetAlias : '?')."' from '{$def['table']}'. Allowed join targets: ".implode(', ', $joinableFrom).'.');
            }

            $targetDef = $this->registry->get($targetAlias);
            $type = ($join['type'] ?? 'inner') === 'left' ? 'left' : 'inner';

            // The target's own columns become referenceable in its on-conditions.
            $availableScope[$targetDef['table']] = $targetAlias;
            $availableTables = array_keys($availableScope);

            $on = [];
            foreach (($join['on'] ?? []) as $cond) {
                $left = $this->assertQualifiedColumn($this->stringRef($cond['left'] ?? '', 'join on.left'), $availableTables, $availableScope);
                $right = $this->assertQualifiedColumn($this->stringRef($cond['right'] ?? '', 'join on.right'), $availableTables, $availableScope);
                $op = $this->stringRef($cond['operator'] ?? '=', 'join operator');

                if (! in_array($op, ['=', '!=', '>', '>=', '<', '<='], true)) {
                    throw new QueryException("Invalid join operator '{$op}'.");
                }

                $on[] = ['left' => $left, 'operator' => $op, 'right' => $right];
            }

            if (empty($on)) {
                throw new QueryException("Join to '{$targetAlias}' requires at least one `on` condition.");
            }

            $normalized[] = ['type' => $type, 'alias' => $targetAlias, 'table' => $targetDef['table'], 'on' => $on];
            $joinableFrom = array_merge($joinableFrom, $targetDef['joinable']);
        }

        return $normalized;
    }

    /**
     * @param  array<int, mixed>  $aggregates
     * @param  array<string, mixed>  $defaults
     * @param  array<int, string>  $tables
     * @param  array<string, string>  $tableScope
     * @return list<array{function:string, column:string, alias:string}>
     */
    protected function normalizeAggregates(array $aggregates, array $defaults, array $tables, array $tableScope): array
    {
        $normalized = [];

        foreach ($aggregates as $agg) {
            $fn = strtolower($this->stringRef($agg['function'] ?? '', 'aggregate function'));

            if (! in_array($fn, $defaults['allowed_aggregates'], true)) {
                throw new QueryException("Invalid aggregate function '{$fn}'. Allowed: ".implode(', ', $defaults['allowed_aggregates']).'.');
            }

            $column = $this->stringRef($agg['column'] ?? '', 'aggregate column');

            if ($column !== '*') {
                $column = $this->assertQualifiedColumn($column, $tables, $tableScope);
            }

            $alias = $this->stringRef($agg['alias'] ?? '', 'aggregate alias');

            if (! preg_match('/^[a-z_][a-z0-9_]*$/i', $alias)) {
                throw new QueryException("Aggregate alias '{$alias}' must match [a-z_][a-z0-9_]*.");
            }

            $normalized[] = ['function' => $fn, 'column' => $column, 'alias' => $alias];
        }

        return $normalized;
    }

    /**
     * @param  array<int, string>  $select
     * @param  array<string, mixed>  $def
     * @param  array<int, string>  $tables
     * @param  array<string, string>  $tableScope
     * @return list<string>
     */
    protected function normalizeSelect(array $select, array $def, string $mode, array $tables, array $tableScope): array
    {
        if (empty($select)) {
            // Default: the base model's allowlisted columns (never '*' — protects sensitive columns).
            return $mode === 'join'
                ? array_map(fn ($c) => "{$def['table']}.{$c}", $def['columns'])
                : $def['columns'];
        }

        return array_map(function ($col) use ($def, $mode, $tables, $tableScope) {
            $col = $this->stringRef($col, 'select column');

            if ($mode === 'join') {
                return $this->assertQualifiedColumn($col, $tables, $tableScope);
            }

            if (! in_array($col, $def['columns'], true)) {
                throw new QueryException("Column '{$col}' is not selectable on '{$def['table']}'.");
            }

            return $col;
        }, array_values($select));
    }

    /**
     * @param  array<int, string>  $with
     * @param  array<string, mixed>  $def
     * @return array<string, string>
     */
    protected function normalizeWith(array $with, array $def): array
    {
        $targets = [];

        foreach ($with as $relation) {
            $relation = $this->stringRef($relation, 'relation name');

            if (! array_key_exists($relation, $def['relations'])) {
                throw new QueryException("Relation '{$relation}' is not loadable on '{$def['table']}'. Allowed: ".implode(', ', array_keys($def['relations'])).'.');
            }

            $targets[$relation] = $def['relations'][$relation];
        }

        return $targets;
    }

    /**
     * @param  array<int, mixed>  $filters
     * @param  array<string, mixed>  $def
     * @param  array<int, string>  $tables
     * @param  array<string, string>  $tableScope
     * @param  array<string, mixed>  $defaults
     * @return list<array<string, mixed>>
     */
    protected function normalizeFilters(array $filters, array $def, string $mode, array $tables, array $tableScope, array $defaults): array
    {
        $normalized = [];

        foreach ($filters as $filter) {
            $op = strtolower((string) ($filter['operator'] ?? ''));

            if (! in_array($op, $defaults['allowed_operators'], true)) {
                throw new QueryException("Invalid filter operator '{$op}'. Allowed: ".implode(', ', $defaults['allowed_operators']).'.');
            }

            $column = $this->stringRef($filter['column'] ?? '', 'filter column');
            $column = $mode === 'join'
                ? $this->assertQualifiedColumn($column, $tables, $tableScope)
                : $this->assertBaseColumn($column, $def);

            $isId = $this->isIdColumn($column);

            $entry = [
                'boolean' => ($filter['boolean'] ?? 'and') === 'or' ? 'or' : 'and',
                'column' => $column,
                'operator' => $op,
                'value' => null,
                'values' => null,
                'is_id' => $isId,
            ];

            if (in_array($op, ['in', 'not in'], true)) {
                $values = $filter['values'] ?? [];

                if (! is_array($values) || empty($values)) {
                    throw new QueryException("Operator '{$op}' requires a non-empty `values` array.");
                }

                $entry['values'] = $isId
                    ? array_map(fn ($v) => $this->decodeId($v), $values)
                    : array_values($values);
            } elseif (! in_array($op, ['is null', 'is not null'], true)) {
                $value = $filter['value'] ?? null;
                $entry['value'] = $isId ? $this->decodeId($value) : $value;
            }

            $normalized[] = $entry;
        }

        return $normalized;
    }

    /**
     * @param  array<int, mixed>  $orderBy
     * @param  array<string, mixed>  $def
     * @param  array<int, string>  $tables
     * @param  array<string, string>  $tableScope
     * @param  list<array{function:string, column:string, alias:string}>  $aggregates
     * @return list<array{column:string, direction:'asc'|'desc'}>
     */
    protected function normalizeOrderBy(array $orderBy, array $def, string $mode, array $tables, array $tableScope, array $aggregates): array
    {
        $aggregateAliases = array_map(fn ($a) => $a['alias'], $aggregates);
        $normalized = [];

        foreach ($orderBy as $order) {
            $column = $this->stringRef($order['column'] ?? '', 'order_by column');
            $direction = ($order['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

            if (in_array($column, $aggregateAliases, true)) {
                $normalized[] = ['column' => $column, 'direction' => $direction];

                continue;
            }

            $column = $mode === 'join'
                ? $this->assertQualifiedColumn($column, $tables, $tableScope)
                : $this->assertBaseColumn($column, $def);

            $normalized[] = ['column' => $column, 'direction' => $direction];
        }

        return $normalized;
    }

    /**
     * @param  array<int, string>  $columns
     * @param  array<string, mixed>  $def
     * @param  array<int, string>  $tables
     * @param  array<string, string>  $tableScope
     * @return list<string>
     */
    protected function normalizeColumnList(array $columns, array $def, string $mode, array $tables, array $tableScope, string $context): array
    {
        return array_map(function ($col) use ($def, $mode, $tables, $tableScope) {
            $col = $this->stringRef($col, 'column');

            return $mode === 'join'
                ? $this->assertQualifiedColumn($col, $tables, $tableScope)
                : $this->assertBaseColumn($col, $def);
        }, array_values($columns));
    }

    /**
     * @param  array<string, mixed>  $def
     */
    protected function assertBaseColumn(string $column, array $def): string
    {
        if (! in_array($column, $def['columns'], true)) {
            throw new QueryException("Column '{$column}' is not available on '{$def['table']}'.");
        }

        return $column;
    }

    /**
     * Validate a "table.column" reference against the set of tables present in the query.
     *
     * @param  array<int, string>  $tables
     * @param  array<string, string>  $tableScope  table => alias
     */
    protected function assertQualifiedColumn(string $column, array $tables, ?array $tableScope = null): string
    {
        if (! str_contains($column, '.')) {
            throw new QueryException("Column '{$column}' must be qualified as `table.column` in join mode.");
        }

        [$table, $col] = explode('.', $column, 2);

        if (! in_array($table, $tables, true)) {
            throw new QueryException("Table '{$table}' is not part of this query. Present tables: ".implode(', ', $tables).'.');
        }

        // Resolve the owning model definition to check the column allowlist.
        $alias = $tableScope[$table] ?? null;

        if ($alias !== null) {
            $def = $this->registry->get($alias);

            if (! in_array($col, $def['columns'], true)) {
                throw new QueryException("Column '{$col}' is not available on '{$table}'.");
            }
        }

        return "{$table}.{$col}";
    }

    protected function isIdColumn(string $column): bool
    {
        $name = str_contains($column, '.') ? explode('.', $column, 2)[1] : $column;

        return $name === 'id' || str_ends_with($name, '_id');
    }

    /**
     * Ensure a reference (column/relation/operator name) is a string, yielding
     * an actionable QueryException rather than a raw TypeError on bad input.
     */
    protected function stringRef(mixed $value, string $context): string
    {
        if (! is_string($value)) {
            throw new QueryException("Expected a string for {$context}, got ".gettype($value).'.');
        }

        return $value;
    }

    /**
     * Decode a sqid id value to an integer. Accepts an already-integer value.
     */
    protected function decodeId(mixed $value): int
    {
        if (is_int($value) || (is_string($value) && ctype_digit($value))) {
            return (int) $value;
        }

        try {
            return Sqids::decode((string) $value);
        } catch (\Throwable $e) {
            throw new QueryException("Invalid encoded id '{$value}'. Pass the sqid-encoded id returned by other tools.");
        }
    }
}

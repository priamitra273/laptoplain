<?php

namespace App\Mcp\Tools;

use App\Services\DynamicQuery\DynamicQueryService;
use App\Services\DynamicQuery\QueryException;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('query-data')]
#[Title('Query Data')]
#[Description('Run a read-only (SELECT) dynamic query against project/task models. Supports column selection, explicit joins (flat results) OR relation loading via `with` (nested results), filters, group_by + aggregates, ordering, and pagination. Results are automatically scoped to the projects you can see, and all ids are sqid-encoded. Call list-queryable-models first to discover valid models, columns, relations, and operators.')]
#[IsReadOnly]
#[IsIdempotent]
class QueryDataTool extends Tool
{
    public function __construct(protected DynamicQueryService $service) {}

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'model' => ['required', 'string'],
            'select' => ['sometimes', 'array'],
            'select.*' => ['string'],
            'distinct' => ['sometimes', 'boolean'],
            'joins' => ['sometimes', 'array'],
            'joins.*.type' => ['sometimes', 'in:inner,left'],
            'joins.*.model' => ['required_with:joins', 'string'],
            'joins.*.on' => ['required_with:joins', 'array', 'min:1'],
            'joins.*.on.*.left' => ['required', 'string'],
            'joins.*.on.*.operator' => ['sometimes', 'string'],
            'joins.*.on.*.right' => ['required', 'string'],
            'with' => ['sometimes', 'array'],
            'with.*' => ['string'],
            'filters' => ['sometimes', 'array'],
            'filters.*.boolean' => ['sometimes', 'in:and,or'],
            'filters.*.column' => ['required_with:filters', 'string'],
            'filters.*.operator' => ['required_with:filters', 'string'],
            'filters.*.values' => ['sometimes', 'array'],
            'group_by' => ['sometimes', 'array'],
            'group_by.*' => ['string'],
            'aggregates' => ['sometimes', 'array'],
            'aggregates.*.function' => ['required_with:aggregates', 'string'],
            'aggregates.*.column' => ['required_with:aggregates', 'string'],
            'aggregates.*.alias' => ['required_with:aggregates', 'string'],
            'order_by' => ['sometimes', 'array'],
            'order_by.*.column' => ['required_with:order_by', 'string'],
            'order_by.*.direction' => ['sometimes', 'in:asc,desc'],
            'limit' => ['sometimes', 'integer', 'min:1'],
            'offset' => ['sometimes', 'integer', 'min:0'],
            'with_trashed' => ['sometimes', 'boolean'],
        ], [
            'model.required' => 'You must provide a `model`. Call the list-queryable-models tool to see available models.',
        ]);

        // `value` is intentionally unconstrained (string/number/bool), so it is read from the raw request.
        $raw = $request->all();
        foreach (($raw['filters'] ?? []) as $index => $filter) {
            if (array_key_exists('value', $filter)) {
                $validated['filters'][$index]['value'] = $filter['value'];
            }
        }

        try {
            $data = $this->service->run($validated, $request->user());
        } catch (QueryException $e) {
            return Response::error($e->getMessage());
        }

        return Response::json(['data' => $data]);
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'model' => $schema->string()
                ->description('Model alias to query (see list-queryable-models). Example: "task".')
                ->required(),
            'select' => $schema->array()->items($schema->string())
                ->description('Columns to return. Relation mode: plain column names. Join mode: qualified "table.column". Defaults to the safe column list.'),
            'distinct' => $schema->boolean()->description('Return distinct rows.'),
            'joins' => $schema->array()->items($schema->object([
                'type' => $schema->string()->enum(['inner', 'left'])->description('Join type (default inner).'),
                'model' => $schema->string()->description('Model alias to join (must be joinable from the base model).'),
                'on' => $schema->array()->items($schema->object([
                    'left' => $schema->string()->description('Qualified column "table.column".'),
                    'operator' => $schema->string()->description('Comparison operator, default "=".'),
                    'right' => $schema->string()->description('Qualified column "table.column".'),
                ])),
            ]))->description('Explicit table joins (flat results). Do not combine with `with`.'),
            'with' => $schema->array()->items($schema->string())
                ->description('Relations to eager-load (nested results). Do not combine with joins/aggregates/group_by.'),
            'filters' => $schema->array()->items($schema->object([
                'boolean' => $schema->string()->enum(['and', 'or'])->description('How this filter combines (default and).'),
                'column' => $schema->string()->description('Column to filter. Join mode: "table.column".'),
                'operator' => $schema->string()->description('One of =, !=, >, >=, <, <=, like, in, not in, is null, is not null.'),
                'value' => $schema->string()->description('Value for scalar operators. For id/_id columns pass the sqid-encoded id.'),
                'values' => $schema->array()->items($schema->string())->description('Values for the in / not in operators.'),
            ]))->description('Filter conditions (parametrized).'),
            'group_by' => $schema->array()->items($schema->string())
                ->description('Group-by columns (qualified in join mode). Required alongside aggregates.'),
            'aggregates' => $schema->array()->items($schema->object([
                'function' => $schema->string()->enum(['count', 'sum', 'avg', 'min', 'max']),
                'column' => $schema->string()->description('Qualified "table.column" or "*".'),
                'alias' => $schema->string()->description('Result key for this aggregate.'),
            ]))->description('Aggregate expressions. Do not combine with `with`.'),
            'order_by' => $schema->array()->items($schema->object([
                'column' => $schema->string(),
                'direction' => $schema->string()->enum(['asc', 'desc']),
            ]))->description('Ordering.'),
            'limit' => $schema->integer()->description('Max rows (default 50, hard max 200).'),
            'offset' => $schema->integer()->description('Rows to skip.'),
            'with_trashed' => $schema->boolean()->description('Include soft-deleted rows.'),
        ];
    }

    /**
     * @return array<string, Type>
     */
    public function outputSchema(JsonSchema $schema): array
    {
        return [
            'data' => $schema->array()
                ->description('Result rows. Nested objects for relation mode, flat rows for join/aggregate mode. All ids are sqid-encoded.')
                ->required(),
        ];
    }
}

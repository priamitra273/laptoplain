<?php

namespace App\Mcp\Tools;

use App\Services\DynamicQuery\QueryRegistry;
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

#[Name('list-queryable-models')]
#[Title('List Queryable Models')]
#[Description('Lists every model the query-data tool can read: model alias, table, selectable columns, loadable relations (with their target model), joinable models, scope type, and the allowed filter operators and aggregate functions. Call this before building a query-data request.')]
#[IsReadOnly]
#[IsIdempotent]
class QueryableSchemaTool extends Tool
{
    public function __construct(protected QueryRegistry $registry) {}

    public function handle(Request $request): Response
    {
        $models = [];

        foreach ($this->registry->models() as $alias => $def) {
            $models[] = [
                'model' => $alias,
                'table' => $def['table'],
                'scope' => $def['scope'],
                'columns' => $def['columns'],
                'relations' => $def['relations'],
                'joinable' => $def['joinable'],
            ];
        }

        $defaults = $this->registry->defaults();

        return Response::json([
            'data' => [
                'models' => $models,
                'operators' => $defaults['allowed_operators'],
                'aggregates' => $defaults['allowed_aggregates'],
                'default_limit' => $defaults['limit'],
                'max_limit' => $defaults['max_limit'],
            ],
        ]);
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            //
        ];
    }

    /**
     * @return array<string, Type>
     */
    public function outputSchema(JsonSchema $schema): array
    {
        return [
            'data' => $schema->object([
                'models' => $schema->array()->items($schema->object([
                    'model' => $schema->string(),
                    'table' => $schema->string(),
                    'scope' => $schema->string(),
                    'columns' => $schema->array()->items($schema->string()),
                    'joinable' => $schema->array()->items($schema->string()),
                ]))->description('Queryable models and their metadata.'),
                'operators' => $schema->array()->items($schema->string()),
                'aggregates' => $schema->array()->items($schema->string()),
                'default_limit' => $schema->integer(),
                'max_limit' => $schema->integer(),
            ])->description('Catalog of everything the query-data tool can read.')->required(),
        ];
    }
}

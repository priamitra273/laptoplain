<?php

namespace App\Mcp\Tools;

use App\Facades\Sqids;
use App\Services\ProjectLazyService;
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

#[Name('get-task-options')]
#[Title('Get Task Options')]
#[Description('Fetch the master option lists used by task forms: statuses, priorities, types, categories, and tags. Use the encoded ids when creating or filtering tasks.')]
#[IsReadOnly]
#[IsIdempotent]
class TaskOptionsTool extends Tool
{
    public function __construct(
        protected ProjectLazyService $service,
    ) {}

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $options = $this->service->taskFormOptions();

        return Response::json([
            'data' => Sqids::rec_encode_ids_in_list($options),
        ]);
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            //
        ];
    }

    /**
     * Get the tool's output schema.
     *
     * @return array<string, Type>
     */
    public function outputSchema(JsonSchema $schema): array
    {
        return [
            'data' => $schema->object([
                'taskStatuses' => $schema->array()
                    ->items($schema->object([
                        'id' => $schema->string(),
                        'name' => $schema->string(),
                        'severity' => $schema->string(),
                        'score' => $schema->number(),
                    ]))
                    ->description('Task status options.'),
                'taskPriorities' => $schema->array()
                    ->items($this->labelledOption($schema))
                    ->description('Task priority options.'),
                'taskTypes' => $schema->array()
                    ->items($this->labelledOption($schema))
                    ->description('Task type options.'),
                'taskCategories' => $schema->array()
                    ->items($schema->object([
                        'id' => $schema->string(),
                        'name' => $schema->string(),
                        'icon' => $schema->string(),
                        'severity' => $schema->string(),
                    ]))
                    ->description('Task category options.'),
                'tags' => $schema->array()
                    ->items($this->labelledOption($schema))
                    ->description('Tag options.'),
            ])->description('Master option lists for task forms; all ids are sqid-encoded.')
                ->required(),
        ];
    }

    /**
     * The common {id, name, severity} option shape shared by priorities, types, and tags.
     */
    private function labelledOption(JsonSchema $schema): Type
    {
        return $schema->object([
            'id' => $schema->string(),
            'name' => $schema->string(),
            'severity' => $schema->string(),
        ]);
    }
}

<?php

namespace App\Mcp\Tools;

use App\Data\Mcp\ProjectData;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;

#[Name('get-user-projects')]
#[Title('Get Optimistic User Projects')]
#[Description('Fetch all projects for a user.')]
class MyProjectTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $data = $request->user()
            ->projects()
            ->with('status', 'priority')
            ->get();

        return Response::json([
            'data' => ProjectData::collect($data)->toArray(),
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
            'data' => $schema->array()
                ->items($schema->object([
                    'id' => $schema->string()->description('Using for get list of tasks'),
                    'project_no' => $schema->string()->description('Unique project identifier'),
                    'emoji' => $schema->string(),
                    'title' => $schema->string(),
                    'status_id' => $schema->string(),
                    'status_name' => $schema->string(),
                    'priority_id' => $schema->string(),
                    'priority_name' => $schema->string(),
                    'description' => $schema->string(),
                    'start_date' => $schema->string(),
                    'due_date' => $schema->string(),
                    'progress' => $schema->number(),
                ]))
                ->description('List of projects')
                ->required(),
        ];
    }
}

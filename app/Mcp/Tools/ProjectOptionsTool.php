<?php

namespace App\Mcp\Tools;

use App\Data\Project\ProjectPriorityData;
use App\Data\Project\ProjectStatusData;
use App\Facades\Sqids;
use App\Repositories\ProjectRepository;
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
use Spatie\LaravelData\DataCollection;

#[Name('get-project-options')]
#[Title('Get Project Options')]
#[Description('Fetch the master option lists used by project forms: statuses and priorities. Use the encoded ids when creating or filtering projects.')]
#[IsReadOnly]
#[IsIdempotent]
class ProjectOptionsTool extends Tool
{
    public function __construct(
        protected ProjectRepository $repository,
    ) {}

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $data = [
            'statuses' => ProjectStatusData::collect(
                $this->repository->getProjectStatuses(),
                DataCollection::class
            )->toArray(),

            'priorities' => ProjectPriorityData::collect(
                $this->repository->getProjectPriorities(),
                DataCollection::class
            )->toArray(),
        ];

        return Response::json([
            'data' => Sqids::rec_encode_ids_in_list($data),
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
                'statuses' => $schema->array()
                    ->items($this->option($schema))
                    ->description('Project status options.'),
                'priorities' => $schema->array()
                    ->items($this->option($schema))
                    ->description('Project priority options.'),
            ])->description('Master option lists for project forms; all ids are sqid-encoded.')
                ->required(),
        ];
    }

    /**
     * The common {id, name, severity} option shape shared by statuses and priorities.
     */
    private function option(JsonSchema $schema): Type
    {
        return $schema->object([
            'id' => $schema->string(),
            'name' => $schema->string(),
            'severity' => $schema->string(),
        ]);
    }
}

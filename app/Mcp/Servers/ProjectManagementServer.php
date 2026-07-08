<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\CreateProjectTool;
use App\Mcp\Tools\MyProjectTool;
use App\Mcp\Tools\ProjectDetailTool;
use App\Mcp\Tools\ProjectOptionsTool;
use App\Mcp\Tools\TaskOptionsTool;
use App\Mcp\Tools\TaskProjectTools;
use App\Mcp\Tools\UpdateProjectTool;
use App\Mcp\Tools\UpdateTaskStatusTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('Project Management Server')]
#[Version('0.0.1')]
#[Instructions('This is a server for the project management app.')]
class ProjectManagementServer extends Server
{
    protected array $tools = [
        MyProjectTool::class,
        TaskProjectTools::class,
        TaskOptionsTool::class,
        ProjectDetailTool::class,
        ProjectOptionsTool::class,
        CreateProjectTool::class,
        UpdateProjectTool::class,
        UpdateTaskStatusTool::class,
    ];

    protected array $resources = [
        //
    ];

    protected array $prompts = [
        //
    ];
}

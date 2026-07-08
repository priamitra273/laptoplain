<?php

use App\Mcp\Servers\ProjectManagementServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::oauthRoutes();

// Mcp::web('/mcp/demo', \App\Mcp\Servers\PublicServer::class);
Mcp::web('/mcp', ProjectManagementServer::class)
    ->middleware('auth:api');

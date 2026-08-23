<?php

use App\Mcp\Servers\DirectoryServer;
use App\Mcp\Servers\TicketsServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::local('directory', DirectoryServer::class);
Mcp::local('tickets', TicketsServer::class);

Mcp::web('/mcp/directory', DirectoryServer::class)
    ->middleware(['auth:sanctum', 'throttle:mcp']);

Mcp::web('/mcp/tickets', TicketsServer::class)
    ->middleware(['auth:sanctum', 'throttle:mcp']);

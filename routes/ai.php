<?php

use App\Mcp\Servers\TicketsServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::local('tickets', TicketsServer::class);

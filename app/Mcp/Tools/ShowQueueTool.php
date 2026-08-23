<?php

namespace App\Mcp\Tools;

use App\Mcp\Resources\QueueApp;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\RendersApp;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('show_queue')]
#[Description('Open the interactive ticket queue. The host renders QueueApp after this call.')]
#[IsReadOnly]
#[RendersApp(resource: QueueApp::class)]
class ShowQueueTool extends Tool
{
    public function handle(Request $request): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return Response::error('You must be signed in.');
        }

        if (! $user->can('viewAny', Ticket::class)) {
            return Response::error('Permission denied.');
        }

        return Response::text('Open queue loaded.');
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}

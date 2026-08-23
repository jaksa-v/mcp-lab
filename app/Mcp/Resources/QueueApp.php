<?php

namespace App\Mcp\Resources;

use App\Models\Ticket;
use App\Models\User;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\AppResource;
use Laravel\Mcp\Server\Attributes\AppMeta;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Ui\Enums\Library;

#[Name('queue-app')]
#[Title('Open queue')]
#[Description('Interactive open-ticket queue. The iframe refreshes through get_queue_data.')]
#[AppMeta(libraries: [Library::Tailwind, Library::Alpine])]
class QueueApp extends AppResource
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

        return Response::view('mcp.queue-app', [
            'title' => $this->title(),
        ]);
    }
}

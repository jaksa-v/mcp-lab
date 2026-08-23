<?php

namespace App\Mcp\Resources;

use App\Enums\Status;
use App\Models\Ticket;
use App\Models\User;
use App\Repositories\TicketRepository;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\MimeType;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Attributes\Uri;
use Laravel\Mcp\Server\Resource;

#[Name('queue')]
#[Title('Open queue')]
#[Description('Open tickets the current user can see, rendered as a markdown list.')]
#[Uri('desk://queue')]
#[MimeType('text/markdown')]
class QueueResource extends Resource
{
    public function __construct(private TicketRepository $tickets) {}

    public function handle(Request $request): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return Response::error('You must be signed in.');
        }

        if (! $user->can('viewAny', Ticket::class)) {
            return Response::error('Permission denied.');
        }

        $tickets = $this->tickets->listVisibleTo($user, Status::Open);

        if ($tickets->isEmpty()) {
            return Response::text("# Open queue\n\nNo open tickets.");
        }

        $lines = $tickets->map(function (Ticket $ticket): string {
            $assignee = $ticket->assignee_id !== null
                ? $ticket->assignee->name
                : 'Unassigned';

            return sprintf(
                '#%d [%s / %s] %s — %s → %s',
                $ticket->id,
                $ticket->status->value,
                $ticket->priority->value,
                $ticket->subject,
                $ticket->requester->name,
                $assignee,
            );
        });

        return Response::text("# Open queue\n\n".$lines->implode("\n"));
    }
}

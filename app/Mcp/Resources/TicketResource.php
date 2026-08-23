<?php

namespace App\Mcp\Resources;

use App\Models\Comment;
use App\Models\User;
use App\Repositories\TicketRepository;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\MimeType;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Contracts\HasUriTemplate;
use Laravel\Mcp\Server\Resource;
use Laravel\Mcp\Support\UriTemplate;

#[Name('ticket')]
#[Title('Ticket dossier')]
#[Description('Markdown dossier for one ticket, including the request and comments.')]
#[MimeType('text/markdown')]
class TicketResource extends Resource implements HasUriTemplate
{
    public function __construct(private TicketRepository $tickets) {}

    public function uriTemplate(): UriTemplate
    {
        return new UriTemplate('desk://tickets/{id}');
    }

    public function handle(Request $request): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return Response::error('You must be signed in.');
        }

        $ticket = $this->tickets->find((int) $request->get('id'));

        if ($ticket === null) {
            return Response::error('No ticket matches that id. Use desk://queue to see ids you can read.');
        }

        if (! $user->can('view', $ticket)) {
            return Response::error('Permission denied.');
        }

        $assignee = $ticket->assignee_id !== null
            ? $ticket->assignee->name
            : 'Unassigned';

        $lines = [
            "# Ticket #{$ticket->id}: {$ticket->subject}",
            '',
            '- Status: '.$ticket->status->value,
            '- Priority: '.$ticket->priority->value,
            "- Requester: {$ticket->requester->name}",
            "- Assignee: {$assignee}",
            '',
            '## Request',
            '',
            $ticket->body,
        ];

        if ($ticket->comments->isEmpty()) {
            return Response::text(implode("\n", $lines));
        }

        $lines[] = '';
        $lines[] = '## Comments';
        $lines[] = '';

        foreach ($ticket->comments as $comment) {
            $lines[] = $this->commentLine($comment);
            $lines[] = '';
        }

        return Response::text(implode("\n", $lines));
    }

    private function commentLine(Comment $comment): string
    {
        $when = $comment->created_at?->toDateString() ?? 'unknown date';

        return "**{$comment->user->name}** ({$when}): {$comment->body}";
    }
}

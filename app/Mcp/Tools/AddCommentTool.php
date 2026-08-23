<?php

namespace App\Mcp\Tools;

use App\Models\User;
use App\Repositories\TicketRepository;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('add_comment')]
#[Description('Add a comment to a ticket. Requesters can comment on their own tickets. Agents can comment on any ticket.')]
class AddCommentTool extends Tool
{
    public function __construct(private TicketRepository $tickets) {}

    public function handle(Request $request): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return Response::error('You must be signed in.');
        }

        $validated = $request->validate([
            'ticket_id' => ['required', 'integer'],
            'body' => ['required', 'string'],
        ], [
            'ticket_id.required' => 'Give me the ticket id, like 12.',
            'ticket_id.integer' => 'Ticket id must be a number.',
            'body.required' => 'Write a comment. Empty notes do not help anyone.',
        ]);

        $ticket = $this->tickets->find((int) $validated['ticket_id']);

        if ($ticket === null) {
            return Response::error('No ticket matches that id. Use list_tickets to see ids you can read.');
        }

        if (! $user->can('comment', $ticket)) {
            return Response::error('Permission denied.');
        }

        $this->tickets->addComment($ticket, $user, $validated['body']);

        return Response::text("Commented on ticket #{$ticket->id}.");
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'ticket_id' => $schema->integer()
                ->description('The ticket id from list_tickets.')
                ->required(),
            'body' => $schema->string()
                ->description('The comment to add.')
                ->required(),
        ];
    }
}

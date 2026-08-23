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
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('get_ticket')]
#[Description('Read one ticket by id, including subject, body, status, requester, and assignee.')]
#[IsReadOnly]
class GetTicketTool extends Tool
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
        ], [
            'ticket_id.required' => 'Give me the ticket id, like 12.',
            'ticket_id.integer' => 'Ticket id must be a number.',
        ]);

        $ticket = $this->tickets->find((int) $validated['ticket_id']);

        if ($ticket === null) {
            return Response::error('No ticket matches that id. Use list_tickets to see ids you can read.');
        }

        if (! $user->can('view', $ticket)) {
            return Response::error('Permission denied.');
        }

        $assignee = $ticket->assignee_id !== null
            ? $ticket->assignee->name
            : 'Unassigned';

        return Response::text(implode("\n", [
            "Ticket #{$ticket->id}",
            "Subject: {$ticket->subject}",
            'Status: '.$ticket->status->value,
            'Priority: '.$ticket->priority->value,
            "Requester: {$ticket->requester->name}",
            "Assignee: {$assignee}",
            '',
            $ticket->body,
        ]));
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
        ];
    }
}

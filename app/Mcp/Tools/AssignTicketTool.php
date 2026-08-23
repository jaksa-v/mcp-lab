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
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[Name('assign_ticket')]
#[Description('Assign a ticket to a person by user id. Agents and admins only. Calling it again with the same person is a no-op.')]
#[IsIdempotent]
class AssignTicketTool extends Tool
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
            'assignee_id' => ['required', 'integer'],
        ], [
            'ticket_id.required' => 'Give me the ticket id, like 12.',
            'ticket_id.integer' => 'Ticket id must be a number.',
            'assignee_id.required' => 'Give me the assignee user id, like 4.',
            'assignee_id.integer' => 'Assignee id must be a number.',
        ]);

        $ticket = $this->tickets->find((int) $validated['ticket_id']);

        if ($ticket === null) {
            return Response::error('No ticket matches that id. Use list_tickets to see ids you can read.');
        }

        if (! $user->can('update', $ticket)) {
            return Response::error('Permission denied.');
        }

        $assignee = User::query()->find((int) $validated['assignee_id']);

        if (! $assignee instanceof User) {
            return Response::error('No person matches that assignee id.');
        }

        $ticket = $this->tickets->assign($ticket, $assignee);

        return Response::text("Assigned ticket #{$ticket->id} to {$assignee->name}.");
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
            'assignee_id' => $schema->integer()
                ->description('The user id of the person who should own the ticket.')
                ->required(),
        ];
    }
}

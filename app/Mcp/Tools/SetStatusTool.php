<?php

namespace App\Mcp\Tools;

use App\Enums\Status;
use App\Models\User;
use App\Repositories\TicketRepository;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('set_status')]
#[Description('Change a ticket status to open, pending, or closed. Agents and admins only.')]
class SetStatusTool extends Tool
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
            'status' => ['required', 'string', 'in:open,pending,closed'],
        ], [
            'ticket_id.required' => 'Give me the ticket id, like 12.',
            'ticket_id.integer' => 'Ticket id must be a number.',
            'status.required' => 'Say whether the ticket should be open, pending, or closed.',
            'status.in' => 'Status must be open, pending, or closed.',
        ]);

        $ticket = $this->tickets->find((int) $validated['ticket_id']);

        if ($ticket === null) {
            return Response::error('No ticket matches that id. Use list_tickets to see ids you can read.');
        }

        if (! $user->can('update', $ticket)) {
            return Response::error('Permission denied.');
        }

        $status = Status::from($validated['status']);
        $ticket = $this->tickets->setStatus($ticket, $status);

        return Response::text("Ticket #{$ticket->id} is now {$ticket->status->value}.");
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
            'status' => $schema->string()
                ->enum(['open', 'pending', 'closed'])
                ->description('open, pending, or closed.')
                ->required(),
        ];
    }
}

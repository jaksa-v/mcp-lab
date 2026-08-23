<?php

namespace App\Mcp\Tools;

use App\Enums\Role;
use App\Models\User;
use App\Repositories\TicketRepository;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[Name('delete_ticket')]
#[Description('Permanently delete a ticket. Admins only.')]
#[IsDestructive]
class DeleteTicketTool extends Tool
{
    public function __construct(private TicketRepository $tickets) {}

    public function shouldRegister(Request $request): bool
    {
        $user = $request->user();

        return $user instanceof User && $user->role === Role::Admin;
    }

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

        if (! $user->can('delete', $ticket)) {
            return Response::error('Permission denied.');
        }

        $id = $ticket->id;
        $this->tickets->delete($ticket);

        return Response::text("Deleted ticket #{$id}.");
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

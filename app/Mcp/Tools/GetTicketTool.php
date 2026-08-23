<?php

namespace App\Mcp\Tools;

use App\Models\User;
use App\Repositories\TicketRepository;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Exceptions\ClientException;
use Laravel\Mcp\Exceptions\JsonRpcException;
use Laravel\Mcp\Facades\Mcp;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('get_ticket')]
#[Description('Read one ticket by id, including subject, body, status, requester, and assignee. People are resolved through the directory server.')]
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

        $requester = $this->personName($ticket->requester_id) ?? 'Unknown';
        $assignee = $ticket->assignee_id !== null
            ? ($this->personName($ticket->assignee_id) ?? 'Unknown')
            : 'Unassigned';

        return Response::text(implode("\n", [
            "Ticket #{$ticket->id}",
            "Subject: {$ticket->subject}",
            'Status: '.$ticket->status->value,
            'Priority: '.$ticket->priority->value,
            "Requester: {$requester}",
            "Assignee: {$assignee}",
            '',
            $ticket->body,
        ]))->withMeta(['source' => 'eloquent']);
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

    private function personName(int $id): ?string
    {
        try {
            $result = Mcp::client('directory')->readResource('directory://people/'.$id);
        } catch (ClientException|JsonRpcException) {
            return null;
        }

        if (preg_match('/^#\s+(.+)$/m', $result->content(), $matches) !== 1) {
            return null;
        }

        return $matches[1];
    }
}

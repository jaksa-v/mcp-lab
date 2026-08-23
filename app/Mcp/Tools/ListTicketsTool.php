<?php

namespace App\Mcp\Tools;

use App\Enums\Priority;
use App\Enums\Status;
use App\Models\Ticket;
use App\Models\User;
use App\Repositories\TicketRepository;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('list_tickets')]
#[Description('List helpdesk tickets the current user can see. Requesters only see their own. Filter by status or priority.')]
#[IsReadOnly]
class ListTicketsTool extends Tool
{
    public function __construct(private TicketRepository $tickets) {}

    public function handle(Request $request): Response|ResponseFactory
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return Response::error('You must be signed in.');
        }

        if (! $user->can('viewAny', Ticket::class)) {
            return Response::error('Permission denied.');
        }

        $validated = $request->validate([
            'status' => ['sometimes', 'nullable', 'string', 'in:open,pending,closed'],
            'priority' => ['sometimes', 'nullable', 'string', 'in:low,normal,high,urgent'],
        ], [
            'status.in' => 'Status must be open, pending, or closed.',
            'priority.in' => 'Priority must be low, normal, high, or urgent.',
        ]);

        $tickets = $this->tickets->listVisibleTo(
            $user,
            isset($validated['status']) ? Status::from($validated['status']) : null,
            isset($validated['priority']) ? Priority::from($validated['priority']) : null,
        );

        if ($tickets->isEmpty()) {
            return Response::text('No tickets match.');
        }

        return $this->listedTickets($tickets);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'status' => $schema->string()
                ->enum(['open', 'pending', 'closed'])
                ->description('Optional status filter: open, pending, or closed.'),
            'priority' => $schema->string()
                ->enum(['low', 'normal', 'high', 'urgent'])
                ->description('Optional priority filter: low, normal, high, or urgent.'),
        ];
    }

    /**
     * @return array<string, Type>
     */
    public function outputSchema(JsonSchema $schema): array
    {
        return [
            'tickets' => $schema->array()
                ->items($schema->object([
                    'id' => $schema->integer()->description('Ticket id.')->required(),
                    'subject' => $schema->string()->description('Short subject.')->required(),
                    'status' => $schema->string()->enum(['open', 'pending', 'closed'])->required(),
                    'priority' => $schema->string()->enum(['low', 'normal', 'high', 'urgent'])->required(),
                    'requester' => $schema->string()->description('Requester name.')->required(),
                    'assignee' => $schema->string()->description('Assignee name, if any.')->nullable(),
                    'uri' => $schema->string()->description('desk://tickets/{id} resource.')->required(),
                ]))
                ->description('Tickets the current user can see.')
                ->required(),
        ];
    }

    /**
     * @param  Collection<int, Ticket>  $tickets
     */
    private function listedTickets(Collection $tickets): ResponseFactory
    {
        $payload = [
            'tickets' => $tickets->map($this->toListedTicket(...))->values()->all(),
        ];

        $links = $tickets->map(fn (Ticket $ticket): Response => Response::resourceLink(
            uri: 'desk://tickets/'.$ticket->id,
            name: 'ticket-'.$ticket->id,
            mimeType: 'text/markdown',
            title: $ticket->subject,
        ))->all();

        return Response::make([
            ...Response::structured($payload)->responses()->all(),
            ...$links,
        ])->withStructuredContent($payload);
    }

    /**
     * @return array{id: int, subject: string, status: string, priority: string, requester: string, assignee: string|null, uri: string}
     */
    private function toListedTicket(Ticket $ticket): array
    {
        return [
            'id' => $ticket->id,
            'subject' => $ticket->subject,
            'status' => $ticket->status->value,
            'priority' => $ticket->priority->value,
            'requester' => $ticket->requester->name,
            'assignee' => $ticket->assignee_id !== null ? $ticket->assignee->name : null,
            'uri' => 'desk://tickets/'.$ticket->id,
        ];
    }
}

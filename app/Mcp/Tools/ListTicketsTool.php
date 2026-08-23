<?php

namespace App\Mcp\Tools;

use App\Enums\Priority;
use App\Enums\Status;
use App\Models\Ticket;
use App\Models\User;
use App\Repositories\TicketRepository;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('list_tickets')]
#[Description('List helpdesk tickets the current user can see. Requesters only see their own. Filter by status or priority.')]
class ListTicketsTool extends Tool
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

        return Response::text($lines->implode("\n"));
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
}

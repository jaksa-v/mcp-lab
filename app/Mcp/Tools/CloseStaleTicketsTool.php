<?php

namespace App\Mcp\Tools;

use App\Enums\Status;
use App\Models\Ticket;
use App\Models\User;
use App\Repositories\TicketRepository;
use Generator;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('close_stale_tickets')]
#[Description('Close every open ticket older than N days. Agents and admins only. Yields progress as each ticket closes.')]
class CloseStaleTicketsTool extends Tool
{
    public function __construct(private TicketRepository $tickets) {}

    /**
     * @return Generator<int, Response>
     */
    public function handle(Request $request): Generator
    {
        $user = $request->user();

        if (! $user instanceof User) {
            yield Response::error('You must be signed in.');

            return;
        }

        $validated = $request->validate([
            'days' => ['required', 'integer', 'min:1'],
        ], [
            'days.required' => 'Say how many days a ticket must sit open before it is stale, like 7.',
            'days.integer' => 'Days must be a number, like 7.',
            'days.min' => 'Days must be at least 1.',
        ]);

        $tickets = $this->tickets->listOpenOlderThan((int) $validated['days']);

        if ($tickets->contains(fn (Ticket $ticket): bool => ! $user->can('update', $ticket))) {
            yield Response::error('Permission denied.');

            return;
        }

        if ($tickets->isEmpty()) {
            yield Response::text('No open tickets are that old.');

            return;
        }

        $total = $tickets->count();

        foreach ($tickets->values() as $index => $ticket) {
            $this->tickets->setStatus($ticket, Status::Closed);

            yield Response::notification('processing/progress', [
                'current' => $index + 1,
                'total' => $total,
                'ticket_id' => $ticket->id,
            ]);

            yield Response::text("Closed ticket #{$ticket->id}: {$ticket->subject}");
        }
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'days' => $schema->integer()
                ->description('Close open tickets older than this many days.')
                ->required(),
        ];
    }
}

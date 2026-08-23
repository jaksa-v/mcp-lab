<?php

namespace App\Mcp\Prompts;

use App\Models\User;
use App\Repositories\TicketRepository;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Prompts\Argument;

#[Name('triage_ticket')]
#[Description('Build a prompt to triage a ticket: priority, team, and next status.')]
class TriageTicketPrompt extends Prompt
{
    public function __construct(private TicketRepository $tickets) {}

    /**
     * @return Response|array<int, Response>
     */
    public function handle(Request $request): Response|array
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return Response::error('You must be signed in.');
        }

        $validated = $request->validate([
            'ticket_id' => ['required', 'integer'],
        ], [
            'ticket_id.required' => 'Give me the ticket id to triage, like 12.',
            'ticket_id.integer' => 'Ticket id must be a number.',
        ]);

        $ticket = $this->tickets->find((int) $validated['ticket_id']);

        if ($ticket === null) {
            return Response::error('No ticket matches that id. Use list_tickets to see ids you can read.');
        }

        if (! $user->can('view', $ticket)) {
            return Response::error('Permission denied.');
        }

        return [
            Response::text(implode("\n", [
                'You are triaging Northwind Support tickets. Read the subject and the body. Do not guess from the subject alone.',
                'Set priority. Urgent if money, safety, or a blocked warehouse is on the line. High if a customer is waiting. Otherwise normal or low.',
                'Assign a team. Billing owns refunds and invoices. Warehouse owns shipping and pallets. Support owns everything else.',
                'Leave the ticket open while you work. Pending if you are waiting on the customer. Closed only when the requester is done.',
                'The full playbook is at desk://playbook.',
            ]))->asAssistant(),
            Response::text(implode("\n", [
                "Triage ticket #{$ticket->id}: {$ticket->subject}",
                'Status: '.$ticket->status->value,
                'Priority: '.$ticket->priority->value,
                '',
                $ticket->body,
            ])),
        ];
    }

    /**
     * @return array<int, Argument>
     */
    public function arguments(): array
    {
        return [
            new Argument(
                name: 'ticket_id',
                description: 'The ticket id from list_tickets.',
                required: true,
            ),
        ];
    }
}

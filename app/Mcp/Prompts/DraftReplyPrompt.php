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

#[Name('draft_reply')]
#[Description('Build a prompt to draft a customer-facing reply to a ticket in a given tone.')]
class DraftReplyPrompt extends Prompt
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
            'tone' => ['required', 'string', 'max:50'],
        ], [
            'ticket_id.required' => 'Give me the ticket id, like 12.',
            'ticket_id.integer' => 'Ticket id must be a number.',
            'tone.required' => 'Say how the reply should sound, like curt, warm, or formal.',
            'tone.max' => 'Keep the tone under 50 characters. Examples: curt, warm, formal.',
        ]);

        $ticket = $this->tickets->find((int) $validated['ticket_id']);

        if ($ticket === null) {
            return Response::error('No ticket matches that id. Use list_tickets to see ids you can read.');
        }

        if (! $user->can('view', $ticket)) {
            return Response::error('Permission denied.');
        }

        $tone = $validated['tone'];

        return [
            Response::text(implode("\n", [
                'You are a Northwind Support agent writing a customer-facing reply.',
                "Write in a {$tone} tone.",
                'Stick to the ticket facts. Do not invent refunds, tracking numbers, or promises we cannot keep.',
            ]))->asAssistant(),
            Response::text(implode("\n\n", [
                "Draft a reply to ticket #{$ticket->id}: {$ticket->subject}",
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
            new Argument(
                name: 'tone',
                description: 'How the reply should sound, like curt, warm, or formal.',
                required: true,
            ),
        ];
    }
}

<?php

namespace App\Mcp\Tools;

use App\Enums\Priority;
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

#[Name('create_ticket')]
#[Description('Open a new helpdesk ticket as the signed-in user. Requesters open tickets for themselves.')]
class CreateTicketTool extends Tool
{
    public function __construct(private TicketRepository $tickets) {}

    public function handle(Request $request): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return Response::error('You must be signed in.');
        }

        if (! $user->can('create', Ticket::class)) {
            return Response::error('Permission denied.');
        }

        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'priority' => ['sometimes', 'nullable', 'string', 'in:low,normal,high,urgent'],
        ], [
            'subject.required' => 'Give the ticket a subject, like "Refund for order 4412".',
            'subject.max' => 'Keep the subject under 255 characters.',
            'body.required' => 'Describe the problem in the body so an agent knows what happened.',
            'priority.in' => 'Priority must be low, normal, high, or urgent.',
        ]);

        $ticket = $this->tickets->createFor(
            $user,
            $validated['subject'],
            $validated['body'],
            isset($validated['priority']) ? Priority::from($validated['priority']) : Priority::Normal,
        );

        return Response::text("Opened ticket #{$ticket->id}: {$ticket->subject}");
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'subject' => $schema->string()
                ->description('Short subject, like "Refund for order 4412".')
                ->required(),
            'body' => $schema->string()
                ->description('What happened, in enough detail for an agent to act.')
                ->required(),
            'priority' => $schema->string()
                ->enum(['low', 'normal', 'high', 'urgent'])
                ->description('Optional priority. Defaults to normal.'),
        ];
    }
}

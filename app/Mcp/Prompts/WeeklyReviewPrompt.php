<?php

namespace App\Mcp\Prompts;

use App\Enums\Status;
use App\Models\Ticket;
use App\Models\User;
use App\Repositories\TicketRepository;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Prompt;

#[Name('weekly_review')]
#[Description('Build a prompt to recap open tickets for the week. Agents and admins only.')]
class WeeklyReviewPrompt extends Prompt
{
    public function __construct(private TicketRepository $tickets) {}

    public function shouldRegister(Request $request): bool
    {
        $user = $request->user();

        return $user instanceof User && $user->role->isStaff();
    }

    /**
     * @return Response|array<int, Response>
     */
    public function handle(Request $request): Response|array
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return Response::error('You must be signed in.');
        }

        $open = $this->tickets->listVisibleTo($user, Status::Open);

        $queue = $open->isEmpty()
            ? 'There are no open tickets.'
            : $open->map(function (Ticket $ticket): string {
                return sprintf(
                    '#%d [%s] %s',
                    $ticket->id,
                    $ticket->priority->value,
                    $ticket->subject,
                );
            })->implode("\n");

        return [
            Response::text(implode("\n", [
                'You are writing Northwind Support\'s weekly review for staff.',
                'Summarize volume, call out urgent or stuck tickets, and name anything that needs an admin.',
                'Do not invent tickets that are not in the list.',
            ]))->asAssistant(),
            Response::text("Open tickets this week:\n\n{$queue}"),
        ];
    }
}

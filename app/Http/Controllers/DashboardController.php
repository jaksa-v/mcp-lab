<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Dump tickets and people so write tools can be checked by refresh.
     */
    public function __invoke(): Response
    {
        return Inertia::render('dashboard', [
            'tickets' => Ticket::query()
                ->select(['id', 'subject', 'status', 'priority', 'requester_id', 'assignee_id'])
                ->with(['requester:id,name', 'assignee:id,name'])
                ->latest()
                ->get()
                ->map(fn (Ticket $ticket): array => [
                    'id' => $ticket->id,
                    'subject' => $ticket->subject,
                    'status' => $ticket->status->value,
                    'priority' => $ticket->priority->value,
                    'requester' => $ticket->requester->name,
                    'assignee' => $ticket->assignee?->name,
                ]),
            'people' => User::query()
                ->select(['id', 'name', 'role', 'team_id', 'on_call'])
                ->with(['team:id,name'])
                ->orderBy('id')
                ->get()
                ->map(fn (User $user): array => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'role' => $user->role->value,
                    'team' => $user->team?->name,
                    'on_call' => $user->on_call,
                ]),
        ]);
    }
}

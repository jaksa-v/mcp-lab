<?php

namespace App\Repositories;

use App\Enums\Priority;
use App\Enums\Status;
use App\Models\Comment;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class TicketRepository
{
    /**
     * @return Collection<int, Ticket>
     */
    public function listVisibleTo(User $user, ?Status $status = null, ?Priority $priority = null): Collection
    {
        return Ticket::query()
            ->select(['id', 'subject', 'body', 'status', 'priority', 'requester_id', 'assignee_id', 'team_id', 'created_at'])
            ->with(['requester:id,name', 'assignee:id,name'])
            ->when($status instanceof Status, fn ($query) => $query->where('status', $status))
            ->when($priority instanceof Priority, fn ($query) => $query->where('priority', $priority))
            ->when(! $user->role->isStaff(), fn ($query) => $query->whereBelongsTo($user, 'requester'))
            ->latest()
            ->get();
    }

    public function find(int $id): ?Ticket
    {
        return Ticket::query()
            ->with([
                'requester:id,name',
                'assignee:id,name',
                'comments' => fn ($query) => $query->oldest()->with('user:id,name'),
            ])
            ->find($id);
    }

    public function createFor(User $requester, string $subject, string $body, Priority $priority = Priority::Normal): Ticket
    {
        return Ticket::query()->create([
            'subject' => $subject,
            'body' => $body,
            'status' => Status::Open,
            'priority' => $priority,
            'requester_id' => $requester->id,
        ]);
    }

    public function addComment(Ticket $ticket, User $author, string $body): Comment
    {
        return $ticket->comments()->create([
            'user_id' => $author->id,
            'body' => $body,
        ]);
    }

    public function setStatus(Ticket $ticket, Status $status): Ticket
    {
        $ticket->update(['status' => $status]);

        return $ticket->refresh();
    }

    public function assign(Ticket $ticket, User $assignee): Ticket
    {
        $ticket->update(['assignee_id' => $assignee->id]);

        return $ticket->load(['requester:id,name', 'assignee:id,name']);
    }

    public function delete(Ticket $ticket): void
    {
        $ticket->delete();
    }
}

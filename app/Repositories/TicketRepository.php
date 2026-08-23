<?php

namespace App\Repositories;

use App\Enums\Priority;
use App\Enums\Status;
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
            ->with(['requester:id,name', 'assignee:id,name'])
            ->find($id);
    }
}

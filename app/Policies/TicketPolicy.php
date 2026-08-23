<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    public function view(User $user, Ticket $ticket): bool
    {
        return $user->role->isStaff() || $ticket->requester()->is($user);
    }

    public function create(User $user): bool
    {
        return $user->exists;
    }

    public function update(User $user, Ticket $ticket): bool
    {
        return $ticket->exists && $user->role->isStaff();
    }

    public function comment(User $user, Ticket $ticket): bool
    {
        return $user->role->isStaff() || $ticket->requester()->is($user);
    }

    public function delete(User $user, Ticket $ticket): bool
    {
        return $user->role === Role::Admin;
    }
}

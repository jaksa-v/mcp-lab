<?php

use App\Models\Ticket;
use App\Models\User;

test('a requester can view and comment on their own ticket', function () {
    $requester = User::factory()->requester()->create();
    $ticket = Ticket::factory()->for($requester, 'requester')->create();

    expect($requester->can('view', $ticket))->toBeTrue()
        ->and($requester->can('comment', $ticket))->toBeTrue()
        ->and($requester->can('create', Ticket::class))->toBeTrue();
});

test('a requester cannot view or comment on someone elses ticket', function () {
    $requester = User::factory()->requester()->create();
    $ticket = Ticket::factory()->create();

    expect($requester->can('view', $ticket))->toBeFalse()
        ->and($requester->can('comment', $ticket))->toBeFalse();
});

test('a requester cannot assign or delete a ticket', function () {
    $requester = User::factory()->requester()->create();
    $ticket = Ticket::factory()->for($requester, 'requester')->create();

    expect($requester->can('update', $ticket))->toBeFalse()
        ->and($requester->can('delete', $ticket))->toBeFalse();
});

test('an agent can view, assign, and comment on any ticket', function () {
    $agent = User::factory()->agent()->create();
    $ticket = Ticket::factory()->create();

    expect($agent->can('view', $ticket))->toBeTrue()
        ->and($agent->can('update', $ticket))->toBeTrue()
        ->and($agent->can('comment', $ticket))->toBeTrue()
        ->and($agent->can('delete', $ticket))->toBeFalse();
});

test('an admin can delete a ticket', function () {
    $admin = User::factory()->admin()->create();
    $ticket = Ticket::factory()->create();

    expect($admin->can('view', $ticket))->toBeTrue()
        ->and($admin->can('update', $ticket))->toBeTrue()
        ->and($admin->can('delete', $ticket))->toBeTrue();
});

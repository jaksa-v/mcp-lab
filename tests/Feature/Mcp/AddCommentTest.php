<?php

use App\Mcp\Servers\TicketsServer;
use App\Mcp\Tools\AddCommentTool;
use App\Models\Comment;
use App\Models\Ticket;
use App\Models\User;

test('a requester can comment on their own ticket', function () {
    $requester = User::factory()->requester()->create();
    $ticket = Ticket::factory()->for($requester, 'requester')->create();

    TicketsServer::actingAs($requester)
        ->tool(AddCommentTool::class, [
            'ticket_id' => $ticket->id,
            'body' => 'The pallet arrived cracked.',
        ])
        ->assertOk()
        ->assertSee('Commented on ticket #'.$ticket->id);

    $comment = Comment::query()
        ->where('ticket_id', $ticket->id)
        ->where('user_id', $requester->id)
        ->where('body', 'The pallet arrived cracked.')
        ->first();

    expect($comment)->not->toBeNull();
});

test('a requester cannot comment on someone elses ticket', function () {
    $requester = User::factory()->requester()->create();
    $ticket = Ticket::factory()->create();

    TicketsServer::actingAs($requester)
        ->tool(AddCommentTool::class, [
            'ticket_id' => $ticket->id,
            'body' => 'I should not be able to write this.',
        ])
        ->assertHasErrors()
        ->assertSee('Permission denied.');
});

test('an agent can comment on any ticket', function () {
    $agent = User::factory()->agent()->create();
    $ticket = Ticket::factory()->create();

    TicketsServer::actingAs($agent)
        ->tool(AddCommentTool::class, [
            'ticket_id' => $ticket->id,
            'body' => 'Asked warehouse for photos.',
        ])
        ->assertOk()
        ->assertSee('Commented on ticket #'.$ticket->id);
});

test('an empty comment is rejected with a sentence', function () {
    $agent = User::factory()->agent()->create();
    $ticket = Ticket::factory()->create();

    TicketsServer::actingAs($agent)
        ->tool(AddCommentTool::class, [
            'ticket_id' => $ticket->id,
        ])
        ->assertHasErrors()
        ->assertSee('Write a comment. Empty notes do not help anyone.');
});

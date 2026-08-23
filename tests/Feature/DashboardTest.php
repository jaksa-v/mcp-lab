<?php

use App\Models\User;
use Database\Seeders\LabSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->has('tickets')
            ->has('people', 1)
            ->where('people.0.id', $user->id)
            ->where('people.0.name', $user->name)
            ->where('people.0.role', 'requester'));
});

test('ada can see twenty tickets on the dashboard', function () {
    $this->seed(LabSeeder::class);

    $ada = User::query()->where('email', 'ada@northwind.test')->firstOrFail();

    $this->actingAs($ada)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->has('tickets', 20)
            ->has('people', 8)
            ->has('tickets.0', fn (Assert $ticket) => $ticket
                ->hasAll(['id', 'subject', 'status', 'priority', 'requester', 'assignee'])));
});

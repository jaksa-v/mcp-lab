<?php

use App\Models\Article;
use App\Models\User;

test('a requester can view a public article', function () {
    $requester = User::factory()->requester()->create();
    $article = Article::factory()->public()->create();

    expect($requester->can('view', $article))->toBeTrue();
});

test('a requester cannot view an internal article', function () {
    $requester = User::factory()->requester()->create();
    $article = Article::factory()->internal()->create();

    expect($requester->can('view', $article))->toBeFalse();
});

test('an agent can view an internal article', function () {
    $agent = User::factory()->agent()->create();
    $article = Article::factory()->internal()->create();

    expect($agent->can('view', $article))->toBeTrue();
});

test('an admin can view an internal article', function () {
    $admin = User::factory()->admin()->create();
    $article = Article::factory()->internal()->create();

    expect($admin->can('view', $article))->toBeTrue();
});

<?php

use App\Enums\Role;
use App\Enums\Visibility;
use App\Models\Article;
use App\Models\Comment;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\LabSeeder;
use Illuminate\Support\Facades\Storage;

test('it seeds the northwind lab data', function () {
    $this->seed(LabSeeder::class);

    expect(Team::query()->count())->toBe(3)
        ->and(User::query()->count())->toBe(8)
        ->and(Ticket::query()->count())->toBe(20)
        ->and(Comment::query()->count())->toBe(30)
        ->and(Article::query()->count())->toBe(4)
        ->and(User::query()->where('on_call', true)->count())->toBe(1)
        ->and(User::query()->where('email', 'ada@northwind.test')->first()?->role)->toBe(Role::Requester)
        ->and(User::query()->where('email', 'sam@northwind.test')->first()?->role)->toBe(Role::Agent)
        ->and(User::query()->where('email', 'root@northwind.test')->first()?->role)->toBe(Role::Admin)
        ->and(Article::query()->where('visibility', Visibility::Public)->count())->toBe(2)
        ->and(Article::query()->where('visibility', Visibility::Internal)->count())->toBe(2);

    expect(Storage::disk('local')->exists('badge.png'))->toBeTrue();
});

test('it can be run twice without duplicating rows', function () {
    $this->seed(LabSeeder::class);
    $this->seed(LabSeeder::class);

    expect(Team::query()->count())->toBe(3)
        ->and(User::query()->count())->toBe(8)
        ->and(Ticket::query()->count())->toBe(20)
        ->and(Comment::query()->count())->toBe(30)
        ->and(Article::query()->count())->toBe(4);
});

<?php

namespace App\Repositories;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class DirectoryRepository
{
    /**
     * @return Collection<int, User>
     */
    public function searchPeople(string $query, ?string $teamSlug = null): Collection
    {
        return User::query()
            ->with('team:id,name,slug')
            ->when(
                $teamSlug !== null && $teamSlug !== '',
                fn (Builder $builder): Builder => $builder->whereHas(
                    'team',
                    fn (Builder $team): Builder => $team->where('slug', $teamSlug),
                ),
            )
            ->where(function (Builder $builder) use ($query): void {
                $like = '%'.$query.'%';

                $builder->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('title', 'like', $like);
            })
            ->orderBy('name')
            ->limit(20)
            ->get();
    }

    public function findPerson(int $id): ?User
    {
        return User::query()
            ->with('team:id,name,slug')
            ->find($id);
    }

    /**
     * @return Collection<int, Team>
     */
    public function listTeams(): Collection
    {
        return Team::query()
            ->orderBy('name')
            ->get();
    }

    public function findTeamBySlug(string $slug): ?Team
    {
        return Team::query()
            ->where('slug', $slug)
            ->first();
    }

    public function onCallPerson(): ?User
    {
        return User::query()
            ->with('team:id,name,slug')
            ->where('on_call', true)
            ->orderBy('id')
            ->first();
    }

    /**
     * @return array{id: int, name: string, email: string, title: string|null, role: string, team: string|null, on_call: bool}
     */
    public function toPerson(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'title' => $user->title,
            'role' => $user->role->value,
            'team' => $user->team?->slug,
            'on_call' => $user->on_call,
        ];
    }
}

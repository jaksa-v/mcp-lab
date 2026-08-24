<?php

namespace App\Repositories;

use App\Enums\Visibility;
use App\Models\Article;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ArticleRepository
{
    /**
     * @return Collection<int, Article>
     */
    public function searchVisibleTo(User $user, string $query): Collection
    {
        return Article::query()
            ->when(
                ! $user->role->isStaff(),
                fn (Builder $builder): Builder => $builder->where('visibility', Visibility::Public),
            )
            ->where(function (Builder $builder) use ($query): void {
                $like = '%'.$query.'%';

                $builder->where('title', 'like', $like)
                    ->orWhere('body', 'like', $like)
                    ->orWhere('slug', 'like', $like);
            })
            ->orderBy('title')
            ->limit(20)
            ->get();
    }

    public function findBySlug(string $slug): ?Article
    {
        return Article::query()->where('slug', $slug)->first();
    }
}

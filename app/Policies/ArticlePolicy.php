<?php

namespace App\Policies;

use App\Enums\Visibility;
use App\Models\Article;
use App\Models\User;

class ArticlePolicy
{
    public function view(User $user, Article $article): bool
    {
        return $article->visibility === Visibility::Public || $user->role->isStaff();
    }
}

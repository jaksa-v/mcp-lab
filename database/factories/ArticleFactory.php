<?php

namespace Database\Factories;

use App\Enums\Visibility;
use App\Models\Article;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Article>
 */
class ArticleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'slug' => fake()->unique()->slug(),
            'title' => fake()->sentence(4),
            'body' => fake()->paragraphs(3, true),
            'visibility' => Visibility::Public,
        ];
    }

    public function public(): static
    {
        return $this->state(fn (array $attributes): array => [
            'visibility' => Visibility::Public,
        ]);
    }

    public function internal(): static
    {
        return $this->state(fn (array $attributes): array => [
            'visibility' => Visibility::Internal,
        ]);
    }
}

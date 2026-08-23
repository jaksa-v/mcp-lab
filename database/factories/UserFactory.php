<?php

namespace Database\Factories;

use App\Enums\Role;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role' => Role::Requester,
            'team_id' => null,
            'title' => fake()->jobTitle(),
            'on_call' => false,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the model has two-factor authentication configured.
     */
    public function withTwoFactor(): static
    {
        return $this;
    }

    public function requester(): static
    {
        return $this->state(fn (array $attributes): array => [
            'role' => Role::Requester,
        ]);
    }

    public function agent(): static
    {
        return $this->state(fn (array $attributes): array => [
            'role' => Role::Agent,
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes): array => [
            'role' => Role::Admin,
        ]);
    }

    public function onCall(): static
    {
        return $this->state(fn (array $attributes): array => [
            'on_call' => true,
        ]);
    }

    public function forTeam(Team $team): static
    {
        return $this->state(fn (array $attributes): array => [
            'team_id' => $team->id,
        ]);
    }
}

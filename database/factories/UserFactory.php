<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
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
            'is_admin' => false,
            'xp_points' => 0,
            'level' => 1,
            'current_streak' => 0,
            'last_activity_date' => null,
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
     * Indicate that the user is an admin.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_admin' => true,
        ]);
    }

    /**
     * Set specific XP and level.
     */
    public function withXp(int $xp): static
    {
        return $this->state(fn (array $attributes) => [
            'xp_points' => $xp,
            'level' => max(1, floor($xp / 100) + 1),
        ]);
    }

    /**
     * Set a streak.
     */
    public function withStreak(int $days): static
    {
        return $this->state(fn (array $attributes) => [
            'current_streak' => $days,
            'last_activity_date' => now()->subDay(),
        ]);
    }
}

<?php

namespace Database\Factories;

use App\Models\Discussion;
use App\Models\DiscussionReply;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DiscussionReplyFactory extends Factory
{
    protected $model = DiscussionReply::class;

    public function definition(): array
    {
        return [
            'discussion_id' => Discussion::factory(),
            'user_id' => User::factory(),
            'parent_id' => null,
            'content' => fake()->paragraphs(1, true),
            'is_best_answer' => false,
        ];
    }

    public function bestAnswer(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_best_answer' => true,
        ]);
    }

    public function asReplyTo(DiscussionReply $parent): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => $parent->id,
            'discussion_id' => $parent->discussion_id,
        ]);
    }
}

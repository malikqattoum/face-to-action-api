<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class LogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'customer_name' => fake()->name(),
            'action_taken' => fake()->sentence(6),
            'amount' => fake()->randomFloat(2, 20, 500),
            'currency' => 'USD',
            'next_steps' => fake()->optional()->sentence(4),
            'recorded_at' => fake()->dateTimeBetween('-30 days', 'now'),
            'transcribed_text' => fake()->optional()->paragraph(2),
            'audio_path' => null,
        ];
    }
}

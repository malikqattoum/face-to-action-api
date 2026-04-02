<?php

namespace Database\Factories;

use App\Models\CallSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CallSessionFactory extends Factory
{
    protected $model = CallSession::class;

    public function definition(): array
    {
        $startedAt = fake()->dateTimeBetween('-1 week', 'now');
        $duration = fake()->numberBetween(30, 600);
        $endedAt = (clone $startedAt)->modify("+{$duration} seconds");

        return [
            'user_id' => User::factory(),
            'contact_name' => fake()->name(),
            'phone_number' => fake()->phoneNumber(),
            'direction' => fake()->randomElement(['incoming', 'outgoing', 'missed']),
            'duration_seconds' => $duration,
            'started_at' => $startedAt,
            'ended_at' => $endedAt,
            'notes' => fake()->optional()->sentence(),
            'has_voice_memo' => fake()->boolean(30),
        ];
    }

    public function incoming(): static
    {
        return $this->state(fn (array $attrs) => ['direction' => 'incoming']);
    }

    public function outgoing(): static
    {
        return $this->state(fn (array $attrs) => ['direction' => 'outgoing']);
    }

    public function missed(): static
    {
        return $this->state(fn (array $attrs) => ['direction' => 'missed', 'duration_seconds' => 0]);
    }

    public function withMemo(): static
    {
        return $this->state(fn (array $attrs) => ['has_voice_memo' => true]);
    }
}

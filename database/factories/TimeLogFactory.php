<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TimeLog>
 */
class TimeLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = $this->faker->dateTimeBetween('-1 year', 'now');
        $end = (clone $start)->modify('+'.rand(30, 240).' minutes');
        $duration = $end->getTimestamp() - $start->getTimestamp();

        return [
            'task_id' => Task::inRandomOrder()->first()->id ?? Task::factory(),
            'user_id' => User::inRandomOrder()->first()->id ?? User::factory(),
            'start_time' => $start,
            'end_time' => $end,
            'duration' => $duration,
            'description' => $this->faker->sentence(),
            'mode' => $this->faker->randomElement(['Inside Office', 'Remote', 'Field Work']),
            'bucket' => $this->faker->randomElement(['Development', 'Meetings', 'Research']),
            'created_at' => $start,
            'updated_at' => $start,
        ];
    }
}

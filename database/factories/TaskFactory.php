<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class TaskFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(),
            'description' => $this->faker->paragraphs(2, true),
            'progress' => $this->faker->numberBetween(0, 100),
            'planned_hours' => $this->faker->randomFloat(2, 1, 40),
            'deadline' => $this->faker->dateTimeBetween('now', '+1 year')->format('Y-m-d H:i:s'),
            'priority' => $this->faker->randomElement(['low', 'medium', 'high', 'urgent']),
            'bucket' => $this->faker->word(),
            'task_id' => 'TASK-'.$this->faker->unique()->numberBetween(10000, 99999),
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d H:i:s'),
            'updated_at' => now()->format('Y-m-d H:i:s'),
        ];
    }
}

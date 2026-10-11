<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class TagFactory extends Factory
{
    public function definition(): array
    {
        $name = $this->faker->unique()->word().'-'.$this->faker->unique()->numberBetween(1, 1000);

        return [
            'name' => $name,
            'slug' => str()->slug($name),
            'color' => $this->faker->hexColor(),
        ];
    }
}

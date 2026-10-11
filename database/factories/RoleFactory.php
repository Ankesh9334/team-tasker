<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Role>
 */
class RoleFactory extends Factory
{
    public function definition(): array
    {
        $name = $this->faker->jobTitle().' '.$this->faker->unique()->numberBetween(1, 1000);

        return [
            'name' => $name,
            'slug' => str()->slug($name),
        ];
    }
}

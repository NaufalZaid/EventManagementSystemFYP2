<?php

namespace Database\Factories;

use App\Models\Society;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Society> */
class SocietyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company().' Society',
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\Salon;
use Illuminate\Database\Eloquent\Factories\Factory;

class SalonFactory extends Factory
{
    protected $model = Salon::class;

    public function definition(): array
    {
        return [
            'nombre' => fake()->unique()->word(),
            'codigo' => strtoupper(fake()->bothLetters(1)) . '-' . fake()->numerify('###'),
            'capacidad' => fake()->numberBetween(5, 50),
            'ubicacion' => fake()->address(),
        ];
    }
}

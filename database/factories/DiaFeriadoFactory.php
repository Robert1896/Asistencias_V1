<?php

namespace Database\Factories;

use App\Models\DiaFeriado;
use Illuminate\Database\Eloquent\Factories\Factory;

class DiaFeriadoFactory extends Factory
{
    protected $model = DiaFeriado::class;

    public function definition(): array
    {
        return [
            'fecha' => fake()->dateTimeBetween('+1 month', '+3 month')->format('Y-m-d'),
            'nombre' => fake()->word(),
            'recurrente' => fake()->boolean(),
        ];
    }
}

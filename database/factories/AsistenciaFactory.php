<?php

namespace Database\Factories;

use App\Models\Asistencia;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AsistenciaFactory extends Factory
{
    protected $model = Asistencia::class;

    public function definition(): array
    {
        return [
            'usuario_id' => User::factory(),
            'fecha' => today()->toDateString(),
            'hora_entrada' => fake()->time('H:i:s', '09:00'),
            'hora_salida' => fake()->time('H:i:s', '17:00'),
            'estado' => 'Presente',
            'justificado' => 'No',
        ];
    }

    public function tardy(): static
    {
        return $this->state(fn (array $attributes) => ['estado' => 'Tarde']);
    }

    public function absent(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'Ausente',
            'hora_entrada' => null,
            'hora_salida' => null,
        ]);
    }
}

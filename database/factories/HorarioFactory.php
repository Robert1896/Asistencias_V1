<?php

namespace Database\Factories;

use App\Models\Horario;
use Illuminate\Database\Eloquent\Factories\Factory;

class HorarioFactory extends Factory
{
    protected $model = Horario::class;

    public function definition(): array
    {
        $horaInicio = fake()->randomElement(['07:00', '07:45', '08:35', '09:20', '10:10', '10:55', '11:45', '13:05', '13:50', '14:40', '15:25', '16:15', '17:00']);
        $horaFin = $this->calcularFin($horaInicio);

        return [
            'dia_semana' => fake()->numberBetween(1, 6),
            'hora_inicio' => $horaInicio,
            'hora_fin' => $horaFin,
            'nombre' => fake()->word(),
        ];
    }

    private function calcularFin(string $inicio): string
    {
        $mins = (int) substr($inicio, 3, 2) + 45;
        $hrs = (int) substr($inicio, 0, 2);
        if ($mins >= 60) {
            $hrs += 1;
            $mins -= 60;
        }
        return sprintf('%02d:%02d', $hrs, $mins);
    }
}

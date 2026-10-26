<?php

namespace Database\Factories;

use App\Models\Rol;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'nombre' => fake()->firstName(),
            'apellido' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('ClaveFactorySegura2026!'),
            'cedula' => fake()->unique()->numerify('#######'),
            'rol_id' => Rol::factory(),
            'estatus' => 'activo',
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['estatus' => 'inactivo']);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'rol_id' => 1,
        ]);
    }

    public function docente(): static
    {
        return $this->state(fn (array $attributes) => [
            'rol_id' => 3,
        ]);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use HasFactory;

    protected $table = 'activity_log';

    protected $fillable = [
        'user_id',
        'accion',
        'descripcion',
        'tipo',
        'datos',
    ];

    protected function casts(): array
    {
        return [
            'datos' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function log(string $accion, string $descripcion, string $tipo = 'sistema', ?array $datos = null): static
    {
        return static::create([
            'user_id'     => auth()->id(),
            'accion'      => $accion,
            'descripcion' => $descripcion,
            'tipo'        => $tipo,
            'datos'       => $datos,
        ]);
    }
}

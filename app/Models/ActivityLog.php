<?php

namespace App\Models;

use App\Support\DeviceDetector;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

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
        'dispositivo',
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

    public static function log(string $accion, string $descripcion, string $tipo = 'sistema', ?array $datos = null, ?int $userId = null): ?static
    {
        try {
            $userId = $userId ?? auth()->id();

            $data = [
                'user_id'     => $userId,
                'accion'      => $accion,
                'descripcion' => $descripcion,
                'tipo'        => $tipo,
                'datos'       => $datos,
            ];

            if (Schema::hasColumn('activity_log', 'dispositivo')) {
                $data['dispositivo'] = DeviceDetector::detectar();
            }

            return static::create($data);
        } catch (\Throwable $e) {
            return null;
        }
    }
}

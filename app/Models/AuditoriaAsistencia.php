<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditoriaAsistencia extends Model
{
    protected $table = 'auditoria_asistencias';

    protected $fillable = [
        'asistencia_id',
        'modificado_por',
        'accion',
        'antes',
        'despues',
        'motivo',
    ];

    protected $casts = [
        'antes'   => 'array',
        'despues' => 'array',
    ];

    public function asistencia(): BelongsTo
    {
        return $this->belongsTo(Asistencia::class);
    }

    public function modificadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'modificado_por');
    }

    public static function registrar(
        int $asistenciaId,
        ?int $userId,
        string $accion,
        ?array $antes = null,
        ?array $despues = null,
        ?string $motivo = null
    ): void {
        static::create([
            'asistencia_id'  => $asistenciaId,
            'modificado_por' => $userId,
            'accion'         => $accion,
            'antes'          => $antes,
            'despues'        => $despues,
            'motivo'         => $motivo,
        ]);
    }
}

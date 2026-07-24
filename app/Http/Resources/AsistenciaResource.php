<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AsistenciaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'fecha'       => $this->fecha,
            'hora_entrada' => $this->hora_entrada,
            'hora_salida' => $this->hora_salida,
            'estado'      => $this->estado,
            'justificado' => $this->justificado,
            'usuario'     => $this->whenLoaded('usuario', fn() => [
                'id'     => $this->usuario->id,
                'nombre' => $this->usuario->nombre_completo,
                'cedula' => $this->usuario->cedula,
            ]),
            'created_at'  => $this->created_at,
        ];
    }
}

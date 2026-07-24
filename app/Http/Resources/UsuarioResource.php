<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UsuarioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'       => $this->id,
            'nombre'   => $this->nombre,
            'apellido' => $this->apellido,
            'cedula'   => $this->cedula,
            'email'    => $this->email,
            'rol'      => $this->whenLoaded('rol', fn() => [
                'id'     => $this->rol->id,
                'nombre' => $this->rol->nombre,
            ]),
            'estatus'  => $this->estatus,
            'created_at' => $this->created_at,
        ];
    }
}

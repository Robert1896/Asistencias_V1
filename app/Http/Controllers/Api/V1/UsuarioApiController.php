<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UsuarioResource;
use App\Models\User;
use Illuminate\Http\Request;

class UsuarioApiController extends Controller
{
    public function index(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $usuarios = User::with('rol')
            ->when($request->search, fn($q, $s) => $q->where('nombre', 'like', '%'.str_replace(['%', '_'], ['\\%', '\\_'], $s).'%')
                ->orWhere('email', 'like', '%'.str_replace(['%', '_'], ['\\%', '\\_'], $s).'%'))
            ->when($request->rol_id, fn($q, $r) => $q->where('rol_id', $r))
            ->orderBy('id')
            ->paginate(min((int) $request->get('per_page', 15), 100));

        return UsuarioResource::collection($usuarios);
    }

    public function show(User $usuario): UsuarioResource
    {
        $usuario->load('rol');
        return new UsuarioResource($usuario);
    }
}

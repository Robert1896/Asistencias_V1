<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UsuarioResource;
use App\Models\User;
use App\Support\LikeQuery;
use Illuminate\Http\Request;

class UsuarioApiController extends Controller
{
    public function index(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $usuarios = User::with('rol')
            ->when($request->search, function ($q, $search) {
                LikeQuery::apply($q, 'nombre', $search);
                $q->orWhere('email', 'like', escape_like($search));
            })
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

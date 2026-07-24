@extends('layouts.app')

@section('title', 'Días Feriados')
@section('tone-level', '1')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="bi bi-calendar-event me-2"></i>Días Feriados</h4>
    <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary btn-sm">← Dashboard</a>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white">
        <h6 class="mb-0">Agregar Día Feriado</h6>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.feriados.store') }}" class="row g-3 align-items-end">
            @csrf
            @if ($errors->any())
                <div class="alert alert-danger py-1 px-3 small mb-2 col-12">
                    @foreach ($errors->all() as $error)
                        <span class="me-2">{{ $error }}</span>@if(!$loop->last)&middot;@endif
                    @endforeach
                </div>
            @endif
            <div class="col-md-3">
                <label class="form-label">Fecha</label>
                <input type="date" name="fecha" class="form-control @error('fecha') is-invalid @enderror" required value="{{ old('fecha') }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Nombre</label>
                <input type="text" name="nombre" class="form-control @error('nombre') is-invalid @enderror" placeholder="Ej: Navidad" required value="{{ old('nombre') }}">
            </div>
            <div class="col-md-2">
                <div class="form-check">
                    <input type="checkbox" name="recurrente" class="form-check-input" value="1" {{ old('recurrente') ? 'checked' : '' }}>
                    <label class="form-check-label">Recurrente</label>
                </div>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Agregar</button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h6 class="mb-0">Feriados del {{ $anio }}</h6>
        <div>
            <a href="{{ route('admin.feriados', ['anio' => $anio - 1]) }}" class="btn btn-outline-secondary btn-sm">← {{ $anio - 1 }}</a>
            <a href="{{ route('admin.feriados', ['anio' => $anio + 1]) }}" class="btn btn-outline-secondary btn-sm">{{ $anio + 1 }} →</a>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" role="table" aria-label="Feriados">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Día</th>
                        <th>Nombre</th>
                        <th>Recurrente</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($feriados as $f)
                    <tr>
                        <td>{{ $f->fecha->format('d/m/Y') }}</td>
                        <td>{{ $f->translatedFormat('l') }}</td>
                        <td>{{ $f->nombre }}</td>
                        <td>
                            @if ($f->recurrente)
                                <span class="badge bg-info">Sí</span>
                            @else
                                <span class="badge bg-secondary">No</span>
                            @endif
                        </td>
                        <td>
                            <form method="POST" action="{{ route('admin.feriados.destroy', $f) }}" class="d-inline"
                                  onsubmit="return confirm('¿Eliminar {{ $f->nombre }}?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger btn-sm" aria-label="Eliminar"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-muted text-center py-3">Sin feriados registrados para {{ $anio }}.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@extends('layouts.app')

@section('title', 'Mis Justificaciones')
@section('tone-level', '2')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="bi bi-clipboard-check me-2"></i>Mis Justificaciones</h4>
    <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary btn-sm">← Dashboard</a>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white">
        <h6 class="mb-0"><i class="bi bi-exclamation-circle me-1"></i>Asistencias sin justificar</h6>
    </div>
    <div class="card-body p-0">
        @if ($asistencias->isEmpty())
            <div class="text-center text-muted py-4">
                <i class="bi bi-check-circle fs-1 text-success"></i>
                <p class="mt-2">No tiene asistencias pendientes por justificar.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Estado</th>
                            <th>Entrada</th>
                            <th>Salida</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($asistencias as $a)
                        <tr>
                            <td>{{ $a->fecha->format('d/m/Y') }}</td>
                            <td>
                                <span class="badge bg-{{ $a->estado === 'Tarde' ? 'warning' : 'danger' }}">
                                    {{ $a->estado }}
                                </span>
                            </td>
                            <td>{{ $a->hora_entrada ?? '—' }}</td>
                            <td>{{ $a->hora_salida ?? '—' }}</td>
                            <td>
                                <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal"
                                        data-bs-target="#justificarModal{{ $a->id }}">
                                    <i class="bi bi-paperclip me-1"></i>Justificar
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

@foreach ($asistencias as $a)
<div class="modal fade" id="justificarModal{{ $a->id }}" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('justificaciones.solicitar', $a) }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-header border-0">
                    <h6 class="modal-title">Justificar — {{ $a->fecha->format('d/m/Y') }}</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                                            <div class="mb-3">
                                                <label class="form-label">Motivo de la justificación *</label>
                                                <textarea name="texto_justificacion" class="form-control @error('texto_justificacion') is-invalid @enderror" rows="3"
                                                          placeholder="Describa el motivo..." required></textarea>
                                                @error('texto_justificacion')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Archivo adjunto <small class="text-muted">(opcional, máx 5MB)</small></label>
                                                <input type="file" name="archivo" class="form-control form-control-sm @error('archivo') is-invalid @enderror"
                                                       accept=".pdf,.jpg,.jpeg,.png">
                                                <small class="text-muted">PDF, JPG o PNG</small>
                                                @error('archivo')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm">Enviar Justificación</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach

@if ($historial->isNotEmpty())
<div class="card shadow-sm border-0">
    <div class="card-header bg-white">
        <h6 class="mb-0"><i class="bi bi-clock-history me-1"></i>Últimas justificaciones enviadas</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Estado</th>
                        <th>Motivo</th>
                        <th>Archivo</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($historial as $h)
                    <tr>
                        <td>{{ $h->fecha->format('d/m/Y') }}</td>
                        <td><span class="badge bg-{{ $h->estado === 'Tarde' ? 'warning' : 'danger' }}">{{ $h->estado }}</span></td>
                        <td class="small">{{ Str::limit($h->texto_justificacion, 60) }}</td>
                        <td>
                            @if ($h->archivo_justificacion)
                                <a href="{{ route('justificaciones.archivo', $h) }}" target="_blank" class="btn btn-outline-secondary btn-sm">
                                    <i class="bi bi-file-earmark"></i>
                                </a>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif
@endsection

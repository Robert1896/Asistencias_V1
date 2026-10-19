@extends('layouts.app')

@section('title', "Horarios: {$user->nombre_completo}")
@section('tone-level', '2')

@push('styles')
<style nonce="{{ $cspNonce ?? '' }}">
    .bloque-checkbox {
        transition: all 0.15s ease;
    }
    .bloque-checkbox:hover {
        background: #f0f4ff;
    }
    .bloque-checkbox input[type="checkbox"]:checked + .bloque-label {
        font-weight: 600;
    }
    .tipo-select, .salon-select {
        font-size: 0.7rem;
        padding: 0.1rem 0.3rem;
    }
    .salon-select {
        max-width: 110px;
    }
    .materia-select, .seccion-input {
        font-size: 0.7rem;
        padding: 0.1rem 0.3rem;
    }
</style>
@endpush

@section('content')
<nav aria-label="breadcrumb" class="breadcrumb-admin">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Inicio</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.docentes.horarios') }}">Horarios Docentes</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ $user->nombre_completo }}</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">
        <i class="bi bi-person-gear me-2"></i>
        Horarios de: <strong>{{ $user->nombre_completo }}</strong>
    </h4>
    <div>
        <span class="badge bg-secondary me-2">{{ $user->email }}</span>
        <a href="{{ route('admin.docentes.horarios') }}" class="btn btn-outline-secondary btn-sm">← Volver</a>
    </div>
</div>

<form method="POST" action="{{ route('admin.docentes.horarios.update', $user) }}" id="horarioForm">
    @csrf @method('PUT')

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Selecciona los bloques horarios por día</h6>
                <div>
                    <button type="button" class="btn btn-outline-primary btn-sm me-1" id="btnMarcarTodos">
                        <i class="bi bi-check-all"></i> Marcar/Desmarcar todo
                    </button>
                    <button type="button" class="btn btn-outline-info btn-sm" id="btnLimpiarSalones">
                        <i class="bi bi-x-circle"></i> Limpiar salones no seleccionados
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body">
            @if ($errors->any())
                <div class="alert alert-danger py-2 small">
                    <ul class="mb-0 ps-3">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                </div>
            @endif

            <div class="table-responsive">
                <table class="table table-bordered mb-0 align-middle" role="table" aria-label="Horario semanal del docente">
                    <thead class="table-light">
                        <tr>
                            <th style="width:90px;">Horario</th>
                            @foreach ($agrupado['dias'] as $num => $nom)
                                <th class="text-center" style="min-width:180px;">{{ $nom }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $rangos = [];
                            foreach ($agrupado['bloques'] as $dia => $bloques) {
                                foreach ($bloques as $b) {
                                    $key = $b->hora_inicio . '-' . $b->hora_fin;
                                    $rangos[$key] = [$b->hora_inicio, $b->hora_fin];
                                }
                            }
                            ksort($rangos);
                        @endphp

                        @foreach ($rangos as $rangoKey => $rango)
                            <tr>
                                <td class="small text-nowrap">
                                    <strong>{{ substr($rango[0], 0, 5) }}</strong>
                                    &nbsp;–&nbsp;
                                    <strong>{{ substr($rango[1], 0, 5) }}</strong>
                                </td>
                                @foreach ($agrupado['dias'] as $num => $nom)
                                    @php
                                        $bloque = collect($agrupado['bloques'][$num] ?? [])
                                            ->firstWhere('hora_inicio', $rango[0]);
                                        $checked = false;
                                        $tipoSeleccionado = 'carga_academica';
                                        $coordSeleccionada = '';
                                        $salonSeleccionado = '';
                                        if ($bloque) {
                                            foreach (['carga_academica', 'asesoria', 'administrativa'] as $t) {
                                                $k = $bloque->id . '-' . $t;
                                                if (isset($asignados[$k])) {
                                                    $checked = true;
                                                    $tipoSeleccionado = $t;
                                                    $coordSeleccionada = $asignados[$k]->pivot->coordinacion_id ?? '';
                                                    $salonSeleccionado = $asignados[$k]->pivot->salon_id ?? '';
                                                    break;
                                                }
                                            }
                                        }
                                        $keyPrefix = $bloque ? $bloque->id . '-carga_academica' : '';
                                        $salonesOcupados = $colisiones[$bloque->id ?? 0] ?? [];
                                    @endphp
                                    <td class="text-center p-1 bloque-checkbox" style="vertical-align:top;">
                                        @if ($bloque)
                                            <div class="form-check d-flex justify-content-center mb-1">
                                                <input class="form-check-input bloque-input" type="checkbox"
                                                       name="bloques[{{ $keyPrefix }}][horario_id]"
                                                       value="{{ $bloque->id }}"
                                                       id="chk-{{ $bloque->id }}"
                                                       {{ $checked ? 'checked' : '' }}>
                                            </div>

                                            {{-- Tipo de actividad --}}
                                            <select name="bloques[{{ $keyPrefix }}][tipo]"
                                                    class="form-select form-select-sm tipo-select mb-1"
                                                    aria-label="Tipo de actividad"
                                                    data-marcar-checkbox="chk-{{ $bloque->id }}">
                                                <option value="carga_academica" {{ $tipoSeleccionado === 'carga_academica' ? 'selected' : '' }}>Clase</option>
                                                <option value="asesoria" {{ $tipoSeleccionado === 'asesoria' ? 'selected' : '' }}>Asesoría</option>
                                                <option value="administrativa" {{ $tipoSeleccionado === 'administrativa' ? 'selected' : '' }}>Admin.</option>
                                            </select>

                                            {{-- Coordinación --}}
                                            <select name="bloques[{{ $keyPrefix }}][coord]"
                                                    class="form-select form-select-sm tipo-select mb-1"
                                                    aria-label="Coordinación">
                                                <option value="">Sin coord.</option>
                                                @foreach ($coordinaciones as $c)
                                                    <option value="{{ $c->id }}" {{ $coordSeleccionada == $c->id ? 'selected' : '' }}>
                                                        {{ $c->nombre }}
                                                    </option>
                                                @endforeach
                                            </select>

                                            {{-- Salón (filtrado por disponibilidad) --}}
                                            <select name="bloques[{{ $keyPrefix }}][salon_id]"
                                                    class="form-select form-select-sm salon-select mt-1"
                                                    aria-label="Salón"
                                                    data-horario-id="{{ $bloque->id }}"
                                                    data-ocupados="{{ json_encode($salonesOcupados) }}"
                                                    data-marcar-checkbox="chk-{{ $bloque->id }}">
                                                <option value="">Sin salón</option>
                                                @foreach ($salones as $salon)
                                                    @php
                                                        $ocupado = in_array($salon->id, $salonesOcupados);
                                                        $esActual = $salonSeleccionado == $salon->id;
                                                    @endphp
                                                    <option value="{{ $salon->id }}"
                                                            {{ $esActual ? 'selected' : '' }}
                                                            {{ $ocupado && !$esActual ? 'disabled class="text-danger"' : '' }}>
                                                        {{ $salon->codigo }} @if($ocupado && !$esActual)(ocupado)@endif
                                                    </option>
                                                @endforeach
                                            </select>

                                            {{-- Materia (solo para carga_academica) --}}
                                            @php
                                                $cargaSel = $asignados[$keyPrefix]->pivot->carga_academica_id ?? '';
                                                $seccionVal = $asignados[$keyPrefix]->pivot->seccion ?? '';
                                            @endphp
                                            <select name="bloques[{{ $keyPrefix }}][carga_academica_id]"
                                                    class="form-select form-select-sm materia-select mt-1"
                                                    aria-label="Materia"
                                                    data-marcar-checkbox="chk-{{ $bloque->id }}"
                                                    style="{{ $tipoSeleccionado !== 'carga_academica' ? 'display:none;' : '' }}">
                                                <option value="">Sin materia</option>
                                                @foreach ($cargas as $c)
                                                    <option value="{{ $c->id }}" {{ $cargaSel == $c->id ? 'selected' : '' }}>
                                                        {{ $c->materia }}
                                                    </option>
                                                @endforeach
                                            </select>

                                            {{-- Sección (solo para carga_academica) --}}
                                            <input type="text" name="bloques[{{ $keyPrefix }}][seccion]"
                                                   class="form-control form-control-sm seccion-input mt-1"
                                                   placeholder="Sección"
                                                   aria-label="Sección"
                                                   maxlength="30"
value="{{ $seccionVal }}"
                                                    data-marcar-checkbox="chk-{{ $bloque->id }}"
                                                   style="{{ $tipoSeleccionado !== 'carga_academica' ? 'display:none;' : '' }}">
                                        @else
                                            <span class="text-muted" style="font-size:0.7rem;">—</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white text-end">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-save me-1"></i> Guardar Horarios
            </button>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
document.addEventListener('DOMContentLoaded', function () {
    // Marcar/Desmarcar todo
    document.getElementById('btnMarcarTodos')?.addEventListener('click', function () {
        const inputs = document.querySelectorAll('.bloque-input');
        const algunaMarcada = Array.from(inputs).some(i => i.checked);
        inputs.forEach(i => i.checked = !algunaMarcada);
    });

    // Limpiar salones de casillas no marcadas
    document.getElementById('btnLimpiarSalones')?.addEventListener('click', function () {
        document.querySelectorAll('.bloque-input').forEach(chk => {
            if (!chk.checked) {
                const td = chk.closest('td');
                const salonSelect = td?.querySelector('.salon-select');
                if (salonSelect) salonSelect.value = '';
            }
        });
    });

    // Al marcar/desmarcar checkbox, si se desmarca limpiar salón
    document.querySelectorAll('.bloque-input').forEach(chk => {
        chk.addEventListener('change', function () {
            if (!this.checked) {
                const td = this.closest('td');
                const salonSelect = td?.querySelector('.salon-select');
                if (salonSelect) salonSelect.value = '';
            }
        });
    });

    // Mostrar/ocultar materia y sección según tipo de actividad
    document.querySelectorAll('.tipo-select').forEach(sel => {
        sel.addEventListener('change', function() {
            const td = this.closest('td');
            const materiaSelect = td?.querySelector('.materia-select');
            const seccionInput = td?.querySelector('.seccion-input');
            const esCarga = this.value === 'carga_academica';
            if (materiaSelect) materiaSelect.style.display = esCarga ? '' : 'none';
            if (seccionInput) seccionInput.style.display = esCarga ? '' : 'none';
        });
    });
});
</script>
@endpush

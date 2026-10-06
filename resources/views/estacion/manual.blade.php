@extends('layouts.app')

@section('title', 'Registro Manual de Asistencia')
@section('tone-level', '2')

@section('content')
<div class="container-fluid py-3">
    <nav aria-label="breadcrumb" class="breadcrumb-admin">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Inicio</a></li>
            <li class="breadcrumb-item"><a href="{{ route('estacion.index') }}">Estación</a></li>
            <li class="breadcrumb-item active" aria-current="page">Registro Manual</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0 fw-bold"><i class="bi bi-pencil-square me-2"></i>Registro Manual de Asistencia</h4>
        <a href="{{ route('estacion.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Volver a Estación
        </a>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0"><i class="bi bi-search me-2"></i>Buscar Empleado</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nombre, apellido o cédula</label>
                        <input type="text" id="busqueda" class="form-control" placeholder="Escriba para buscar..." autocomplete="off">
                        <div id="resultadosBusqueda" class="list-group mt-1" style="display:none;"></div>
                    </div>

                    <div id="empleadoSeleccionado" style="display:none;">
                        <div class="alert alert-light border d-flex align-items-center">
                            <div class="me-3">
                                <i class="bi bi-person-circle fa-2x text-primary"></i>
                            </div>
                            <div>
                                <h6 class="mb-0" id="empleadoNombre"></h6>
                                <small class="text-muted" id="empleadoInfo"></small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Registrar</h5>
                </div>
                <div class="card-body">
                    <div id="formRegistro" style="display:none;">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Tipo de registro</label>
                            <div class="d-flex gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="tipoRegistro" id="tipoEntrada" value="entrada" checked>
                                    <label class="form-check-label fw-semibold" for="tipoEntrada">
                                        <i class="bi bi-box-arrow-in-down text-success me-1"></i>Entrada
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="tipoRegistro" id="tipoSalida" value="salida">
                                    <label class="form-check-label fw-semibold" for="tipoSalida">
                                        <i class="bi bi-box-arrow-up text-primary me-1"></i>Salida
                                    </label>
                                </div>
                            </div>
                        </div>

                        <button type="button" id="btnRegistrar" class="btn btn-success btn-lg w-100">
                            <i class="bi bi-check-circle me-2"></i>Registrar Asistencia
                        </button>
                    </div>

                    <div id="sinEmpleado" class="text-center text-muted py-4">
                        <i class="bi bi-person-plus fa-3x mb-2 d-block"></i>
                        Busque y seleccione un empleado para registrar su asistencia.
                    </div>

                    <div id="resultadoRegistro" style="display:none;" class="mt-3"></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
document.addEventListener('DOMContentLoaded', function() {
    const inputBusqueda = document.getElementById('busqueda');
    const resultadosDiv = document.getElementById('resultadosBusqueda');
    const empleadoSeleccionado = document.getElementById('empleadoSeleccionado');
    const formRegistro = document.getElementById('formRegistro');
    const sinEmpleado = document.getElementById('sinEmpleado');
    const btnRegistrar = document.getElementById('btnRegistrar');
    const resultadoRegistro = document.getElementById('resultadoRegistro');
    let empleadoActual = null;
    let debounceTimer = null;

    inputBusqueda.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        const q = this.value.trim();
        if (q.length < 2) {
            resultadosDiv.style.display = 'none';
            return;
        }
        debounceTimer = setTimeout(() => buscarEmpleado(q), 300);
    });

    function buscarEmpleado(q) {
        fetch('{{ route("estacion.buscar") }}?q=' + encodeURIComponent(q))
            .then(r => r.json())
            .then(data => {
                resultadosDiv.innerHTML = '';
                if (data.length === 0) {
                    resultadosDiv.innerHTML = '<div class="list-group-item text-muted small">Sin resultados</div>';
                } else {
                    data.forEach(function(emp) {
                        const item = document.createElement('button');
                        item.type = 'button';
                        item.className = 'list-group-item list-group-item-action';
                        const rolLabel = emp.rol_id === 3 ? 'Docente' : 'Administrativo';
                        item.innerHTML = '<strong>' + esc(emp.nombre_completo) + '</strong>'
                            + (emp.cedula ? ' <span class="text-muted">(' + esc(emp.cedula) + ')</span>' : '')
                            + '<br><small class="text-muted">' + rolLabel + '</small>';
                        item.addEventListener('click', function() { seleccionarEmpleado(emp); });
                        resultadosDiv.appendChild(item);
                    });
                }
                resultadosDiv.style.display = 'block';
            });
    }

    function seleccionarEmpleado(emp) {
        empleadoActual = emp;
        resultadosDiv.style.display = 'none';
        inputBusqueda.value = emp.nombre_completo;
        const rolLabel = emp.rol_id === 3 ? 'Docente' : 'Administrativo';
        document.getElementById('empleadoNombre').textContent = emp.nombre_completo;
        document.getElementById('empleadoInfo').textContent = (emp.cedula ? 'Cédula: ' + emp.cedula + ' | ' : '') + 'Rol: ' + rolLabel;
        empleadoSeleccionado.style.display = 'block';
        formRegistro.style.display = 'block';
        sinEmpleado.style.display = 'none';
        resultadoRegistro.style.display = 'none';
    }

    btnRegistrar.addEventListener('click', function() {
        if (!empleadoActual) return;
        const tipo = document.querySelector('input[name="tipoRegistro"]:checked').value;
        btnRegistrar.disabled = true;
        btnRegistrar.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Registrando...';

        fetch('{{ route("estacion.registrar") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ empleado_id: empleadoActual.id, tipo: tipo })
        })
        .then(r => r.json())
        .then(data => {
            const bgClass = data.ok ? (data.tipo === 'warning' ? 'alert-warning' : 'alert-success') : 'alert-danger';
            resultadoRegistro.className = 'alert ' + bgClass + ' mb-0';
            resultadoRegistro.textContent = data.mensaje;
            resultadoRegistro.style.display = 'block';
        })
        .catch(() => {
            resultadoRegistro.className = 'alert alert-danger mb-0';
            resultadoRegistro.textContent = 'Error de conexión';
            resultadoRegistro.style.display = 'block';
        })
        .finally(() => {
            btnRegistrar.disabled = false;
            btnRegistrar.innerHTML = '<i class="bi bi-check-circle me-2"></i>Registrar Asistencia';
        });
    });

    function esc(s) { var d = document.createElement('div'); d.appendChild(document.createTextNode(s || '')); return d.innerHTML; }
});
</script>
@endpush

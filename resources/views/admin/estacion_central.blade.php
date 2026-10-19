@php
    $titulo = 'Estación Central';
@endphp

@extends('layouts.app')

@section('title', $titulo)
@section('tone-level', '1')

@push('styles')
<style nonce="{{ $cspNonce ?? '' }}">
    .modo-radio {
        cursor: pointer;
        transition: all .15s ease-in-out;
    }
    .modo-radio:hover {
        background-color: var(--bs-light);
    }
    .modo-radio.active {
        background-color: rgba(13, 110, 253, 0.05);
        border-color: rgba(13, 110, 253, 0.25) !important;
    }
</style>
@endpush

@section('content')
<div class="row justify-content-center">
    <div class="col-md-9 col-lg-7">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0 fw-bold"><i class="bi bi-geo-alt me-2"></i>{{ $titulo }}</h4>
        </div>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        @endif

        <div class="alert alert-info d-flex align-items-start">
            <i class="bi bi-info-circle-fill me-2 mt-1"></i>
            <div>
                <strong>Reglas de operación:</strong>
                <ul class="mb-0 small mt-2">
                    <li><strong>Sin verificación (ninguno):</strong> cualquier equipo con permiso de estación puede operar (predeterminado hasta activar la verificación).</li>
                    <li><strong>Red (IP/rango):</strong> solo permite operar desde la IP/CIDR indicada. Válido para HTTP/HTTPS.</li>
                    <li><strong>GPS:</strong> exige ubicación del navegador dentro del radio. <strong>Requiere HTTPS</strong> en producción (localhost permitido).</li>
                    <li><strong>Ambos:</strong> deben cumplir IP/rango y ubicación GPS.</li>
                    <li>La verificación <strong>NO</strong> se aplica al escaneo propio del docente (<code>/estacion/salon-qr</code>).</li>
                    <li>Si es la <strong>primera asistencia del día</strong> de un docente, la entrada solo puede crearse desde la estación principal (escaneado en estación, manual o ad-hoc bajo verificación activa).</li>
                </ul>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold"><i class="bi bi-sliders me-2"></i>Configuración de la estación principal</h6>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.estacion.central.update') }}" method="POST" id="formEstacionCentral">
                    @csrf
                    @method('PUT')

                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Nombre *</label>
                            <input type="text" class="form-control @error('nombre') is-invalid @enderror"
                                   name="nombre" value="{{ old('nombre', $config->nombre) }}" required
                                   placeholder="Estación Central - Recepción">
                            @error('nombre')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label d-block">Método de verificación *</label>
                            @php
                                $modoActual = old('modo', $config->modo ?? 'ninguno');
                            @endphp
                            <div class="d-flex flex-column gap-2">
                                <label class="border rounded p-3 modo-radio {{ $modoActual === 'ninguno' ? 'active' : '' }}">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="modo" id="modoNinguno" value="ninguno"
                                               {{ $modoActual === 'ninguno' ? 'checked' : '' }}>
                                        <label class="form-check-label w-100" for="modoNinguno">
                                            <strong>Sin verificación</strong>
                                            <div class="small text-muted">Desactiva la restricción por ubicación/red.</div>
                                        </label>
                                    </div>
                                </label>

                                <label class="border rounded p-3 modo-radio {{ $modoActual === 'red' ? 'active' : '' }}">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="modo" id="modoRed" value="red"
                                               {{ $modoActual === 'red' ? 'checked' : '' }}>
                                        <label class="form-check-label w-100" for="modoRed">
                                            <strong>Red (IP o rango CIDR)</strong>
                                            <div class="small text-muted">Permite operar únicamente desde la(s) IP o subred autorizada(s).</div>
                                        </label>
                                    </div>
                                </label>

                                <label class="border rounded p-3 modo-radio {{ $modoActual === 'gps' ? 'active' : '' }}">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="modo" id="modoGps" value="gps"
                                               {{ $modoActual === 'gps' ? 'checked' : '' }}>
                                        <label class="form-check-label w-100" for="modoGps">
                                            <strong>GPS (geolocalización)</strong>
                                            <div class="small text-muted">Requiere que el navegador envíe coordenadas dentro del radio. <strong>Necesita HTTPS</strong>.</div>
                                        </label>
                                    </div>
                                </label>

                                <label class="border rounded p-3 modo-radio {{ $modoActual === 'ambos' ? 'active' : '' }}">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="modo" id="modoAmbos" value="ambos"
                                               {{ $modoActual === 'ambos' ? 'checked' : '' }}>
                                        <label class="form-check-label w-100" for="modoAmbos">
                                            <strong>Ambos (Red + GPS)</strong>
                                            <div class="small text-muted">Exige IP autorizada Y ubicación dentro del radio.</div>
                                        </label>
                                    </div>
                                </label>
                            </div>
                            @error('modo')
                                <div class="text-danger small mt-2">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <div class="border rounded p-3 bg-light-subtle">
                                <h6 class="mb-2"><i class="bi bi-hdd-network me-2"></i>Restricción por red</h6>
                                <label class="form-label">IP o rango (CIDR) autorizado</label>
                                <div class="input-group">
                                    <input type="text" class="form-control @error('ip_permitida') is-invalid @enderror"
                                           name="ip_permitida" id="ip_permitida"
                                           value="{{ old('ip_permitida', $config->ip_permitida) }}"
                                           placeholder="200.45.10.5 o 200.45.10.0/24 (separe con coma si son varias)">
                                    <button class="btn btn-outline-secondary" type="button" id="btnUsarMiIp">
                                        <i class="bi bi-laptop me-1"></i>Usar mi IP actual
                                    </button>
                                    @error('ip_permitida')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="form-text">Su IP detectada ahora: <code>{{ $ipActual }}</code></div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="border rounded p-3 bg-light-subtle">
                                <h6 class="mb-2"><i class="bi bi-crosshair2 me-2"></i>Restricción por GPS (geolocalización)</h6>
                                <div class="row g-3 align-items-end">
                                    <div class="col-md-4">
                                        <label class="form-label">Latitud *</label>
                                        <input type="number" step="0.0000001" class="form-control @error('latitud') is-invalid @enderror"
                                               name="latitud" id="latitud"
                                               value="{{ old('latitud', $config->latitud) }}"
                                               placeholder="-33.448889">
                                        @error('latitud')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Longitud *</label>
                                        <input type="number" step="0.0000001" class="form-control @error('longitud') is-invalid @enderror"
                                               name="longitud" id="longitud"
                                               value="{{ old('longitud', $config->longitud) }}"
                                               placeholder="-70.669265">
                                        @error('longitud')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Radio de tolerancia (metros) *</label>
                                        <input type="number" min="10" max="20000" step="1" class="form-control @error('radio_metros') is-invalid @enderror"
                                               name="radio_metros" id="radio_metros"
                                               value="{{ old('radio_metros', $config->radio_metros) }}">
                                        @error('radio_metros')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-12 d-flex flex-wrap gap-2 align-items-center">
                                        <button class="btn btn-outline-primary" type="button" id="btnUsarMiUbicacion">
                                            <i class="bi bi-geo-alt me-1"></i>Usar mi ubicación actual
                                        </button>
                                        <small class="text-muted">Se recomienda un radio entre 100 y 300 m para interiores.</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="form-check form-switch border rounded p-3 bg-white">
                                <input class="form-check-input" type="checkbox" name="activo" value="1" id="activoCheck"
                                       {{ old('activo', $config->activo) ? 'checked' : '' }}>
                                <label class="form-check-label" for="activoCheck">
                                    <strong>Activar verificación de estación central</strong>
                                    <br><small class="text-muted">Cuando está activo, las rutas de estación aplicarán la restricción seleccionada.</small>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 d-flex justify-content-between">
                        <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i>Volver
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i>Guardar configuración
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
    document.addEventListener('DOMContentLoaded', function () {
        const radios = document.querySelectorAll('input[name="modo"]');
        const modoRadios = document.querySelectorAll('.modo-radio');
        radios.forEach(r => {
            r.addEventListener('change', function () {
                modoRadios.forEach(m => m.classList.remove('active'));
                const lbl = document.querySelector('label[for="' + this.id + '"]');
                if (lbl) lbl.closest('.modo-radio').classList.add('active');
            });
        });

        document.getElementById('btnUsarMiIp')?.addEventListener('click', function () {
            const ip = '{{ $ipActual }}';
            if (ip) {
                document.getElementById('ip_permitida').value = ip;
            } else {
                alert('No se pudo detectar la IP actual.');
            }
        });

        document.getElementById('btnUsarMiUbicacion')?.addEventListener('click', function (btn) {
            btn.target.disabled = true;
            if (!navigator.geolocation) {
                alert('La geolocalización no está disponible en este navegador.');
                btn.target.disabled = false;
                return;
            }
            navigator.geolocation.getCurrentPosition(function (pos) {
                document.getElementById('latitud').value = pos.coords.latitude.toFixed(7);
                document.getElementById('longitud').value = pos.coords.longitude.toFixed(7);
                btn.target.disabled = false;
            }, function (err) {
                alert('No se pudo obtener la ubicación: ' + (err.message || 'Permiso denegado o error de hardware.'));
                btn.target.disabled = false;
            }, { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 });
        });
    });
</script>
@endpush

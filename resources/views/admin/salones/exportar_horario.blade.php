<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Horario - {{ $salon->codigo }}</title>
    <style nonce="{{ $cspNonce ?? '' }}">
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 10px; color: #333; }
        .header { text-align: center; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid #212529; }
        .header h1 { font-size: 16px; margin-bottom: 4px; }
        .header p { font-size: 11px; color: #666; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #212529; color: #fff; padding: 6px 8px; border: 1px solid #495057; font-size: 10px; }
        td { padding: 4px 6px; border: 1px solid #dee2e6; vertical-align: top; text-align: center; font-size: 9px; }
        .cell-filled { background: #e3f2fd; text-align: left; }
        .cell-empty { background: #f8f9fa; color: #aaa; }
        .docente-name { font-weight: bold; font-size: 10px; }
        .materias { font-size: 8px; color: #888; }
        .badge { display: inline-block; font-size: 8px; padding: 1px 5px; border-radius: 3px; color: #fff; }
        .badge-ca { background: #0d6efd; }
        .badge-ases { background: #0dcaf0; color: #000; }
        .badge-adm { background: #198754; }
        .footer { margin-top: 15px; text-align: center; font-size: 9px; color: #999; border-top: 1px solid #dee2e6; padding-top: 5px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Horario Semanal — {{ $salon->nombre }}</h1>
        <p>Código: {{ $salon->codigo }} | Capacidad: {{ $salon->capacidad ?? '—' }} | {{ $salon->ubicacion ?? '' }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:80px;">Horario</th>
                @foreach ($dias as $num => $nom)
                    <th>{{ $nom }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($grid as $row)
                <tr>
                    <td style="font-weight:bold;white-space:nowrap;text-align:center;">{{ $row['hora'] }}</td>
                    @foreach ($dias as $diaNum => $diaNom)
                        @php $cell = $row[$diaNum] ?? ['vacio' => true]; @endphp
                        @if ($cell['skip'] ?? false)
                            @continue
                        @elseif ($cell['vacio'] ?? false)
                            <td class="cell-empty">—</td>
                        @else
                            <td class="cell-filled" rowspan="{{ $cell['rowspan'] }}">
                                <div class="docente-name">{{ $cell['docente'] }}</div>
                                @if ($cell['materias'])
                                    <div class="materias">{{ $cell['materias'] }}</div>
                                @endif
                                @php
                                    $badgeClass = match($cell['tipo']) {
                                        'carga_academica' => 'badge-ca',
                                        'asesoria' => 'badge-ases',
                                        'administrativa' => 'badge-adm',
                                        default => '',
                                    };
                                    $badgeText = match($cell['tipo']) {
                                        'carga_academica' => 'C.A.',
                                        'asesoria' => 'Asesoría',
                                        'administrativa' => 'Adm.',
                                        default => $cell['tipo'],
                                    };
                                @endphp
                                <span class="badge {{ $badgeClass }}">{{ $badgeText }}</span>
                            </td>
                        @endif
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Generado el {{ now()->format('d/m/Y H:i') }} — Sistema de Control de Asistencia
    </div>
</body>
</html>

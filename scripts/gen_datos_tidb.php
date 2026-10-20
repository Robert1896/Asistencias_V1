<?php

$dumpPath = $argv[1] ?? 'C:\\xampp\\htdocs\\backups_asistencias_v1\\20261019_103758\\qr_asistencia.sql';
$outPath  = $argv[2] ?? 'C:\\xampp\\htdocs\\asistencias_v1\\datos_tidb.sql';

$raw = file_get_contents($dumpPath);
if ($raw === false) {
    fwrite(STDERR, "No se pudo leer el dump: {$dumpPath}\n");
    exit(1);
}

$bom = substr($raw, 0, 2);
if ($bom === "\xFF\xFE") {
    $raw = substr($raw, 2);
}
$sql = mb_convert_encoding($raw, 'UTF-8', 'UTF-16LE');

$tablesDeseadas = [
    'activity_log',
    'asistencias',
    'coordinaciones',
    'correo_config',
    'departamentos',
    'horarios',
    'horarios_administrativos',
    'permisos',
    'qr_tokens',
    'rol_permiso',
    'roles',
    'salones',
    'sessions',
    'users',
];

/**
 * Tabla CP437: mapea cada byte 0x80-0xFF a su caracter Unicode (128 entradas).
 * El dump original se genero leyendo columnas UTF-8 como CP437, por lo que
 * cada byte de un caracter UTF-8 (p.ej. 0xC3) quedo como un caracter de caja
 * (p.ej. U+251C '├'). Para revertir, convertimos cada caracter a su byte
 * original y reinterpretamos la secuencia de bytes como UTF-8.
 */
function tablaCp437(): array
{
    // Codepoints Unicode de CP437 para los bytes 0x80-0xFF (canonicos).
    $codepoints = [
        0x00C7, 0x00FC, 0x00E9, 0x00E2, 0x00E4, 0x00E0, 0x00E5, 0x00E7, 0x00EA, 0x00EB, 0x00E8, 0x00EF, 0x00EE, 0x00EC, 0x00C4, 0x00C5,
        0x00C9, 0x00E6, 0x00C6, 0x00F4, 0x00F6, 0x00F2, 0x00FB, 0x00F9, 0x00FF, 0x00D6, 0x00DC, 0x00A2, 0x00A3, 0x00A5, 0x20A7, 0x0192,
        0x00E1, 0x00ED, 0x00F3, 0x00FA, 0x00F1, 0x00D1, 0x00AA, 0x00BA, 0x00BF, 0x2310, 0x00AC, 0x00BD, 0x00BC, 0x00A1, 0x00AB, 0x00BB,
        0x2591, 0x2592, 0x2593, 0x2502, 0x2524, 0x2561, 0x2562, 0x2556, 0x2555, 0x2563, 0x2551, 0x2557, 0x255D, 0x255C, 0x255B, 0x2510,
        0x2514, 0x2534, 0x252C, 0x251C, 0x2500, 0x253C, 0x255E, 0x255F, 0x255A, 0x2554, 0x2569, 0x2566, 0x2560, 0x2550, 0x256C, 0x2567,
        0x2568, 0x2564, 0x2565, 0x2559, 0x2558, 0x2552, 0x2553, 0x256B, 0x256A, 0x2518, 0x250C, 0x2588, 0x2584, 0x258C, 0x2590, 0x2580,
        0x03B1, 0x00DF, 0x0393, 0x03C0, 0x03A3, 0x03C3, 0x00B5, 0x03C4, 0x03A6, 0x0398, 0x03A9, 0x03B4, 0x221E, 0x03C6, 0x03B5, 0x2229,
        0x2261, 0x00B1, 0x2265, 0x2264, 0x2320, 0x2321, 0x00F7, 0x2248, 0x00B0, 0x2219, 0x00B7, 0x221A, 0x207F, 0x00B2, 0x25A0, 0x00A0,
    ];

    $mapa = [];
    foreach ($codepoints as $indice => $codepoint) {
        $caracter = mb_chr($codepoint);
        // Ignorar el caracter del byte 0xFF (espacio duro) si coincide con ASCII,
        // para no alterar espacios normales al revertir.
        if (mb_ord($caracter) >= 0x80) {
            $mapa[0x80 + $indice] = $caracter;
        }
    }

    return $mapa;
}

/**
 * Reemplazos para mojibake de doble paso (UTF-8 -> CP1252 -> CP437) que no se
 * recuperan con un solo paso CP437. Se aplican despues de revertirCp437.
 */
function reemplazosDoblePaso(): array
{
    return [
        "\u{00D4}\u{00C7}\u{00F4}" => "\u{2013}", // en-dash –
        "\u{00D4}\u{00C7}\u{00AA}" => "\u{2026}", // ellipsis …
        "\u{00D4}\u{00E5}\u{00C6}" => "\u{2192}", // arrow →
        "\u{251C}\u{00AE}"         => "\u{00E9}", // e acentuada e (é)
    ];
}

/**
 * Revierte el mojibake CP437 de un texto completo.
 *
 * Convierte cada caracter a su byte CP437 (los ASCII y caracteres fuera de la
 * tabla se conservan tal cual). Luego recorre los bytes: cuando forman una
 * secuencia UTF-8 valida, la emite; cuando no, devuelve el caracter CP437
 * original de ese byte.
 */
function revertirCp437(string $texto): string
{
    $byteACaracter = tablaCp437();
    $caracterAByte = array_flip($byteACaracter);

    // 1. Convertir a bytes CP437.
    $bytes = '';
    foreach (mb_str_split($texto) as $caracter) {
        if (isset($caracterAByte[$caracter])) {
            $bytes .= chr($caracterAByte[$caracter]);
        } else {
            $bytes .= $caracter;
        }
    }

    // 2. Reescanear validando UTF-8, revirtiendo a CP437 los bytes invalidos.
    $salida   = '';
    $longitud = strlen($bytes);
    $i        = 0;

    while ($i < $longitud) {
        $byte = ord($bytes[$i]);

        if ($byte < 0x80) {
            $salida .= $bytes[$i];
            $i++;
            continue;
        }

        $largo = 0;
        if (($byte & 0xE0) === 0xC0) {
            $largo = 2;
        } elseif (($byte & 0xF0) === 0xE0) {
            $largo = 3;
        } elseif (($byte & 0xF8) === 0xF0) {
            $largo = 4;
        }

        if ($largo > 0 && $i + $largo <= $longitud) {
            $secuencia = substr($bytes, $i, $largo);
            if (mb_check_encoding($secuencia, 'UTF-8') && mb_strlen($secuencia, 'UTF-8') === 1) {
                $salida .= $secuencia;
                $i += $largo;
                continue;
            }
        }

        // Byte que no forma UTF-8 valido: conservar el caracter CP437 original.
        $salida .= $byteACaracter[$byte] ?? $bytes[$i];
        $i++;
    }

    // 3. Corregir remanentes de doble codificacion.
    return strtr($salida, reemplazosDoblePaso());
}

function columnasDeTabla(string $sql, string $tabla): array
{
    $i = strpos($sql, 'CREATE TABLE `' . $tabla . '` (');
    if ($i === false) {
        return [];
    }
    $fin = strpos($sql, 'PRIMARY KEY', $i);
    if ($fin === false) {
        $fin = strpos($sql, ') ENGINE', $i);
    }
    $cuerpo = substr($sql, $i, $fin - $i);

    preg_match_all('/^\s*`([a-zA-Z0-9_]+)`\s+/m', $cuerpo, $m);

    return $m[1] ?? [];
}

$bloques = [];
foreach ($tablesDeseadas as $tabla) {
    $needle = 'INSERT INTO `' . $tabla . '` VALUES';
    $pos = strpos($sql, $needle);
    if ($pos === false) {
        fwrite(STDERR, "AVISO: no se encontro INSERT para {$tabla}\n");
        continue;
    }
    $start = $pos;
    $end = strpos($sql, ";\r\n", $start);
    if ($end === false) {
        $end = strpos($sql, ";\n", $start);
    }
    if ($end === false) {
        fwrite(STDERR, "AVISO: no se encontro fin del INSERT para {$tabla}\n");
        continue;
    }

    $insert = substr($sql, $start, $end - $start + 1);
    // Todo caracter no-ASCII del dump aparece dentro de literales de texto,
    // por lo que es seguro revertir el INSERT completo.
    $insert = revertirCp437($insert);
    $columnas = columnasDeTabla($sql, $tabla);

    if ($columnas !== []) {
        $colsList = implode(', ', array_map(fn ($c) => '`' . $c . '`', $columnas));
        $insert = str_replace(
            'INSERT INTO `' . $tabla . '` VALUES',
            'INSERT INTO `' . $tabla . '` (' . $colsList . ') VALUES',
            $insert
        );
    } else {
        fwrite(STDERR, "AVISO: sin columnas para {$tabla}, se deja posicional\n");
    }

    $bloques[$tabla] = $insert;
}

$salida = "SET NAMES utf8mb4;\n";
$salida .= "SET FOREIGN_KEY_CHECKS=0;\n";
foreach ($tablesDeseadas as $tabla) {
    if (isset($bloques[$tabla])) {
        $salida .= "TRUNCATE TABLE `{$tabla}`;\n";
        $salida .= $bloques[$tabla] . "\n";
    }
}
$salida .= "SET FOREIGN_KEY_CHECKS=1;\n";

file_put_contents($outPath, $salida);

echo "Generado: {$outPath}\n";
echo 'Tablas incluidas: ' . count($bloques) . "\n";
foreach ($bloques as $t => $b) {
    echo "  - {$t} (" . strlen($b) . " bytes)\n";
}

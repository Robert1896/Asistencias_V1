<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Response as ResponseFacade;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExportService
{
    private const CSS = 'body{font-family:Arial,sans-serif;font-size:11px;color:#333;margin:20px;}h1{font-size:16px;color:#1a237e;border-bottom:2px solid #1a237e;padding-bottom:6px;}h2{font-size:13px;color:#37474f;margin-top:24px;}table{width:100%;border-collapse:collapse;margin-top:8px;margin-bottom:18px;}th{background:#1a237e;color:#fff;padding:6px 8px;text-align:left;font-size:10px;}td{padding:5px 8px;border-bottom:1px solid #ddd;font-size:10px;}tr:nth-child(even){background:#f5f5f5;}.footer{margin-top:30px;font-size:9px;color:#999;text-align:center;border-top:1px solid #ddd;padding-top:8px;}';

    public function exportarPdf($reporte, $resumenDiario, string $titulo): Response
    {
        $html = '<!DOCTYPE html><html><head><meta charset="utf-8">';
        $html .= '<style>' . self::CSS . '</style></head><body>';
        $html .= '<h1>' . $titulo . '</h1>';

        $html .= '<h2>Productividad Individual</h2>';
        $html .= '<table><thead><tr><th>Empleado</th><th>Rol</th><th>Días</th><th>Horas Reales</th><th>Horas Teóricas</th><th>Productividad</th></tr></thead><tbody>';
        foreach ($reporte as $r) {
            $color = $r->productividad >= 80 ? '#4caf50' : ($r->productividad >= 50 ? '#ff9800' : '#f44336');
            $html .= '<tr><td>' . e($r->nombre) . '</td><td>' . e($r->rol_nombre) . '</td><td>' . $r->dias_asistidos . '</td><td>' . number_format($r->horas_reales, 1) . ' h</td><td>' . number_format($r->horas_teoricas, 1) . ' h</td><td style="color:' . $color . ';font-weight:bold;">' . $r->productividad . '%</td></tr>';
        }
        if ($reporte->isEmpty()) {
            $html .= '<tr><td colspan="6" style="text-align:center;color:#999;">Sin datos en el período.</td></tr>';
        }
        $html .= '</tbody></table>';

        $html .= '<h2>Resumen Diario</h2>';
        $html .= '<table><thead><tr><th>Fecha</th><th>Total</th><th>Presentes</th><th>Tardes</th><th>Inasistentes</th><th>Apoyo Sábado</th></tr></thead><tbody>';
        foreach ($resumenDiario as $d) {
            $html .= '<tr><td>' . e($d->fecha) . '</td><td>' . $d->total_registros . '</td><td>' . $d->presentes . '</td><td>' . $d->tardes . '</td><td>' . $d->inasistentes . '</td><td>' . $d->apoyos_sabado . '</td></tr>';
        }
        if ($resumenDiario->isEmpty()) {
            $html .= '<tr><td colspan="6" style="text-align:center;color:#999;">Sin registros en el período.</td></tr>';
        }
        $html .= '</tbody></table>';

        $html .= '<div class="footer">Generado el ' . now()->format('d/m/Y H:i') . ' — Sistema de Control de Asistencia</div>';
        $html .= '</body></html>';

        $pdf = Pdf::loadHTML($html)->setPaper('a4', 'landscape');
        return $pdf->download('reporte_productividad.pdf');
    }

    public function exportarExcel($reporte, $resumenDiario, string $titulo): Response
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()->setTitle('Reporte de Productividad')->setSubject('Reporte');

        $styleHeader = [
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1A237E']],
            'alignment' => ['horizontal' => 'center'],
        ];

        $styleBorder = [
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
        ];

        $cols = ['A', 'B', 'C', 'D', 'E', 'F'];

        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('Productividad Individual');
        $sheet1->mergeCells('A1:F1');
        $sheet1->setCellValue('A1', $titulo);
        $sheet1->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('1A237E');

        $headers = ['Empleado', 'Rol', 'Días', 'Horas Reales', 'Horas Teóricas', 'Productividad (%)'];
        foreach ($headers as $i => $h) {
            $sheet1->setCellValue($cols[$i] . '3', $h);
        }
        $sheet1->getStyle('A3:F3')->applyFromArray($styleHeader);

        $row = 4;
        foreach ($reporte as $r) {
            $sheet1->setCellValue('A' . $row, $r->nombre);
            $sheet1->setCellValue('B' . $row, $r->rol_nombre);
            $sheet1->setCellValue('C' . $row, $r->dias_asistidos);
            $sheet1->setCellValue('D' . $row, $r->horas_reales);
            $sheet1->setCellValue('E' . $row, $r->horas_teoricas);
            $sheet1->setCellValue('F' . $row, $r->productividad);
            $row++;
        }
        if ($reporte->isEmpty()) {
            $sheet1->mergeCells('A4:F4');
            $sheet1->setCellValue('A4', 'Sin datos en el período.');
        }

        $sheet1->getStyle('A3:F' . ($row - 1))->applyFromArray($styleBorder);
        $sheet1->getColumnDimension('A')->setWidth(30);
        $sheet1->getColumnDimension('B')->setWidth(18);
        $sheet1->getColumnDimension('C')->setWidth(10);
        $sheet1->getColumnDimension('D')->setWidth(14);
        $sheet1->getColumnDimension('E')->setWidth(14);
        $sheet1->getColumnDimension('F')->setWidth(16);

        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Resumen Diario');
        $sheet2->mergeCells('A1:F1');
        $sheet2->setCellValue('A1', 'Resumen Diario de Asistencias');
        $sheet2->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('1A237E');

        $headers2 = ['Fecha', 'Total', 'Presentes', 'Tardes', 'Inasistentes', 'Apoyo Sábado'];
        foreach ($headers2 as $i => $h) {
            $sheet2->setCellValue($cols[$i] . '3', $h);
        }
        $sheet2->getStyle('A3:F3')->applyFromArray($styleHeader);

        $row = 4;
        foreach ($resumenDiario as $d) {
            $sheet2->setCellValue('A' . $row, $d->fecha);
            $sheet2->setCellValue('B' . $row, $d->total_registros);
            $sheet2->setCellValue('C' . $row, $d->presentes);
            $sheet2->setCellValue('D' . $row, $d->tardes);
            $sheet2->setCellValue('E' . $row, $d->inasistentes);
            $sheet2->setCellValue('F' . $row, $d->apoyos_sabado);
            $row++;
        }
        if ($resumenDiario->isEmpty()) {
            $sheet2->mergeCells('A4:F4');
            $sheet2->setCellValue('A4', 'Sin registros en el período.');
        }

        $sheet2->getStyle('A3:F' . ($row - 1))->applyFromArray($styleBorder);

        $writer = new Xlsx($spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), 'reporte_');
        $writer->save($tempFile);
        $content = file_get_contents($tempFile);
        unlink($tempFile);

        return ResponseFacade::make($content, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment;filename="reporte_productividad.xlsx"',
        ]);
    }

    public function exportarCsv($reporte, $resumenDiario, string $titulo): Response
    {
        $handle = fopen('php://temp', 'r+');

        fwrite($handle, "\xEF\xBB\xBF");

        fputcsv($handle, ['REPORTE DE PRODUCTIVIDAD', $titulo], ';');
        fputcsv($handle, [], ';');
        fputcsv($handle, ['Empleado', 'Rol', 'Días', 'Horas Reales', 'Horas Teóricas', 'Productividad (%)'], ';');
        foreach ($reporte as $r) {
            fputcsv($handle, [$r->nombre, $r->rol_nombre, $r->dias_asistidos, $r->horas_reales, $r->horas_teoricas, $r->productividad], ';');
        }

        fputcsv($handle, [], ';');
        fputcsv($handle, ['RESUMEN DIARIO'], ';');
        fputcsv($handle, ['Fecha', 'Total', 'Presentes', 'Tardes', 'Inasistentes', 'Apoyo Sábado'], ';');
        foreach ($resumenDiario as $d) {
            fputcsv($handle, [$d->fecha, $d->total_registros, $d->presentes, $d->tardes, $d->inasistentes, $d->apoyos_sabado], ';');
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return ResponseFacade::make($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment;filename="reporte_productividad.csv"',
        ]);
    }

    public function empleadoPdf($empleado, $asistencias, string $titulo): Response
    {
        $css = str_replace('.footer', '.stats{display:flex;gap:20px;margin:10px 0;}.stat{padding:8px 16px;background:#f0f0f0;border-radius:6px;text-align:center;}.stat strong{display:block;font-size:18px;color:#1a237e;}.footer', self::CSS);

        $html = '<!DOCTYPE html><html><head><meta charset="utf-8">';
        $html .= '<style>' . $css . '</style></head><body>';
        $html .= '<h1>' . $titulo . '</h1>';
        $html .= '<p><strong>Período:</strong> ' . $asistencias->first()?->fecha?->format('d/m/Y') . ' al ' . $asistencias->last()?->fecha?->format('d/m/Y') . ' | <strong>Rol:</strong> ' . e($empleado->rol->nombre) . '</p>';

        $diasAsistidos = $asistencias->count();
        $horasReales = $asistencias->sum(function ($a) {
            if ($a->hora_entrada && $a->hora_salida) {
                return \Carbon\Carbon::parse($a->hora_entrada)->diffInHours(\Carbon\Carbon::parse($a->hora_salida));
            }
            return 0;
        });

        $html .= '<div class="stats">';
        $html .= '<div class="stat"><strong>' . $diasAsistidos . '</strong>Días Asistidos</div>';
        $html .= '<div class="stat"><strong>' . number_format($horasReales, 1) . 'h</strong>Horas Reales</div>';
        $html .= '<div class="stat"><strong>' . $asistencias->where('estado', 'Presente')->count() . '</strong>Presentes</div>';
        $html .= '<div class="stat"><strong>' . $asistencias->where('estado', 'Tarde')->count() . '</strong>Tardes</div>';
        $html .= '<div class="stat"><strong>' . $asistencias->where('estado', 'Inasistente')->count() . '</strong>Inasistentes</div>';
        $html .= '</div>';

        $html .= '<h2>Detalle de Asistencias</h2>';
        $html .= '<table><thead><tr><th>Fecha</th><th>Entrada</th><th>Salida</th><th>Estado</th><th>Justificado</th><th>Motivo</th></tr></thead><tbody>';
        foreach ($asistencias as $a) {
            $color = match($a->estado) { 'Presente' => '#4caf50', 'Tarde' => '#ff9800', 'Inasistente' => '#f44336', default => '#2196f3' };
            $html .= '<tr>';
            $html .= '<td>' . $a->fecha->format('d/m/Y') . '</td>';
            $html .= '<td>' . ($a->hora_entrada ?? '—') . '</td>';
            $html .= '<td>' . ($a->hora_salida ?? '—') . '</td>';
            $html .= '<td style="color:' . $color . ';font-weight:bold;">' . $a->estado . '</td>';
            $html .= '<td>' . $a->justificado . '</td>';
            $html .= '<td>' . e($a->texto_justificacion ?? '—') . '</td>';
            $html .= '</tr>';
        }
        if ($asistencias->isEmpty()) {
            $html .= '<tr><td colspan="6" style="text-align:center;color:#999;">Sin registros en el período.</td></tr>';
        }
        $html .= '</tbody></table>';
        $html .= '<div class="footer">Generado el ' . now()->format('d/m/Y H:i') . ' — Sistema de Control de Asistencia</div>';
        $html .= '</body></html>';

        $pdf = Pdf::loadHTML($html)->setPaper('a4', 'landscape');
        return $pdf->download('reporte_' . $empleado->id . '.pdf');
    }
}

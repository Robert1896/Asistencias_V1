<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ExportService;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ReporteController extends Controller
{
    public function index(Request $request, ReportService $reportService): View
    {
        $request->validate([
            'fecha_inicio' => 'nullable|date',
            'fecha_fin'    => 'nullable|date|after_or_equal:fecha_inicio',
            'tipo'         => 'nullable|in:general,docentes,administrativos',
        ]);

        $fechaInicio = $request->input('fecha_inicio', now()->startOfMonth()->toDateString());
        $fechaFin    = $request->input('fecha_fin', now()->endOfMonth()->toDateString());
        $tipoReporte = $request->input('tipo', 'general');

        $datos = $reportService->calcularProductividad($fechaInicio, $fechaFin, $tipoReporte);

        return view('admin.reportes', [
            'reporte'       => $datos['reporte'],
            'resumenDiario' => $datos['resumenDiario'],
            'fechaInicio'   => $fechaInicio,
            'fechaFin'      => $fechaFin,
            'tipoReporte'   => $tipoReporte,
        ]);
    }

    public function exportar(Request $request, ReportService $reportService, ExportService $exportService): Response
    {
        $request->validate([
            'formato'      => 'required|in:pdf,excel,csv',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin'    => 'nullable|date|after_or_equal:fecha_inicio',
            'tipo'         => 'nullable|in:general,docentes,administrativos',
        ]);

        $formato     = $request->input('formato', 'pdf');
        $fechaInicio = $request->input('fecha_inicio', now()->startOfMonth()->toDateString());
        $fechaFin    = $request->input('fecha_fin', now()->endOfMonth()->toDateString());
        $tipoReporte = $request->input('tipo', 'general');

        $datos = $reportService->calcularProductividad($fechaInicio, $fechaFin, $tipoReporte);

        $titulo = 'Reporte de Productividad — ' . now()->format('d/m/Y');

        return match ($formato) {
            'pdf'   => $exportService->exportarPdf($datos['reporte'], $datos['resumenDiario'], $titulo),
            'excel' => $exportService->exportarExcel($datos['reporte'], $datos['resumenDiario'], $titulo),
            default => $exportService->exportarCsv($datos['reporte'], $datos['resumenDiario'], $titulo),
        };
    }

    public function empleado(Request $request, User $empleado, ReportService $reportService): View
    {
        $request->validate([
            'fecha_inicio' => 'nullable|date',
            'fecha_fin'    => 'nullable|date|after_or_equal:fecha_inicio',
        ]);

        $fechaInicio = $request->input('fecha_inicio', now()->startOfMonth()->toDateString());
        $fechaFin    = $request->input('fecha_fin', now()->endOfMonth()->toDateString());

        $estadisticas = $reportService->calcularProductoEmpleado($empleado->id, $fechaInicio, $fechaFin);

        $asistencias = $empleado->asistencias()
            ->with('horario')
            ->whereBetween('fecha', [$fechaInicio, $fechaFin])
            ->orderByDesc('fecha')
            ->get();

        return view('admin.reporte_empleado', compact(
            'empleado', 'asistencias', 'estadisticas', 'fechaInicio', 'fechaFin'
        ));
    }

    public function empleadoExportar(Request $request, User $empleado, ExportService $exportService): Response
    {
        $request->validate([
            'formato'      => 'required|in:pdf',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin'    => 'nullable|date|after_or_equal:fecha_inicio',
        ]);

        $formato     = $request->input('formato', 'pdf');
        $fechaInicio = $request->input('fecha_inicio', now()->startOfMonth()->toDateString());
        $fechaFin    = $request->input('fecha_fin', now()->endOfMonth()->toDateString());

        $asistencias = $empleado->asistencias()
            ->whereBetween('fecha', [$fechaInicio, $fechaFin])
            ->orderBy('fecha')
            ->get();

        $titulo = "Reporte Individual — {$empleado->nombre_completo}";

        return $exportService->empleadoPdf($empleado, $asistencias, $titulo);
    }
}

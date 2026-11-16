<?php

/**
 * @file    ReporteController.php
 *
 * @author  Alex Candia <alex.leonar.candia@gmail.com>
 *
 * @created 2026-11-15
 *
 * @description
 * Controlador de las subopciones de Reportes del sidebar. Cada método
 * renderiza la vista de reportes acotada a una sola pestaña; los selectores de
 * materia y examen y el contenido de cada reporte se comparten con la vista
 * pages.reportes.
 *
 * @see  App\Services\Reporte\ListarFiltrosReporteService
 * @see  resources/views/pages/reportes.blade.php
 */

namespace App\Http\Controllers\Reporte;

use App\Http\Controllers\Controller;
use App\Services\Reporte\ListarFiltrosReporteService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ReporteController extends Controller
{
    public function asistencia(Request $request, ListarFiltrosReporteService $filtros): View
    {
        return $this->vista($request, $filtros, 'asistencia', 'Reporte de asistencia');
    }

    public function horarioIngreso(Request $request, ListarFiltrosReporteService $filtros): View
    {
        return $this->vista($request, $filtros, 'horarios', 'Reporte de horario de ingreso');
    }

    public function intentosNoAutorizados(Request $request, ListarFiltrosReporteService $filtros): View
    {
        return $this->vista($request, $filtros, 'ingresos', 'Reporte de intentos no autorizados');
    }

    /**
     * Vista compartida con los selectores y solo la pestaña pedida.
     */
    private function vista(
        Request $request,
        ListarFiltrosReporteService $filtros,
        string $soloTab,
        string $titulo,
    ): View {
        return view('pages.reportes', array_merge(
            $filtros->ejecutar($request->user()),
            [
                'soloTab' => $soloTab,
                'titulo' => $titulo,
            ],
        ));
    }
}

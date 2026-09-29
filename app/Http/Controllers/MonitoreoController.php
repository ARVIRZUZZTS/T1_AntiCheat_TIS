<?php

/**
 * @file    MonitoreoController.php
 *
 * @author  David E. Chavez T. <virzuzz12345@gmail.com>
 *
 * @created 2026-09-29
 *
 * @updated 2026-09-29
 *
 * @description
 * Controlador HTTP de la página de monitoreo en vivo. Obtiene el examen
 * más reciente, genera el reporte de asistencia y pasa los datos a la vista.
 *
 * @changelog
 * - 2026-09-29  [David E. Chavez T.]  feat: creación inicial del controlador.
 *
 * @see  GenerarReporteAsistenciaService
 */

namespace App\Http\Controllers;

use App\Models\Examen;
use App\Services\Monitoreo\GenerarReporteAsistenciaService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class MonitoreoController extends Controller
{
    public function __invoke(GenerarReporteAsistenciaService $servicio): View
    {
        $examen = Examen::orderByDesc('fecha')->orderByDesc('id_examen')->firstOrFail();
        $reporte = $servicio->ejecutar($examen->id_examen);

        $todosEstudiantes = $reporte['estudiantes'];

        $conteos = [
            'inscritos' => $todosEstudiantes->count(),
            'habilitados' => $todosEstudiantes->filter(fn ($e) => $e['estado_asistencia'] === 'presente')->count(),
            'deshabilitados' => $todosEstudiantes->filter(fn ($e) => $e['observaciones'] === 'deshabilitado')->count(),
            'sospechosos' => $todosEstudiantes->filter(fn ($e) => $e['estado_asistencia'] === 'pendiente')->count(),
            'tramposos' => $todosEstudiantes->filter(fn ($e) => $e['estado_asistencia'] === 'ausente')->count(),
        ];

        $pagina = (int) request('page', 1);
        $porPagina = config('monitoreo.por_pagina', 10);
        $estudiantes = new LengthAwarePaginator(
            $todosEstudiantes->forPage($pagina, $porPagina)->values(),
            $todosEstudiantes->count(),
            $porPagina,
            $pagina,
            ['path' => request()->url()]
        );

        return view('pages.monitoreo', [
            'examen' => $examen,
            'estudiantes' => $estudiantes,
            'conteos' => $conteos,
        ]);
    }
}

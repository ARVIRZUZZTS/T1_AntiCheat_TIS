<?php

/**
 * @file    EditarCursoVistaController.php
 * @author  Alisson D. Alvarado <alvaradoalissondalet@gmail.com>
 * @created 2026-11-21
 * @updated 2026-11-23
 *
 * @description
 * Prepara la página de edición con los cursos propios y el catálogo de gestiones.
 *
 * @changelog
 * - 2026-11-21  [Alisson D. Alvarado]  feat: preparar la vista de edición de cursos.
 * - 2026-11-23  [Alisson D. Alvarado]  feat: mostrar solo cursos propios activos.
 */

namespace App\Http\Controllers;

use App\Services\Curso\ListarCursosDocenteService;
use App\Services\Curso\ListarGestionesService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class EditarCursoVistaController extends Controller
{
    /**
     * Reutiliza los servicios existentes para mantener el alcance del docente.
     *
     * @param  Request  $request  Solicitud del docente autenticado.
     * @param  ListarCursosDocenteService  $cursos  Listado de cursos propios.
     * @param  ListarGestionesService  $gestiones  Catálogo de gestiones.
     * @return View Página de edición de cursos.
     * @author Alisson D. Alvarado <alvaradoalissondalet@gmail.com>
     * @since 2026-11-21
     */
    public function __invoke(Request $request, ListarCursosDocenteService $cursos, ListarGestionesService $gestiones): View
    {
        return view('pages.curso-editar', [
            'cursos' => $cursos->conMetricas((int) $request->user()->id_usuario)
                ->filter(fn (array $fila): bool => $fila['curso']->estaEnCurso())
                ->values(),
            'gestiones' => $gestiones->ejecutar(),
        ]);
    }
}

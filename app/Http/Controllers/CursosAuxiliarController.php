<?php

/**
 * @file    CursosAuxiliarController.php
 * @author  Alisson D. Alvarado <alvaradoalissondalet@gmail.com>
 * @created 2026-11-24
 * @updated 2026-11-24
 *
 * @description
 * Presenta los cursos asignados al auxiliar autenticado.
 *
 * @changelog
 * - 2026-11-24  [Alisson D. Alvarado]  feat: conectar la lista de cursos del auxiliar.
 */

namespace App\Http\Controllers;

use App\Models\Rol;
use App\Services\Auxiliar\ListarCursosAuxiliarService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/** Entrada de la página de cursos del auxiliar. */
class CursosAuxiliarController extends Controller
{
    /**
     * @param  Request  $request  Solicitud del usuario autenticado.
     * @param  ListarCursosAuxiliarService  $servicio  Servicio de cursos asignados.
     * @return View Lista de cursos en el formato del docente.
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException Si no es auxiliar.
     * @author Alisson D. Alvarado <alvaradoalissondalet@gmail.com>
     * @since 2026-11-24
     */
    public function __invoke(Request $request, ListarCursosAuxiliarService $servicio): View
    {
        abort_unless($request->user()->roles->contains('nombre_rol', Rol::NOMBRE_AUXILIAR), 403);

        return view('pages.cursos', [
            'cursos' => $servicio->ejecutar((int) $request->user()->id_usuario),
        ]);
    }
}

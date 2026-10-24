<?php

/**
 * @file    ListarGestionesService.php
 *
 * @author  Valery D. Ortuno P <valerydariana98@gmail.com>
 *
 * @created 2026-10-24
 *
 * @description
 * Catálogo de gestiones (tabla `tipo_gestion`: primer semestre, invierno,
 * segundo semestre, verano) para el selector "Gestion" del formulario de
 * crear curso, en el formato que espera x-ui.select (id => etiqueta).
 *
 * @changelog
 * - 2026-10-24  [Valery D. Ortuno P]  feat: creación inicial del servicio.
 *
 * @see  App\Http\Controllers\CursoController
 * @see  resources/views/pages/curso-crear.blade.php
 */

namespace App\Services\Curso;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ListarGestionesService
{
    /**
     * Gestiones ordenadas como en el calendario académico (por id).
     *
     * @return array<int, string>  id_tipo_gestion => etiqueta ("Primer semestre").
     *
     * @author Valery D. Ortuno P <valerydariana98@gmail.com>
     * @since  2026-10-24
     */
    public function ejecutar(): array
    {
        return DB::table('tipo_gestion')
            ->orderBy('id_tipo_gestion')
            ->pluck('nombre_tipo_gestion', 'id_tipo_gestion')
            ->map(fn (string $nombre): string => Str::ucfirst($nombre))
            ->all();
    }
}

<?php

/**
 * @file    ListarGestionesService.php
 *
 * @author  Valery D. Ortuno P <valerydariana98@gmail.com>
 *
 * @created 2026-10-24
 *
 * @updated 2026-10-26
 *
 * @description
 * Catálogo de gestiones (tabla `tipo_gestion`: primer semestre, invierno,
 * segundo semestre, verano) para el selector "Gestion" del formulario de
 * crear curso, en el formato que espera x-ui.select (id => etiqueta).
 * Se cachea unos minutos (CACHE_MINUTOS): la aplicación no modifica el
 * catálogo y lo usan el formulario y la validación del alta.
 *
 * @changelog
 * - 2026-10-24  [Valery D. Ortuno P]  feat: creación inicial del servicio.
 * - 2026-10-26  [Valery D. Ortuno P]  perf: el catálogo se cachea.
 *
 * @see  App\Http\Controllers\CursoController
 * @see  resources/views/pages/curso-crear.blade.php
 */

namespace App\Services\Curso;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ListarGestionesService
{
    /** Minutos que se cachea el catálogo. */
    public const CACHE_MINUTOS = 10;

    /** Clave de caché del catálogo. */
    public const CACHE_CLAVE = 'catalogos.gestiones';

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
        return Cache::remember(
            self::CACHE_CLAVE,
            now()->addMinutes(self::CACHE_MINUTOS),
            fn (): array => DB::table('tipo_gestion')
                ->orderBy('id_tipo_gestion')
                ->pluck('nombre_tipo_gestion', 'id_tipo_gestion')
                ->map(fn (string $nombre): string => Str::ucfirst($nombre))
                ->all(),
        );
    }
}

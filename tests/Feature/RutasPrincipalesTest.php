<?php

/**
 * @file    RutasPrincipalesTest.php
 *
 * @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
 *
 * @created 2026-09-28
 *
 * @updated 2026-09-28
 * @updated 2026-10-24
 *
 * @description
 * Prueba de humo (smoke test) de las rutas principales de navegación. Se
 * agregó tras un bug real: un merge (`76cc77a`) borró en silencio la ruta
 * `materias.detalle` (hoy `cursos.detalle`) sin dejar marcadores de conflicto
 * ni romper la compilación — nadie lo notó hasta probarlo a mano. Este test
 * existe para que una pérdida de ruta como esa falle en la próxima corrida de
 * tests, no en producción.
 *
 * @see  routes/web.php
 *
 * @changelog
 * - 2026-09-28  [T1]  test: creación inicial (fix de la ruta materias.detalle).
 * - 2026-09-28  [T1]  test: la ruta recibe el id_curso numérico (la lista ya
 *   no usa códigos mock tipo MAT-101) y se prueba contra un curso real.
 * - 2026-10-24  [Valery D. Ortuno P]  test: Materias pasa a Cursos (rutas
 *   `cursos` y `cursos.detalle`) y se suman Crear curso y la sección Exámenes.
 *   Las pruebas inician sesión con el docente del curso, porque las rutas son
 *   solo para docentes y cada uno ve solo sus cursos.
 */

namespace Tests\Feature;

use App\Models\Curso;
use App\Models\Usuario;
use Tests\TestCase;

class RutasPrincipalesTest extends TestCase
{
    public function test_la_lista_de_cursos_carga_sin_error(): void
    {
        $this->actingAsDocenteDe($this->cursoDeLaBase());

        $this->get(route('cursos'))->assertOk();
    }

    public function test_la_pagina_de_crear_curso_carga_sin_error(): void
    {
        $this->actingAsDocenteDe($this->cursoDeLaBase());

        $this->get(route('cursos.crear'))->assertOk();
    }

    /**
     * Regresión puntual: route('cursos.detalle', ...) debe existir y
     * resolver, porque pages/cursos.blade.php genera un link con ella
     * para cada curso listado.
     */
    public function test_el_detalle_de_un_curso_carga_sin_error(): void
    {
        $curso = $this->cursoDeLaBase();
        $this->actingAsDocenteDe($curso);

        $this->get(route('cursos.detalle', $curso->id_curso))->assertOk();
    }

    public function test_el_link_de_la_lista_apunta_al_detalle_correcto(): void
    {
        $curso = $this->cursoDeLaBase();
        $this->actingAsDocenteDe($curso);

        $this->get(route('cursos'))
            ->assertSee(route('cursos.detalle', $curso->id_curso), false);
    }

    public function test_un_docente_no_abre_el_curso_de_otro(): void
    {
        $curso = $this->cursoDeLaBase();
        $otroCurso = Curso::query()->where('sis_doc', '!=', $curso->sis_doc)->first();

        if ($otroCurso === null) {
            $this->markTestSkipped('La base no tiene cursos de dos docentes distintos.');
        }

        $this->actingAsDocenteDe($curso);

        $this->get(route('cursos.detalle', $otroCurso->id_curso))->assertForbidden();
    }

    public function test_la_seccion_examenes_carga_sin_error(): void
    {
        $curso = $this->cursoDeLaBase();
        $this->actingAsDocenteDe($curso);

        $this->get(route('examenes'))->assertOk();
        $this->get(route('examenes.curso', $curso->id_curso))->assertOk();
        $this->get(route('examenes.crear', $curso->id_curso))->assertOk();
    }

    private function cursoDeLaBase(): Curso
    {
        return Curso::query()->orderBy('id_curso')->firstOrFail();
    }

    private function actingAsDocenteDe(Curso $curso): void
    {
        $this->actingAs(Usuario::query()->findOrFail($curso->sis_doc));
    }
}

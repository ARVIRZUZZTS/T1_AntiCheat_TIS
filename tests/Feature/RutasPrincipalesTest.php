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
 * @updated 2026-11-09
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
 * - 2026-11-09  [Diegootm]  fix: el link del detalle se busca por el marcador
 *   __CURSO__ del enlace de Alpine — la URL viaja JSON-escapada y el texto
 *   literal ya no aparece en el HTML.
 * - 2026-11-15  [Alex Candia]  test: humo de las subopciones de Cursos (Ver /
 *   Habilitar estudiantes) y de Reportes (asistencia, horario, intentos).
 */

namespace Tests\Feature;

use App\Models\Curso;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RutasPrincipalesTest extends TestCase
{
    // La prueba arma sus propios datos en una transacción que se revierte,
    // igual que el resto del suite; antes dependía de que la base ya tuviera
    // cursos sembrados.
    use DatabaseTransactions;

    private Curso $curso;

    private Curso $cursoAjeno;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('rol')->insertOrIgnore(['id_rol' => 1, 'nombre_rol' => 'docente']);

        $docente = $this->crearDocente(880001, 'RutasDocenteUno');
        $otroDocente = $this->crearDocente(880002, 'RutasDocenteDos');

        $this->curso = $this->crearCurso(880101, 'Rutas Curso Propio', $docente);
        $this->cursoAjeno = $this->crearCurso(880102, 'Rutas Curso Ajeno', $otroDocente);
    }

    public function test_la_lista_de_cursos_carga_sin_error(): void
    {
        $this->actingAsDocenteDe($this->curso);

        $this->get(route('cursos'))->assertOk();
    }

    public function test_la_raiz_redirige_al_listado_real_del_docente(): void
    {
        $this->actingAsDocenteDe($this->curso);

        $this->get('/')->assertRedirect(route('cursos'));
        $this->get('/inicio')->assertRedirect('/');
    }

    public function test_la_pagina_de_crear_curso_carga_sin_error(): void
    {
        $this->actingAsDocenteDe($this->curso);

        $this->get(route('cursos.crear'))->assertOk();
    }

    /**
     * Regresión puntual: route('cursos.detalle', ...) debe existir y
     * resolver, porque pages/cursos.blade.php genera un link con ella
     * para cada curso listado.
     */
    public function test_el_detalle_de_un_curso_carga_sin_error(): void
    {
        $this->actingAsDocenteDe($this->curso);

        $this->get(route('cursos.detalle', $this->curso->id_curso))->assertOk();
    }

    public function test_un_docente_no_abre_el_curso_de_otro(): void
    {
        $this->actingAsDocenteDe($this->curso);

        $this->get(route('cursos.detalle', $this->cursoAjeno->id_curso))->assertForbidden();
    }

    public function test_las_subopciones_de_cursos_cargan_sin_error(): void
    {
        $this->actingAsDocenteDe($this->curso);

        $this->get(route('cursos.ver-estudiantes'))->assertOk();
        $this->get(route('cursos.habilitar-estudiantes'))->assertOk();
        $this->get(route('cursos.ver-estudiantes', ['curso' => $this->curso->id_curso]))->assertOk();
        $this->get(route('cursos.habilitar-estudiantes', ['curso' => $this->curso->id_curso]))->assertOk();
        $this->get(route('cursos.ver', ['curso' => $this->curso->id_curso]))
            ->assertRedirect(route('cursos.ver-estudiantes', ['curso' => $this->curso->id_curso]));
    }

    public function test_las_subopciones_de_reportes_cargan_sin_error(): void
    {
        $this->actingAsDocenteDe($this->curso);

        $this->get(route('reportes.asistencia'))->assertOk();
        $this->get(route('reportes.horario-ingreso'))->assertOk();
        $this->get(route('reportes.intentos-no-autorizados'))->assertOk();
    }

    public function test_la_seccion_examenes_carga_sin_error(): void
    {
        $this->actingAsDocenteDe($this->curso);

        $this->get(route('examenes'))->assertOk();
        $this->get(route('examenes.curso', $this->curso->id_curso))->assertOk();
        // El alta por curso se renombro a examenes.curso.crear (el nombre
        // examenes.crear lo usa ahora la pagina nueva "Crear examen").
        $this->get(route('examenes.curso.crear', $this->curso->id_curso))->assertOk();
    }

    private function crearDocente(int $id, string $nombreUsuario): Usuario
    {
        DB::table('usuario')->insert([
            'id_usuario' => $id,
            'cod_sis' => 'RUT'.$id,
            'password' => Hash::make('secret123'),
            'nombre_usuario' => $nombreUsuario,
            'apellido' => 'Prueba',
            'estado_cuenta' => 'habilitada',
        ]);
        DB::table('rol_usuario')->insert(['id_usuario' => $id, 'id_rol' => 1]);

        return Usuario::query()->findOrFail($id);
    }

    private function crearCurso(int $id, string $nombre, Usuario $docente): Curso
    {
        DB::table('curso')->insert([
            'id_curso' => $id,
            'nombre_curso' => $nombre,
            'sis_doc' => $docente->id_usuario,
            'estado' => 'EnCurso',
            'fecha_creacion' => now()->toDateString(),
        ]);

        return Curso::query()->findOrFail($id);
    }

    private function actingAsDocenteDe(Curso $curso): void
    {
        $this->actingAs(Usuario::query()->findOrFail($curso->sis_doc));
    }
}

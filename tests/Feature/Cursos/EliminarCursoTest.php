<?php

/**
 * @file    EliminarCursoTest.php
 *
 * @author  Alex Candia
 *
 * @created 2026-11-16
 *
 * @description
 * Pruebas de la eliminación de un curso: el docente dueño puede borrarlo y se
 * limpian sus vínculos, sus exámenes se desvinculan con
 * EliminarExamenCursoService, el modal obtiene el conteo de auxiliares y
 * estudiantes afectados, otro docente no puede borrarlo y, al quedar sin
 * cursos, la lista muestra el estado vacío.
 *
 * @changelog
 * - 2026-11-16  [Alex Candia]  test: eliminación de curso, conteo de afectados,
 *   lista de la página "Eliminar curso" y respuesta JSON del borrado.
 * - 2026-11-16  [Alex Candia]  test: los exámenes del curso se eliminan con el
 *   servicio de examen.
 *
 * @see  App\Services\Curso\EliminarCursoService
 * @see  App\Services\Curso\ContarAfectadosCursoService
 * @see  App\Services\Examen\EliminarExamenCursoService
 * @see  App\Policies\CursoPolicy
 */

namespace Tests\Feature\Cursos;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EliminarCursoTest extends TestCase
{
    use DatabaseTransactions;

    private const DOCENTE = 970001;

    private const OTRO = 970002;

    private const AUXILIAR_UNO = 970011;

    private const AUXILIAR_DOS = 970012;

    private const CURSO_PROPIO = 970101;

    private const CURSO_AJENO = 970102;

    private const EXAMEN = 970401;

    private const ESTUDIANTE_EXAMEN = 970501;

    private const INCIDENCIA = 970601;

    protected function setUp(): void
    {
        parent::setUp();

        $rolDocente = DB::table('rol')->where('nombre_rol', 'docente')->value('id_rol');
        $rolAuxiliar = DB::table('rol')->where('nombre_rol', 'auxiliar')->value('id_rol');
        $tipoExamen = DB::table('tipo_examen')->value('id_tipo_examen');
        $gestion = DB::table('tipo_gestion')->value('id_tipo_gestion');

        $this->assertNotNull($rolDocente);
        $this->assertNotNull($rolAuxiliar);
        $this->assertNotNull($tipoExamen);
        $this->assertNotNull($gestion);

        foreach ([
            [self::DOCENTE, 'docente', $rolDocente],
            [self::OTRO, 'docente', $rolDocente],
            [self::AUXILIAR_UNO, 'auxiliar', $rolAuxiliar],
            [self::AUXILIAR_DOS, 'auxiliar', $rolAuxiliar],
        ] as [$id, $tipo, $rol]) {
            DB::table('usuario')->insert([
                'id_usuario' => $id,
                'cod_sis' => 'USR'.$id,
                'password' => 'x',
                'nombre_usuario' => ucfirst($tipo).$id,
                'apellido' => (string) $id,
            ]);
            DB::table('rol_usuario')->insert(['id_usuario' => $id, 'id_rol' => $rol]);
        }

        foreach ([
            [self::CURSO_PROPIO, 'Curso a eliminar', self::DOCENTE],
            [self::CURSO_AJENO, 'Curso de otro docente', self::OTRO],
        ] as [$id, $nombre, $docente]) {
            DB::table('curso')->insert([
                'id_curso' => $id,
                'nombre_curso' => $nombre,
                'sis_doc' => $docente,
                'estado' => 'EnCurso',
                'fecha_creacion' => now()->toDateString(),
            ]);
            DB::table('curso_tipo_gestion')->insert([
                'id_curso' => $id,
                'id_tg' => $gestion,
                'anio' => now()->year,
            ]);
        }

        foreach (['970301', '970302', '970303'] as $sis) {
            DB::table('estudiante')->insert([
                'sis_estudiante' => $sis,
                'nombre_estudiante' => 'Estudiante',
                'apellido_estudiante' => $sis,
            ]);
            DB::table('estudiante_curso')->insert([
                'sis_estudiante' => $sis,
                'id_curso' => self::CURSO_PROPIO,
            ]);
        }

        foreach ([
            [self::AUXILIAR_UNO, self::CURSO_PROPIO],
            [self::AUXILIAR_DOS, self::CURSO_PROPIO],
            [self::AUXILIAR_UNO, self::CURSO_AJENO],
        ] as [$auxiliar, $curso]) {
            DB::table('auxiliar_curso')->insert([
                'id_auxiliar' => $auxiliar,
                'id_curso' => $curso,
                'estado' => 'Activo',
            ]);
        }

        DB::table('examen')->insert([
            'id_examen' => self::EXAMEN,
            'fecha' => now()->addDay()->toDateString(),
            'hora_inicio' => '12:00:00',
            'duracion' => 90,
            'creador' => self::DOCENTE,
            'tipo_examen' => $tipoExamen,
        ]);
        DB::table('examen_curso')->insert([
            ['id_examen' => self::EXAMEN, 'id_curso' => self::CURSO_PROPIO],
            ['id_examen' => self::EXAMEN, 'id_curso' => self::CURSO_AJENO],
        ]);

        DB::table('estudiante_examen')->insert([
            'id_estudiante_examen' => self::ESTUDIANTE_EXAMEN,
            'sis_estudiante' => '970301',
            'id_examen' => self::EXAMEN,
            'id_curso' => self::CURSO_PROPIO,
            'estado' => 'habilitado',
        ]);

        DB::table('central_riesgo')->insert([
            'id_registro' => self::INCIDENCIA,
            'id_registrador' => self::DOCENTE,
            'id_curso' => self::CURSO_PROPIO,
            'tipo_infraccion' => 'sospechoso',
            'sis_estudiante' => '970301',
            'id_examen' => self::EXAMEN,
            'fecha_registro' => now()->toDateTimeString(),
        ]);
    }

    public function test_el_docente_dueno_elimina_su_curso_y_se_limpian_los_vinculos(): void
    {
        $this->actingAs(Usuario::findOrFail(self::DOCENTE))
            ->delete(route('cursos.destroy', self::CURSO_PROPIO))
            ->assertRedirect(route('cursos'))
            ->assertSessionHas('mensaje', 'Curso eliminado con exito');

        $this->assertDatabaseMissing('curso', ['id_curso' => self::CURSO_PROPIO]);
        $this->assertDatabaseMissing('curso_tipo_gestion', ['id_curso' => self::CURSO_PROPIO]);
        $this->assertDatabaseMissing('auxiliar_curso', ['id_curso' => self::CURSO_PROPIO]);
        $this->assertDatabaseMissing('estudiante_curso', ['id_curso' => self::CURSO_PROPIO]);
        $this->assertDatabaseMissing('examen_curso', ['id_curso' => self::CURSO_PROPIO]);

        // El servicio de examen borra la inscripción de este curso y desvincula
        // el examen, pero respeta el vínculo con el otro curso que lo comparte.
        $this->assertDatabaseMissing('estudiante_examen', [
            'id_estudiante_examen' => self::ESTUDIANTE_EXAMEN,
        ]);
        $this->assertDatabaseHas('examen', ['id_examen' => self::EXAMEN]);
        $this->assertDatabaseHas('examen_curso', [
            'id_examen' => self::EXAMEN,
            'id_curso' => self::CURSO_AJENO,
        ]);

        // Las incidencias pueden sobrevivir sin curso, pero no sin examen.
        $this->assertDatabaseHas('central_riesgo', [
            'id_registro' => self::INCIDENCIA,
            'id_curso' => null,
            'id_examen' => self::EXAMEN,
        ]);

        $this->get(route('cursos'))
            ->assertOk()
            ->assertSee('No tienes cursos registrados');
    }

    public function test_el_conteo_de_afectados_informa_auxiliares_y_estudiantes(): void
    {
        $this->actingAs(Usuario::findOrFail(self::DOCENTE))
            ->getJson(route('cursos.eliminar.afectados', self::CURSO_PROPIO))
            ->assertOk()
            ->assertExactJson([
                'auxiliares' => 2,
                'estudiantes' => 3,
            ]);
    }

    public function test_la_pagina_eliminar_lista_los_cursos_con_su_boton(): void
    {
        $this->actingAs(Usuario::findOrFail(self::DOCENTE))
            ->get(route('cursos.eliminar'))
            ->assertOk()
            ->assertSee('Curso a eliminar')
            ->assertSee('Eliminar curso')
            ->assertSee('data-url-afectados', false)
            ->assertDontSee('Curso de otro docente');
    }

    public function test_la_eliminacion_por_json_devuelve_el_mensaje_de_exito(): void
    {
        $this->actingAs(Usuario::findOrFail(self::DOCENTE))
            ->deleteJson(route('cursos.destroy', self::CURSO_PROPIO))
            ->assertOk()
            ->assertExactJson(['mensaje' => 'Curso eliminado con exito']);

        $this->assertDatabaseMissing('curso', ['id_curso' => self::CURSO_PROPIO]);
    }

    public function test_otro_docente_no_puede_eliminar_ni_consultar_el_curso(): void
    {
        $this->actingAs(Usuario::findOrFail(self::OTRO));

        $this->getJson(route('cursos.eliminar.afectados', self::CURSO_PROPIO))
            ->assertForbidden();

        $this->delete(route('cursos.destroy', self::CURSO_PROPIO))
            ->assertForbidden();

        $this->assertDatabaseHas('curso', ['id_curso' => self::CURSO_PROPIO]);
    }
}

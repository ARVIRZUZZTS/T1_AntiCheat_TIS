<?php

/**
 * @file    CoordinadorAsignarAuxiliarTest.php
 *
 * @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
 *
 * @created 2026-11-27
 *
 * @description
 * Restricción del coordinador al asignar: solo en los cursos y exámenes de su
 * docente responsable en los que él mismo está asignado, y nunca a sí mismo.
 * También que las opciones y la lista reflejan ese alcance.
 *
 * @changelog
 * - 2026-11-27  [Diego Tejerina]  feat: creación inicial.
 *
 * @see  App\Services\Auxiliar\AsignarAuxiliarService
 * @see  App\Services\Auxiliar\ListarAsignacionesAuxiliaresService
 */

namespace Tests\Feature\Auxiliares;

use App\Services\Auxiliar\AsignarAuxiliarService;
use App\Services\Auxiliar\ListarAsignacionesAuxiliaresService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CoordinadorAsignarAuxiliarTest extends TestCase
{
    use DatabaseTransactions;

    private const DOCENTE = 1001;

    private const COORDINADOR = 1002;

    private const AUXILIAR = 1003;

    private const CURSO_SUYO = 2001;     // El coordinador está asignado.

    private const CURSO_AJENO = 2002;    // Del docente, pero el coordinador no.

    private AsignarAuxiliarService $servicio;

    protected function setUp(): void
    {
        parent::setUp();
        $this->servicio = app(AsignarAuxiliarService::class);

        $clave = Hash::make('secreto123');
        DB::table('usuario')->insert(['id_usuario' => self::DOCENTE, 'cod_sis' => 'DOC1001', 'correo' => 'doc1001@umss.edu', 'password' => $clave, 'nombre_usuario' => 'Doc', 'nombre' => 'Doc', 'apellido' => 'Uno', 'estado_cuenta' => 'habilitada', 'puede_crear_auxiliares' => false, 'tipo_auxiliar' => null, 'id_docente_responsable' => null]);
        DB::table('usuario')->insert(['id_usuario' => self::COORDINADOR, 'cod_sis' => 'AUX1002', 'correo' => 'coord1002@ejemplo.com', 'password' => $clave, 'nombre_usuario' => 'Coord', 'nombre' => 'Coord', 'apellido' => 'Dos', 'estado_cuenta' => 'habilitada', 'puede_crear_auxiliares' => true, 'tipo_auxiliar' => 'UMSS', 'id_docente_responsable' => self::DOCENTE]);
        DB::table('usuario')->insert(['id_usuario' => self::AUXILIAR, 'cod_sis' => 'AUX1003', 'correo' => 'aux1003@ejemplo.com', 'password' => $clave, 'nombre_usuario' => 'Aux', 'nombre' => 'Aux', 'apellido' => 'Tres', 'estado_cuenta' => 'habilitada', 'puede_crear_auxiliares' => false, 'tipo_auxiliar' => 'UMSS', 'id_docente_responsable' => self::DOCENTE]);
        DB::table('rol_usuario')->insert([
            ['id_usuario' => self::DOCENTE, 'id_rol' => 1],
            ['id_usuario' => self::COORDINADOR, 'id_rol' => 2],
            ['id_usuario' => self::AUXILIAR, 'id_rol' => 2],
        ]);
        DB::table('curso')->insert([
            ['id_curso' => self::CURSO_SUYO, 'nombre_curso' => 'Curso Suyo', 'sis_doc' => self::DOCENTE, 'fecha_creacion' => '2026-02-01', 'estado' => 'EnCurso'],
            ['id_curso' => self::CURSO_AJENO, 'nombre_curso' => 'Curso Ajeno', 'sis_doc' => self::DOCENTE, 'fecha_creacion' => '2026-02-01', 'estado' => 'EnCurso'],
        ]);
        // El coordinador solo está asignado al "curso suyo".
        DB::table('auxiliar_curso')->insert([
            ['id_auxiliar' => self::COORDINADOR, 'id_curso' => self::CURSO_SUYO, 'estado' => 'Activo'],
        ]);
    }

    public function test_asigna_en_un_curso_en_el_que_esta_asignado(): void
    {
        $curso = $this->servicio->asignarCurso(self::DOCENTE, self::AUXILIAR, self::CURSO_SUYO, self::COORDINADOR);

        $this->assertSame('Curso Suyo', $curso);
        $this->assertDatabaseHas('auxiliar_curso', [
            'id_auxiliar' => self::AUXILIAR, 'id_curso' => self::CURSO_SUYO, 'estado' => 'Activo',
        ]);
    }

    public function test_no_asigna_en_un_curso_en_el_que_no_esta_asignado(): void
    {
        try {
            $this->servicio->asignarCurso(self::DOCENTE, self::AUXILIAR, self::CURSO_AJENO, self::COORDINADOR);
            $this->fail('No debería permitir asignar fuera de su alcance.');
        } catch (ValidationException $error) {
            $this->assertSame(AsignarAuxiliarService::MENSAJE_FUERA_DE_ALCANCE, collect($error->errors())->flatten()->first());
        }

        $this->assertDatabaseMissing('auxiliar_curso', ['id_auxiliar' => self::AUXILIAR, 'id_curso' => self::CURSO_AJENO]);
    }

    public function test_no_puede_asignarse_a_si_mismo(): void
    {
        try {
            $this->servicio->asignarCurso(self::DOCENTE, self::COORDINADOR, self::CURSO_SUYO, self::COORDINADOR);
            $this->fail('No debería permitir asignarse a sí mismo.');
        } catch (ValidationException $error) {
            $this->assertSame(AsignarAuxiliarService::MENSAJE_NO_A_SI_MISMO, collect($error->errors())->flatten()->first());
        }
    }

    public function test_las_opciones_solo_incluyen_sus_cursos_y_se_excluye_a_si_mismo(): void
    {
        $datos = app(ListarAsignacionesAuxiliaresService::class)->ejecutar(self::DOCENTE, self::COORDINADOR);

        $idsCursos = array_column($datos['cursos'], 'id');
        $this->assertSame([self::CURSO_SUYO], $idsCursos);

        $idsAuxiliares = array_column($datos['auxiliares'], 'id');
        $this->assertContains(self::AUXILIAR, $idsAuxiliares);
        $this->assertNotContains(self::COORDINADOR, $idsAuxiliares);
    }

    public function test_el_docente_no_tiene_la_restriccion_del_coordinador(): void
    {
        // Sin coordinador (lo hace el docente), puede asignar en cualquiera de sus cursos.
        $curso = $this->servicio->asignarCurso(self::DOCENTE, self::AUXILIAR, self::CURSO_AJENO, null);

        $this->assertSame('Curso Ajeno', $curso);
    }
}

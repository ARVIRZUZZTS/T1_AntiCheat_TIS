<?php

/**
 * @file    AuxiliaresDocenteApiTest.php
 *
 * @author  OchoaCesar <cesareduardonick@gmail.com>
 *
 * @created 2026-11-08
 *
 * @updated 2026-11-08
 *
 * @description
 * Pruebas del endpoint GET /api/docentes/me/auxiliares: lista de todas las
 * cuentas de auxiliar con estado ACTIVO/INACTIVO según sus asignaciones en los
 * cursos del docente, además de 401 sin sesión, 403 con otro rol, aislamiento
 * por docente y solo lectura.
 *
 * @changelog
 * - 2026-11-08  [OchoaCesar]  test: cobertura del endpoint de auxiliares.
 * - 2026-11-24  [Equipo T1]  test: el estado sale de las asignaciones activas
 *   y las cuentas sin curso aparecen como INACTIVO.
 *
 * @see  App\Http\Controllers\Api\AuxiliaresDocenteController
 */

namespace Tests\Feature\Auxiliares;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AuxiliaresDocenteApiTest extends TestCase
{
    use DatabaseTransactions;

    private const DOCENTE = 997101;

    private const OTRO_DOCENTE = 997102;

    private const USUARIO_AUXILIAR = 997103;

    private const DOCENTE_VACIO = 997104;

    private const CURSO_ALGORITMOS = 997201;

    private const CURSO_BASES = 997202;

    private const CURSO_DE_OTRO = 997203;

    private const AUXILIAR_ACTIVO = 997301;

    private const AUXILIAR_OTRO_CURSO = 997302;

    private const AUXILIAR_DE_BAJA = 997303;

    private const AUXILIAR_DE_OTRO_DOCENTE = 997304;

    protected function setUp(): void
    {
        parent::setUp();

        // Los roles son datos de referencia (002_seed_data.sql); la BD de
        // pruebas puede venir sin ellos si solo corrió `migrate`.
        DB::table('rol')->insertOrIgnore([
            ['id_rol' => 1, 'nombre_rol' => 'docente'],
            ['id_rol' => 2, 'nombre_rol' => 'auxiliar'],
        ]);

        $rolDocente = DB::table('rol')->where('nombre_rol', 'docente')->value('id_rol');
        $rolAuxiliar = DB::table('rol')->where('nombre_rol', 'auxiliar')->value('id_rol');

        $this->assertNotNull($rolDocente);
        $this->assertNotNull($rolAuxiliar);

        foreach ([
            self::DOCENTE => $rolDocente,
            self::OTRO_DOCENTE => $rolDocente,
            self::USUARIO_AUXILIAR => $rolAuxiliar,
            self::DOCENTE_VACIO => $rolDocente,
            self::AUXILIAR_ACTIVO => $rolAuxiliar,
            self::AUXILIAR_OTRO_CURSO => $rolAuxiliar,
            self::AUXILIAR_DE_BAJA => $rolAuxiliar,
            self::AUXILIAR_DE_OTRO_DOCENTE => $rolAuxiliar,
        ] as $id => $rol) {
            DB::table('usuario')->insert([
                'id_usuario' => $id, 'cod_sis' => 'SIS'.$id,
                'password' => 'x', 'nombre_usuario' => 'U'.$id, 'apellido' => (string) $id,
            ]);
            DB::table('rol_usuario')->insert(['id_usuario' => $id, 'id_rol' => $rol]);
        }

        foreach ([
            [self::CURSO_ALGORITMOS, 'Algoritmos I', self::DOCENTE],
            [self::CURSO_BASES, 'Bases de Datos', self::DOCENTE],
            [self::CURSO_DE_OTRO, 'Zoología', self::OTRO_DOCENTE],
        ] as [$id, $nombre, $docente]) {
            DB::table('curso')->insert([
                'id_curso' => $id, 'nombre_curso' => $nombre,
                'sis_doc' => $docente, 'estado' => 'EnCurso',
                'fecha_creacion' => now()->toDateString(),
            ]);
        }

        foreach ([
            [self::AUXILIAR_ACTIVO, self::CURSO_ALGORITMOS, 'Activo'],
            [self::AUXILIAR_OTRO_CURSO, self::CURSO_BASES, 'Activo'],
            [self::AUXILIAR_DE_BAJA, self::CURSO_ALGORITMOS, 'Baja'],
            [self::AUXILIAR_DE_OTRO_DOCENTE, self::CURSO_DE_OTRO, 'Activo'],
        ] as [$auxiliar, $curso, $estado]) {
            DB::table('auxiliar_curso')->insert([
                'id_auxiliar' => $auxiliar, 'id_curso' => $curso, 'estado' => $estado,
            ]);
        }
    }

    public function test_sin_sesion_responde_401(): void
    {
        $this->getJson(route('api.docentes.me.auxiliares'))->assertUnauthorized();
    }

    public function test_con_rol_distinto_de_docente_responde_403(): void
    {
        $this->actingAs(Usuario::findOrFail(self::USUARIO_AUXILIAR));

        $this->getJson(route('api.docentes.me.auxiliares'))->assertForbidden();
    }

    public function test_devuelve_solo_los_auxiliares_del_docente_autenticado_con_su_curso(): void
    {
        $this->actingAs(Usuario::findOrFail(self::DOCENTE));

        $respuesta = $this->getJson(route('api.docentes.me.auxiliares'))->assertOk();

        $filas = $respuesta->json();
        $porSis = collect($filas)->keyBy('codigoSis');

        $this->assertCount(5, $filas);
        $this->assertSame(
            ['id', 'nombre', 'apellido', 'codigoSis', 'curso', 'estado'],
            array_keys($filas[0]),
        );

        $this->assertSame('ACTIVO', $porSis->get('SIS'.self::AUXILIAR_ACTIVO)['estado']);
        $this->assertSame('Algoritmos I', $porSis->get('SIS'.self::AUXILIAR_ACTIVO)['curso']);
        $this->assertSame('ACTIVO', $porSis->get('SIS'.self::AUXILIAR_OTRO_CURSO)['estado']);
        $this->assertSame('Bases de Datos', $porSis->get('SIS'.self::AUXILIAR_OTRO_CURSO)['curso']);

        // Baja y cuentas sin asignación en los cursos de este docente: inactivas.
        foreach ([self::AUXILIAR_DE_BAJA, self::AUXILIAR_DE_OTRO_DOCENTE, self::USUARIO_AUXILIAR] as $id) {
            $this->assertSame('INACTIVO', $porSis->get('SIS'.$id)['estado']);
            $this->assertSame('', $porSis->get('SIS'.$id)['curso']);
        }

        $this->assertNotContains('BAJA', array_column($filas, 'estado'));
    }

    public function test_sin_asignaciones_responde_200_con_auxiliares_inactivos(): void
    {
        $this->actingAs(Usuario::findOrFail(self::DOCENTE_VACIO));

        $respuesta = $this->getJson(route('api.docentes.me.auxiliares'))->assertOk();

        $filas = $respuesta->json();

        $this->assertCount(5, $filas);
        $this->assertSame(array_fill(0, 5, 'INACTIVO'), array_column($filas, 'estado'));
        $this->assertSame(array_fill(0, 5, ''), array_column($filas, 'curso'));
    }

    public function test_no_admite_escritura(): void
    {
        $this->actingAs(Usuario::findOrFail(self::DOCENTE));

        $this->postJson(route('api.docentes.me.auxiliares'))->assertStatus(405);
    }
}

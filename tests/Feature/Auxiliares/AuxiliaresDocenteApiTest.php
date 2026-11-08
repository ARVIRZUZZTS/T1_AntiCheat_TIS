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
 * Pruebas del endpoint GET /api/docentes/me/auxiliares: lista de los
 * auxiliares del docente autenticado con los criterios del ticket (200 con
 * lista vacía, 401 sin sesión, 403 con otro rol, aislamiento por docente y
 * solo lectura).
 *
 * @changelog
 * - 2026-11-08  [OchoaCesar]  test: cobertura del endpoint de auxiliares.
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

        $this->assertCount(3, $filas);
        $this->assertSame(
            ['id', 'nombre', 'apellido', 'codigoSis', 'curso', 'estado'],
            array_keys($filas[0]),
        );

        // Orden por curso: Algoritmos I (dos auxiliares) y luego Bases de Datos.
        $this->assertSame(
            ['Algoritmos I', 'Algoritmos I', 'Bases de Datos'],
            array_column($filas, 'curso'),
        );
        $this->assertSame(
            ['SIS'.self::AUXILIAR_ACTIVO, 'SIS'.self::AUXILIAR_DE_BAJA, 'SIS'.self::AUXILIAR_OTRO_CURSO],
            array_column($filas, 'codigoSis'),
        );
        $this->assertSame(['ACTIVO', 'BAJA', 'ACTIVO'], array_column($filas, 'estado'));

        // La baja sigue en la lista y el auxiliar de otro docente no aparece.
        $this->assertNotContains(self::AUXILIAR_DE_OTRO_DOCENTE, array_column($filas, 'id'));
    }

    public function test_sin_auxiliares_responde_200_con_lista_vacia(): void
    {
        $this->actingAs(Usuario::findOrFail(self::DOCENTE_VACIO));

        $respuesta = $this->getJson(route('api.docentes.me.auxiliares'))->assertOk();

        $this->assertSame([], $respuesta->json());
    }

    public function test_no_admite_escritura(): void
    {
        $this->actingAs(Usuario::findOrFail(self::DOCENTE));

        $this->postJson(route('api.docentes.me.auxiliares'))->assertStatus(405);
    }
}

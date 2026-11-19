<?php

/**
 * @file    DetalleExamenTest.php
 *
 * @author  Valery D. Ortuno P <valerydariana98@gmail.com>
 *
 * @created 2026-11-19
 *
 * @description
 * Pruebas del "Ver detalle" de Ver examenes: el endpoint devuelve los datos
 * del examen, sus cursos y ambientes, y las reglas y materiales permitidos
 * (catalogo y escritos a mano) de la configuracion del docente que consulta,
 * o los del examen si aun no tiene una. Solo lo ve quien lo creo, el docente
 * de uno de sus cursos o el invitado que acepto.
 *
 * Usan SQLite en memoria con un esquema minimo, asi que no leen ni escriben en
 * la base de desarrollo.
 *
 * @changelog
 * - 2026-11-19  [Valery D. Ortuno P]  test: creacion inicial.
 *
 * @see App\Http\Controllers\Api\ExamenDetalleController
 * @see App\Services\Examen\ObtenerDetalleExamenService
 * @see App\Policies\ExamenPolicy::ver()
 */

namespace Tests\Feature\Examenes;

use App\Models\Usuario;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DetalleExamenTest extends TestCase
{
    private const ROBERTO = 1;

    private const PATRICIA = 2;

    private const FERNANDO = 3;

    private const AUXILIAR = 4;

    private const EXAMEN = 50;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.connections.detalle_pruebas' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => false,
        ]]);
        DB::setDefaultConnection('detalle_pruebas');
        $this->crearEsquema();
        $this->crearDatos();
    }

    protected function tearDown(): void
    {
        DB::purge('detalle_pruebas');
        parent::tearDown();
    }

    public function test_el_creador_ve_los_datos_cursos_ambientes_y_su_configuracion(): void
    {
        $this->actingAs(Usuario::findOrFail(self::ROBERTO))
            ->getJson(route('api.examenes.detalle', ['examen' => self::EXAMEN]))
            ->assertOk()
            ->assertExactJson(['datos' => [
                'id' => self::EXAMEN,
                'tipo' => 'examen parcial',
                'fecha' => '02/12/2026',
                'hora_inicio' => '08:15',
                'hora_fin' => '09:45',
                'duracion' => 90,
                'creador' => 'Roberto Silva',
                'cursos' => [
                    ['nombre' => 'Calculo I - Grupo 1', 'docente' => 'Roberto Silva'],
                    ['nombre' => 'Calculo I - Grupo 2', 'docente' => 'Patricia Rojas'],
                ],
                'ambientes' => ['Aula 623', 'Aula 691B'],
                // Catalogo primero y despues lo escrito a mano.
                'normas' => ['Prohibido hablar', 'Celular apagado', 'Sin salir del aula'],
                'materiales' => ['Calculadora', 'Hoja de formulas'],
            ]]);
    }

    public function test_cada_docente_ve_las_reglas_de_su_propia_configuracion(): void
    {
        $this->actingAs(Usuario::findOrFail(self::PATRICIA))
            ->getJson(route('api.examenes.detalle', ['examen' => self::EXAMEN]))
            ->assertOk()
            ->assertJsonPath('datos.normas', ['Prohibido hablar'])
            ->assertJsonPath('datos.materiales', ['Calculadora', 'Regla']);
    }

    public function test_sin_configuracion_propia_usa_las_reglas_del_examen(): void
    {
        DB::table('configuracion_examen_docente')->where('id_docente', self::ROBERTO)->delete();

        $this->actingAs(Usuario::findOrFail(self::ROBERTO))
            ->getJson(route('api.examenes.detalle', ['examen' => self::EXAMEN]))
            ->assertOk()
            ->assertJsonPath('datos.normas', ['Prohibido hablar', 'Traer carnet'])
            ->assertJsonPath('datos.materiales', ['Calculadora', 'Lapiz']);
    }

    public function test_el_invitado_que_acepto_puede_ver_el_detalle(): void
    {
        DB::table('invitacion_examen_compartido')->insert([
            'id_invitacion' => 1, 'id_docente_invitador' => self::ROBERTO,
            'id_docente_invitado' => self::FERNANDO, 'id_examen' => self::EXAMEN, 'estado' => 'aceptado',
        ]);

        $this->actingAs(Usuario::findOrFail(self::FERNANDO))
            ->getJson(route('api.examenes.detalle', ['examen' => self::EXAMEN]))
            ->assertOk()
            ->assertJsonPath('datos.id', self::EXAMEN);
    }

    public function test_un_docente_ajeno_al_examen_no_lo_ve(): void
    {
        DB::table('invitacion_examen_compartido')->insert([
            'id_invitacion' => 1, 'id_docente_invitador' => self::ROBERTO,
            'id_docente_invitado' => self::FERNANDO, 'id_examen' => self::EXAMEN, 'estado' => 'pendiente',
        ]);

        $this->actingAs(Usuario::findOrFail(self::FERNANDO))
            ->getJson(route('api.examenes.detalle', ['examen' => self::EXAMEN]))
            ->assertForbidden();
    }

    public function test_un_auxiliar_no_ve_el_detalle(): void
    {
        $this->actingAs(Usuario::findOrFail(self::AUXILIAR))
            ->getJson(route('api.examenes.detalle', ['examen' => self::EXAMEN]))
            ->assertForbidden();
    }

    public function test_sin_sesion_no_hay_detalle(): void
    {
        $this->getJson(route('api.examenes.detalle', ['examen' => self::EXAMEN]))
            ->assertUnauthorized();
    }

    public function test_un_examen_sin_cursos_ni_reglas_devuelve_listas_vacias(): void
    {
        DB::table('examen')->insert([
            'id_examen' => 51, 'fecha' => null, 'hora_inicio' => null, 'duracion' => null,
            'creador' => self::ROBERTO, 'tipo_examen' => null,
        ]);

        $this->actingAs(Usuario::findOrFail(self::ROBERTO))
            ->getJson(route('api.examenes.detalle', ['examen' => 51]))
            ->assertOk()
            ->assertJsonPath('datos.tipo', null)
            ->assertJsonPath('datos.fecha', null)
            ->assertJsonPath('datos.hora_fin', null)
            ->assertJsonPath('datos.cursos', [])
            ->assertJsonPath('datos.normas', [])
            ->assertJsonPath('datos.materiales', []);
    }

    public function test_la_lista_de_ver_examenes_tiene_ver_detalle_y_no_eliminar(): void
    {
        $parcial = file_get_contents(resource_path('views/partials/examenes-lista.blade.php'));

        $this->assertStringContainsString('Ver detalle', $parcial);
        $this->assertStringContainsString("route('api.examenes.detalle'", $parcial);
        $this->assertStringNotContainsString('abrirModalEliminar', $parcial);
        $this->assertStringNotContainsString('<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6"', $parcial);
    }

    private function crearEsquema(): void
    {
        Schema::create('usuario', function (Blueprint $tabla): void {
            $tabla->integer('id_usuario')->primary();
            $tabla->string('cod_sis');
            $tabla->string('password');
            $tabla->string('nombre_usuario');
            $tabla->string('apellido');
            $tabla->string('nombre')->nullable();
            $tabla->string('correo')->nullable();
            $tabla->string('estado_cuenta')->default('habilitada');
        });
        Schema::create('rol', function (Blueprint $tabla): void {
            $tabla->integer('id_rol')->primary();
            $tabla->string('nombre_rol');
        });
        Schema::create('rol_usuario', function (Blueprint $tabla): void {
            $tabla->integer('id_usuario');
            $tabla->integer('id_rol');
        });
        Schema::create('tipo_examen', function (Blueprint $tabla): void {
            $tabla->integer('id_tipo_examen')->primary();
            $tabla->string('nombre_tipo_examen');
        });
        Schema::create('examen', function (Blueprint $tabla): void {
            $tabla->integer('id_examen')->primary();
            $tabla->string('nombre_examen')->nullable();
            $tabla->date('fecha')->nullable();
            $tabla->string('hora_inicio')->nullable();
            $tabla->integer('duracion')->nullable();
            $tabla->integer('creador');
            $tabla->integer('tipo_examen')->nullable();
        });
        Schema::create('curso', function (Blueprint $tabla): void {
            $tabla->integer('id_curso')->primary();
            $tabla->string('nombre_curso');
            $tabla->integer('sis_doc');
            $tabla->string('estado')->default('EnCurso');
        });
        Schema::create('examen_curso', function (Blueprint $tabla): void {
            $tabla->integer('id_examen');
            $tabla->integer('id_curso');
        });
        Schema::create('ambiente', function (Blueprint $tabla): void {
            $tabla->integer('id_ambiente')->primary();
            $tabla->string('nombre_ambiente');
        });
        Schema::create('examen_ambiente', function (Blueprint $tabla): void {
            $tabla->integer('id_examen');
            $tabla->integer('id_ambiente');
        });
        Schema::create('norma', function (Blueprint $tabla): void {
            $tabla->integer('id_norma')->primary();
            $tabla->string('detalle_norma');
        });
        Schema::create('examen_norma', function (Blueprint $tabla): void {
            $tabla->integer('id_examen');
            $tabla->integer('id_norma');
        });
        Schema::create('material', function (Blueprint $tabla): void {
            $tabla->integer('id_material')->primary();
            $tabla->string('descripcion');
        });
        Schema::create('examen_material_permitido', function (Blueprint $tabla): void {
            $tabla->integer('id_examen');
            $tabla->integer('id_material_permitido');
        });
        Schema::create('norma_personalizada', function (Blueprint $tabla): void {
            $tabla->integer('id_examen');
            $tabla->integer('numero_norma');
            $tabla->string('descripcion_norma');
        });
        Schema::create('material_personalizado', function (Blueprint $tabla): void {
            $tabla->integer('id_examen');
            $tabla->integer('numero_material');
            $tabla->string('descripcion_material');
        });
        Schema::create('configuracion_examen_docente', function (Blueprint $tabla): void {
            $tabla->integer('id_configuracion_examen')->primary();
            $tabla->integer('id_examen');
            $tabla->integer('id_docente');
            $tabla->timestamps();
        });
        Schema::create('configuracion_examen_norma', function (Blueprint $tabla): void {
            $tabla->integer('id_configuracion_examen');
            $tabla->integer('id_norma');
        });
        Schema::create('configuracion_examen_material', function (Blueprint $tabla): void {
            $tabla->integer('id_configuracion_examen');
            $tabla->integer('id_material');
        });
        Schema::create('configuracion_norma_personalizada', function (Blueprint $tabla): void {
            $tabla->increments('id_configuracion_norma');
            $tabla->integer('id_configuracion_examen');
            $tabla->integer('numero_norma');
            $tabla->string('descripcion_norma');
        });
        Schema::create('configuracion_material_personalizado', function (Blueprint $tabla): void {
            $tabla->increments('id_configuracion_material');
            $tabla->integer('id_configuracion_examen');
            $tabla->integer('numero_material');
            $tabla->string('descripcion_material');
        });
        Schema::create('invitacion_examen_compartido', function (Blueprint $tabla): void {
            $tabla->integer('id_invitacion')->primary();
            $tabla->integer('id_docente_invitador');
            $tabla->integer('id_docente_invitado');
            $tabla->integer('id_examen');
            $tabla->string('estado');
        });
    }

    private function crearDatos(): void
    {
        DB::table('rol')->insert([
            ['id_rol' => 1, 'nombre_rol' => 'docente'],
            ['id_rol' => 2, 'nombre_rol' => 'auxiliar'],
        ]);

        $usuario = fn (int $id, string $usuario, string $nombre, string $apellido): array => [
            'id_usuario' => $id, 'cod_sis' => 'SIS'.$id, 'password' => 'x',
            'nombre_usuario' => $usuario, 'nombre' => $nombre, 'apellido' => $apellido,
        ];
        DB::table('usuario')->insert([
            $usuario(self::ROBERTO, 'Roberto', 'Roberto', 'Silva'),
            $usuario(self::PATRICIA, 'Patricia', 'Patricia', 'Rojas'),
            $usuario(self::FERNANDO, 'Fernando', 'Fernando', 'Vargas'),
            $usuario(self::AUXILIAR, 'Diego', 'Diego', 'Mamani'),
        ]);
        DB::table('rol_usuario')->insert([
            ['id_usuario' => self::ROBERTO, 'id_rol' => 1],
            ['id_usuario' => self::PATRICIA, 'id_rol' => 1],
            ['id_usuario' => self::FERNANDO, 'id_rol' => 1],
            ['id_usuario' => self::AUXILIAR, 'id_rol' => 2],
        ]);

        DB::table('tipo_examen')->insert(['id_tipo_examen' => 1, 'nombre_tipo_examen' => 'examen parcial']);
        DB::table('examen')->insert([
            'id_examen' => self::EXAMEN, 'fecha' => '2026-12-02', 'hora_inicio' => '08:15:00',
            'duracion' => 90, 'creador' => self::ROBERTO, 'tipo_examen' => 1,
        ]);

        // Patricia gestiona el segundo curso del examen compartido.
        DB::table('curso')->insert([
            ['id_curso' => 10, 'nombre_curso' => 'Calculo I - Grupo 2', 'sis_doc' => self::PATRICIA],
            ['id_curso' => 11, 'nombre_curso' => 'Calculo I - Grupo 1', 'sis_doc' => self::ROBERTO],
        ]);
        DB::table('examen_curso')->insert([
            ['id_examen' => self::EXAMEN, 'id_curso' => 10],
            ['id_examen' => self::EXAMEN, 'id_curso' => 11],
        ]);

        DB::table('ambiente')->insert([
            ['id_ambiente' => 1, 'nombre_ambiente' => 'Aula 691B'],
            ['id_ambiente' => 2, 'nombre_ambiente' => 'Aula 623'],
        ]);
        DB::table('examen_ambiente')->insert([
            ['id_examen' => self::EXAMEN, 'id_ambiente' => 1],
            ['id_examen' => self::EXAMEN, 'id_ambiente' => 2],
        ]);

        DB::table('norma')->insert([
            ['id_norma' => 1, 'detalle_norma' => 'Prohibido hablar'],
            ['id_norma' => 2, 'detalle_norma' => 'Celular apagado'],
        ]);
        DB::table('material')->insert([
            ['id_material' => 1, 'descripcion' => 'Calculadora'],
            ['id_material' => 2, 'descripcion' => 'Regla'],
        ]);

        // Configuracion del examen (la que se usa si el docente no tiene una).
        DB::table('examen_norma')->insert(['id_examen' => self::EXAMEN, 'id_norma' => 1]);
        DB::table('norma_personalizada')->insert(['id_examen' => self::EXAMEN, 'numero_norma' => 1, 'descripcion_norma' => 'Traer carnet']);
        DB::table('examen_material_permitido')->insert(['id_examen' => self::EXAMEN, 'id_material_permitido' => 1]);
        DB::table('material_personalizado')->insert(['id_examen' => self::EXAMEN, 'numero_material' => 1, 'descripcion_material' => 'Lapiz']);

        // Configuracion propia de Roberto y de Patricia.
        DB::table('configuracion_examen_docente')->insert([
            ['id_configuracion_examen' => 100, 'id_examen' => self::EXAMEN, 'id_docente' => self::ROBERTO],
            ['id_configuracion_examen' => 101, 'id_examen' => self::EXAMEN, 'id_docente' => self::PATRICIA],
        ]);
        DB::table('configuracion_examen_norma')->insert([
            ['id_configuracion_examen' => 100, 'id_norma' => 2],
            ['id_configuracion_examen' => 100, 'id_norma' => 1],
            ['id_configuracion_examen' => 101, 'id_norma' => 1],
        ]);
        DB::table('configuracion_norma_personalizada')->insert([
            'id_configuracion_examen' => 100, 'numero_norma' => 1, 'descripcion_norma' => 'Sin salir del aula',
        ]);
        DB::table('configuracion_examen_material')->insert([
            ['id_configuracion_examen' => 100, 'id_material' => 1],
            ['id_configuracion_examen' => 101, 'id_material' => 1],
            ['id_configuracion_examen' => 101, 'id_material' => 2],
        ]);
        DB::table('configuracion_material_personalizado')->insert([
            'id_configuracion_examen' => 100, 'numero_material' => 1, 'descripcion_material' => 'Hoja de formulas',
        ]);
    }
}

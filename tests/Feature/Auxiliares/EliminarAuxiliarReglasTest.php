<?php

/**
 * @file    EliminarAuxiliarReglasTest.php
 *
 * @author  Valery D. Ortuno P <valerydariana98@gmail.com>
 *
 * @created 2026-11-28
 *
 * @description
 * Reglas de "Eliminar auxiliar" con el formato de Eliminar examen: solo se
 * elimina al auxiliar que no tiene nada asociado. Con cursos activos, o si
 * estuvo asignado a algun curso o examen, al presionar "Eliminar" el aviso
 * sale en su fila, no se abre el modal y el servidor tambien lo rechaza.
 *
 * Usa SQLite en memoria con un esquema minimo, asi que no lee ni escribe en la
 * base de desarrollo.
 *
 * @changelog
 * - 2026-11-28  [Valery D. Ortuno P]  test: creacion inicial.
 *
 * @see App\Livewire\Auxiliares\EliminarAuxiliar
 * @see App\Services\Auxiliar\ListarAuxiliaresEliminablesService
 * @see App\Services\Auxiliar\EliminarAuxiliarService
 */

namespace Tests\Feature\Auxiliares;

use App\Livewire\Auxiliares\EliminarAuxiliar;
use App\Models\Usuario;
use App\Services\Auxiliar\EliminarAuxiliarService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class EliminarAuxiliarReglasTest extends TestCase
{
    private const DOCENTE = 1;

    private const LIBRE = 10;       // sin nada asociado

    private const ACTIVO = 11;      // con curso activo

    private const ANTERIOR = 12;    // tuvo un curso (archivado)

    private const CON_EXAMEN = 13;  // tuvo un examen asignado

    private const ACTIVO_CURSO = 100;

    private const ARCHIVADO_CURSO = 101;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.connections.eliminar_auxiliar_pruebas' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => false,
        ]]);
        DB::setDefaultConnection('eliminar_auxiliar_pruebas');
        Carbon::setTestNow('2026-11-28 10:00:00');
        $this->crearEsquema();
        $this->crearDatos();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        DB::purge('eliminar_auxiliar_pruebas');
        parent::tearDown();
    }

    public function test_la_lista_dice_que_tiene_asociado_cada_auxiliar(): void
    {
        $filas = collect(Livewire::actingAs($this->usuario(self::DOCENTE))
            ->test(EliminarAuxiliar::class)
            ->instance()->filtradas())->keyBy('id');

        $this->assertTrue($filas[self::LIBRE]['eliminable']);
        $this->assertSame(['Calculo I'], $filas[self::ACTIVO]['cursosActivos']);
        $this->assertSame(['Fisica Vieja'], $filas[self::ANTERIOR]['cursosPasados']);
        $this->assertSame(1, $filas[self::CON_EXAMEN]['examenes']);
        foreach ([self::ACTIVO, self::ANTERIOR, self::CON_EXAMEN] as $id) {
            $this->assertFalse($filas[$id]['eliminable']);
        }
    }

    public function test_con_algo_asociado_el_aviso_sale_en_su_fila_y_no_se_abre_el_modal(): void
    {
        $componente = Livewire::actingAs($this->usuario(self::DOCENTE))->test(EliminarAuxiliar::class);

        $componente->call('abrirModal', self::ACTIVO)
            ->assertSet('modalAbierto', false)
            ->assertSet('avisoId', self::ACTIVO)
            ->assertSet('avisoMensaje', EliminarAuxiliarService::MENSAJE_CURSOS_ACTIVOS)
            ->assertSee('No se puede eliminar el auxiliar');

        foreach ([self::ANTERIOR, self::CON_EXAMEN] as $id) {
            $componente->call('abrirModal', $id)
                ->assertSet('modalAbierto', false)
                ->assertSet('avisoMensaje', EliminarAuxiliarService::MENSAJE_CON_HISTORIAL);
        }
    }

    public function test_el_servidor_rechaza_eliminar_a_quien_tiene_algo_asociado(): void
    {
        foreach ([
            self::ACTIVO => EliminarAuxiliarService::MENSAJE_CURSOS_ACTIVOS,
            self::ANTERIOR => EliminarAuxiliarService::MENSAJE_CON_HISTORIAL,
            self::CON_EXAMEN => EliminarAuxiliarService::MENSAJE_CON_HISTORIAL,
        ] as $id => $mensaje) {
            try {
                app(EliminarAuxiliarService::class)->ejecutar($this->usuario(self::DOCENTE), $this->usuario($id));
                $this->fail("Se elimino al auxiliar {$id}.");
            } catch (ValidationException $error) {
                $this->assertSame([$mensaje], $error->errors()['auxiliar']);
            }
            $this->assertSame('habilitada', DB::table('usuario')->where('id_usuario', $id)->value('estado_cuenta'));
        }
    }

    public function test_el_auxiliar_sin_nada_asociado_se_elimina_con_modal_de_exito(): void
    {
        Livewire::actingAs($this->usuario(self::DOCENTE))
            ->test(EliminarAuxiliar::class)
            ->call('abrirModal', self::LIBRE)
            ->assertSet('modalAbierto', true)
            ->call('eliminar')
            ->assertSet('mostrarExito', true)
            ->assertSee('Auxiliar eliminado con exito');

        $this->assertSame('eliminada', DB::table('usuario')->where('id_usuario', self::LIBRE)->value('estado_cuenta'));
    }

    public function test_la_busqueda_incluye_los_cursos_y_avisa_sin_coincidencias(): void
    {
        $componente = Livewire::actingAs($this->usuario(self::DOCENTE))->test(EliminarAuxiliar::class);

        $this->assertSame([self::ACTIVO], $componente->set('busqueda', 'calculo')->instance()->filtradas()->pluck('id')->all());
        $componente->set('busqueda', 'zzz')->assertSee('No se encontraron coincidencias');
    }

    private function usuario(int $id): Usuario
    {
        return Usuario::with('roles')->findOrFail($id);
    }

    private function crearEsquema(): void
    {
        Schema::create('usuario', function (Blueprint $tabla): void {
            $tabla->integer('id_usuario')->primary();
            $tabla->string('cod_sis');
            $tabla->string('password');
            $tabla->string('nombre_usuario');
            $tabla->string('nombre')->nullable();
            $tabla->string('apellido');
            $tabla->string('correo')->nullable();
            $tabla->string('carnet')->nullable();
            $tabla->string('tipo_auxiliar')->nullable();
            $tabla->string('estado_cuenta')->default('habilitada');
            $tabla->integer('id_docente_responsable')->nullable();
            $tabla->integer('id_creador')->nullable();
            $tabla->boolean('puede_crear_auxiliares')->default(false);
            $tabla->timestamp('fecha_creacion')->nullable();
        });
        Schema::create('rol', function (Blueprint $tabla): void {
            $tabla->integer('id_rol')->primary();
            $tabla->string('nombre_rol');
        });
        Schema::create('rol_usuario', function (Blueprint $tabla): void {
            $tabla->integer('id_usuario');
            $tabla->integer('id_rol');
        });
        Schema::create('curso', function (Blueprint $tabla): void {
            $tabla->integer('id_curso')->primary();
            $tabla->string('nombre_curso');
            $tabla->integer('sis_doc');
            $tabla->string('estado');
        });
        Schema::create('auxiliar_curso', function (Blueprint $tabla): void {
            $tabla->integer('id_auxiliar');
            $tabla->integer('id_curso');
            $tabla->string('estado');
        });
        Schema::create('examen', function (Blueprint $tabla): void {
            $tabla->integer('id_examen')->primary();
            $tabla->date('fecha')->nullable();
            $tabla->string('hora_inicio')->nullable();
            $tabla->integer('duracion')->nullable();
        });
        Schema::create('examen_curso', function (Blueprint $tabla): void {
            $tabla->integer('id_examen');
            $tabla->integer('id_curso');
        });
        Schema::create('auxiliar_examen', function (Blueprint $tabla): void {
            $tabla->integer('id_auxiliar');
            $tabla->integer('id_examen');
            $tabla->boolean('ver_ingreso')->default(true);
            $tabla->boolean('ver_monitor')->default(true);
        });
        Schema::create('sessions', function (Blueprint $tabla): void {
            $tabla->string('id')->primary();
            $tabla->integer('user_id')->nullable();
            $tabla->text('payload')->nullable();
            $tabla->integer('last_activity')->nullable();
        });
    }

    private function crearDatos(): void
    {
        DB::table('rol')->insert([['id_rol' => 1, 'nombre_rol' => 'docente'], ['id_rol' => 2, 'nombre_rol' => 'auxiliar']]);

        $auxiliar = fn (int $id, string $nombre): array => [
            'id_usuario' => $id, 'cod_sis' => (string) (202500000 + $id), 'password' => 'x', 'nombre_usuario' => $nombre,
            'nombre' => $nombre, 'apellido' => 'Prueba', 'correo' => strtolower($nombre).'@ej.com',
            'tipo_auxiliar' => 'UMSS', 'id_docente_responsable' => self::DOCENTE, 'id_creador' => self::DOCENTE,
        ];
        DB::table('usuario')->insert(['id_usuario' => self::DOCENTE, 'cod_sis' => 'DOC1', 'password' => 'x', 'nombre_usuario' => 'Roberto', 'apellido' => 'Silva']);
        DB::table('usuario')->insert([
            $auxiliar(self::LIBRE, 'Libre'),
            $auxiliar(self::ACTIVO, 'Activo'),
            $auxiliar(self::ANTERIOR, 'Anterior'),
            $auxiliar(self::CON_EXAMEN, 'Examinador'),
        ]);
        DB::table('rol_usuario')->insert([
            ['id_usuario' => self::DOCENTE, 'id_rol' => 1],
            ...array_map(fn (int $id): array => ['id_usuario' => $id, 'id_rol' => 2], [self::LIBRE, self::ACTIVO, self::ANTERIOR, self::CON_EXAMEN]),
        ]);

        DB::table('curso')->insert([
            ['id_curso' => self::ACTIVO_CURSO, 'nombre_curso' => 'Calculo I', 'sis_doc' => self::DOCENTE, 'estado' => 'EnCurso'],
            ['id_curso' => self::ARCHIVADO_CURSO, 'nombre_curso' => 'Fisica Vieja', 'sis_doc' => self::DOCENTE, 'estado' => 'Archivado'],
        ]);
        DB::table('auxiliar_curso')->insert([
            ['id_auxiliar' => self::ACTIVO, 'id_curso' => self::ACTIVO_CURSO, 'estado' => 'Activo'],
            ['id_auxiliar' => self::ANTERIOR, 'id_curso' => self::ARCHIVADO_CURSO, 'estado' => 'Activo'],
        ]);
        DB::table('examen')->insert(['id_examen' => 500, 'fecha' => '2026-11-01', 'hora_inicio' => '08:00:00', 'duracion' => 90]);
        DB::table('auxiliar_examen')->insert(['id_auxiliar' => self::CON_EXAMEN, 'id_examen' => 500]);
    }
}

<?php

/**
 * @file    GestionarAmbientesTest.php
 *
 * @author  Valery D. Ortuno P <valerydariana98@gmail.com>
 *
 * @created 2026-11-19
 *
 * @description
 * Pruebas del alta de ambientes desde el selector de Crear / Editar examen:
 * el nombre se arma con el tipo (aula, laboratorio o auditorio), no se repite
 * y entra al catalogo del formulario al instante. Solo los docentes pueden
 * agregar, y no hay ruta para eliminar.
 *
 * Usan SQLite en memoria con un esquema minimo, asi que no leen ni escriben en
 * la base de desarrollo.
 *
 * @changelog
 * - 2026-11-19  [Valery D. Ortuno P]  test: creacion inicial.
 * - 2026-11-19  [Valery D. Ortuno P]  test: se quitan las pruebas de eliminar
 *   (los ambientes solo se agregan).
 * - 2026-11-20  [Valery D. Ortuno P]  test: el duplicado se detecta aunque cambien
 *   espacios, guiones, acentos o ceros a la izquierda, y el aviso dice cual es.
 *
 * @see App\Services\Ambiente\GestionarAmbientesService
 * @see App\Http\Controllers\Api\AmbienteController
 */

namespace Tests\Feature\Examenes;

use App\Models\Usuario;
use App\Services\Ambiente\GestionarAmbientesService;
use App\Services\Examen\CatalogosExamenService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GestionarAmbientesTest extends TestCase
{
    private const DOCENTE = 1;

    private const AUXILIAR = 2;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.connections.ambientes_pruebas' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => false,
        ]]);
        DB::setDefaultConnection('ambientes_pruebas');
        Cache::forget(CatalogosExamenService::CACHE_PREFIJO.'ambientes');
        $this->crearEsquema();
    }

    protected function tearDown(): void
    {
        DB::purge('ambientes_pruebas');
        parent::tearDown();
    }

    public function test_agrega_un_aula_con_el_tipo_en_el_nombre(): void
    {
        $this->comoDocente()
            ->postJson(route('api.ambientes.crear'), ['tipo' => 'aula', 'nombre' => '  625  '])
            ->assertCreated()
            ->assertExactJson([
                'mensaje' => 'Ambiente agregado',
                'datos' => ['id' => 4, 'nombre' => 'Aula 625', 'termino' => 'aula 625'],
            ]);

        $this->assertDatabaseHas('ambiente', ['id_ambiente' => 4, 'nombre_ambiente' => 'Aula 625']);
    }

    public function test_agrega_laboratorios_y_auditorios(): void
    {
        $this->comoDocente()
            ->postJson(route('api.ambientes.crear'), ['tipo' => 'laboratorio', 'nombre' => '3'])
            ->assertCreated()
            ->assertJsonPath('datos.nombre', 'Laboratorio 3');

        $this->comoDocente()
            ->postJson(route('api.ambientes.crear'), ['tipo' => 'auditorio', 'nombre' => 'Central   FCyT'])
            ->assertCreated()
            ->assertJsonPath('datos.nombre', 'Auditorio Central FCyT');
    }

    public function test_el_ambiente_nuevo_entra_al_catalogo_del_formulario(): void
    {
        // El catalogo queda cacheado antes del alta, como en la pagina.
        $antes = app(CatalogosExamenService::class)->ids('ambientes');
        $this->assertNotContains(4, $antes);

        $this->comoDocente()->postJson(route('api.ambientes.crear'), ['tipo' => 'aula', 'nombre' => '625'])->assertCreated();

        $this->assertContains(4, app(CatalogosExamenService::class)->ids('ambientes'));
    }

    public function test_no_repite_un_ambiente_que_ya_existe(): void
    {
        $this->comoDocente()
            ->postJson(route('api.ambientes.crear'), ['tipo' => 'aula', 'nombre' => '623'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.nombre.0', GestionarAmbientesService::MENSAJE_DUPLICADO.': Aula 623.');

        $this->assertSame(3, DB::table('ambiente')->count());
    }

    public function test_no_repite_un_ambiente_escrito_distinto(): void
    {
        // Ya existen Aula 623 y Aula 690B.
        foreach (['0623', '690 b', '690-B', '690b'] as $variante) {
            $this->comoDocente()
                ->postJson(route('api.ambientes.crear'), ['tipo' => 'aula', 'nombre' => $variante])
                ->assertUnprocessable()
                ->assertJsonPath('errors.nombre.0', fn (string $mensaje): bool => str_starts_with($mensaje, GestionarAmbientesService::MENSAJE_DUPLICADO));
        }

        $this->assertSame(3, DB::table('ambiente')->count());
    }

    public function test_numeros_distintos_si_son_ambientes_distintos(): void
    {
        // Laboratorio 10 no es Laboratorio 1.
        $this->comoDocente()
            ->postJson(route('api.ambientes.crear'), ['tipo' => 'laboratorio', 'nombre' => '10'])
            ->assertCreated()
            ->assertJsonPath('datos.nombre', 'Laboratorio 10');
    }

    public function test_la_clave_ignora_mayusculas_espacios_guiones_acentos_y_ceros(): void
    {
        $this->assertSame('aula692a', GestionarAmbientesService::clave('AULA 0692 - a'));
        $this->assertSame('auditoriomusica', GestionarAmbientesService::clave('Auditorio Música'));
        $this->assertNotSame(GestionarAmbientesService::clave('Laboratorio 1'), GestionarAmbientesService::clave('Laboratorio 10'));
    }

    public function test_valida_el_tipo_y_el_identificador(): void
    {
        $this->comoDocente()
            ->postJson(route('api.ambientes.crear'), ['tipo' => 'cancha', 'nombre' => '1'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.tipo.0', GestionarAmbientesService::MENSAJE_TIPO);

        $this->comoDocente()
            ->postJson(route('api.ambientes.crear'), ['tipo' => 'aula', 'nombre' => '   '])
            ->assertUnprocessable()
            ->assertJsonPath('errors.nombre.0', GestionarAmbientesService::MENSAJE_IDENTIFICADOR);

        $this->comoDocente()
            ->postJson(route('api.ambientes.crear'), ['tipo' => 'aula', 'nombre' => '62<5>'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.nombre.0', GestionarAmbientesService::MENSAJE_CARACTERES);

        $this->comoDocente()
            ->postJson(route('api.ambientes.crear'), ['tipo' => 'aula', 'nombre' => str_repeat('a', 46)])
            ->assertUnprocessable()
            ->assertJsonPath('errors.nombre.0', GestionarAmbientesService::MENSAJE_LARGO);
    }

    public function test_no_hay_ruta_para_eliminar_ambientes(): void
    {
        $this->comoDocente()
            ->deleteJson('/api/ambientes/3')
            ->assertNotFound();

        $this->assertSame(3, DB::table('ambiente')->count());
    }

    public function test_solo_un_docente_agrega_ambientes(): void
    {
        $this->actingAs(Usuario::findOrFail(self::AUXILIAR))
            ->postJson(route('api.ambientes.crear'), ['tipo' => 'aula', 'nombre' => '625'])
            ->assertForbidden();

        $this->assertSame(3, DB::table('ambiente')->count());
    }

    private function comoDocente(): static
    {
        return $this->actingAs(Usuario::findOrFail(self::DOCENTE));
    }

    private function crearEsquema(): void
    {
        Schema::create('usuario', function (Blueprint $tabla): void {
            $tabla->integer('id_usuario')->primary();
            $tabla->string('cod_sis');
            $tabla->string('password');
            $tabla->string('nombre_usuario');
            $tabla->string('apellido');
        });
        Schema::create('rol', function (Blueprint $tabla): void {
            $tabla->integer('id_rol')->primary();
            $tabla->string('nombre_rol');
        });
        Schema::create('rol_usuario', function (Blueprint $tabla): void {
            $tabla->integer('id_usuario');
            $tabla->integer('id_rol');
        });
        Schema::create('ambiente', function (Blueprint $tabla): void {
            $tabla->integer('id_ambiente')->primary();
            $tabla->string('nombre_ambiente', 50);
        });

        DB::table('rol')->insert([['id_rol' => 1, 'nombre_rol' => 'docente'], ['id_rol' => 2, 'nombre_rol' => 'auxiliar']]);
        DB::table('usuario')->insert([
            ['id_usuario' => self::DOCENTE, 'cod_sis' => 'DOC001', 'password' => 'x', 'nombre_usuario' => 'Roberto', 'apellido' => 'Silva'],
            ['id_usuario' => self::AUXILIAR, 'cod_sis' => 'AUX001', 'password' => 'x', 'nombre_usuario' => 'Diego', 'apellido' => 'Mamani'],
        ]);
        DB::table('rol_usuario')->insert([
            ['id_usuario' => self::DOCENTE, 'id_rol' => 1],
            ['id_usuario' => self::AUXILIAR, 'id_rol' => 2],
        ]);
        DB::table('ambiente')->insert([
            ['id_ambiente' => 1, 'nombre_ambiente' => 'Aula 623'],
            ['id_ambiente' => 2, 'nombre_ambiente' => 'Laboratorio 1'],
            ['id_ambiente' => 3, 'nombre_ambiente' => 'Aula 690B'],
        ]);
    }
}

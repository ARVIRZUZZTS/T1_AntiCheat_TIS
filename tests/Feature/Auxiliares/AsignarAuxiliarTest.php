<?php

/**
 * @file    AsignarAuxiliarTest.php
 *
 * @author  Valery D. Ortuno P <valerydariana98@gmail.com>
 *
 * @created 2026-11-22
 *
 * @description
 * Pruebas de la HU 24 (asignar auxiliar a un curso o examen): la pagina
 * "Asignar auxiliar" lista a todos los auxiliares con sus asignaciones en los
 * cursos del docente; asignar a un curso (Ingreso y Monitor en todos sus
 * examenes) o a un examen futuro eligiendo Ingreso, Monitor o las dos; quitar
 * asignaciones; y lo que despues ve el auxiliar en Monitor en vivo (Ingreso,
 * Monitor, sidebar y permiso del monitor) y en su seccion "Ver examenes".
 *
 * Usan SQLite en memoria con un esquema minimo y corren la migracion de la
 * HU, asi que no leen ni escriben en la base de desarrollo.
 *
 * @changelog
 * - 2026-11-22  [Valery D. Ortuno P]  test: creacion inicial (HU 24).
 * - 2026-11-22  [Valery D. Ortuno P]  test: "Ver examenes" del auxiliar.
 * - 2026-11-22  [Valery D. Ortuno P]  test: no se asignan examenes de cursos
 *   archivados.
 * - 2026-11-24  [Valery D. Ortuno P]  test: no se muestran cursos archivados ni
 *   examenes pasados, y el orden por el examen mas proximo.
 *
 * @see App\Livewire\Auxiliares\AsignarAuxiliar
 * @see App\Services\Auxiliar\AsignarAuxiliarService
 * @see App\Services\Auxiliar\AccesoAuxiliarService
 */

namespace Tests\Feature\Auxiliares;

use App\Livewire\Auxiliares\AsignarAuxiliar;
use App\Models\Examen;
use App\Models\Usuario;
use App\Services\Auxiliar\AccesoAuxiliarService;
use App\Services\Auxiliar\AsignarAuxiliarService;
use App\Services\Auxiliar\ListarExamenesAuxiliarService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class AsignarAuxiliarTest extends TestCase
{
    private const ROBERTO = 1;

    private const PATRICIA = 2;

    private const LUIS = 10;

    private const ANA = 11;

    private const CALCULO = 100;

    private const ALGEBRA = 101;

    private const ARCHIVADO = 102;

    private const AJENO = 103;

    private const PARCIAL_CALCULO = 500;

    private const FINAL_CALCULO = 501;

    private const PASADO_CALCULO = 502;

    private const PARCIAL_ALGEBRA = 503;

    private const EXAMEN_AJENO = 504;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.connections.auxiliares_pruebas' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => false,
        ]]);
        DB::setDefaultConnection('auxiliares_pruebas');
        Carbon::setTestNow('2026-11-22 10:00:00');
        $this->crearEsquema();
        $this->crearDatos();
        (require database_path('migrations/2026_11_22_000001_crear_auxiliar_examen.php'))->up();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        DB::purge('auxiliares_pruebas');
        parent::tearDown();
    }

    public function test_la_pagina_lista_a_todos_los_auxiliares_con_sus_asignaciones_en_mis_cursos(): void
    {
        DB::table('auxiliar_examen')->insert([
            'id_auxiliar' => self::ANA, 'id_examen' => self::PARCIAL_ALGEBRA,
            'ver_ingreso' => true, 'ver_monitor' => false, 'asignado_por' => self::ROBERTO,
        ]);

        $this->actingAs($this->usuario(self::ROBERTO))
            ->get(route('auxiliares.asignar'))
            ->assertOk()
            ->assertSee('Ana Rojas')
            ->assertSee('Luis Perez')
            ->assertSee('Curso: Calculo I - Grupo 1')
            ->assertSee('Examen: Examen primer parcial - Algebra I - Grupo 1 · 05/12/2026 08:15')
            ->assertSee('Solo Ingreso')
            // El curso de Patricia no es asunto de Roberto.
            ->assertDontSee('Fisica - Grupo 3');
    }

    public function test_las_opciones_son_mis_cursos_en_curso_y_mis_examenes_futuros(): void
    {
        $datos = Livewire::actingAs($this->usuario(self::ROBERTO))->test(AsignarAuxiliar::class)->instance()->datos;

        $this->assertSame([self::ALGEBRA, self::CALCULO], array_column($datos['cursos'], 'id'));
        $this->assertSame([self::PARCIAL_CALCULO, self::PARCIAL_ALGEBRA, self::FINAL_CALCULO], array_column($datos['examenes'], 'id'));
    }

    public function test_no_se_muestran_cursos_archivados_ni_examenes_pasados(): void
    {
        // Siguen en la base (quien controlo), pero no se muestran.
        DB::table('auxiliar_curso')->insert(['id_auxiliar' => self::ANA, 'id_curso' => self::ARCHIVADO, 'estado' => 'Activo']);
        DB::table('auxiliar_examen')->insert([
            'id_auxiliar' => self::ANA, 'id_examen' => self::PASADO_CALCULO,
            'ver_ingreso' => true, 'ver_monitor' => true, 'asignado_por' => self::ROBERTO,
        ]);

        $datos = Livewire::actingAs($this->usuario(self::ROBERTO))->test(AsignarAuxiliar::class)->instance()->datos;
        $ana = collect($datos['auxiliares'])->firstWhere('id', self::ANA);

        $this->assertSame([], $ana['cursos']);
        $this->assertSame([], $ana['examenes']);
        $this->assertSame(1, DB::table('auxiliar_curso')->where('id_auxiliar', self::ANA)->count());
        $this->assertSame(1, DB::table('auxiliar_examen')->where('id_auxiliar', self::ANA)->count());
    }

    public function test_ordena_por_el_examen_mas_proximo_y_sin_asignaciones_al_final(): void
    {
        // Luis ve Calculo I (examen hoy 14:00); Ana un examen del 05/12.
        DB::table('auxiliar_examen')->insert([
            'id_auxiliar' => self::ANA, 'id_examen' => self::PARCIAL_ALGEBRA,
            'ver_ingreso' => true, 'ver_monitor' => false, 'asignado_por' => self::ROBERTO,
        ]);
        DB::table('usuario')->insert(['id_usuario' => 12, 'cod_sis' => 'SIS12', 'password' => 'x', 'nombre_usuario' => 'Abel', 'apellido' => 'Soto']);
        DB::table('rol_usuario')->insert(['id_usuario' => 12, 'id_rol' => 2]);

        $datos = Livewire::actingAs($this->usuario(self::ROBERTO))->test(AsignarAuxiliar::class)->instance()->datos;

        $this->assertSame([self::LUIS, self::ANA, 12], array_column($datos['auxiliares'], 'id'));
        $this->assertSame(['2026-11-22 14:00:00', '2026-12-05 08:15:00', null], array_column($datos['auxiliares'], 'proximo'));
    }

    public function test_asigna_un_auxiliar_a_un_curso(): void
    {
        Livewire::actingAs($this->usuario(self::ROBERTO))
            ->test(AsignarAuxiliar::class)
            ->call('asignarCurso', self::ANA, self::ALGEBRA)
            ->assertReturned('')
            ->assertSet('mensaje', 'Se asigno a Ana Rojas al curso Algebra I - Grupo 1. Vera Ingreso y Monitor en todos sus examenes.');

        $this->assertDatabaseHas('auxiliar_curso', ['id_auxiliar' => self::ANA, 'id_curso' => self::ALGEBRA, 'estado' => 'Activo']);
    }

    public function test_reasignar_un_curso_dado_de_baja_lo_reactiva(): void
    {
        DB::table('auxiliar_curso')->insert(['id_auxiliar' => self::ANA, 'id_curso' => self::ALGEBRA, 'estado' => 'Baja']);

        Livewire::actingAs($this->usuario(self::ROBERTO))
            ->test(AsignarAuxiliar::class)
            ->call('asignarCurso', self::ANA, self::ALGEBRA)
            ->assertReturned('');

        $this->assertSame(1, DB::table('auxiliar_curso')->where('id_auxiliar', self::ANA)->count());
        $this->assertSame('Activo', DB::table('auxiliar_curso')->where('id_auxiliar', self::ANA)->value('estado'));
    }

    public function test_no_asigna_un_curso_repetido_ajeno_archivado_ni_a_quien_no_es_auxiliar(): void
    {
        $componente = Livewire::actingAs($this->usuario(self::ROBERTO))->test(AsignarAuxiliar::class);

        $componente->call('asignarCurso', self::LUIS, self::CALCULO)->assertReturned(AsignarAuxiliarService::MENSAJE_YA_EN_CURSO);
        $componente->call('asignarCurso', self::ANA, self::AJENO)->assertReturned(AsignarAuxiliarService::MENSAJE_CURSO_INVALIDO);
        $componente->call('asignarCurso', self::ANA, self::ARCHIVADO)->assertReturned(AsignarAuxiliarService::MENSAJE_CURSO_INVALIDO);
        $componente->call('asignarCurso', self::PATRICIA, self::ALGEBRA)->assertReturned(AsignarAuxiliarService::MENSAJE_NO_AUXILIAR);

        $this->assertSame(1, DB::table('auxiliar_curso')->count());
    }

    public function test_no_asigna_examenes_futuros_de_un_curso_archivado(): void
    {
        DB::table('examen')->insert([
            'id_examen' => 505, 'fecha' => '2026-12-15', 'hora_inicio' => '08:00:00',
            'duracion' => 90, 'creador' => self::ROBERTO, 'tipo_examen' => 1,
        ]);
        DB::table('examen_curso')->insert(['id_examen' => 505, 'id_curso' => self::ARCHIVADO]);

        $componente = Livewire::actingAs($this->usuario(self::ROBERTO))->test(AsignarAuxiliar::class);

        $this->assertNotContains(505, array_column($componente->instance()->datos['examenes'], 'id'));
        $componente->call('asignarExamen', self::ANA, 505, true, true)
            ->assertReturned(AsignarAuxiliarService::MENSAJE_EXAMEN_INVALIDO);
        $this->assertSame(0, DB::table('auxiliar_examen')->count());
    }

    public function test_asigna_un_auxiliar_a_un_examen_con_las_paginas_elegidas(): void
    {
        Livewire::actingAs($this->usuario(self::ROBERTO))
            ->test(AsignarAuxiliar::class)
            ->call('asignarExamen', self::ANA, self::PARCIAL_ALGEBRA, true, false)
            ->assertReturned('')
            ->assertSet('mensaje', 'Se asigno a Ana Rojas al examen. Vera solo Ingreso.');

        $this->assertDatabaseHas('auxiliar_examen', [
            'id_auxiliar' => self::ANA, 'id_examen' => self::PARCIAL_ALGEBRA,
            'ver_ingreso' => 1, 'ver_monitor' => 0, 'asignado_por' => self::ROBERTO,
        ]);
    }

    public function test_reasignar_el_mismo_examen_actualiza_las_paginas(): void
    {
        $componente = Livewire::actingAs($this->usuario(self::ROBERTO))->test(AsignarAuxiliar::class);
        $componente->call('asignarExamen', self::ANA, self::PARCIAL_ALGEBRA, true, false);

        $componente->call('asignarExamen', self::ANA, self::PARCIAL_ALGEBRA, true, true)
            ->assertReturned('')
            ->assertSet('mensaje', 'Se actualizo la asignacion de Ana Rojas al examen. Vera Ingreso y Monitor.');

        $this->assertSame(1, DB::table('auxiliar_examen')->count());
        $this->assertSame(1, (int) DB::table('auxiliar_examen')->value('ver_monitor'));
    }

    public function test_no_asigna_examenes_pasados_ajenos_sin_paginas_o_que_ya_ve_por_el_curso(): void
    {
        $componente = Livewire::actingAs($this->usuario(self::ROBERTO))->test(AsignarAuxiliar::class);

        $componente->call('asignarExamen', self::ANA, self::PASADO_CALCULO, true, true)
            ->assertReturned(AsignarAuxiliarService::MENSAJE_EXAMEN_INVALIDO);
        $componente->call('asignarExamen', self::ANA, self::EXAMEN_AJENO, true, true)
            ->assertReturned(AsignarAuxiliarService::MENSAJE_EXAMEN_INVALIDO);
        $componente->call('asignarExamen', self::ANA, self::PARCIAL_ALGEBRA, false, false)
            ->assertReturned(AsignarAuxiliarService::MENSAJE_SIN_PAGINA);
        // Luis ya esta en Calculo I: ve todos sus examenes.
        $componente->call('asignarExamen', self::LUIS, self::PARCIAL_CALCULO, true, false)
            ->assertReturned(sprintf(AsignarAuxiliarService::MENSAJE_YA_POR_CURSO, 'Calculo I - Grupo 1'));

        $this->assertSame(0, DB::table('auxiliar_examen')->count());
    }

    public function test_quitar_un_curso_lo_da_de_baja_y_quitar_un_examen_lo_borra(): void
    {
        DB::table('auxiliar_examen')->insert([
            'id_auxiliar' => self::ANA, 'id_examen' => self::PARCIAL_ALGEBRA,
            'ver_ingreso' => true, 'ver_monitor' => true, 'asignado_por' => self::ROBERTO,
        ]);
        $componente = Livewire::actingAs($this->usuario(self::ROBERTO))->test(AsignarAuxiliar::class);

        $componente->call('quitarCurso', self::LUIS, self::CALCULO)->assertReturned('');
        $componente->call('quitarExamen', self::ANA, self::PARCIAL_ALGEBRA)->assertReturned('');

        $this->assertSame('Baja', DB::table('auxiliar_curso')->where('id_auxiliar', self::LUIS)->value('estado'));
        $this->assertSame(0, DB::table('auxiliar_examen')->count());

        // Ya no existen.
        $componente->call('quitarCurso', self::LUIS, self::CALCULO)->assertReturned(AsignarAuxiliarService::MENSAJE_NO_EXISTE);
        $componente->call('quitarExamen', self::ANA, self::PARCIAL_ALGEBRA)->assertReturned(AsignarAuxiliarService::MENSAJE_NO_EXISTE);
    }

    public function test_un_docente_no_quita_asignaciones_de_cursos_ajenos(): void
    {
        Livewire::actingAs($this->usuario(self::PATRICIA))
            ->test(AsignarAuxiliar::class)
            ->call('quitarCurso', self::LUIS, self::CALCULO)
            ->assertReturned(AsignarAuxiliarService::MENSAJE_NO_EXISTE);

        $this->assertSame('Activo', DB::table('auxiliar_curso')->where('id_auxiliar', self::LUIS)->value('estado'));
    }

    public function test_un_auxiliar_no_entra_a_asignar(): void
    {
        $this->actingAs($this->usuario(self::LUIS))
            ->get(route('auxiliares.asignar'))
            ->assertForbidden();
    }

    public function test_el_curso_da_acceso_a_ingreso_y_monitor_en_todos_sus_examenes(): void
    {
        $acceso = app(AccesoAuxiliarService::class);

        foreach ([AccesoAuxiliarService::INGRESO, AccesoAuxiliarService::MONITOR] as $pagina) {
            $ids = $acceso->idsExamenes(self::LUIS, $pagina)->pluck('id_examen')->map(fn ($id) => (int) $id)->sort()->values()->all();
            $this->assertSame([self::PARCIAL_CALCULO, self::FINAL_CALCULO, self::PASADO_CALCULO], $ids);
        }
        $this->assertSame(['ingreso' => true, 'monitor' => true], $acceso->paginas(self::LUIS));
    }

    public function test_el_examen_suelto_da_acceso_solo_a_las_paginas_marcadas(): void
    {
        DB::table('auxiliar_examen')->insert([
            'id_auxiliar' => self::ANA, 'id_examen' => self::PARCIAL_ALGEBRA,
            'ver_ingreso' => true, 'ver_monitor' => false, 'asignado_por' => self::ROBERTO,
        ]);
        $acceso = app(AccesoAuxiliarService::class);

        $this->assertTrue($acceso->puede(self::ANA, self::PARCIAL_ALGEBRA, AccesoAuxiliarService::INGRESO));
        $this->assertFalse($acceso->puede(self::ANA, self::PARCIAL_ALGEBRA, AccesoAuxiliarService::MONITOR));
        $this->assertFalse($acceso->puede(self::ANA, self::PARCIAL_CALCULO, AccesoAuxiliarService::INGRESO));
        $this->assertSame(['ingreso' => true, 'monitor' => false], $acceso->paginas(self::ANA));
        // Sigue la configuracion de quien lo asigno.
        $this->assertSame(self::ROBERTO, $acceso->docenteConfiguracion(self::ANA, self::PARCIAL_ALGEBRA));
    }

    public function test_un_curso_de_baja_no_da_acceso(): void
    {
        DB::table('auxiliar_curso')->where('id_auxiliar', self::LUIS)->update(['estado' => 'Baja']);

        $this->assertSame(['ingreso' => false, 'monitor' => false], app(AccesoAuxiliarService::class)->paginas(self::LUIS));
        $this->assertSame([], app(AccesoAuxiliarService::class)->idsExamenes(self::LUIS, AccesoAuxiliarService::MONITOR)->pluck('id_examen')->all());
    }

    public function test_el_permiso_del_monitor_respeta_la_asignacion(): void
    {
        DB::table('auxiliar_examen')->insert([
            ['id_auxiliar' => self::ANA, 'id_examen' => self::PARCIAL_ALGEBRA, 'ver_ingreso' => true, 'ver_monitor' => false, 'asignado_por' => self::ROBERTO],
            ['id_auxiliar' => self::ANA, 'id_examen' => self::FINAL_CALCULO, 'ver_ingreso' => false, 'ver_monitor' => true, 'asignado_por' => self::ROBERTO],
        ]);
        $ana = $this->usuario(self::ANA);

        $this->assertFalse(Gate::forUser($ana)->allows('monitorear', Examen::findOrFail(self::PARCIAL_ALGEBRA)));
        $this->assertTrue(Gate::forUser($ana)->allows('monitorear', Examen::findOrFail(self::FINAL_CALCULO)));
        $this->assertTrue(Gate::forUser($this->usuario(self::LUIS))->allows('monitorear', Examen::findOrFail(self::PARCIAL_CALCULO)));
    }

    public function test_el_sidebar_del_auxiliar_muestra_solo_sus_paginas(): void
    {
        DB::table('auxiliar_examen')->insert([
            'id_auxiliar' => self::ANA, 'id_examen' => self::PARCIAL_ALGEBRA,
            'ver_ingreso' => true, 'ver_monitor' => false, 'asignado_por' => self::ROBERTO,
        ]);

        $html = $this->actingAs($this->usuario(self::ANA))->get(route('auxiliar.cursos'))->assertOk()->getContent();

        $this->assertStringContainsString('href="'.route('monitoreo.proximos').'"', $html);
        $this->assertStringContainsString('href="'.route('monitoreo.ingreso').'"', $html);
        $this->assertStringNotContainsString('href="'.route('monitoreo').'"', $html);
    }

    public function test_la_migracion_se_revierte_y_se_vuelve_a_correr(): void
    {
        $migracion = require database_path('migrations/2026_11_22_000001_crear_auxiliar_examen.php');

        $migracion->down();
        $this->assertFalse(Schema::hasTable('auxiliar_examen'));

        $migracion->up();
        $migracion->up();
        $this->assertTrue(Schema::hasTable('auxiliar_examen'));
    }

    public function test_ver_examenes_muestra_todos_los_examenes_de_su_curso(): void
    {
        $examenes = app(ListarExamenesAuxiliarService::class)->ejecutar(self::LUIS);

        // Proximos primero (del mas cercano) y el finalizado al final.
        $this->assertSame([self::PARCIAL_CALCULO, self::FINAL_CALCULO, self::PASADO_CALCULO], array_column($examenes, 'id'));
        $this->assertSame([
            'id' => self::PARCIAL_CALCULO,
            'tipo' => 'Examen primer parcial',
            'cursos' => 'Calculo I - Grupo 1',
            'fecha' => '22/11/2026',
            'horario' => '14:00 - 15:30',
            'ambientes' => 'Aula 623, Aula 691B',
            'estado' => ListarExamenesAuxiliarService::PROGRAMADO,
            'acceso' => 'Ingreso y Monitor',
            'origen' => 'Por el curso Calculo I - Grupo 1',
        ], $examenes[0]);
        $this->assertSame(ListarExamenesAuxiliarService::FINALIZADO, $examenes[2]['estado']);
    }

    public function test_ver_examenes_muestra_solo_el_examen_suelto_con_sus_paginas(): void
    {
        DB::table('auxiliar_examen')->insert([
            'id_auxiliar' => self::ANA, 'id_examen' => self::PARCIAL_ALGEBRA,
            'ver_ingreso' => false, 'ver_monitor' => true, 'asignado_por' => self::ROBERTO,
        ]);

        $examenes = app(ListarExamenesAuxiliarService::class)->ejecutar(self::ANA);

        $this->assertSame([self::PARCIAL_ALGEBRA], array_column($examenes, 'id'));
        $this->assertSame('Solo Monitor', $examenes[0]['acceso']);
        $this->assertSame('Asignado a este examen', $examenes[0]['origen']);
        $this->assertSame('—', $examenes[0]['ambientes']);
    }

    public function test_la_pagina_ver_examenes_del_auxiliar(): void
    {
        $this->actingAs($this->usuario(self::LUIS))
            ->get(route('auxiliar.examenes'))
            ->assertOk()
            ->assertSee('Examen primer parcial - Calculo I - Grupo 1')
            ->assertSee('Por el curso Calculo I - Grupo 1')
            ->assertSee('Proximos 2')
            ->assertSee('Finalizados 1')
            ->assertSee('href="'.route('auxiliar.examenes').'"', false);

        // Sin asignaciones (curso de baja): mensaje vacio.
        DB::table('auxiliar_curso')->update(['estado' => 'Baja']);
        $this->actingAs($this->usuario(self::LUIS))
            ->get(route('auxiliar.examenes'))
            ->assertOk()
            ->assertSee('Todavia no tienes examenes asignados.');
    }

    public function test_un_docente_no_entra_a_ver_examenes_del_auxiliar(): void
    {
        $this->actingAs($this->usuario(self::ROBERTO))
            ->get(route('auxiliar.examenes'))
            ->assertForbidden();
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
        Schema::create('curso', function (Blueprint $tabla): void {
            $tabla->integer('id_curso')->primary();
            $tabla->string('nombre_curso');
            $tabla->integer('sis_doc');
            $tabla->string('estado');
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
        Schema::create('examen_curso', function (Blueprint $tabla): void {
            $tabla->integer('id_examen');
            $tabla->integer('id_curso');
        });
        Schema::create('auxiliar_curso', function (Blueprint $tabla): void {
            $tabla->integer('id_auxiliar');
            $tabla->integer('id_curso');
            $tabla->string('estado');
            $tabla->primary(['id_auxiliar', 'id_curso']);
        });
        Schema::create('ambiente', function (Blueprint $tabla): void {
            $tabla->integer('id_ambiente')->primary();
            $tabla->string('nombre_ambiente');
        });
        Schema::create('examen_ambiente', function (Blueprint $tabla): void {
            $tabla->integer('id_examen');
            $tabla->integer('id_ambiente');
        });
        // El sidebar del docente cuenta sus invitaciones pendientes.
        Schema::create('invitacion_examen_compartido', function (Blueprint $tabla): void {
            $tabla->integer('id_invitacion')->primary();
            $tabla->integer('id_docente_invitado');
            $tabla->string('estado');
        });
    }

    private function crearDatos(): void
    {
        DB::table('rol')->insert([['id_rol' => 1, 'nombre_rol' => 'docente'], ['id_rol' => 2, 'nombre_rol' => 'auxiliar']]);

        $usuario = fn (int $id, string $usuario, string $apellido): array => [
            'id_usuario' => $id, 'cod_sis' => 'SIS'.$id, 'password' => 'x', 'nombre_usuario' => $usuario, 'apellido' => $apellido,
        ];
        DB::table('usuario')->insert([
            $usuario(self::ROBERTO, 'Roberto', 'Silva'),
            $usuario(self::PATRICIA, 'Patricia', 'Rojas'),
            $usuario(self::LUIS, 'Luis', 'Perez'),
            $usuario(self::ANA, 'Ana', 'Rojas'),
        ]);
        DB::table('rol_usuario')->insert([
            ['id_usuario' => self::ROBERTO, 'id_rol' => 1],
            ['id_usuario' => self::PATRICIA, 'id_rol' => 1],
            ['id_usuario' => self::LUIS, 'id_rol' => 2],
            ['id_usuario' => self::ANA, 'id_rol' => 2],
        ]);

        DB::table('curso')->insert([
            ['id_curso' => self::CALCULO, 'nombre_curso' => 'Calculo I - Grupo 1', 'sis_doc' => self::ROBERTO, 'estado' => 'EnCurso'],
            ['id_curso' => self::ALGEBRA, 'nombre_curso' => 'Algebra I - Grupo 1', 'sis_doc' => self::ROBERTO, 'estado' => 'EnCurso'],
            ['id_curso' => self::ARCHIVADO, 'nombre_curso' => 'Fisica Vieja', 'sis_doc' => self::ROBERTO, 'estado' => 'Archivado'],
            ['id_curso' => self::AJENO, 'nombre_curso' => 'Fisica - Grupo 3', 'sis_doc' => self::PATRICIA, 'estado' => 'EnCurso'],
        ]);
        DB::table('tipo_examen')->insert(['id_tipo_examen' => 1, 'nombre_tipo_examen' => 'examen primer parcial']);

        $examen = fn (int $id, string $fecha, string $hora): array => [
            'id_examen' => $id, 'fecha' => $fecha, 'hora_inicio' => $hora, 'duracion' => 90, 'creador' => self::ROBERTO, 'tipo_examen' => 1,
        ];
        DB::table('examen')->insert([
            $examen(self::PARCIAL_CALCULO, '2026-11-22', '14:00:00'),
            $examen(self::FINAL_CALCULO, '2026-12-10', '08:15:00'),
            $examen(self::PASADO_CALCULO, '2026-11-22', '08:00:00'),
            $examen(self::PARCIAL_ALGEBRA, '2026-12-05', '08:15:00'),
            ['creador' => self::PATRICIA] + $examen(self::EXAMEN_AJENO, '2026-12-01', '09:00:00'),
        ]);
        DB::table('examen_curso')->insert([
            ['id_examen' => self::PARCIAL_CALCULO, 'id_curso' => self::CALCULO],
            ['id_examen' => self::FINAL_CALCULO, 'id_curso' => self::CALCULO],
            ['id_examen' => self::PASADO_CALCULO, 'id_curso' => self::CALCULO],
            ['id_examen' => self::PARCIAL_ALGEBRA, 'id_curso' => self::ALGEBRA],
            ['id_examen' => self::EXAMEN_AJENO, 'id_curso' => self::AJENO],
        ]);

        DB::table('ambiente')->insert([
            ['id_ambiente' => 1, 'nombre_ambiente' => 'Aula 691B'],
            ['id_ambiente' => 2, 'nombre_ambiente' => 'Aula 623'],
        ]);
        DB::table('examen_ambiente')->insert([
            ['id_examen' => self::PARCIAL_CALCULO, 'id_ambiente' => 1],
            ['id_examen' => self::PARCIAL_CALCULO, 'id_ambiente' => 2],
        ]);

        // Luis ya esta asignado a Calculo I.
        DB::table('auxiliar_curso')->insert(['id_auxiliar' => self::LUIS, 'id_curso' => self::CALCULO, 'estado' => 'Activo']);
    }
}

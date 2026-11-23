<?php

/**
 * @file    ExamenCompartidoCursosTest.php
 *
 * @author  Valery D. Ortuno P <valerydariana98@gmail.com>
 *
 * @created 2026-11-22
 *
 * @description
 * Pruebas de las HU 131 y 132 sobre la pantalla de invitaciones:
 *
 *  - Enviar: solo cursos activos propios, solo examenes futuros con fecha y
 *    hora (incluido uno recien creado), busqueda en vivo, resumen del envio,
 *    duplicado y cancelar una pendiente.
 *  - Aceptar en dos pasos: primero se acepta y despues se eligen los cursos
 *    del invitado; el examen queda en esos cursos (`examen_curso`) con sus
 *    estudiantes inscritos bajo su configuracion. Sin cursos o con cursos
 *    ajenos no se asocia. Si elige "Mas tarde", queda por asociar.
 *  - El detalle del examen muestra el curso de cada docente.
 *
 * Usan SQLite en memoria con un esquema minimo, asi que no leen ni escriben en
 * la base de desarrollo.
 *
 * @changelog
 * - 2026-11-22  [Valery D. Ortuno P]  test: creacion inicial (HU 131 y 132).
 * - 2026-11-22  [Valery D. Ortuno P]  test: aceptar y asociar en dos pasos.
 *
 * @see App\Livewire\Invitaciones\Index
 * @see App\Services\Invitacion\AsociarCursosInvitacionService
 */

namespace Tests\Feature\Invitaciones;

use App\Livewire\Invitaciones\Index;
use App\Models\Usuario;
use App\Services\Examen\ObtenerDetalleExamenService;
use App\Services\Invitacion\AsociarCursosInvitacionService;
use App\Services\Invitacion\EnviarInvitacionExamenService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class ExamenCompartidoCursosTest extends TestCase
{
    private const ROBERTO = 1;

    private const PATRICIA = 2;

    private const CALCULO_ROBERTO = 10;

    private const ARCHIVADO_ROBERTO = 11;

    private const FISICA_PATRICIA = 20;

    private const QUIMICA_PATRICIA = 21;

    private const NUEVO = 100;

    private const PASADO = 101;

    private const INVITACION = 500;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.connections.compartidos_pruebas' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => false,
        ]]);
        DB::setDefaultConnection('compartidos_pruebas');
        Carbon::setTestNow('2026-11-22 10:00:00');
        $this->crearEsquema();
        $this->crearDatos();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        DB::purge('compartidos_pruebas');
        parent::tearDown();
    }

    public function test_enviar_lista_solo_mis_cursos_activos(): void
    {
        Livewire::actingAs($this->usuario(self::ROBERTO))
            ->test(Index::class, ['tab' => 'enviar'])
            ->assertSet('cursos', [['id' => self::CALCULO_ROBERTO, 'nombre' => 'Calculo I - Grupo 1']]);
    }

    public function test_enviar_lista_solo_examenes_futuros_con_fecha_y_hora(): void
    {
        Livewire::actingAs($this->usuario(self::ROBERTO))
            ->test(Index::class, ['tab' => 'enviar'])
            ->set('idCurso', self::CALCULO_ROBERTO)
            ->assertSet('examenes', [['id' => self::NUEVO, 'nombre' => 'Examen parcial · 05/12/2026 08:15']]);
    }

    public function test_busca_en_vivo_envia_y_muestra_el_resumen(): void
    {
        $componente = Livewire::actingAs($this->usuario(self::ROBERTO))
            ->test(Index::class, ['tab' => 'enviar'])
            ->set('idCurso', self::CALCULO_ROBERTO)
            ->set('idExamen', self::NUEVO)
            ->set('busquedaDocente', 'pat');

        $this->assertSame([self::PATRICIA], array_column($componente->get('docentes'), 'id'));

        $componente->call('seleccionarDocente', self::PATRICIA)
            ->call('enviar')
            ->assertSet('mensajeExito', 'Invitacion enviada con exito')
            ->assertSet('resumenEnvio', [
                'examen' => 'Examen parcial · 05/12/2026 08:15',
                'curso' => 'Calculo I - Grupo 1',
                'docente' => 'Patricia Rojas',
            ])
            ->assertSet('docenteSeleccionado', null);

        $this->assertSame(['pendiente'], array_column($componente->get('enviadas'), 'estado'));
        $this->assertDatabaseHas('invitacion_examen_compartido', [
            'id_examen' => self::NUEVO, 'id_docente_invitado' => self::PATRICIA, 'estado' => 'pendiente',
        ]);
        $this->assertDatabaseHas('notificacion_docente', ['id_docente_destinatario' => self::PATRICIA]);
    }

    public function test_no_invita_dos_veces_al_mismo_docente(): void
    {
        $this->invitar();

        Livewire::actingAs($this->usuario(self::ROBERTO))
            ->test(Index::class, ['tab' => 'enviar'])
            ->set('idCurso', self::CALCULO_ROBERTO)
            ->set('idExamen', self::NUEVO)
            ->set('docenteSeleccionado', self::PATRICIA)
            ->call('enviar')
            ->assertSet('mensajeError', EnviarInvitacionExamenService::MENSAJE_DUPLICADA);
    }

    public function test_el_que_invita_cancela_una_pendiente(): void
    {
        $this->invitar();

        $componente = Livewire::actingAs($this->usuario(self::ROBERTO))
            ->test(Index::class, ['tab' => 'enviar'])
            ->set('idCurso', self::CALCULO_ROBERTO)
            ->set('idExamen', self::NUEVO)
            ->call('cancelar', self::INVITACION)
            ->assertSet('mensajeExito', 'Invitacion cancelada.');

        $this->assertSame(['cancelado'], array_column($componente->get('enviadas'), 'estado'));
    }

    public function test_aceptar_asocia_el_examen_a_los_cursos_elegidos_con_sus_estudiantes(): void
    {
        $this->invitar();

        $componente = Livewire::actingAs($this->usuario(self::PATRICIA))->test(Index::class, ['tab' => 'recibir']);

        // Paso 1: acepta. Queda por asociar a un curso.
        $componente->call('aceptar', self::INVITACION)
            ->assertReturned('')
            ->assertSet('invitacionesRecibidas', []);
        $this->assertSame([self::INVITACION], array_column($componente->get('aceptadasSinCurso'), 'id'));

        // Paso 2: elige sus cursos.
        $componente->call('asociarCursos', self::INVITACION, [self::FISICA_PATRICIA, self::QUIMICA_PATRICIA])
            ->assertReturned(['error' => '', 'cursos' => 'Fisica I, Quimica General'])
            ->assertSet('aceptadasSinCurso', []);

        $this->assertDatabaseHas('invitacion_examen_compartido', ['id_invitacion' => self::INVITACION, 'estado' => 'aceptado']);
        $this->assertEqualsCanonicalizing(
            [self::CALCULO_ROBERTO, self::FISICA_PATRICIA, self::QUIMICA_PATRICIA],
            DB::table('examen_curso')->where('id_examen', self::NUEVO)->pluck('id_curso')->map(fn ($id) => (int) $id)->all(),
        );

        // Sus estudiantes quedan inscritos con la configuracion de Patricia.
        $configPatricia = DB::table('configuracion_examen_docente')
            ->where('id_examen', self::NUEVO)->where('id_docente', self::PATRICIA)->value('id_configuracion_examen');
        $this->assertNotNull($configPatricia);
        $this->assertEqualsCanonicalizing(
            ['300000002', '300000003'],
            DB::table('estudiante_examen')->where('id_configuracion_examen', $configPatricia)->pluck('sis_estudiante')->all(),
        );
        $this->assertDatabaseHas('notificacion_docente', ['id_docente_destinatario' => self::ROBERTO]);
    }

    public function test_sin_cursos_o_con_cursos_ajenos_no_asocia(): void
    {
        $this->invitar();
        $componente = Livewire::actingAs($this->usuario(self::PATRICIA))->test(Index::class, ['tab' => 'recibir']);
        $componente->call('aceptar', self::INVITACION)->assertReturned('');

        $componente->call('asociarCursos', self::INVITACION, [])
            ->assertReturned(['error' => AsociarCursosInvitacionService::MENSAJE_SIN_CURSOS, 'cursos' => '']);
        $componente->call('asociarCursos', self::INVITACION, [self::CALCULO_ROBERTO])
            ->assertReturned(['error' => AsociarCursosInvitacionService::MENSAJE_CURSO_INVALIDO, 'cursos' => '']);

        // Aceptada pero sin curso: el examen sigue solo en el curso de Roberto.
        $this->assertDatabaseHas('invitacion_examen_compartido', ['id_invitacion' => self::INVITACION, 'estado' => 'aceptado']);
        $this->assertSame(1, DB::table('examen_curso')->where('id_examen', self::NUEVO)->count());
        $this->assertSame([self::INVITACION], array_column($componente->get('aceptadasSinCurso'), 'id'));
    }

    public function test_una_aceptada_sin_curso_se_asocia_despues(): void
    {
        $this->invitar('aceptado');
        DB::table('configuracion_examen_docente')->insert([
            'id_configuracion_examen' => 901, 'id_examen' => self::NUEVO, 'id_docente' => self::PATRICIA,
        ]);

        $componente = Livewire::actingAs($this->usuario(self::PATRICIA))->test(Index::class, ['tab' => 'recibir']);
        $this->assertSame([self::INVITACION], array_column($componente->get('aceptadasSinCurso'), 'id'));

        $componente->call('asociarCursos', self::INVITACION, [self::FISICA_PATRICIA])
            ->assertReturned(['error' => '', 'cursos' => 'Fisica I'])
            ->assertSet('aceptadasSinCurso', []);

        $this->assertDatabaseHas('examen_curso', ['id_examen' => self::NUEVO, 'id_curso' => self::FISICA_PATRICIA]);
    }

    public function test_el_detalle_muestra_el_curso_de_cada_docente(): void
    {
        $this->invitar();
        Livewire::actingAs($this->usuario(self::PATRICIA))
            ->test(Index::class, ['tab' => 'recibir'])
            ->call('aceptar', self::INVITACION)
            ->call('asociarCursos', self::INVITACION, [self::FISICA_PATRICIA]);

        $detalle = app(ObtenerDetalleExamenService::class)->ejecutar(self::NUEVO, self::PATRICIA);

        $this->assertSame([
            ['docente' => 'Roberto Silva', 'rol' => 'Creador', 'cursos' => ['Calculo I - Grupo 1']],
            ['docente' => 'Patricia Rojas', 'rol' => 'Invitado', 'cursos' => ['Fisica I']],
        ], $detalle['participantes']);
    }

    private function invitar(string $estado = 'pendiente'): void
    {
        DB::table('invitacion_examen_compartido')->insert([
            'id_invitacion' => self::INVITACION, 'id_docente_invitador' => self::ROBERTO,
            'id_docente_invitado' => self::PATRICIA, 'id_examen' => self::NUEVO, 'estado' => $estado,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function usuario(int $id): Usuario
    {
        return Usuario::with('roles')->findOrFail($id);
    }

    private function crearEsquema(): void
    {
        $tablas = [
            'usuario' => function (Blueprint $t): void {
                $t->integer('id_usuario')->primary();
                $t->string('cod_sis');
                $t->string('password');
                $t->string('nombre_usuario');
                $t->string('apellido');
                $t->string('nombre')->nullable();
                $t->string('correo')->nullable();
                $t->string('estado_cuenta')->default('habilitada');
            },
            'rol' => fn (Blueprint $t) => [$t->integer('id_rol')->primary(), $t->string('nombre_rol')],
            'rol_usuario' => fn (Blueprint $t) => [$t->integer('id_usuario'), $t->integer('id_rol')],
            'curso' => function (Blueprint $t): void {
                $t->integer('id_curso')->primary();
                $t->string('nombre_curso');
                $t->integer('sis_doc');
                $t->string('estado');
                $t->date('fecha_creacion')->nullable();
            },
            'tipo_examen' => fn (Blueprint $t) => [$t->integer('id_tipo_examen')->primary(), $t->string('nombre_tipo_examen')],
            'examen' => function (Blueprint $t): void {
                $t->integer('id_examen')->primary();
                $t->string('nombre_examen')->nullable();
                $t->date('fecha')->nullable();
                $t->string('hora_inicio')->nullable();
                $t->integer('duracion')->nullable();
                $t->integer('creador');
                $t->integer('tipo_examen')->nullable();
            },
            'examen_curso' => fn (Blueprint $t) => [$t->integer('id_examen'), $t->integer('id_curso')],
            'ambiente' => fn (Blueprint $t) => [$t->integer('id_ambiente')->primary(), $t->string('nombre_ambiente')],
            'examen_ambiente' => fn (Blueprint $t) => [$t->integer('id_examen'), $t->integer('id_ambiente')],
            'estudiante' => function (Blueprint $t): void {
                $t->string('sis_estudiante')->primary();
                $t->string('nombre_estudiante');
                $t->string('apellido_estudiante');
            },
            'estudiante_curso' => fn (Blueprint $t) => [$t->integer('id_curso'), $t->string('sis_estudiante')],
            'estudiante_examen' => function (Blueprint $t): void {
                $t->integer('id_estudiante_examen')->primary();
                $t->string('sis_estudiante');
                $t->integer('id_examen');
                $t->string('estado');
                $t->string('motivo')->nullable();
                $t->integer('modificado_por')->nullable();
                $t->timestamp('fecha_modificacion')->nullable();
                $t->integer('id_configuracion_examen')->nullable();
                $t->integer('id_curso')->nullable();
            },
            'registro_asistencia' => fn (Blueprint $t) => [$t->integer('id_ingreso')->primary(), $t->integer('id_examen'), $t->string('id_estudiante')->nullable()],
            'central_riesgo' => fn (Blueprint $t) => [$t->integer('id_registro')->primary(), $t->integer('id_examen')->nullable()],
            'configuracion_examen_docente' => function (Blueprint $t): void {
                $t->increments('id_configuracion_examen');
                $t->integer('id_examen');
                $t->integer('id_docente');
                $t->timestamps();
            },
            'configuracion_examen_norma' => fn (Blueprint $t) => [$t->integer('id_configuracion_examen'), $t->integer('id_norma')],
            'configuracion_examen_material' => fn (Blueprint $t) => [$t->integer('id_configuracion_examen'), $t->integer('id_material')],
            'configuracion_norma_personalizada' => fn (Blueprint $t) => [$t->increments('id_configuracion_norma'), $t->integer('id_configuracion_examen'), $t->integer('numero_norma'), $t->string('descripcion_norma')],
            'configuracion_material_personalizado' => fn (Blueprint $t) => [$t->increments('id_configuracion_material'), $t->integer('id_configuracion_examen'), $t->integer('numero_material'), $t->string('descripcion_material')],
            'norma' => fn (Blueprint $t) => [$t->integer('id_norma')->primary(), $t->string('detalle_norma')],
            'material' => fn (Blueprint $t) => [$t->integer('id_material')->primary(), $t->string('descripcion')],
            'examen_norma' => fn (Blueprint $t) => [$t->integer('id_examen'), $t->integer('id_norma')],
            'examen_material_permitido' => fn (Blueprint $t) => [$t->integer('id_examen'), $t->integer('id_material_permitido')],
            'norma_personalizada' => fn (Blueprint $t) => [$t->integer('id_examen'), $t->integer('numero_norma'), $t->string('descripcion_norma')],
            'material_personalizado' => fn (Blueprint $t) => [$t->integer('id_examen'), $t->integer('numero_material'), $t->string('descripcion_material')],
            'invitacion_examen_compartido' => function (Blueprint $t): void {
                $t->integer('id_invitacion')->primary();
                $t->integer('id_docente_invitador');
                $t->integer('id_docente_invitado');
                $t->integer('id_examen');
                $t->string('estado');
                $t->timestamps();
            },
            'notificacion_docente' => function (Blueprint $t): void {
                $t->integer('id_notificacion')->primary();
                $t->integer('id_central_riesgo')->nullable();
                $t->integer('id_curso')->nullable();
                $t->integer('id_docente_destinatario');
                $t->integer('id_invitacion')->nullable();
                $t->string('tipo')->nullable();
                $t->string('estado')->nullable();
                $t->boolean('oculta')->default(false);
                $t->timestamp('fecha')->nullable();
            },
        ];

        foreach ($tablas as $nombre => $definicion) {
            Schema::create($nombre, fn (Blueprint $tabla) => $definicion($tabla));
        }
    }

    private function crearDatos(): void
    {
        DB::table('rol')->insert([['id_rol' => 1, 'nombre_rol' => 'docente'], ['id_rol' => 2, 'nombre_rol' => 'auxiliar']]);
        DB::table('usuario')->insert([
            ['id_usuario' => self::ROBERTO, 'cod_sis' => 'DOC001', 'password' => 'x', 'nombre_usuario' => 'Roberto', 'apellido' => 'Silva'],
            ['id_usuario' => self::PATRICIA, 'cod_sis' => 'DOC002', 'password' => 'x', 'nombre_usuario' => 'Patricia', 'apellido' => 'Rojas'],
        ]);
        DB::table('rol_usuario')->insert([['id_usuario' => self::ROBERTO, 'id_rol' => 1], ['id_usuario' => self::PATRICIA, 'id_rol' => 1]]);

        DB::table('curso')->insert([
            ['id_curso' => self::CALCULO_ROBERTO, 'nombre_curso' => 'Calculo I - Grupo 1', 'sis_doc' => self::ROBERTO, 'estado' => 'EnCurso'],
            ['id_curso' => self::ARCHIVADO_ROBERTO, 'nombre_curso' => 'Fisica Vieja', 'sis_doc' => self::ROBERTO, 'estado' => 'Archivado'],
            ['id_curso' => self::FISICA_PATRICIA, 'nombre_curso' => 'Fisica I', 'sis_doc' => self::PATRICIA, 'estado' => 'EnCurso'],
            ['id_curso' => self::QUIMICA_PATRICIA, 'nombre_curso' => 'Quimica General', 'sis_doc' => self::PATRICIA, 'estado' => 'EnCurso'],
        ]);
        DB::table('tipo_examen')->insert(['id_tipo_examen' => 1, 'nombre_tipo_examen' => 'examen parcial']);
        DB::table('examen')->insert([
            ['id_examen' => self::NUEVO, 'fecha' => '2026-12-05', 'hora_inicio' => '08:15:00', 'duracion' => 90, 'creador' => self::ROBERTO, 'tipo_examen' => 1],
            ['id_examen' => self::PASADO, 'fecha' => '2026-11-01', 'hora_inicio' => '08:15:00', 'duracion' => 90, 'creador' => self::ROBERTO, 'tipo_examen' => 1],
        ]);
        DB::table('examen_curso')->insert([
            ['id_examen' => self::NUEVO, 'id_curso' => self::CALCULO_ROBERTO],
            ['id_examen' => self::PASADO, 'id_curso' => self::CALCULO_ROBERTO],
        ]);
        DB::table('configuracion_examen_docente')->insert([
            ['id_configuracion_examen' => 900, 'id_examen' => self::NUEVO, 'id_docente' => self::ROBERTO],
        ]);

        DB::table('estudiante')->insert([
            ['sis_estudiante' => '300000001', 'nombre_estudiante' => 'Ana', 'apellido_estudiante' => 'Lopez'],
            ['sis_estudiante' => '300000002', 'nombre_estudiante' => 'Beto', 'apellido_estudiante' => 'Mendez'],
            ['sis_estudiante' => '300000003', 'nombre_estudiante' => 'Carla', 'apellido_estudiante' => 'Quispe'],
        ]);
        DB::table('estudiante_curso')->insert([
            ['id_curso' => self::CALCULO_ROBERTO, 'sis_estudiante' => '300000001'],
            ['id_curso' => self::FISICA_PATRICIA, 'sis_estudiante' => '300000002'],
            ['id_curso' => self::QUIMICA_PATRICIA, 'sis_estudiante' => '300000003'],
        ]);
    }
}

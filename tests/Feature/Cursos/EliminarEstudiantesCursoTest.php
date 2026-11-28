<?php

/**
 * @file    EliminarEstudiantesCursoTest.php
 *
 * @author  Valery D. Ortuno P <valerydariana98@gmail.com>
 *
 * @created 2026-11-28
 *
 * @description
 * "Cursos > Eliminar estudiantes" (HU 34): lista los cursos activos del
 * docente con estudiantes, quita la lista de un curso (inscripciones,
 * habilitaciones con sus ambientes y estudiantes que quedan sin nada) y no lo
 * permite si el curso ya tomo un examen o tiene ingresos o incidencias.
 *
 * Usa SQLite en memoria con un esquema minimo, asi que no lee ni escribe en la
 * base de desarrollo.
 *
 * @see App\Livewire\Cursos\EliminarEstudiantes
 * @see App\Services\Curso\ListarCursosEliminarEstudiantesService
 * @see App\Services\Curso\EliminarEstudiantesCursoService
 */

namespace Tests\Feature\Cursos;

use App\Livewire\Cursos\EliminarEstudiantes;
use App\Models\Curso;
use App\Models\Usuario;
use App\Services\Curso\EliminarEstudiantesCursoService;
use App\Services\Curso\ListarCursosEliminarEstudiantesService as Listar;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class EliminarEstudiantesCursoTest extends TestCase
{
    private const DOCENTE = 1;

    private const OTRO_DOCENTE = 2;

    private const LIBRE = 100;       // examen futuro, sin ingresos

    private const TOMADO = 101;      // ya tomo un examen

    private const CON_INGRESO = 102; // examen futuro pero con un ingreso

    private const AJENO = 103;       // de otro docente

    private const SIN_LISTA = 104;   // sin estudiantes

    private const ARCHIVADO = 105;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.connections.eliminar_estudiantes_pruebas' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => false,
        ]]);
        DB::setDefaultConnection('eliminar_estudiantes_pruebas');
        Carbon::setTestNow('2026-11-28 10:00:00');
        $this->crearEsquema();
        $this->crearDatos();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        DB::purge('eliminar_estudiantes_pruebas');
        parent::tearDown();
    }

    public function test_lista_los_cursos_activos_con_estudiantes_y_si_se_pueden_eliminar(): void
    {
        $filas = Livewire::actingAs($this->docente())->test(EliminarEstudiantes::class)
            ->assertSee('Eliminar estudiantes')
            ->instance()->filtrados()->keyBy('id');

        $this->assertEqualsCanonicalizing([self::LIBRE, self::TOMADO, self::CON_INGRESO], $filas->keys()->all());
        $this->assertSame(3, $filas[self::LIBRE]['estudiantes']);
        $this->assertSame('Semestre 2 2026', $filas[self::LIBRE]['gestion']);
        $this->assertTrue($filas[self::LIBRE]['eliminable']);
        $this->assertSame(Listar::MOTIVO_EXAMEN_TOMADO, $filas[self::TOMADO]['motivo']);
        $this->assertSame(Listar::MOTIVO_CON_INGRESOS, $filas[self::CON_INGRESO]['motivo']);
    }

    public function test_si_no_se_puede_eliminar_el_aviso_sale_en_su_fila_y_no_se_abre_el_modal(): void
    {
        Livewire::actingAs($this->docente())->test(EliminarEstudiantes::class)
            ->call('abrirModal', self::TOMADO)
            ->assertSet('modalAbierto', false)
            ->assertSet('avisoId', self::TOMADO)
            ->assertSee('No se puede eliminar la lista')
            ->assertSee(Listar::MOTIVO_EXAMEN_TOMADO)
            ->call('abrirModal', self::CON_INGRESO)
            ->assertSet('modalAbierto', false)
            ->assertSet('avisoMensaje', Listar::MOTIVO_CON_INGRESOS);
    }

    public function test_elimina_la_lista_del_curso_con_modal_de_exito(): void
    {
        $componente = Livewire::actingAs($this->docente())->test(EliminarEstudiantes::class)
            ->call('abrirModal', self::LIBRE)
            ->assertSet('modalAbierto', true)
            ->assertSee('Se quitaran 3 estudiantes')
            ->call('eliminar')
            ->assertSet('modalAbierto', false)
            ->assertSet('mostrarExito', true)
            ->assertSee(EliminarEstudiantesCursoService::MENSAJE_EXITO);

        $this->assertSame(0, DB::table('estudiante_curso')->where('id_curso', self::LIBRE)->count());
        $this->assertSame(0, DB::table('estudiante_examen')->where('id_curso', self::LIBRE)->count());
        $this->assertSame(0, DB::table('estudiante_examen_ambiente')->where('id_ee', 1)->count());
        // Los que quedaron sin nada se borran; el que esta en otro curso queda.
        $this->assertEqualsCanonicalizing(['S3', 'S4', 'S5', 'S6'], DB::table('estudiante')->pluck('sis_estudiante')->all());
        $this->assertSame(1, DB::table('estudiante_curso')->where('id_curso', self::AJENO)->count());
        // El curso ya no aparece en la lista.
        $this->assertNotContains(self::LIBRE, $componente->instance()->filtrados()->pluck('id')->all());
    }

    public function test_el_servidor_rechaza_cursos_con_historial_ajenos_o_archivados(): void
    {
        $servicio = app(EliminarEstudiantesCursoService::class);

        foreach ([self::TOMADO => Listar::MOTIVO_EXAMEN_TOMADO, self::CON_INGRESO => Listar::MOTIVO_CON_INGRESOS] as $id => $mensaje) {
            try {
                $servicio->ejecutar($this->docente(), Curso::findOrFail($id));
                $this->fail("Se elimino la lista del curso {$id}.");
            } catch (ValidationException $error) {
                $this->assertSame([$mensaje], $error->errors()['curso']);
            }
        }

        foreach ([self::AJENO, self::ARCHIVADO] as $id) {
            try {
                $servicio->ejecutar($this->docente(), Curso::findOrFail($id));
                $this->fail("Se elimino la lista del curso {$id}.");
            } catch (AuthorizationException) {
                $this->assertGreaterThan(0, DB::table('estudiante_curso')->where('id_curso', $id)->count());
            }
        }

        $this->assertSame(1, DB::table('estudiante_curso')->where('id_curso', self::TOMADO)->count());
    }

    public function test_la_busqueda_filtra_por_curso_y_avisa_sin_coincidencias(): void
    {
        $componente = Livewire::actingAs($this->docente())->test(EliminarEstudiantes::class);

        $this->assertSame([self::TOMADO], $componente->set('busqueda', 'fisica')->instance()->filtrados()->pluck('id')->all());
        $componente->set('busqueda', 'zzz')->assertSee('No se encontraron coincidencias');
    }

    private function docente(): Usuario
    {
        return Usuario::with('roles')->findOrFail(self::DOCENTE);
    }

    private function crearEsquema(): void
    {
        Schema::create('usuario', function (Blueprint $tabla): void {
            $tabla->integer('id_usuario')->primary();
            $tabla->string('cod_sis');
            $tabla->string('password');
            $tabla->string('nombre_usuario');
            $tabla->string('apellido');
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
            $tabla->date('fecha_creacion')->nullable();
        });
        Schema::create('tipo_gestion', function (Blueprint $tabla): void {
            $tabla->integer('id_tipo_gestion')->primary();
            $tabla->string('nombre_tipo_gestion');
        });
        Schema::create('curso_tipo_gestion', function (Blueprint $tabla): void {
            $tabla->integer('id_curso');
            $tabla->integer('id_tg');
            $tabla->integer('anio');
        });
        Schema::create('estudiante', function (Blueprint $tabla): void {
            $tabla->string('sis_estudiante')->primary();
            $tabla->string('nombre_estudiante');
            $tabla->string('apellido_estudiante');
        });
        Schema::create('estudiante_curso', function (Blueprint $tabla): void {
            $tabla->string('sis_estudiante');
            $tabla->integer('id_curso');
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
        Schema::create('estudiante_examen', function (Blueprint $tabla): void {
            $tabla->integer('id_estudiante_examen')->primary();
            $tabla->string('sis_estudiante');
            $tabla->integer('id_examen');
            $tabla->integer('id_curso')->nullable();
            $tabla->string('estado');
        });
        Schema::create('estudiante_examen_ambiente', function (Blueprint $tabla): void {
            $tabla->integer('id_ee');
            $tabla->integer('id_ambiente');
        });
        Schema::create('registro_asistencia', function (Blueprint $tabla): void {
            $tabla->integer('id_ingreso')->primary();
            $tabla->integer('id_examen');
            $tabla->string('id_estudiante');
            $tabla->integer('id_registrador');
        });
        Schema::create('central_riesgo', function (Blueprint $tabla): void {
            $tabla->integer('id_registro')->primary();
            $tabla->integer('id_examen');
            $tabla->string('sis_estudiante');
        });
    }

    private function crearDatos(): void
    {
        DB::table('rol')->insert(['id_rol' => 1, 'nombre_rol' => 'docente']);
        DB::table('usuario')->insert([
            ['id_usuario' => self::DOCENTE, 'cod_sis' => 'DOC1', 'password' => 'x', 'nombre_usuario' => 'Roberto', 'apellido' => 'Silva'],
            ['id_usuario' => self::OTRO_DOCENTE, 'cod_sis' => 'DOC2', 'password' => 'x', 'nombre_usuario' => 'Patricia', 'apellido' => 'Vega'],
        ]);
        DB::table('rol_usuario')->insert([['id_usuario' => self::DOCENTE, 'id_rol' => 1], ['id_usuario' => self::OTRO_DOCENTE, 'id_rol' => 1]]);

        $curso = fn (int $id, string $nombre, int $docente = self::DOCENTE, string $estado = 'EnCurso'): array => [
            'id_curso' => $id, 'nombre_curso' => $nombre, 'sis_doc' => $docente, 'estado' => $estado,
        ];
        DB::table('curso')->insert([
            $curso(self::LIBRE, 'Calculo I'),
            $curso(self::TOMADO, 'Fisica I'),
            $curso(self::CON_INGRESO, 'Quimica I'),
            $curso(self::AJENO, 'Algebra I', self::OTRO_DOCENTE),
            $curso(self::SIN_LISTA, 'Sin Lista'),
            $curso(self::ARCHIVADO, 'Fisica Vieja', self::DOCENTE, 'Archivado'),
        ]);
        DB::table('tipo_gestion')->insert(['id_tipo_gestion' => 1, 'nombre_tipo_gestion' => 'semestre 2']);
        DB::table('curso_tipo_gestion')->insert(['id_curso' => self::LIBRE, 'id_tg' => 1, 'anio' => 2026]);

        DB::table('estudiante')->insert(array_map(
            fn (string $sis): array => ['sis_estudiante' => $sis, 'nombre_estudiante' => 'Est', 'apellido_estudiante' => $sis],
            ['S1', 'S2', 'S3', 'S4', 'S5', 'S6'],
        ));
        DB::table('estudiante_curso')->insert([
            ['sis_estudiante' => 'S1', 'id_curso' => self::LIBRE],
            ['sis_estudiante' => 'S2', 'id_curso' => self::LIBRE],
            ['sis_estudiante' => 'S3', 'id_curso' => self::LIBRE],
            ['sis_estudiante' => 'S3', 'id_curso' => self::AJENO],
            ['sis_estudiante' => 'S4', 'id_curso' => self::TOMADO],
            ['sis_estudiante' => 'S5', 'id_curso' => self::CON_INGRESO],
            ['sis_estudiante' => 'S6', 'id_curso' => self::ARCHIVADO],
        ]);

        DB::table('examen')->insert([
            ['id_examen' => 500, 'fecha' => '2026-12-10', 'hora_inicio' => '08:00:00', 'duracion' => 90],
            ['id_examen' => 501, 'fecha' => '2026-11-01', 'hora_inicio' => '08:00:00', 'duracion' => 90],
            ['id_examen' => 502, 'fecha' => '2026-12-15', 'hora_inicio' => '08:00:00', 'duracion' => 90],
        ]);
        DB::table('examen_curso')->insert([
            ['id_examen' => 500, 'id_curso' => self::LIBRE],
            ['id_examen' => 501, 'id_curso' => self::TOMADO],
            ['id_examen' => 502, 'id_curso' => self::CON_INGRESO],
        ]);
        DB::table('estudiante_examen')->insert([
            ['id_estudiante_examen' => 1, 'sis_estudiante' => 'S1', 'id_examen' => 500, 'id_curso' => self::LIBRE, 'estado' => 'habilitado'],
            ['id_estudiante_examen' => 2, 'sis_estudiante' => 'S2', 'id_examen' => 500, 'id_curso' => self::LIBRE, 'estado' => 'habilitado'],
            ['id_estudiante_examen' => 3, 'sis_estudiante' => 'S4', 'id_examen' => 501, 'id_curso' => self::TOMADO, 'estado' => 'habilitado'],
        ]);
        DB::table('estudiante_examen_ambiente')->insert(['id_ee' => 1, 'id_ambiente' => 1]);
        DB::table('registro_asistencia')->insert(['id_ingreso' => 1, 'id_examen' => 502, 'id_estudiante' => 'S5', 'id_registrador' => self::DOCENTE]);
    }
}

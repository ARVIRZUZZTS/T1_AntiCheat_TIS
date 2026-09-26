<?php

/**
 * @file    CambiarEstadoEstudianteTest.php
 *
 * @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
 *
 * @created 2026-09-26
 *
 * @updated 2026-09-26
 *
 * @description
 * Pruebas de integración del cambio de estado de un estudiante (issue #27),
 * contra el componente Livewire EstudiantesCurso (los modales de #25/#26
 * llaman a estos mismos métodos). El esquema no tiene migraciones Eloquent
 * (se crea vía docker/postgres/init/001_create_schema.sql), por eso se usa
 * DatabaseTransactions: cada test crea sus propios datos con IDs dedicados
 * (rango 900100+) y se revierten al terminar. Requiere el contenedor de
 * Docker levantado, con las columnas modificado_por/fecha_modificacion ya
 * agregadas a estudiante_examen (ver #27).
 *
 * @see  App\Livewire\Examenes\EstudiantesCurso
 * @see  App\Services\Examen\CambiarEstadoEstudianteService
 *
 * @changelog
 * - 2026-09-26  [T1]  test: creación inicial.
 */

namespace Tests\Feature\Examenes;

use App\Livewire\Examenes\EstudiantesCurso;
use App\Models\Curso;
use App\Models\Estudiante;
use App\Models\EstudianteExamen;
use App\Models\Examen;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class CambiarEstadoEstudianteTest extends TestCase
{
    use DatabaseTransactions;

    private const ID_CURSO = 900101;

    private const ID_EXAMEN = 900101;

    private const ID_TIPO_EXAMEN = 900101;

    private const ID_DOCENTE = 900101;

    private const SIS = '90010001';

    private const SIS_SIN_ESTADO = '90010002';

    private const ID_CURSO_SIN_EXAMEN = 900102;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('tipo_examen')->insert([
            'id_tipo_examen' => self::ID_TIPO_EXAMEN,
            'nombre_tipo_examen' => 'PP',
        ]);

        DB::table('usuario')->insert([
            'id_usuario' => self::ID_DOCENTE,
            'cod_sis' => 'TESTDOC2',
            'contraseña' => 'x',
            'nombre_usuario' => 'Docente',
            'apellido' => 'De Prueba',
        ]);

        Curso::create([
            'id_curso' => self::ID_CURSO,
            'nombre_curso' => 'Curso de Prueba 27',
            'sis_doc' => self::ID_DOCENTE,
            'fecha_creacion' => now()->toDateString(),
            'estado' => 'EnCurso',
        ]);

        Examen::create([
            'id_examen' => self::ID_EXAMEN,
            'fecha' => now()->toDateString(),
            'hora_inicio' => '08:00',
            'hora_fin' => '10:00',
            'duracion' => 120,
            'creador' => self::ID_DOCENTE,
            'tipo_examen' => self::ID_TIPO_EXAMEN,
        ]);

        DB::table('examen_curso')->insert([
            'id_examen' => self::ID_EXAMEN,
            'id_curso' => self::ID_CURSO,
        ]);

        Estudiante::create([
            'sis_estudiante' => self::SIS,
            'nombre_estudiante' => 'Test',
            'apellido_estudiante' => 'Veintisiete',
            'carrera' => 'Sistemas',
        ]);

        DB::table('estudiante_curso')->insert([
            'sis_estudiante' => self::SIS,
            'id_curso' => self::ID_CURSO,
        ]);

        EstudianteExamen::create([
            'id_estudiante_examen' => self::ID_EXAMEN,
            'sis_estudiante' => self::SIS,
            'id_examen' => self::ID_EXAMEN,
            'estado' => 'habilitado',
            'motivo' => null,
        ]);

        // Estudiante inscrito en el curso pero SIN fila en estudiante_examen
        // todavia (caso "sin estado" de la issue #29) — para probar que
        // habilitar()/inhabilitar() la crean sobre la marcha.
        Estudiante::create([
            'sis_estudiante' => self::SIS_SIN_ESTADO,
            'nombre_estudiante' => 'Test',
            'apellido_estudiante' => 'SinEstado',
            'carrera' => 'Sistemas',
        ]);

        DB::table('estudiante_curso')->insert([
            'sis_estudiante' => self::SIS_SIN_ESTADO,
            'id_curso' => self::ID_CURSO,
        ]);

        // Curso sin ningun examen vinculado, para probar el caso "sin examen actual".
        Curso::create([
            'id_curso' => self::ID_CURSO_SIN_EXAMEN,
            'nombre_curso' => 'Curso sin examen',
            'sis_doc' => self::ID_DOCENTE,
            'fecha_creacion' => now()->toDateString(),
            'estado' => 'EnCurso',
        ]);
    }

    private function curso(): Curso
    {
        return Curso::findOrFail(self::ID_CURSO);
    }

    private function cursoSinExamen(): Curso
    {
        return Curso::findOrFail(self::ID_CURSO_SIN_EXAMEN);
    }

    private function registro(): EstudianteExamen
    {
        return EstudianteExamen::where('sis_estudiante', self::SIS)
            ->where('id_examen', self::ID_EXAMEN)
            ->firstOrFail();
    }

    private function registroDe(string $sis): ?EstudianteExamen
    {
        return EstudianteExamen::where('sis_estudiante', $sis)
            ->where('id_examen', self::ID_EXAMEN)
            ->first();
    }

    /**
     * Criterio 2 de #27 (mitad inhabilitar): confirmar cambia el estado y
     * guarda el motivo.
     */
    public function test_confirmar_inhabilitar_cambia_el_estado_y_guarda_el_motivo(): void
    {
        Livewire::test(EstudiantesCurso::class, ['curso' => $this->curso()])
            ->call('abrirModalInhabilitar', self::SIS)
            ->set('motivoInhabilitacion', 'No entrego las tareas')
            ->call('confirmarInhabilitar')
            ->assertSet('sisModalAbierto', null)
            ->assertSet('mensajeErrorModal', '');

        $registro = $this->registro();
        $this->assertSame('deshabilitado', $registro->estado->value);
        $this->assertSame('No entrego las tareas', $registro->motivo);
    }

    /**
     * Criterio 2 de #27 (mitad habilitar): confirmar cambia el estado y
     * limpia el motivo anterior.
     */
    public function test_confirmar_habilitar_cambia_el_estado_y_borra_el_motivo(): void
    {
        EstudianteExamen::where('sis_estudiante', self::SIS)
            ->where('id_examen', self::ID_EXAMEN)
            ->update(['estado' => 'deshabilitado', 'motivo' => 'Motivo viejo']);

        Livewire::test(EstudiantesCurso::class, ['curso' => $this->curso()])
            ->call('abrirModalHabilitar', self::SIS)
            ->call('confirmarHabilitar')
            ->assertSet('sisModalAbierto', null);

        $registro = $this->registro();
        $this->assertSame('habilitado', $registro->estado->value);
        $this->assertNull($registro->motivo);
    }

    /**
     * Criterio 3 de #27: cerrar sin confirmar no persiste nada, aunque ya
     * se haya escrito un motivo en el campo.
     */
    public function test_cerrar_modal_sin_confirmar_no_modifica_el_estado(): void
    {
        Livewire::test(EstudiantesCurso::class, ['curso' => $this->curso()])
            ->call('abrirModalInhabilitar', self::SIS)
            ->set('motivoInhabilitacion', 'Un motivo cualquiera')
            ->call('cerrarModal')
            ->assertSet('sisModalAbierto', null);

        $registro = $this->registro();
        $this->assertSame('habilitado', $registro->estado->value);
        $this->assertNull($registro->motivo);
    }

    /**
     * Criterio 1 de #27: el flag que usa el botón de confirmar del modal de
     * #26 refleja si el motivo actual es válido.
     */
    public function test_motivo_valido_se_actualiza_en_tiempo_real(): void
    {
        Livewire::test(EstudiantesCurso::class, ['curso' => $this->curso()])
            ->call('abrirModalInhabilitar', self::SIS)
            ->assertSet('motivoValido', false)
            ->set('motivoInhabilitacion', 'Motivo valido')
            ->assertSet('motivoValido', true)
            ->set('motivoInhabilitacion', '')
            ->assertSet('motivoValido', false);
    }

    public function test_inhabilitar_sin_motivo_muestra_error_y_no_cambia_el_estado(): void
    {
        Livewire::test(EstudiantesCurso::class, ['curso' => $this->curso()])
            ->call('abrirModalInhabilitar', self::SIS)
            ->call('confirmarInhabilitar')
            ->assertSet('mensajeErrorModal', 'El motivo es obligatorio');

        $this->assertSame('habilitado', $this->registro()->estado->value);
    }

    public function test_cambiar_estado_de_estudiante_que_no_pertenece_al_curso_muestra_error(): void
    {
        Livewire::test(EstudiantesCurso::class, ['curso' => $this->curso()])
            ->call('abrirModalHabilitar', '00000000')
            ->call('confirmarHabilitar')
            ->assertSet('mensajeErrorModal', 'El estudiante no pertenece a este curso');
    }

    /**
     * habilitar() sobre alguien que ya está habilitado es no-op (decisión
     * de equipo confirmada para #27): no debe fallar ni cambiar nada.
     */
    public function test_habilitar_estudiante_ya_habilitado_no_falla(): void
    {
        Livewire::test(EstudiantesCurso::class, ['curso' => $this->curso()])
            ->call('abrirModalHabilitar', self::SIS)
            ->call('confirmarHabilitar')
            ->assertSet('mensajeErrorModal', '');

        $this->assertSame('habilitado', $this->registro()->estado->value);
    }

    /**
     * Re-inhabilitar a alguien ya deshabilitado no es un no-op: actualiza
     * el motivo (decisión de equipo confirmada para #27).
     */
    public function test_reinhabilitar_actualiza_el_motivo(): void
    {
        EstudianteExamen::where('sis_estudiante', self::SIS)
            ->where('id_examen', self::ID_EXAMEN)
            ->update(['estado' => 'deshabilitado', 'motivo' => 'Motivo original']);

        Livewire::test(EstudiantesCurso::class, ['curso' => $this->curso()])
            ->call('abrirModalInhabilitar', self::SIS)
            ->set('motivoInhabilitacion', 'Motivo corregido')
            ->call('confirmarInhabilitar')
            ->assertSet('mensajeErrorModal', '');

        $registro = $this->registro();
        $this->assertSame('deshabilitado', $registro->estado->value);
        $this->assertSame('Motivo corregido', $registro->motivo);
    }

    /**
     * Habilitar a un estudiante sin fila previa en estudiante_examen (caso
     * "sin estado" de #29) crea el registro sobre la marcha.
     */
    public function test_habilitar_estudiante_sin_fila_previa_crea_el_registro(): void
    {
        $registroAntes = $this->registroDe(self::SIS_SIN_ESTADO);
        $this->assertNull($registroAntes);

        Livewire::test(EstudiantesCurso::class, ['curso' => $this->curso()])
            ->call('abrirModalHabilitar', self::SIS_SIN_ESTADO)
            ->call('confirmarHabilitar')
            ->assertSet('mensajeErrorModal', '');

        $registroDespues = $this->registroDe(self::SIS_SIN_ESTADO);
        $this->assertNotNull($registroDespues);
        $this->assertSame('habilitado', $registroDespues->estado->value);
    }

    /**
     * Inhabilitar a un estudiante sin fila previa en estudiante_examen
     * también la crea, ya con el motivo correspondiente.
     */
    public function test_inhabilitar_estudiante_sin_fila_previa_crea_el_registro(): void
    {
        $registroAntes = $this->registroDe(self::SIS_SIN_ESTADO);
        $this->assertNull($registroAntes);

        Livewire::test(EstudiantesCurso::class, ['curso' => $this->curso()])
            ->call('abrirModalInhabilitar', self::SIS_SIN_ESTADO)
            ->set('motivoInhabilitacion', 'Primera vez que se le pone motivo')
            ->call('confirmarInhabilitar')
            ->assertSet('mensajeErrorModal', '');

        $registroDespues = $this->registroDe(self::SIS_SIN_ESTADO);
        $this->assertNotNull($registroDespues);
        $this->assertSame('deshabilitado', $registroDespues->estado->value);
        $this->assertSame('Primera vez que se le pone motivo', $registroDespues->motivo);
    }

    /**
     * Habilitar en un curso sin ningún examen vinculado no debe romper con
     * un error 500 ni persistir nada — debe mostrarse como error de modal.
     */
    public function test_habilitar_sin_examen_actual_muestra_error(): void
    {
        Livewire::test(EstudiantesCurso::class, ['curso' => $this->cursoSinExamen()])
            ->call('abrirModalHabilitar', self::SIS)
            ->call('confirmarHabilitar')
            ->assertSet('mensajeErrorModal', 'El curso no tiene un examen actual');
    }

    public function test_inhabilitar_sin_examen_actual_muestra_error(): void
    {
        Livewire::test(EstudiantesCurso::class, ['curso' => $this->cursoSinExamen()])
            ->call('abrirModalInhabilitar', self::SIS)
            ->set('motivoInhabilitacion', 'Motivo cualquiera')
            ->call('confirmarInhabilitar')
            ->assertSet('mensajeErrorModal', 'El curso no tiene un examen actual');
    }

    /**
     * El motivo con caracteres no permitidos también se rechaza de punta a
     * punta (no solo a nivel de Service unitario): el botón queda inválido
     * en tiempo real y confirmar no persiste nada.
     */
    public function test_motivo_con_caracteres_invalidos_no_es_valido_y_confirmar_no_persiste(): void
    {
        Livewire::test(EstudiantesCurso::class, ['curso' => $this->curso()])
            ->call('abrirModalInhabilitar', self::SIS)
            ->set('motivoInhabilitacion', 'Motivo con simbolo @')
            ->assertSet('motivoValido', false)
            ->call('confirmarInhabilitar')
            ->assertSet('mensajeErrorModal', 'Caracter no permitido en el motivo (A-z, 0-9, espacio)');

        $this->assertSame('habilitado', $this->registro()->estado->value);
    }

    /**
     * fecha_modificacion queda registrada al confirmar un cambio de estado.
     */
    public function test_guarda_la_fecha_de_modificacion_al_cambiar_estado(): void
    {
        Livewire::test(EstudiantesCurso::class, ['curso' => $this->curso()])
            ->call('abrirModalInhabilitar', self::SIS)
            ->set('motivoInhabilitacion', 'Motivo valido')
            ->call('confirmarInhabilitar');

        $this->assertNotNull($this->registro()->fecha_modificacion);
    }
}

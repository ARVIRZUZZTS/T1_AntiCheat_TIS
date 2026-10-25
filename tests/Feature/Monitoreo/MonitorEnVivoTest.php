<?php

/**
 * @file    MonitorEnVivoTest.php
 *
 * @author  David E. Chavez T. <virzuzz12345@gmail.com>
 *
 * @created 2026-10-10
 *
 * @updated 2026-10-10
 * @updated 2026-10-25
 *
 * @description
 * Pruebas de integración del monitor en vivo (issue de ingreso con Livewire):
 * que los eventos de los modales lleguen al componente y que cada acción
 * escriba de verdad en la base.
 *
 * El componente sigue el examen más reciente (mismo criterio que
 * `MonitorEnVivo::mount()`), así que cada test se cuelga del examen vigente con
 * un estudiante propio de SIS dedicado y `DatabaseTransactions` revierte todo al
 * terminar. No se crea un examen: el esquema de la base ya diverge de las
 * migraciones (le falta `examen.hora_fin`), y un examen nuevo fallaría por esa
 * razón ajena a este flujo.
 *
 * @see  App\Livewire\Monitoreo\MonitorEnVivo
 * @see  App\Services\Asistencia\RegistroIngresoService
 * @see  App\Services\Monitoreo\GenerarReporteAsistenciaService
 *
 * @changelog
 * - 2026-10-10  [David E. Chavez T.]  test: creación inicial. Cubre confirmar
 *   ingreso (persiste `registro_asistencia` y marca `presente`), rechazar y
 *   permitir (cambian la habilitación), registrar tramposo (crea la incidencia)
 *   y un motivo inválido (no escribe y avisa).
 * - 2026-10-25  [Valery D. Ortuno P]  test: el registro pasa por
 *   RegistrarIncidenciaService (HU 5): motivos según la vista, modal de
 *   confirmación y alta de un estudiante no verificado; sin sesión
 *   el registrador es el creador del examen.
 */

namespace Tests\Feature\Monitoreo;

use App\Enums\EstadoEstudianteExamen;
use App\Enums\Motivo;
use App\Enums\TipoInfraccion;
use App\Livewire\Monitoreo\MonitorEnVivo;
use App\Models\CentralRiesgo;
use App\Models\Estudiante;
use App\Models\EstudianteExamen;
use App\Models\Examen;
use App\Models\RegistroAsistencia;
use App\Services\Asistencia\RegistroIngresoService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

class MonitorEnVivoTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * SIS e id de inscripción dedicados para no chocar con los datos sembrados.
     */
    private const SIS = '90019999';

    private const ID_ESTUDIANTE_EXAMEN = 90019999;

    private int $idExamen;

    protected function setUp(): void
    {
        parent::setUp();

        $examen = Examen::query()
            ->orderByDesc('fecha')
            ->orderByDesc('id_examen')
            ->first();

        if ($examen === null) {
            $this->markTestSkipped('No hay ningún examen registrado para probar el monitor.');
        }

        $this->idExamen = (int) $examen->id_examen;

        Estudiante::create([
            'sis_estudiante' => self::SIS,
            'nombre_estudiante' => 'Monitor',
            'apellido_estudiante' => 'Prueba',
            'carrera' => 'Sistemas',
        ]);

        EstudianteExamen::create([
            'id_estudiante_examen' => self::ID_ESTUDIANTE_EXAMEN,
            'sis_estudiante' => self::SIS,
            'id_examen' => $this->idExamen,
            'estado' => EstadoEstudianteExamen::Habilitado->value,
            'motivo' => null,
        ]);
    }

    public function test_confirmar_ingreso_persiste_la_asistencia_y_marca_presente(): void
    {
        $componente = Livewire::test(MonitorEnVivo::class)
            ->call('confirmarIngreso', self::SIS)
            ->assertSet('mensajeError', '')
            ->assertSet('mensajeExito', 'Ingreso registrado correctamente.');

        $this->assertDatabaseHas('registro_asistencia', [
            'id_examen' => $this->idExamen,
            'id_estudiante' => self::SIS,
            // Sin sesión, el registrador es el creador del examen.
            'id_registrador' => Examen::query()->findOrFail($this->idExamen)->creador,
        ]);

        $registro = RegistroAsistencia::query()
            ->where('id_estudiante', self::SIS)
            ->where('id_examen', $this->idExamen)
            ->first();

        $this->assertNotNull($registro, 'El ingreso debió quedar persistido.');
        $this->assertNotNull($registro->hora_ingreso, 'El ingreso debe guardar la hora real.');

        $fila = collect($componente->instance()->informe()['estudiantes'])->firstWhere('sis', self::SIS);

        $this->assertSame('presente', $fila['estado_asistencia'], 'Tras ingresar, el reporte lo marca presente.');
    }

    public function test_rechazar_y_permitir_cambian_la_habilitacion(): void
    {
        Livewire::test(MonitorEnVivo::class)
            ->call('rechazarIngreso', self::SIS)
            ->assertSet('mensajeError', '');

        $this->assertDatabaseHas('estudiante_examen', [
            'sis_estudiante' => self::SIS,
            'id_examen' => $this->idExamen,
            'estado' => EstadoEstudianteExamen::Deshabilitado->value,
            'motivo' => RegistroIngresoService::MOTIVO_RECHAZO,
        ]);

        Livewire::test(MonitorEnVivo::class)
            ->call('permitirIngreso', self::SIS)
            ->assertSet('mensajeError', '');

        $this->assertDatabaseHas('estudiante_examen', [
            'sis_estudiante' => self::SIS,
            'id_examen' => $this->idExamen,
            'estado' => EstadoEstudianteExamen::Habilitado->value,
        ]);
    }

    public function test_registrar_tramposo_crea_la_incidencia_y_muestra_la_confirmacion(): void
    {
        $componente = Livewire::test(MonitorEnVivo::class)
            ->call('cambiarVista', 'monitor')
            ->call('registrarTramposo', self::SIS, Motivo::CopiaOIntercambioDeRespuestas->value, 'Visto copiando.')
            ->assertSet('mensajeError', '');

        $this->assertDatabaseHas('central_riesgo', [
            'sis_estudiante' => self::SIS,
            'id_examen' => $this->idExamen,
            'tipo_infraccion' => TipoInfraccion::Tramposo->value,
            'motivo' => Motivo::CopiaOIntercambioDeRespuestas->value,
        ]);

        $componente->assertSet('incidenciaRegistrada', true);
        $componente->assertSee('Estudiante agregado a la central de riesgos');
    }

    public function test_motivo_invalido_no_registra_incidencia(): void
    {
        $antes = CentralRiesgo::query()->where('id_examen', $this->idExamen)->count();

        Livewire::test(MonitorEnVivo::class)
            ->call('registrarTramposo', self::SIS, 'motivo-que-no-existe')
            ->assertSet('mensajeError', 'Seleccione un motivo de la lista.')
            ->assertSet('incidenciaRegistrada', false);

        $this->assertSame(
            $antes,
            CentralRiesgo::query()->where('id_examen', $this->idExamen)->count(),
            'Con un motivo inválido no se debe escribir ninguna incidencia.',
        );
    }

    /** Los motivos dependen de la vista: uno del monitor no vale en el ingreso. */
    public function test_un_motivo_del_monitor_no_vale_en_el_ingreso(): void
    {
        Livewire::test(MonitorEnVivo::class)
            ->call('registrarTramposo', self::SIS, Motivo::CopiaOIntercambioDeRespuestas->value)
            ->assertSet('mensajeError', 'Seleccione un motivo de la lista.');
    }

    /** Un SIS que no está en la base se registra como estudiante no verificado. */
    public function test_registrar_no_inscrito_da_de_alta_un_estudiante_no_verificado(): void
    {
        Livewire::test(MonitorEnVivo::class)
            ->call('registrarNoInscrito', '900199991', Motivo::IntentoDeIngresoNoAutorizado->value, null, 'Juan Carlos', 'Perez Rojas')
            ->assertSet('mensajeError', '');

        $this->assertDatabaseHas('estudiante', [
            'sis_estudiante' => '900199991',
            'nombre_estudiante' => 'Juan Carlos',
            'apellido_estudiante' => 'Perez Rojas',
            'verificado' => false,
        ]);
        $this->assertDatabaseHas('central_riesgo', [
            'sis_estudiante' => '900199991',
            'id_examen' => $this->idExamen,
        ]);
    }
}

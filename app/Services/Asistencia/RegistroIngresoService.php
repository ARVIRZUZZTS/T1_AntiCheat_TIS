<?php

/**
 * @file    RegistroIngresoService.php
 *
 * @author  Alex Candia <alex.leonar.candia@gmail.com>
 *
 * @created 2026-09-26
 *
 * @updated 2026-10-10
 *
 * @description
 * Servicio del control de ingreso del monitor en vivo. Persiste las cuatro
 * acciones que disparan los modales: registrar el ingreso de un estudiante
 * (fila en `registro_asistencia` con hora y registrador), rechazarlo (queda
 * deshabilitado en `estudiante_examen`), permitir su ingreso (lo habilita) y
 * registrar una incidencia de tramposo en `central_riesgo`.
 *
 * Los PK de estas tablas son enteros NOT NULL sin secuencia (así los define el
 * esquema), de modo que el id se calcula como max + 1 dentro de la transacción,
 * la misma técnica que usa CambiarEstadoEstudianteService.
 *
 * @changelog
 * - 2026-09-26  [Alex Candia]  feat: creación inicial (solo registrar()).
 * - 2026-10-10  [T1]  fix: el servicio era código muerto: usaba
 *   `EstadoEstudianteExamen::HABILITADO` (el caso real es `Habilitado`), no
 *   asignaba el PK `id_ingreso` y lanzaba excepciones que no existen en el
 *   proyecto. Se reescribe sobre `InvalidArgumentException` (convención del
 *   proyecto) y se agregan rechazar(), permitir() y registrarIncidencia().
 *
 * @see  App\Livewire\Monitoreo\MonitorEnVivo
 * @see  App\Services\Examen\CambiarEstadoEstudianteService
 */

namespace App\Services\Asistencia;

use App\Enums\EstadoEstudianteExamen;
use App\Enums\Motivo;
use App\Enums\TipoInfraccion;
use App\Models\CentralRiesgo;
use App\Models\Estudiante;
use App\Models\EstudianteExamen;
use App\Models\RegistroAsistencia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RegistroIngresoService
{
    /**
     * Motivo con el que queda el estudiante rechazado en el control de ingreso.
     *
     * @var string
     */
    public const MOTIVO_RECHAZO = 'Rechazado en el control de ingreso';

    /**
     * Registra el ingreso de un estudiante habilitado a un examen.
     *
     * @param  string  $sisEstudiante  Código SIS del estudiante.
     * @param  int  $idExamen  Examen al que ingresa.
     * @param  int  $idRegistrador  Usuario que registra el ingreso.
     * @return RegistroAsistencia Fila creada.
     *
     * @throws InvalidArgumentException Si el estudiante no está inscrito, no
     *                                  está habilitado o ya tiene un ingreso.
     */
    public function registrar(string $sisEstudiante, int $idExamen, int $idRegistrador): RegistroAsistencia
    {
        return DB::transaction(function () use ($sisEstudiante, $idExamen, $idRegistrador) {
            $inscripcion = $this->obtenerInscripcion($sisEstudiante, $idExamen);

            if ($inscripcion->estado !== EstadoEstudianteExamen::Habilitado) {
                throw new InvalidArgumentException('El estudiante no está habilitado para este examen.');
            }

            $existe = RegistroAsistencia::query()
                ->where('id_estudiante', $sisEstudiante)
                ->where('id_examen', $idExamen)
                ->lockForUpdate()
                ->exists();

            if ($existe) {
                throw new InvalidArgumentException('El estudiante ya tiene un ingreso registrado en este examen.');
            }

            return RegistroAsistencia::create([
                'id_ingreso' => $this->siguienteId(RegistroAsistencia::class, 'id_ingreso'),
                'hora_ingreso' => now()->format('H:i:s'),
                'id_examen' => $idExamen,
                'id_estudiante' => $sisEstudiante,
                'id_registrador' => $idRegistrador,
            ]);
        });
    }

    /**
     * Rechaza el ingreso: deja al estudiante deshabilitado para el examen.
     *
     * No crea fila en `registro_asistencia`: un rechazo no es una asistencia.
     *
     * @param  string  $sisEstudiante  Código SIS del estudiante.
     * @param  int  $idExamen  Examen del ingreso.
     * @param  int  $idRegistrador  Usuario que rechaza.
     * @param  string  $motivo  Motivo con el que queda deshabilitado.
     * @return EstudianteExamen Inscripción actualizada.
     *
     * @throws InvalidArgumentException Si el estudiante no está inscrito.
     */
    public function rechazar(string $sisEstudiante, int $idExamen, int $idRegistrador, string $motivo = self::MOTIVO_RECHAZO): EstudianteExamen
    {
        return $this->cambiarEstado($sisEstudiante, $idExamen, EstadoEstudianteExamen::Deshabilitado, $motivo, $idRegistrador);
    }

    /**
     * Permite el ingreso: habilita al estudiante para el examen.
     *
     * @param  string  $sisEstudiante  Código SIS del estudiante.
     * @param  int  $idExamen  Examen del ingreso.
     * @param  int  $idRegistrador  Usuario que habilita.
     * @return EstudianteExamen Inscripción actualizada.
     *
     * @throws InvalidArgumentException Si el estudiante no está inscrito.
     */
    public function permitir(string $sisEstudiante, int $idExamen, int $idRegistrador): EstudianteExamen
    {
        return $this->cambiarEstado($sisEstudiante, $idExamen, EstadoEstudianteExamen::Habilitado, null, $idRegistrador);
    }

    /**
     * Registra una incidencia de tramposo en la central de riesgos.
     *
     * @param  string  $sisEstudiante  Código SIS del estudiante.
     * @param  int  $idExamen  Examen en el que ocurrió.
     * @param  int  $idRegistrador  Usuario que registra.
     * @param  Motivo  $motivo  Motivo de la incidencia.
     * @param  ?string  $detalle  Detalle libre (obligatorio solo con `Otro`).
     * @return CentralRiesgo Fila creada.
     *
     * @throws InvalidArgumentException Si el estudiante no existe.
     */
    public function registrarIncidencia(
        string $sisEstudiante,
        int $idExamen,
        int $idRegistrador,
        Motivo $motivo,
        ?string $detalle = null,
    ): CentralRiesgo {
        return DB::transaction(function () use ($sisEstudiante, $idExamen, $idRegistrador, $motivo, $detalle) {
            if (! Estudiante::query()->where('sis_estudiante', $sisEstudiante)->exists()) {
                throw new InvalidArgumentException('El estudiante no existe.');
            }

            $detalle = $detalle === null ? null : trim($detalle);

            return CentralRiesgo::create([
                'id_registro' => $this->siguienteId(CentralRiesgo::class, 'id_registro'),
                'sis_estudiante' => $sisEstudiante,
                'id_examen' => $idExamen,
                'id_registrador' => $idRegistrador,
                'motivo' => $motivo->value,
                'detalle_motivo' => $detalle === '' ? null : $detalle,
                'fecha_registro' => now(),
                'tipo_infraccion' => TipoInfraccion::Tramposo->value,
            ]);
        });
    }

    /**
     * Aplica un cambio de habilitación sobre la inscripción del estudiante.
     *
     * @param  string  $sisEstudiante  Código SIS del estudiante.
     * @param  int  $idExamen  Examen del ingreso.
     * @param  EstadoEstudianteExamen  $estado  Estado destino.
     * @param  ?string  $motivo  Motivo (null al habilitar).
     * @param  int  $idRegistrador  Usuario que hace el cambio.
     * @return EstudianteExamen Inscripción actualizada.
     *
     * @throws InvalidArgumentException Si el estudiante no está inscrito.
     */
    private function cambiarEstado(
        string $sisEstudiante,
        int $idExamen,
        EstadoEstudianteExamen $estado,
        ?string $motivo,
        int $idRegistrador,
    ): EstudianteExamen {
        return DB::transaction(function () use ($sisEstudiante, $idExamen, $estado, $motivo, $idRegistrador) {
            $inscripcion = $this->obtenerInscripcion($sisEstudiante, $idExamen);

            // Habilitar a alguien ya habilitado es no-op; deshabilitar siempre
            // reescribe (puede traer un motivo nuevo).
            if ($inscripcion->estado === $estado && $estado === EstadoEstudianteExamen::Habilitado) {
                return $inscripcion;
            }

            $inscripcion->estado = $estado;
            $inscripcion->motivo = $estado === EstadoEstudianteExamen::Habilitado ? null : $motivo;
            $inscripcion->modificado_por = $idRegistrador;
            $inscripcion->fecha_modificacion = now();
            $inscripcion->save();

            return $inscripcion->refresh();
        });
    }

    /**
     * Inscripción del estudiante en el examen (bloqueada para evitar carreras).
     *
     * @param  string  $sisEstudiante  Código SIS del estudiante.
     * @param  int  $idExamen  Examen del ingreso.
     * @return EstudianteExamen Inscripción existente.
     *
     * @throws InvalidArgumentException Si el estudiante no está inscrito.
     */
    private function obtenerInscripcion(string $sisEstudiante, int $idExamen): EstudianteExamen
    {
        $inscripcion = EstudianteExamen::query()
            ->where('sis_estudiante', $sisEstudiante)
            ->where('id_examen', $idExamen)
            ->lockForUpdate()
            ->first();

        if ($inscripcion === null) {
            throw new InvalidArgumentException('El estudiante no está inscrito en este examen.');
        }

        return $inscripcion;
    }

    /**
     * Siguiente id entero de una tabla sin secuencia (max + 1).
     *
     * @param  class-string<Model>  $modelo  Modelo destino.
     * @param  string  $columna  Columna PK.
     * @return int Siguiente id disponible.
     */
    private function siguienteId(string $modelo, string $columna): int
    {
        return ((int) $modelo::query()->max($columna)) + 1;
    }
}

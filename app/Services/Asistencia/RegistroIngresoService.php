<?php

/**
 * @file    RegistroIngresoService.php
 *
 * @author  Alex Candia <alex.leonar.candia@gmail.com>
 *
 * @created 2026-09-26
 *
 * @updated 2026-10-10
 * @updated 2026-10-24
 * @updated 2026-10-25
 *
 * @description
 * Servicio del control de ingreso del monitor en vivo. Persiste las tres
 * acciones de ingreso que disparan los modales: registrar el ingreso de un estudiante
 * (fila en `registro_asistencia` con hora y registrador), rechazarlo (queda
 * deshabilitado en `estudiante_examen`) y permitir su ingreso (lo habilita).
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
 * - 2026-10-24  [Valery D. Ortuno P]  fix: registrarIncidencia() guarda el curso
 *   (`id_curso`) con ResolverCursoIncidenciaService; sin él la incidencia no
 *   aparecía con materia en la central de riesgos (HU 10).
 * - 2026-10-25  [Valery D. Ortuno P]  refactor: se quita registrarIncidencia();
 *   el registro de incidencias pasa a RegistrarIncidenciaService (HU 5).
 *
 * @see  App\Livewire\Monitoreo\MonitorEnVivo
 * @see  App\Services\Examen\CambiarEstadoEstudianteService
 */

namespace App\Services\Asistencia;

use App\Enums\EstadoEstudianteExamen;
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
    public function registrar(
        string $sisEstudiante,
        int $idExamen,
        int $idRegistrador,
        ?int $idDocente = null,
    ): RegistroAsistencia {
        return DB::transaction(function () use ($sisEstudiante, $idExamen, $idRegistrador, $idDocente) {
            $inscripcion = $this->obtenerInscripcion($sisEstudiante, $idExamen, $idDocente);

            if ($inscripcion->estado !== EstadoEstudianteExamen::Habilitado) {
                throw new InvalidArgumentException('El estudiante no está habilitado para este examen.');
            }

            $existe = RegistroAsistencia::query()
                ->where('id_estudiante_examen', $inscripcion->id_estudiante_examen)
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
                'id_estudiante_examen' => $inscripcion->id_estudiante_examen,
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
    public function rechazar(
        string $sisEstudiante,
        int $idExamen,
        int $idRegistrador,
        string $motivo = self::MOTIVO_RECHAZO,
        ?int $idDocente = null,
    ): EstudianteExamen {
        return $this->cambiarEstado($sisEstudiante, $idExamen, EstadoEstudianteExamen::Deshabilitado, $motivo, $idRegistrador, $idDocente);
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
    public function permitir(
        string $sisEstudiante,
        int $idExamen,
        int $idRegistrador,
        ?int $idDocente = null,
    ): EstudianteExamen {
        return $this->cambiarEstado($sisEstudiante, $idExamen, EstadoEstudianteExamen::Habilitado, null, $idRegistrador, $idDocente);
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
        ?int $idDocente,
    ): EstudianteExamen {
        return DB::transaction(function () use ($sisEstudiante, $idExamen, $estado, $motivo, $idRegistrador, $idDocente) {
            $inscripcion = $this->obtenerInscripcion($sisEstudiante, $idExamen, $idDocente);

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
    private function obtenerInscripcion(
        string $sisEstudiante,
        int $idExamen,
        ?int $idDocente = null,
    ): EstudianteExamen {
        $inscripcion = EstudianteExamen::query()
            ->where('sis_estudiante', $sisEstudiante)
            ->where('id_examen', $idExamen)
            ->when($idDocente !== null, fn ($query) => $query->delDocente($idDocente))
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

<?php

/**
 * @file    CambiarEstadoEstudianteService.php
 *
 * @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
 *
 * @created 2026-09-26
 *
 * @updated 2026-09-26
 *
 * @description
 * Servicio de la feature Examen que persiste el cambio de estado de un
 * estudiante (habilitado/deshabilitado) para el examen actual de un curso
 * (issue #27). No conoce modales ni vistas: los componentes de las issues
 * #25 (modal "Habilitar") y #26 (modal "Inhabilitar") solo llaman a
 * habilitar()/inhabilitar() y capturan InvalidArgumentException para
 * mostrar el mensaje al usuario.
 *
 * Autorización pendiente: todavía no hay forma de verificar que quien llama
 * es un docente (el sistema de auth no está conectado a la tabla `usuario`,
 * ver notas de la revisión de #29). Por eso este servicio NO valida quién
 * puede cambiar el estado — queda documentado como pendiente en el PR.
 *
 * @see  App\Livewire\Examenes\EstudiantesCurso
 * @see  App\Models\EstudianteExamen
 *
 * @changelog
 * - 2026-09-26  [T1]  feat: creación inicial del servicio (#27).
 */

namespace App\Services\Examen;

use App\Enums\EstadoEstudianteExamen;
use App\Models\Curso;
use App\Models\EstudianteExamen;
use App\Models\Examen;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class CambiarEstadoEstudianteService
{
    public const MOTIVO_MAX = 150;

    /**
     * Habilita a un estudiante para el examen actual del curso. Si ya está
     * habilitado, no hace nada (operación idempotente).
     *
     * @param  Curso  $curso  Curso al que pertenece el estudiante.
     * @param  string  $sisEstudiante  Código SIS del estudiante.
     * @param  ?int  $idUsuarioModificador  Usuario que realiza el cambio (null si aún no se puede resolver).
     * @return EstudianteExamen Registro actualizado.
     *
     * @throws InvalidArgumentException Si el estudiante no pertenece al curso.
     * @throws RuntimeException Si el curso no tiene un examen actual.
     */
    public function habilitar(Curso $curso, string $sisEstudiante, ?int $idUsuarioModificador = null): EstudianteExamen
    {
        return $this->cambiarEstado($curso, $sisEstudiante, EstadoEstudianteExamen::Habilitado, null, $idUsuarioModificador);
    }

    /**
     * Inhabilita a un estudiante para el examen actual del curso, con un
     * motivo obligatorio. Si ya estaba deshabilitado, actualiza el motivo.
     *
     * @param  Curso  $curso  Curso al que pertenece el estudiante.
     * @param  string  $sisEstudiante  Código SIS del estudiante.
     * @param  string  $motivo  Motivo de la inhabilitación.
     * @param  ?int  $idUsuarioModificador  Usuario que realiza el cambio (null si aún no se puede resolver).
     * @return EstudianteExamen Registro actualizado.
     *
     * @throws InvalidArgumentException Si el motivo es inválido o el estudiante no pertenece al curso.
     * @throws RuntimeException Si el curso no tiene un examen actual.
     */
    public function inhabilitar(
        Curso $curso,
        string $sisEstudiante,
        string $motivo,
        ?int $idUsuarioModificador = null,
    ): EstudianteExamen {
        $this->validarMotivo($motivo);

        return $this->cambiarEstado($curso, $sisEstudiante, EstadoEstudianteExamen::Deshabilitado, trim($motivo), $idUsuarioModificador);
    }

    /**
     * Valida el motivo de inhabilitación: obligatorio, máximo 150
     * caracteres, mismos caracteres permitidos que el resto del sistema
     * (ver ListarEstudiantesCursoConEstadoService::validarBusqueda — deben
     * mantenerse en sincronía si se cambia uno de los dos).
     *
     * Se expone público para que el Livewire la use en tiempo real (ej. en
     * updated('motivoInhabilitacion')) y así habilitar/deshabilitar el botón
     * de confirmar sin duplicar la regla en la vista.
     *
     * @param  ?string  $motivo  Motivo a validar.
     *
     * @throws InvalidArgumentException Si el motivo es inválido.
     */
    public function validarMotivo(?string $motivo): void
    {
        $motivo = trim((string) $motivo);

        if ($motivo === '') {
            throw new InvalidArgumentException('El motivo es obligatorio');
        }

        if (mb_strlen($motivo) > self::MOTIVO_MAX) {
            throw new InvalidArgumentException('El motivo no puede superar los '.self::MOTIVO_MAX.' caracteres');
        }

        if (preg_match('/^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ0-9 ]+$/u', $motivo) !== 1) {
            throw new InvalidArgumentException('Caracter no permitido en el motivo (A-z, 0-9, espacio)');
        }
    }

    /**
     * Aplica el cambio de estado dentro de una transacción: resuelve el
     * examen actual, obtiene o crea la fila de estudiante_examen (bloqueada
     * para evitar condiciones de carrera entre dos docentes) y la guarda.
     *
     * @param  Curso  $curso  Curso al que pertenece el estudiante.
     * @param  string  $sisEstudiante  Código SIS del estudiante.
     * @param  EstadoEstudianteExamen  $estado  Estado destino.
     * @param  ?string  $motivo  Motivo (null al habilitar).
     * @param  ?int  $idUsuarioModificador  Usuario que realiza el cambio.
     * @return EstudianteExamen Registro actualizado.
     *
     * @throws InvalidArgumentException Si el estudiante no pertenece al curso.
     * @throws RuntimeException Si el curso no tiene un examen actual.
     */
    private function cambiarEstado(
        Curso $curso,
        string $sisEstudiante,
        EstadoEstudianteExamen $estado,
        ?string $motivo,
        ?int $idUsuarioModificador,
    ): EstudianteExamen {
        return DB::transaction(function () use ($curso, $sisEstudiante, $estado, $motivo, $idUsuarioModificador) {
            $examen = $curso->examenActual();

            if ($examen === null) {
                throw new RuntimeException('El curso no tiene un examen actual');
            }

            $registro = $this->obtenerOCrearRegistro($curso, $sisEstudiante, $examen);

            // Habilitar sobre alguien ya habilitado es no-op: no reescribe nada.
            // Inhabilitar sobre alguien ya deshabilitado SÍ actualiza (puede
            // traer un motivo nuevo/corregido).
            if ($registro->estado === $estado && $estado === EstadoEstudianteExamen::Habilitado) {
                return $registro;
            }

            $registro->estado = $estado;
            $registro->motivo = $estado === EstadoEstudianteExamen::Habilitado ? null : $motivo;
            $registro->modificado_por = $idUsuarioModificador;
            $registro->fecha_modificacion = now();
            $registro->save();

            return $registro->refresh();
        });
    }

    /**
     * Obtiene la fila de estudiante_examen del estudiante para ese examen
     * (bloqueada con lockForUpdate), o la crea si todavía no existe (caso
     * "sin estado" de la issue #29).
     *
     * @param  Curso  $curso  Curso al que debe pertenecer el estudiante.
     * @param  string  $sisEstudiante  Código SIS del estudiante.
     * @param  Examen  $examen  Examen actual del curso.
     * @return EstudianteExamen Registro existente o recién creado.
     *
     * @throws InvalidArgumentException Si el estudiante no pertenece al curso.
     */
    private function obtenerOCrearRegistro(Curso $curso, string $sisEstudiante, Examen $examen): EstudianteExamen
    {
        $pertenece = $curso->estudiantes()
            ->where('estudiante.sis_estudiante', $sisEstudiante)
            ->exists();

        if (! $pertenece) {
            throw new InvalidArgumentException('El estudiante no pertenece a este curso');
        }

        $registro = EstudianteExamen::where('sis_estudiante', $sisEstudiante)
            ->where('id_examen', $examen->id_examen)
            ->lockForUpdate()
            ->first();

        if ($registro !== null) {
            return $registro;
        }

        $siguienteId = ((int) EstudianteExamen::max('id_estudiante_examen')) + 1;

        return EstudianteExamen::create([
            'id_estudiante_examen' => $siguienteId,
            'sis_estudiante' => $sisEstudiante,
            'id_examen' => $examen->id_examen,
            'estado' => EstadoEstudianteExamen::Habilitado->value,
            'motivo' => null,
        ]);
    }
}

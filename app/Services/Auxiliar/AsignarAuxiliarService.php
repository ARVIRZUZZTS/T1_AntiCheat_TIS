<?php

/**
 * @file    AsignarAuxiliarService.php
 *
 * @author  Valery D. Ortuno P <valerydariana98@gmail.com>
 *
 * @created 2026-11-22
 *
 * @description
 * Asigna un auxiliar ya creado a un curso o a un examen del docente, y quita
 * esas asignaciones (HU 24).
 *
 *  - Curso: fila en `auxiliar_curso` en estado Activo. El auxiliar ve todos
 *    los examenes del curso en Ingreso y en Monitor. Quitarla la pasa a Baja.
 *  - Examen: fila en `auxiliar_examen` con las paginas elegidas (Ingreso,
 *    Monitor o las dos). Solo examenes futuros de los cursos en curso del
 *    docente (no de cursos archivados).
 *    Volver a asignar el mismo examen actualiza las paginas. Quitarla borra
 *    la fila.
 *
 * Se puede asignar cualquier auxiliar del sistema; el docente solo elige
 * entre sus cursos en curso y los examenes futuros de esos cursos.
 *
 * @changelog
 * - 2026-11-22  [Valery D. Ortuno P]  feat: creacion inicial (HU 24).
 * - 2026-11-28  [Valery D. Ortuno P]  feat: `actualizarPermisos()` (crear y/o
 *   asignar auxiliares, lo da el docente al asignar a un curso); el coordinador
 *   tambien puede asignar en los examenes que tiene asignados.
 *
 * @see  App\Livewire\Auxiliares\AsignarAuxiliar
 * @see  App\Services\Auxiliar\AccesoAuxiliarService
 */

namespace App\Services\Auxiliar;

use App\Enums\CursoEstado;
use App\Models\Rol;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AsignarAuxiliarService
{
    public const ESTADO_BAJA = 'Baja';

    public const MENSAJE_NO_AUXILIAR = 'El usuario elegido no es un auxiliar.';

    public const MENSAJE_CURSO_INVALIDO = 'Elige uno de tus cursos en curso.';

    public const MENSAJE_EXAMEN_INVALIDO = 'Elige un examen futuro de tus cursos.';

    public const MENSAJE_SIN_PAGINA = 'Marca al menos Ingreso o Monitor.';

    public const MENSAJE_YA_EN_CURSO = 'El auxiliar ya esta asignado a este curso.';

    public const MENSAJE_YA_POR_CURSO = 'El auxiliar ya ve este examen porque esta asignado al curso %s.';

    public const MENSAJE_NO_EXISTE = 'Esa asignacion ya no existe.';

    public const MENSAJE_NO_A_SI_MISMO = 'No puedes asignarte a ti mismo.';

    public const MENSAJE_FUERA_DE_ALCANCE = 'Solo puedes asignar en los cursos y examenes en los que estas asignado.';

    /**
     * @param  int       $idDocente      Docente dueño de los cursos/examenes.
     * @param  int       $idAuxiliar     Auxiliar al que se asigna.
     * @param  int       $idCurso        Curso a asignar.
     * @param  int|null  $idCoordinador  Coordinador que actua (null si es el docente).
     *
     * @return string Nombre del curso asignado.
     *
     * @throws ValidationException
     */
    public function asignarCurso(int $idDocente, int $idAuxiliar, int $idCurso, ?int $idCoordinador = null): string
    {
        return DB::transaction(function () use ($idDocente, $idAuxiliar, $idCurso, $idCoordinador): string {
            $this->exigirAuxiliar($idAuxiliar);
            $this->exigirAlcanceCurso($idCoordinador, $idAuxiliar, $idCurso);

            $curso = DB::table('curso')
                ->where('id_curso', $idCurso)
                ->where('sis_doc', $idDocente)
                ->where('estado', CursoEstado::EnCurso->value)
                ->first(['id_curso', 'nombre_curso']);

            if ($curso === null) {
                throw ValidationException::withMessages(['curso' => self::MENSAJE_CURSO_INVALIDO]);
            }

            $actual = DB::table('auxiliar_curso')
                ->where('id_auxiliar', $idAuxiliar)
                ->where('id_curso', $idCurso)
                ->lockForUpdate()
                ->value('estado');

            if ($actual === AccesoAuxiliarService::ESTADO_ACTIVO) {
                throw ValidationException::withMessages(['curso' => self::MENSAJE_YA_EN_CURSO]);
            }

            if ($actual === null) {
                DB::table('auxiliar_curso')->insert([
                    'id_auxiliar' => $idAuxiliar,
                    'id_curso' => $idCurso,
                    'estado' => AccesoAuxiliarService::ESTADO_ACTIVO,
                ]);
            } else {
                // Estaba de baja en ese curso: se reactiva.
                DB::table('auxiliar_curso')
                    ->where('id_auxiliar', $idAuxiliar)
                    ->where('id_curso', $idCurso)
                    ->update(['estado' => AccesoAuxiliarService::ESTADO_ACTIVO]);
            }

            return (string) $curso->nombre_curso;
        });
    }

    /**
     * Permisos de coordinador del auxiliar (solo los da un docente, al
     * asignarlo a un curso): crear auxiliares y/o asignar auxiliares, siempre
     * limitados a los auxiliares que el cree y a sus cursos y examenes.
     *
     * @return bool true si algun permiso cambio.
     *
     * @throws ValidationException
     */
    public function actualizarPermisos(int $idAuxiliar, bool $puedeCrear, bool $puedeAsignar): bool
    {
        $this->exigirAuxiliar($idAuxiliar);

        return DB::table('usuario')
            ->where('id_usuario', $idAuxiliar)
            ->where(fn ($distinto) => $distinto
                ->where('puede_crear_auxiliares', '!=', $puedeCrear)
                ->orWhere('puede_asignar_auxiliares', '!=', $puedeAsignar))
            ->update(['puede_crear_auxiliares' => $puedeCrear, 'puede_asignar_auxiliares' => $puedeAsignar]) > 0;
    }

    /**
     * @return array{actualizada: bool}
     *
     * @throws ValidationException
     */
    public function asignarExamen(int $idDocente, int $idAuxiliar, int $idExamen, bool $verIngreso, bool $verMonitor, ?int $idCoordinador = null): array
    {
        if (! $verIngreso && ! $verMonitor) {
            throw ValidationException::withMessages(['paginas' => self::MENSAJE_SIN_PAGINA]);
        }

        return DB::transaction(function () use ($idDocente, $idAuxiliar, $idExamen, $verIngreso, $verMonitor, $idCoordinador): array {
            $this->exigirAuxiliar($idAuxiliar);
            $this->exigirAlcanceExamen($idCoordinador, $idAuxiliar, $idExamen);

            $ahora = now();
            $examenValido = DB::table('examen as e')
                ->where('e.id_examen', $idExamen)
                ->whereExists(fn ($consulta) => $consulta->from('examen_curso as ec')
                    ->join('curso as c', 'c.id_curso', '=', 'ec.id_curso')
                    ->whereColumn('ec.id_examen', 'e.id_examen')
                    ->where('c.sis_doc', $idDocente)
                    ->where('c.estado', CursoEstado::EnCurso->value)
                    ->selectRaw('1'))
                ->where(fn ($consulta) => $consulta
                    ->where('e.fecha', '>', $ahora->toDateString())
                    ->orWhere(fn ($hoy) => $hoy
                        ->where('e.fecha', $ahora->toDateString())
                        ->where('e.hora_inicio', '>', $ahora->format('H:i:s'))))
                ->exists();

            if (! $examenValido) {
                throw ValidationException::withMessages(['examen' => self::MENSAJE_EXAMEN_INVALIDO]);
            }

            // Si ya lo ve por un curso, la asignacion suelta no agrega nada.
            $cursoAsignado = DB::table('examen_curso as ec')
                ->join('auxiliar_curso as ac', 'ac.id_curso', '=', 'ec.id_curso')
                ->join('curso as c', 'c.id_curso', '=', 'ec.id_curso')
                ->where('ec.id_examen', $idExamen)
                ->where('ac.id_auxiliar', $idAuxiliar)
                ->where('ac.estado', AccesoAuxiliarService::ESTADO_ACTIVO)
                ->value('c.nombre_curso');

            if ($cursoAsignado !== null) {
                throw ValidationException::withMessages(['examen' => sprintf(self::MENSAJE_YA_POR_CURSO, $cursoAsignado)]);
            }

            $existe = DB::table('auxiliar_examen')
                ->where('id_auxiliar', $idAuxiliar)
                ->where('id_examen', $idExamen)
                ->lockForUpdate()
                ->exists();

            $datos = ['ver_ingreso' => $verIngreso, 'ver_monitor' => $verMonitor, 'asignado_por' => $idDocente];

            if ($existe) {
                DB::table('auxiliar_examen')
                    ->where('id_auxiliar', $idAuxiliar)
                    ->where('id_examen', $idExamen)
                    ->update($datos);
            } else {
                DB::table('auxiliar_examen')->insert($datos + [
                    'id_auxiliar' => $idAuxiliar,
                    'id_examen' => $idExamen,
                    'fecha_asignacion' => now(),
                ]);
            }

            return ['actualizada' => $existe];
        });
    }

    /**
     * Pasa a Baja la asignacion del auxiliar a un curso del docente.
     *
     * @throws ValidationException
     */
    public function quitarCurso(int $idDocente, int $idAuxiliar, int $idCurso, ?int $idCoordinador = null): void
    {
        $this->exigirAlcanceCurso($idCoordinador, $idAuxiliar, $idCurso);

        $filas = DB::table('auxiliar_curso')
            ->where('id_auxiliar', $idAuxiliar)
            ->where('id_curso', $idCurso)
            ->where('estado', AccesoAuxiliarService::ESTADO_ACTIVO)
            ->whereExists(fn ($consulta) => $consulta->from('curso')
                ->whereColumn('curso.id_curso', 'auxiliar_curso.id_curso')
                ->where('curso.sis_doc', $idDocente)
                ->selectRaw('1'))
            ->update(['estado' => self::ESTADO_BAJA]);

        if ($filas === 0) {
            throw ValidationException::withMessages(['asignacion' => self::MENSAJE_NO_EXISTE]);
        }
    }

    /**
     * Borra la asignacion del auxiliar a un examen de los cursos del docente.
     *
     * @throws ValidationException
     */
    public function quitarExamen(int $idDocente, int $idAuxiliar, int $idExamen, ?int $idCoordinador = null): void
    {
        $this->exigirAlcanceExamen($idCoordinador, $idAuxiliar, $idExamen);

        $filas = DB::table('auxiliar_examen')
            ->where('id_auxiliar', $idAuxiliar)
            ->where('id_examen', $idExamen)
            ->whereExists(fn ($consulta) => $consulta->from('examen_curso as ec')
                ->join('curso as c', 'c.id_curso', '=', 'ec.id_curso')
                ->whereColumn('ec.id_examen', 'auxiliar_examen.id_examen')
                ->where('c.sis_doc', $idDocente)
                ->selectRaw('1'))
            ->delete();

        if ($filas === 0) {
            throw ValidationException::withMessages(['asignacion' => self::MENSAJE_NO_EXISTE]);
        }
    }

    /**
     * Si actua un coordinador: no puede asignarse a si mismo y el curso debe ser
     * uno en el que el coordinador tiene asignacion activa. Sin coordinador (lo
     * hace el docente) no aplica ninguna restriccion extra.
     *
     * @throws ValidationException
     */
    private function exigirAlcanceCurso(?int $idCoordinador, int $idAuxiliar, int $idCurso): void
    {
        if ($idCoordinador === null) {
            return;
        }

        $this->exigirNoSiMismo($idCoordinador, $idAuxiliar);

        $asignado = DB::table('auxiliar_curso')
            ->where('id_auxiliar', $idCoordinador)
            ->where('id_curso', $idCurso)
            ->where('estado', AccesoAuxiliarService::ESTADO_ACTIVO)
            ->exists();

        if (! $asignado) {
            throw ValidationException::withMessages(['curso' => self::MENSAJE_FUERA_DE_ALCANCE]);
        }
    }

    /**
     * Si actua un coordinador: no puede asignarse a si mismo y el examen debe
     * pertenecer a un curso en el que el coordinador esta asignado.
     *
     * @throws ValidationException
     */
    private function exigirAlcanceExamen(?int $idCoordinador, int $idAuxiliar, int $idExamen): void
    {
        if ($idCoordinador === null) {
            return;
        }

        $this->exigirNoSiMismo($idCoordinador, $idAuxiliar);

        // Examen de un curso donde el coordinador es auxiliar, o examen que el
        // coordinador tiene asignado.
        $asignado = DB::table('examen_curso as ec')
            ->join('auxiliar_curso as ac', 'ac.id_curso', '=', 'ec.id_curso')
            ->where('ec.id_examen', $idExamen)
            ->where('ac.id_auxiliar', $idCoordinador)
            ->where('ac.estado', AccesoAuxiliarService::ESTADO_ACTIVO)
            ->exists()
            || DB::table('auxiliar_examen')
                ->where('id_examen', $idExamen)
                ->where('id_auxiliar', $idCoordinador)
                ->exists();

        if (! $asignado) {
            throw ValidationException::withMessages(['examen' => self::MENSAJE_FUERA_DE_ALCANCE]);
        }
    }

    /** @throws ValidationException */
    private function exigirNoSiMismo(int $idCoordinador, int $idAuxiliar): void
    {
        if ($idCoordinador === $idAuxiliar) {
            throw ValidationException::withMessages(['auxiliar' => self::MENSAJE_NO_A_SI_MISMO]);
        }
    }

    /** @throws ValidationException */
    private function exigirAuxiliar(int $idAuxiliar): void
    {
        $esAuxiliar = DB::table('rol_usuario as ru')
            ->join('rol as r', 'r.id_rol', '=', 'ru.id_rol')
            ->where('ru.id_usuario', $idAuxiliar)
            ->where('r.nombre_rol', Rol::NOMBRE_AUXILIAR)
            ->exists();

        if (! $esAuxiliar) {
            throw ValidationException::withMessages(['auxiliar' => self::MENSAJE_NO_AUXILIAR]);
        }
    }
}

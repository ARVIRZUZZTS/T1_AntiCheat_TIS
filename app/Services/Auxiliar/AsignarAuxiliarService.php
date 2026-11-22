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

    /**
     * @return string Nombre del curso asignado.
     *
     * @throws ValidationException
     */
    public function asignarCurso(int $idDocente, int $idAuxiliar, int $idCurso): string
    {
        return DB::transaction(function () use ($idDocente, $idAuxiliar, $idCurso): string {
            $this->exigirAuxiliar($idAuxiliar);

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
     * @return array{actualizada: bool}
     *
     * @throws ValidationException
     */
    public function asignarExamen(int $idDocente, int $idAuxiliar, int $idExamen, bool $verIngreso, bool $verMonitor): array
    {
        if (! $verIngreso && ! $verMonitor) {
            throw ValidationException::withMessages(['paginas' => self::MENSAJE_SIN_PAGINA]);
        }

        return DB::transaction(function () use ($idDocente, $idAuxiliar, $idExamen, $verIngreso, $verMonitor): array {
            $this->exigirAuxiliar($idAuxiliar);

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
    public function quitarCurso(int $idDocente, int $idAuxiliar, int $idCurso): void
    {
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
    public function quitarExamen(int $idDocente, int $idAuxiliar, int $idExamen): void
    {
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

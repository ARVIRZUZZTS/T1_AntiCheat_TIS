<?php

/**
 * @file    EliminarAuxiliarService.php
 *
 * @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
 *
 * @created 2026-11-27
 *
 * @updated 2026-11-28
 *
 * @description
 * Elimina de forma **lógica** a un auxiliar (subsección "Eliminar auxiliar").
 * Reutiliza el patrón de columna de estado de "Archivar curso": en vez de
 * borrar la fila, marca `usuario.estado_cuenta = 'eliminada'`. El auxiliar deja
 * de poder ingresar ("Tu cuenta fue eliminada", vía EstadoCuenta::mensajeAcceso)
 * y desaparece de todas las listas de auxiliares, conservando su historial.
 *
 * Al eliminar, en una sola transacción con `lockForUpdate`:
 *  - marca la cuenta como `eliminada`;
 *  - pasa a `Baja` sus asignaciones de curso activas (se conservan como
 *    historial, igual que `quitarCurso`);
 *  - cierra sus sesiones activas (`sessions`).
 *
 * Guardas (igual que las de Eliminar examen, que bloquean lo que tiene
 * historia): solo se elimina al auxiliar que no tiene nada asociado. Con
 * cursos activos asignados, o si estuvo asignado a algun curso (pasado) o a
 * algun examen, no se puede eliminar. Tampoco con un examen **en curso**.
 *
 * Permisos: ver `UsuarioPolicy::eliminar` (docente responsable; coordinador solo
 * a los que creó; nadie a sí mismo).
 *
 * @changelog
 * - 2026-11-27  [Diego Tejerina]  feat: creación inicial (eliminación lógica).
 * - 2026-11-28  [Valery D. Ortuno P]  fix: no se elimina a un auxiliar con
 *   cursos activos ni a uno que estuvo asignado a cursos o exámenes; solo al
 *   que no tiene nada asociado.
 *
 * @see  App\Policies\UsuarioPolicy
 * @see  App\Enums\EstadoCuenta
 * @see  App\Services\Auxiliar\AccesoAuxiliarService
 * @see  App\Services\Examen\ListarExamenesCursoService
 */

namespace App\Services\Auxiliar;

use App\Enums\EstadoCuenta;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class EliminarAuxiliarService
{
    public const MENSAJE_EXITO = 'Auxiliar eliminado con exito';

    public const MENSAJE_EXAMEN_EN_CURSO = 'No se puede eliminar: tiene un examen en curso';

    public const MENSAJE_NO_AUXILIAR = 'El usuario elegido no es un auxiliar.';

    public const MENSAJE_CURSOS_ACTIVOS = ListarAuxiliaresEliminablesService::MOTIVO_CURSOS_ACTIVOS;

    public const MENSAJE_CON_HISTORIAL = ListarAuxiliaresEliminablesService::MOTIVO_CON_HISTORIAL;

    /** Hora de inicio por defecto cuando el examen no la tiene (igual que Listar exámenes). */
    private const HORA_INICIO_POR_DEFECTO = '00:00:00';

    /** Hora de fin por defecto cuando el examen no tiene duración. */
    private const HORA_FIN_POR_DEFECTO = '23:59:59';

    /**
     * Elimina lógicamente al auxiliar.
     *
     * @param  Usuario  $actor     Usuario autenticado que elimina (docente o coordinador).
     * @param  Usuario  $auxiliar  Auxiliar que se quiere eliminar.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException  Si $actor no puede eliminarlo (403).
     * @throws ValidationException  Si el auxiliar tiene un examen en curso.
     * @throws ModelNotFoundException  Si el auxiliar ya no existe.
     *
     * @author Diego Tejerina <josediegotejerinamolina@gmail.com>
     *
     * @since  2026-11-27
     */
    public function ejecutar(Usuario $actor, Usuario $auxiliar): void
    {
        Gate::forUser($actor)->authorize('eliminar', $auxiliar);

        if (! $auxiliar->esAuxiliar()) {
            throw ValidationException::withMessages(['auxiliar' => self::MENSAJE_NO_AUXILIAR]);
        }

        $motivo = $this->motivoBloqueo((int) $auxiliar->id_usuario);
        if ($motivo !== null) {
            throw ValidationException::withMessages(['auxiliar' => $motivo]);
        }

        if ($this->tieneExamenEnCurso((int) $auxiliar->id_usuario)) {
            throw ValidationException::withMessages(['auxiliar' => self::MENSAJE_EXAMEN_EN_CURSO]);
        }

        DB::transaction(function () use ($auxiliar): void {
            $bloqueado = Usuario::query()
                ->whereKey($auxiliar->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $id = (int) $bloqueado->id_usuario;

            // Las asignaciones de curso activas pasan a Baja: se conservan como
            // historial (igual que "quitar" una asignación), pero dejan de dar
            // acceso en Ingreso y Monitor.
            DB::table('auxiliar_curso')
                ->where('id_auxiliar', $id)
                ->where('estado', AccesoAuxiliarService::ESTADO_ACTIVO)
                ->update(['estado' => AsignarAuxiliarService::ESTADO_BAJA]);

            $bloqueado->estado_cuenta = EstadoCuenta::Eliminada->value;
            $bloqueado->save();

            $this->cerrarSesiones($id);
        });
    }

    /**
     * Por que no se puede eliminar al auxiliar (null si no tiene nada
     * asociado): cursos activos asignados, o cursos o examenes de antes. Una
     * sola consulta.
     */
    private function motivoBloqueo(int $idAuxiliar): ?string
    {
        $fila = DB::selectOne(
            'select exists (select 1 from auxiliar_curso ac join curso c on c.id_curso = ac.id_curso'
            .' where ac.id_auxiliar = ? and ac.estado = ? and c.estado = ?) as activos,'
            .' (exists (select 1 from auxiliar_curso where id_auxiliar = ?)'
            .' or exists (select 1 from auxiliar_examen where id_auxiliar = ?)) as historial',
            [$idAuxiliar, AccesoAuxiliarService::ESTADO_ACTIVO, \App\Enums\CursoEstado::EnCurso->value, $idAuxiliar, $idAuxiliar],
        );

        return match (true) {
            (bool) $fila->activos => self::MENSAJE_CURSOS_ACTIVOS,
            (bool) $fila->historial => self::MENSAJE_CON_HISTORIAL,
            default => null,
        };
    }

    /**
     * Indica si el auxiliar tiene algún examen en curso ahora mismo, entre los
     * exámenes a los que está asignado (por curso activo o por examen suelto).
     * "En curso" usa la misma ventana horaria que ListarExamenesCursoService.
     *
     * @param  int  $idAuxiliar  ID del auxiliar.
     * @return bool  True si al menos un examen asignado está en curso.
     */
    private function tieneExamenEnCurso(int $idAuxiliar): bool
    {
        $ahora = now();

        foreach ($this->examenesAsignados($idAuxiliar) as $examen) {
            if ($examen->fecha === null) {
                continue;
            }

            $inicio = Carbon::parse($examen->fecha.' '.($examen->hora_inicio ?? self::HORA_INICIO_POR_DEFECTO));
            $fin = $examen->duracion === null
                ? Carbon::parse($examen->fecha.' '.self::HORA_FIN_POR_DEFECTO)
                : $inicio->copy()->addMinutes((int) $examen->duracion);

            if ($ahora->gte($inicio) && $ahora->lte($fin)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Exámenes a los que el auxiliar está asignado hoy: los de sus cursos con
     * asignación activa y los exámenes sueltos. Una fila por examen.
     *
     * @param  int  $idAuxiliar  ID del auxiliar.
     * @return \Illuminate\Support\Collection<int, \stdClass>
     */
    private function examenesAsignados(int $idAuxiliar): \Illuminate\Support\Collection
    {
        $porCurso = DB::table('examen as e')
            ->join('examen_curso as ec', 'ec.id_examen', '=', 'e.id_examen')
            ->join('auxiliar_curso as ac', 'ac.id_curso', '=', 'ec.id_curso')
            ->where('ac.id_auxiliar', $idAuxiliar)
            ->where('ac.estado', AccesoAuxiliarService::ESTADO_ACTIVO)
            ->select('e.id_examen', 'e.fecha', 'e.hora_inicio', 'e.duracion');

        return DB::table('examen as e')
            ->join('auxiliar_examen as ae', 'ae.id_examen', '=', 'e.id_examen')
            ->where('ae.id_auxiliar', $idAuxiliar)
            ->select('e.id_examen', 'e.fecha', 'e.hora_inicio', 'e.duracion')
            ->union($porCurso)
            ->get();
    }

    /**
     * Cierra las sesiones activas del usuario (si la tabla `sessions` existe).
     *
     * @param  int  $idUsuario  ID del usuario.
     */
    private function cerrarSesiones(int $idUsuario): void
    {
        if (\Illuminate\Support\Facades\Schema::hasTable('sessions')) {
            DB::table('sessions')->where('user_id', $idUsuario)->delete();
        }
    }
}

<?php

/**
 * @file    ListarAuxiliaresEliminablesService.php
 *
 * @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
 *
 * @created 2026-11-27
 *
 * @updated 2026-11-28
 *
 * @description
 * Datos de la página "Eliminar auxiliar": los auxiliares del usuario
 * autenticado con su correo, tipo (UMSS / Externo), documento (SIS o carnet)
 * y lo que tienen asociado: cursos activos, cursos anteriores y exámenes.
 * Solo se puede eliminar al que no tiene nada asociado (`eliminable`); si no,
 * `motivo` dice por qué.
 *
 * Alcance (igual que UsuarioPolicy::eliminar): el docente ve a los auxiliares de
 * los que es responsable (`id_docente_responsable`); el coordinador solo a los
 * que él creó (`id_creador`). Nunca a sí mismo ni a los eliminados.
 *
 * Todo sale en tres consultas (sin N+1): las cuentas, sus asignaciones de curso
 * (de cualquier docente, activas o pasadas) y la cantidad de exámenes asignados.
 *
 * @changelog
 * - 2026-11-27  [Diego Tejerina]  feat: creación inicial.
 * - 2026-11-28  [Valery D. Ortuno P]  refactor: cada auxiliar trae sus cursos
 *   activos, cursos anteriores y exámenes asignados, y si se puede eliminar
 *   (solo sin nada asociado) con el motivo si no; se quitan el estado y la
 *   lista de cursos de los filtros.
 *
 * @see  App\Services\Auxiliar\EliminarAuxiliarService
 * @see  App\Livewire\Auxiliares\EliminarAuxiliar
 */

namespace App\Services\Auxiliar;

use App\Enums\EstadoCuenta;
use App\Models\Rol;
use App\Models\Usuario;
use App\Enums\CursoEstado;
use Illuminate\Support\Facades\DB;

class ListarAuxiliaresEliminablesService
{
    public const MOTIVO_CURSOS_ACTIVOS = 'No se puede eliminar un auxiliar con cursos activos asignados';

    public const MOTIVO_CON_HISTORIAL = 'No se puede eliminar un auxiliar que estuvo asignado a cursos o examenes';

    /**
     * @param  Usuario  $viewer  Usuario autenticado (docente o coordinador).
     * @return array{
     *     auxiliares: list<array{id: int, nombreCompleto: string, correo: string, tipo: string, documento: string, cursosActivos: list<string>, cursosPasados: list<string>, examenes: int, eliminable: bool, motivo: string}>
     * }
     */
    public function ejecutar(Usuario $viewer): array
    {
        $cuentas = $this->cuentas($viewer);
        $ids = $cuentas->pluck('id_usuario')->map(fn (mixed $id): int => (int) $id)->all();

        // Cursos asignados (de cualquier docente): activo si la asignacion esta
        // Activa y el curso en curso; si no, es un curso anterior.
        $cursos = $ids === [] ? collect() : DB::table('auxiliar_curso as ac')
            ->join('curso as c', 'c.id_curso', '=', 'ac.id_curso')
            ->whereIn('ac.id_auxiliar', $ids)
            ->orderBy('c.nombre_curso')
            ->get(['ac.id_auxiliar', 'ac.estado as asignacion', 'c.estado as estado_curso', 'c.nombre_curso'])
            ->groupBy('id_auxiliar');

        $examenes = $ids === [] ? collect() : DB::table('auxiliar_examen')
            ->whereIn('id_auxiliar', $ids)
            ->selectRaw('id_auxiliar, count(*) as total')
            ->groupBy('id_auxiliar')
            ->pluck('total', 'id_auxiliar');

        $auxiliares = $cuentas
            ->map(function (object $auxiliar) use ($cursos, $examenes): array {
                $suyos = $cursos->get($auxiliar->id_usuario, collect());
                $esActivo = fn (object $fila): bool => $fila->asignacion === AccesoAuxiliarService::ESTADO_ACTIVO
                    && $fila->estado_curso === CursoEstado::EnCurso->value;

                $activos = $suyos->filter($esActivo)->pluck('nombre_curso')->unique()->values()->all();
                $pasados = $suyos->reject($esActivo)->pluck('nombre_curso')->unique()
                    ->diff($activos)->values()->all();
                $totalExamenes = (int) ($examenes->get($auxiliar->id_usuario) ?? 0);

                $nombre = trim((string) ($auxiliar->nombre !== null && trim((string) $auxiliar->nombre) !== ''
                    ? $auxiliar->nombre
                    : $auxiliar->nombre_usuario));
                $esUmss = $auxiliar->tipo_auxiliar === 'UMSS' || $auxiliar->tipo_auxiliar === null;

                return [
                    'id' => (int) $auxiliar->id_usuario,
                    'nombreCompleto' => trim($nombre.' '.$auxiliar->apellido),
                    'correo' => trim((string) $auxiliar->correo),
                    'tipo' => $esUmss ? 'UMSS' : 'Externo',
                    'documento' => $esUmss
                        ? (string) $auxiliar->cod_sis
                        : trim((string) $auxiliar->carnet),
                    'cursosActivos' => $activos,
                    'cursosPasados' => $pasados,
                    'examenes' => $totalExamenes,
                    'eliminable' => $activos === [] && $pasados === [] && $totalExamenes === 0,
                    'motivo' => match (true) {
                        $activos !== [] => self::MOTIVO_CURSOS_ACTIVOS,
                        $pasados !== [] || $totalExamenes > 0 => self::MOTIVO_CON_HISTORIAL,
                        default => '',
                    },
                ];
            })
            ->values()
            ->all();

        return ['auxiliares' => $auxiliares];
    }

    /**
     * Cuentas de auxiliar que el viewer puede eliminar: las que es responsable
     * (docente) o las que creó (coordinador), sin las eliminadas ni la suya.
     *
     * @return \Illuminate\Support\Collection<int, \stdClass>
     */
    private function cuentas(Usuario $viewer): \Illuminate\Support\Collection
    {
        $columna = $viewer->esDocente() ? 'u.id_docente_responsable' : 'u.id_creador';

        return DB::table('usuario as u')
            ->join('rol_usuario as ru', 'ru.id_usuario', '=', 'u.id_usuario')
            ->join('rol as r', 'r.id_rol', '=', 'ru.id_rol')
            ->where('r.nombre_rol', Rol::NOMBRE_AUXILIAR)
            ->where('u.estado_cuenta', '!=', EstadoCuenta::Eliminada->value)
            ->where($columna, (int) $viewer->id_usuario)
            ->where('u.id_usuario', '!=', (int) $viewer->id_usuario)
            ->orderBy('u.apellido')
            ->orderBy('u.nombre_usuario')
            ->get([
                'u.id_usuario', 'u.cod_sis', 'u.nombre_usuario', 'u.nombre', 'u.apellido',
                'u.correo', 'u.carnet', 'u.tipo_auxiliar',
            ]);
    }
}

<?php

/**
 * @file    DesvincularAuxiliarService.php
 * @author  Alisson D. Alvarado <alvaradoalissondalet@gmail.com>
 * @created 2026-11-27
 * @description Retira asignaciones de auxiliares sin eliminar sus cuentas.
 * @changelog
 * - 2026-11-27  [Alisson D. Alvarado]  feat: desvincular cursos y exámenes individuales.
 */

namespace App\Services\Auxiliar;

use App\Enums\CursoEstado;
use App\Models\Rol;
use Illuminate\Support\Facades\DB;

/** Agrupa las asignaciones y reutiliza las comprobaciones de propiedad existentes. */
class DesvincularAuxiliarService
{
    /**
     * Lista auxiliares y asignaciones relacionadas con cursos activos del docente.
     *
     * @param int $idDocente Docente autenticado.
     * @return array Lista de auxiliares con cursos y exámenes.
     * @author Alisson D. Alvarado <alvaradoalissondalet@gmail.com>
     */
    public function listar(int $idDocente): array
    {
        $auxiliares = DB::table('usuario as u')
            ->join('rol_usuario as ru', 'ru.id_usuario', '=', 'u.id_usuario')
            ->join('rol as r', 'r.id_rol', '=', 'ru.id_rol')
            ->where('r.nombre_rol', Rol::NOMBRE_AUXILIAR)
            ->select('u.id_usuario', 'u.nombre', 'u.nombre_usuario', 'u.apellido', 'u.cod_sis')
            ->orderBy('u.apellido')->orderBy('u.nombre_usuario')->get();

        $cursos = DB::table('auxiliar_curso as ac')
            ->join('curso as c', 'c.id_curso', '=', 'ac.id_curso')
            ->where('c.sis_doc', $idDocente)->where('c.estado', CursoEstado::EnCurso->value)
            ->where('ac.estado', AccesoAuxiliarService::ESTADO_ACTIVO)
            ->select('ac.id_auxiliar', 'c.id_curso', 'c.nombre_curso')->get()->groupBy('id_auxiliar');

        $examenes = DB::table('auxiliar_examen as ae')
            ->join('examen as e', 'e.id_examen', '=', 'ae.id_examen')
            ->join('examen_curso as ec', 'ec.id_examen', '=', 'e.id_examen')
            ->join('curso as c', 'c.id_curso', '=', 'ec.id_curso')
            ->leftJoin('tipo_examen as t', 't.id_tipo_examen', '=', 'e.tipo_examen')
            ->where('c.sis_doc', $idDocente)->where('c.estado', CursoEstado::EnCurso->value)
            ->select('ae.id_auxiliar', 'e.id_examen', 'e.nombre_examen', 'e.fecha', 'c.nombre_curso', 't.nombre_tipo_examen')
            ->get()->groupBy('id_auxiliar');

        return $auxiliares->map(function (object $auxiliar) use ($cursos, $examenes): array {
            $nombre = trim((string) ($auxiliar->nombre ?: $auxiliar->nombre_usuario).' '.(string) $auxiliar->apellido);

            return [
                'id' => (int) $auxiliar->id_usuario,
                'nombre' => $nombre,
                'sis' => (string) $auxiliar->cod_sis,
                'cursos' => $cursos->get($auxiliar->id_usuario, collect())
                    ->map(fn (object $curso): array => ['id' => (int) $curso->id_curso, 'nombre' => $curso->nombre_curso])
                    ->values()->all(),
                'examenes' => $examenes->get($auxiliar->id_usuario, collect())->groupBy('id_examen')
                    ->map(function ($filas): array {
                        $examen = $filas->first();
                        $nombre = $examen->nombre_tipo_examen ?: ($examen->nombre_examen ?: 'Examen');

                        return [
                            'id' => (int) $examen->id_examen,
                            'nombre' => $nombre.' - '.$filas->pluck('nombre_curso')->unique()->join(', ')
                                .($examen->fecha ? ' · '.$examen->fecha : ''),
                        ];
                    })->values()->all(),
            ];
        })->all();
    }

    /**
     * Retira el curso y sus asignaciones directas a exámenes en una transacción.
     *
     * @param int $idDocente Propietario del curso.
     * @param int $idAuxiliar Auxiliar seleccionado.
     * @param int $idCurso Curso seleccionado.
     * @return void
     * @throws \Illuminate\Validation\ValidationException Si la asignación no es válida.
     * @author Alisson D. Alvarado <alvaradoalissondalet@gmail.com>
     */
    public function quitarCurso(int $idDocente, int $idAuxiliar, int $idCurso): void
    {
        DB::transaction(function () use ($idDocente, $idAuxiliar, $idCurso): void {
            $asignaciones = app(AsignarAuxiliarService::class);
            $asignaciones->quitarCurso($idDocente, $idAuxiliar, $idCurso);

            // Una asignación directa al examen también permitiría el acceso tras la baja del curso.
            $ids = DB::table('auxiliar_examen as ae')
                ->join('examen_curso as ec', 'ec.id_examen', '=', 'ae.id_examen')
                ->where('ae.id_auxiliar', $idAuxiliar)->where('ec.id_curso', $idCurso)
                ->distinct()->pluck('ae.id_examen');

            foreach ($ids as $idExamen) {
                $asignaciones->quitarExamen($idDocente, $idAuxiliar, (int) $idExamen);
            }
        });
    }
}

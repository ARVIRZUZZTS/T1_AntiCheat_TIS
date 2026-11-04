<?php

/**
 * @file    RegistrarCursoService.php
 *
 * @author  Valery D. Ortuno P <valerydariana98@gmail.com>
 *
 * @created 2026-10-24
 *
 * @updated 2026-10-24
 * @updated 2026-11-04
 *
 * @description
 * Da de alta un curso: la fila de `curso` (activo, con la fecha de hoy y a
 * nombre del docente) y su gestión con su año en `curso_tipo_gestion`, en una
 * sola transacción. Sigue el mismo patrón que RegistrarExamenService: el id no
 * tiene secuencia ni default, así que se calcula con max(id)+1 con la tabla
 * bloqueada para que dos altas simultáneas no se queden con el mismo.
 *
 * No se crea dos veces el mismo curso: misma materia y grupo (sin distinguir
 * mayúsculas) en la misma gestión y año, de cualquier docente, porque un grupo
 * de una materia es uno solo en cada gestión. Sí puede repetirse en otra
 * gestión del mismo año o en la misma gestión de otro año. Se revisa con la
 * tabla bloqueada, así que ni un doble clic en "Crear curso" lo duplica.
 *
 * @see  App\Http\Controllers\CursoController
 * @see  App\Http\Requests\StoreCursoRequest
 * @see  resources/views/pages/curso-crear.blade.php
 *
 * @changelog
 * - 2026-10-24  [Valery D. Ortuno P]  feat: creación inicial del servicio.
 * - 2026-11-04  [Valery D. Ortuno P]  fix: no se crea un curso que ya existe
 *   (misma materia, grupo, gestión y año).
 */

namespace App\Services\Curso;

use App\Enums\CursoEstado;
use App\Models\Curso;
use App\Support\DTO\GestionCursoDTO;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class RegistrarCursoService
{
    /** Aviso que se muestra arriba del formulario cuando el curso ya existe. */
    public const MENSAJE_DUPLICADO = 'Este curso ya existe en la gestion y el año elegidos.';

    /**
     * Crea el curso activo y lo asocia a su gestión (periodo y año).
     *
     * @param  string  $nombreCurso  Nombre ya concatenado: "Materia - Grupo X".
     * @param  int  $idDocente  id_usuario del docente autenticado.
     * @param  GestionCursoDTO  $gestion  Periodo y año elegidos en el formulario.
     *
     * @return Curso  Curso creado.
     *
     * @throws RuntimeException  Si no se pudo determinar el docente.
     * @throws InvalidArgumentException  Si ya existe un curso con ese nombre en esa gestión y año.
     *
     * @author Valery D. Ortuno P <valerydariana98@gmail.com>
     * @since  2026-10-24
     */
    public function ejecutar(string $nombreCurso, int $idDocente, GestionCursoDTO $gestion): Curso
    {
        if ($idDocente < 1) {
            throw new RuntimeException('No se pudo determinar el docente para crear el curso.');
        }

        return DB::transaction(function () use ($nombreCurso, $idDocente, $gestion): Curso {
            DB::statement('LOCK TABLE curso IN SHARE ROW EXCLUSIVE MODE');

            // Con la tabla bloqueada: dos altas iguales a la vez (un doble
            // clic) no pueden pasar las dos esta revisión.
            if ($this->existe($nombreCurso, $gestion)) {
                throw new InvalidArgumentException(self::MENSAJE_DUPLICADO);
            }

            $curso = new Curso;
            $curso->id_curso = ((int) Curso::query()->max('id_curso')) + 1;
            $curso->nombre_curso = $nombreCurso;
            $curso->sis_doc = $idDocente;
            $curso->fecha_creacion = Carbon::today()->toDateString();
            $curso->estado = CursoEstado::EnCurso->value;
            $curso->save();

            DB::table('curso_tipo_gestion')->insert([
                'id_curso' => $curso->id_curso,
                'id_tg' => $gestion->idTipoGestion,
                'anio' => $gestion->anio,
            ]);

            return $curso;
        });
    }

    /**
     * Si ya hay un curso con ese nombre (materia y grupo, sin distinguir
     * mayúsculas) en la misma gestión y el mismo año, de cualquier docente.
     *
     * @param  string  $nombreCurso  "Materia - Grupo X".
     * @param  GestionCursoDTO  $gestion  Periodo y año.
     *
     * @return bool  Si el curso ya existe.
     *
     * @author Valery D. Ortuno P <valerydariana98@gmail.com>
     * @since  2026-11-04
     */
    private function existe(string $nombreCurso, GestionCursoDTO $gestion): bool
    {
        return DB::table('curso as c')
            ->join('curso_tipo_gestion as ctg', 'ctg.id_curso', '=', 'c.id_curso')
            ->whereRaw('lower(c.nombre_curso) = lower(?)', [$nombreCurso])
            ->where('ctg.id_tg', $gestion->idTipoGestion)
            ->where('ctg.anio', $gestion->anio)
            ->exists();
    }
}

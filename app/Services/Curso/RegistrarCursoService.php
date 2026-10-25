<?php

/**
 * @file    RegistrarCursoService.php
 *
 * @author  Valery D. Ortuno P <valerydariana98@gmail.com>
 *
 * @created 2026-10-24
 *
 * @updated 2026-10-24
 *
 * @description
 * Da de alta un curso: la fila de `curso` (activo, con la fecha de hoy y a
 * nombre del docente) y su gestión con su año en `curso_tipo_gestion`, en una
 * sola transacción. Sigue el mismo patrón que RegistrarExamenService: el id no
 * tiene secuencia ni default, así que se calcula con max(id)+1 con la tabla
 * bloqueada para que dos altas simultáneas no se queden con el mismo.
 *
 * @see  App\Http\Controllers\CursoController
 * @see  App\Http\Requests\StoreCursoRequest
 * @see  resources/views/pages/curso-crear.blade.php
 *
 * @changelog
 * - 2026-10-24  [Valery D. Ortuno P]  feat: creación inicial del servicio.
 */

namespace App\Services\Curso;

use App\Enums\CursoEstado;
use App\Models\Curso;
use App\Support\DTO\GestionCursoDTO;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RegistrarCursoService
{
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
}

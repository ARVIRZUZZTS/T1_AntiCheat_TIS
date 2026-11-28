<?php

/**
 * @file    RegistrarCursoService.php
 *
 * @author  Valery D. Ortuno P <valerydariana98@gmail.com>
 *
 * @created 2026-10-24
 *
 * @updated 2026-11-04
 * @updated 2026-11-28
 *
 * @description
 * Da de alta un curso: la fila de `curso` (activo, con la fecha de hoy y a
 * nombre del docente) y su gestión con su año en `curso_tipo_gestion`, en una
 * sola transacción. El id se calcula con max(id)+1 con la tabla bloqueada.
 *
 * El curso ahora apunta a una `materia` (FK) y lleva `numero_grupo` (1-10); se
 * conserva `nombre_curso` autollenado "Materia - Grupo N" para las pantallas
 * que lo usan. No se crea dos veces el mismo curso (misma materia, grupo,
 * gestión y año, de cualquier docente); se revisa con la tabla bloqueada.
 *
 * Al crear, la materia se agrega a la relación del docente (sin tope).
 *
 * @see  App\Http\Controllers\CursoController
 * @see  App\Http\Requests\StoreCursoRequest
 * @see  App\Services\Docente\DocenteMateriaService
 * @see  resources/views/pages/curso-crear.blade.php
 *
 * @changelog
 * - 2026-10-24  [Valery D. Ortuno P]  feat: creación inicial del servicio.
 * - 2026-11-04  [Valery D. Ortuno P]  fix: no se crea un curso que ya existe.
 * - 2026-11-28  [Diego Tejerina]  feat: materia (FK) y numero_grupo; el nombre
 *   se arma del catálogo; el duplicado se mide por materia+grupo+gestión+año; la
 *   materia se suma a la relación del docente (máx. 5).
 */

namespace App\Services\Curso;

use App\Enums\CursoEstado;
use App\Models\Curso;
use App\Models\Materia;
use App\Services\Docente\DocenteMateriaService;
use App\Support\DTO\GestionCursoDTO;
use App\Support\NombreCurso;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class RegistrarCursoService
{
    public function __construct(
        private readonly DocenteMateriaService $docenteMaterias,
    ) {}

    /**
     * Crea el curso activo y lo asocia a su gestión (periodo y año).
     *
     * @param  int  $idMateria  Materia del catálogo elegida.
     * @param  int  $numeroGrupo  Grupo (1-10).
     * @param  int  $idDocente  id_usuario del docente autenticado.
     * @param  GestionCursoDTO  $gestion  Periodo y año elegidos.
     *
     * @return Curso  Curso creado.
     *
     * @throws RuntimeException  Si no se pudo determinar el docente o la materia.
     * @throws InvalidArgumentException  Si ya existe el curso (materia+grupo+gestión+año).
     *
     * @author Valery D. Ortuno P <valerydariana98@gmail.com>
     * @since  2026-10-24
     */
    public function ejecutar(int $idMateria, int $numeroGrupo, int $idDocente, GestionCursoDTO $gestion): Curso
    {
        if ($idDocente < 1) {
            throw new RuntimeException('No se pudo determinar el docente para crear el curso.');
        }

        return DB::transaction(function () use ($idMateria, $numeroGrupo, $idDocente, $gestion): Curso {
            DB::statement('LOCK TABLE curso IN SHARE ROW EXCLUSIVE MODE');

            $materia = Materia::query()->find($idMateria);
            if ($materia === null) {
                throw new RuntimeException('La materia elegida no existe.');
            }

            // La materia queda ligada al docente (sin tope de materias).
            $this->docenteMaterias->agregar($idDocente, $idMateria);

            if ($this->existe($idMateria, $numeroGrupo, $gestion)) {
                throw new InvalidArgumentException(
                    sprintf('Ya existe un curso de %s grupo %d en esta gestion.', $materia->nombre, $numeroGrupo),
                );
            }

            $curso = new Curso;
            $curso->id_curso = ((int) Curso::query()->max('id_curso')) + 1;
            $curso->nombre_curso = NombreCurso::formatear((string) $materia->nombre, (string) $numeroGrupo);
            $curso->sis_doc = $idDocente;
            $curso->id_materia = $idMateria;
            $curso->numero_grupo = $numeroGrupo;
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
     * Si ya hay un curso de esa materia y grupo en la misma gestión y año, de
     * cualquier docente (un grupo de una materia es uno solo por gestión).
     *
     * @param  int  $idMateria  Materia.
     * @param  int  $numeroGrupo  Grupo.
     * @param  GestionCursoDTO  $gestion  Periodo y año.
     *
     * @return bool  Si el curso ya existe.
     *
     * @author Diego Tejerina <josediegotejerinamolina@gmail.com>
     * @since  2026-11-28
     */
    private function existe(int $idMateria, int $numeroGrupo, GestionCursoDTO $gestion): bool
    {
        return DB::table('curso as c')
            ->join('curso_tipo_gestion as ctg', 'ctg.id_curso', '=', 'c.id_curso')
            ->where('c.id_materia', $idMateria)
            ->where('c.numero_grupo', $numeroGrupo)
            ->where('ctg.id_tg', $gestion->idTipoGestion)
            ->where('ctg.anio', $gestion->anio)
            ->exists();
    }
}

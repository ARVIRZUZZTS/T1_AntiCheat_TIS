<?php

/**
 * @file    ActualizarCursoService.php
 *
 * @author  Equipo T1
 *
 * @created 2026-11-21
 *
 * @updated 2026-11-28
 *
 * @description
 * Edita un curso. La materia escrita se resuelve contra el catálogo por su
 * nombre normalizado (sin tildes ni mayúsculas); si no existe, se crea con
 * origen "agregada por usuario". Se fijan `id_materia` y `numero_grupo` (1-10) y
 * se reescribe `nombre_curso` como "Materia - Grupo N". No se crea un duplicado
 * (misma materia, grupo, gestión y año, de otro curso).
 *
 * @changelog
 * - 2026-11-21  [Equipo T1]  feat: edición de curso.
 * - 2026-11-28  [Diego Tejerina]  feat: la edición fija materia (FK) y grupo
 *   numérico, y arma el nombre desde el catálogo.
 *
 * @see  App\Http\Requests\UpdateCursoRequest
 */

namespace App\Services\Curso;

use App\Exceptions\CursoDuplicadoException;
use App\Models\Curso;
use App\Models\Materia;
use App\Support\DTO\ActualizarCursoDTO;
use App\Support\NombreCurso;
use App\Support\Validadores;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class ActualizarCursoService
{
    public function ejecutar(Curso $curso, ActualizarCursoDTO $datos): Curso
    {
        try {
            return DB::transaction(function () use ($curso, $datos): Curso {
                DB::statement('LOCK TABLE curso IN SHARE ROW EXCLUSIVE MODE');

                $materia = $this->resolverMateria($datos->materia);
                $numeroGrupo = (int) $datos->grupo;
                $nombreCurso = NombreCurso::formatear((string) $materia->nombre, (string) $numeroGrupo);

                if ($this->existeDuplicado($curso, (int) $materia->id_materia, $numeroGrupo, $datos)) {
                    throw new CursoDuplicadoException($datos);
                }

                Curso::query()
                    ->where('id_curso', $curso->id_curso)
                    ->update([
                        'nombre_curso' => $nombreCurso,
                        'id_materia' => $materia->id_materia,
                        'numero_grupo' => $numeroGrupo,
                    ]);

                DB::table('curso_tipo_gestion')
                    ->where('id_curso', $curso->id_curso)
                    ->update([
                        'id_tg' => $datos->gestion->idTipoGestion,
                        'anio' => $datos->gestion->anio,
                    ]);

                return $curso->refresh();
            });
        } catch (QueryException $error) {
            if ((string) $error->getCode() === '23505') {
                throw new CursoDuplicadoException($datos, $error);
            }

            throw $error;
        }
    }

    /**
     * Ubica la materia escrita en el catálogo (por nombre normalizado) o la crea
     * con origen "agregada por usuario".
     */
    private function resolverMateria(string $texto): Materia
    {
        $normalizado = Validadores::normalizarNombreMateria($texto);

        return Materia::query()->firstOrCreate(
            ['nombre_normalizado' => $normalizado],
            [
                'nombre' => Validadores::normalizarMateria($texto),
                'codigo' => null,
                'semestre' => null,
                'origen' => Materia::ORIGEN_USUARIO,
                'creado_por' => auth()->id(),
                'fecha_creacion' => now(),
            ],
        );
    }

    private function existeDuplicado(Curso $curso, int $idMateria, int $numeroGrupo, ActualizarCursoDTO $datos): bool
    {
        return DB::table('curso as c')
            ->join('curso_tipo_gestion as ctg', 'ctg.id_curso', '=', 'c.id_curso')
            ->where('c.id_curso', '!=', $curso->id_curso)
            ->where('c.id_materia', $idMateria)
            ->where('c.numero_grupo', $numeroGrupo)
            ->where('ctg.id_tg', $datos->gestion->idTipoGestion)
            ->where('ctg.anio', $datos->gestion->anio)
            ->exists();
    }
}

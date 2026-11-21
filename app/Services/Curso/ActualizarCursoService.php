<?php

namespace App\Services\Curso;

use App\Exceptions\CursoDuplicadoException;
use App\Models\Curso;
use App\Support\DTO\ActualizarCursoDTO;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class ActualizarCursoService
{
    public function ejecutar(Curso $curso, ActualizarCursoDTO $datos): Curso
    {
        try {
            return DB::transaction(function () use ($curso, $datos): Curso {
                DB::statement('LOCK TABLE curso IN SHARE ROW EXCLUSIVE MODE');

                if ($this->existeDuplicado($curso, $datos)) {
                    throw new CursoDuplicadoException($datos);
                }

                Curso::query()
                    ->where('id_curso', $curso->id_curso)
                    ->update(['nombre_curso' => $datos->nombreCurso()]);

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

    public function existeDuplicado(Curso $curso, ActualizarCursoDTO $datos): bool
    {
        return DB::table('curso as c')
            ->join('curso_tipo_gestion as ctg', 'ctg.id_curso', '=', 'c.id_curso')
            ->where('c.id_curso', '!=', $curso->id_curso)
            ->whereRaw('lower(c.nombre_curso) = lower(?)', [$datos->nombreCurso()])
            ->where('ctg.id_tg', $datos->gestion->idTipoGestion)
            ->where('ctg.anio', $datos->gestion->anio)
            ->exists();
    }
}

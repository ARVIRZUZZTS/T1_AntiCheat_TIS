<?php

namespace App\Services\Curso;

use App\Models\Curso;
use App\Support\DTO\ActualizarCursoDTO;
use Illuminate\Support\Facades\DB;

class ActualizarCursoService
{
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

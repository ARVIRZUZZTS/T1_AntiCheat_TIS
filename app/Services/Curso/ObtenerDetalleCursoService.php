<?php

namespace App\Services\Curso;

use App\Models\Curso;
use App\Support\DTO\DetalleCursoDTO;
use App\Support\NombreCurso;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ObtenerDetalleCursoService
{
    public function ejecutar(Curso $curso): DetalleCursoDTO
    {
        $gestion = DB::table('curso_tipo_gestion as ctg')
            ->join('tipo_gestion as tg', 'tg.id_tipo_gestion', '=', 'ctg.id_tg')
            ->where('ctg.id_curso', $curso->id_curso)
            ->select('ctg.id_tg', 'ctg.anio', 'tg.nombre_tipo_gestion')
            ->first();

        abort_if($gestion === null, 404);

        return new DetalleCursoDTO(
            idCurso: (int) $curso->id_curso,
            materia: NombreCurso::materia($curso->nombre_curso),
            grupo: NombreCurso::grupo($curso->nombre_curso) ?? '',
            gestion: (int) $gestion->id_tg,
            gestionNombre: Str::ucfirst((string) $gestion->nombre_tipo_gestion),
            anio: (int) $gestion->anio,
            nombre: $curso->nombre_curso,
        );
    }
}

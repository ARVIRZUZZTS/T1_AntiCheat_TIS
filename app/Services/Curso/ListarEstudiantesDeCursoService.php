<?php

namespace App\Services\Curso;

use App\Models\Curso;
use Illuminate\Database\Eloquent\Collection;

class ListarEstudiantesDeCursoService
{
    public function ejecutar(int $idCurso): Collection
    {
        return Curso::with('estudiantes')
            ->findOrFail($idCurso)
            ->estudiantes;
    }
}
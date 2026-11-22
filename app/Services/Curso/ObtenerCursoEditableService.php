<?php

namespace App\Services\Curso;

use App\Models\Curso;
use App\Models\Usuario;
use Illuminate\Support\Facades\Gate;

class ObtenerCursoEditableService
{
    public function ejecutar(int $idCurso, Usuario $docente): Curso
    {
        $curso = Curso::query()->findOrFail($idCurso);

        Gate::forUser($docente)->authorize('editar', $curso);

        return $curso;
    }
}

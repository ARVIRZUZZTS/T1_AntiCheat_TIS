<?php

/**
 * @file    RestaurarCursoService.php
 *
 * @author  Equipo T1
 *
 * @created 2026-11-20
 *
 * @description
 * Restaura un curso en estado `Archivado`. Solo el docente propietario del curso
 * (`curso.sis_doc`) puede realizar la acción. Al restaurar, el curso pasa a
 * estado `EnCurso` y vuelve a ser editable, conservando intactas sus relaciones
 * históricas (estudiantes, exámenes, incidencias).
 *
 * @changelog
 * - 2026-11-20  [Equipo T1]  feat: creación inicial del servicio (HU #322 · Task #325).
 */
declare(strict_types=1);

namespace App\Services\Curso;

use App\Enums\CursoEstado;
use App\Models\Curso;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class RestaurarCursoService
{
    public const MENSAJE_EXITO = 'Curso restaurado con éxito';

    /**
     * @throws ValidationException Si el curso no se encuentra en estado `Archivado`.
     */
    public function ejecutar(Usuario $docente, Curso $curso): Curso
    {
        Gate::forUser($docente)->authorize('restaurar', $curso);

        return DB::transaction(function () use ($curso): Curso {
            $bloqueado = Curso::query()
                ->whereKey($curso->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($bloqueado->estado !== CursoEstado::Archivado->value) {
                throw ValidationException::withMessages([
                    'curso' => 'Solo se pueden restaurar cursos en estado Archivado.',
                ]);
            }

            $bloqueado->estado = CursoEstado::EnCurso->value;
            $bloqueado->save();

            return $bloqueado->fresh();
        });
    }
}

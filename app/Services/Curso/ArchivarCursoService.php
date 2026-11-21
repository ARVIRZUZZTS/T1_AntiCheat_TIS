<?php

/**
 * @file    ArchivarCursoService.php
 *
 * @author  Equipo T1
 *
 * @created 2026-11-20
 *
 * @description
 * Archiva un curso en estado `EnCurso`. Solo el docente propietario del curso
 * (`curso.sis_doc`) puede realizar la acción. Al archivar, el curso pasa a
 * estado `Archivado` y queda en modo estrictamente de solo lectura, conservando
 * intactas sus relaciones históricas (estudiantes, exámenes, incidencias).
 *
 * @changelog
 * - 2026-11-20  [Equipo T1]  feat: creación inicial del servicio (HU #321 · Task #323).
 */
declare(strict_types=1);

namespace App\Services\Curso;

use App\Enums\CursoEstado;
use App\Models\Curso;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ArchivarCursoService
{
    public const MENSAJE_EXITO = 'Curso archivado con éxito';

    /**
     * @throws ValidationException Si el curso no se encuentra en estado `EnCurso`.
     */
    public function ejecutar(Usuario $docente, Curso $curso): Curso
    {
        Gate::forUser($docente)->authorize('archivar', $curso);

        return DB::transaction(function () use ($curso): Curso {
            $bloqueado = Curso::query()
                ->whereKey($curso->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($bloqueado->estado !== CursoEstado::EnCurso->value) {
                throw ValidationException::withMessages([
                    'curso' => 'Solo se pueden archivar cursos en estado En curso.',
                ]);
            }

            $bloqueado->estado = CursoEstado::Archivado->value;
            $bloqueado->save();

            return $bloqueado->fresh();
        });
    }
}

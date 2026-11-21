<?php

/**
 * @file    AsegurarCursoEditableService.php
 *
 * @author  Equipo T1
 *
 * @created 2026-11-20
 *
 * @description
 * Bloqueo centralizado y reutilizable para cursos archivados. Un curso en
 * estado `Archivado` es estrictamente de solo lectura: ninguna operación de
 * escritura o modificación puede ejecutarse sobre el curso ni sobre sus
 * recursos asociados (exámenes, estudiantes, ingresos, incidencias, etc.).
 *
 * Para exámenes compartidos, la escritura se bloquea si cualquiera de los
 * cursos vinculados al examen está archivado.
 *
 * @changelog
 * - 2026-11-20  [Equipo T1]  feat: creación inicial del servicio (HU #321 · Task #323).
 */
declare(strict_types=1);

namespace App\Services\Curso;

use App\Models\Curso;
use App\Models\Examen;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class AsegurarCursoEditableService
{
    public const MENSAJE_CURSO_ARCHIVADO = 'No se pueden realizar modificaciones en un curso archivado.';

    public const MENSAJE_EXAMEN_CON_CURSO_ARCHIVADO = 'No se pueden realizar modificaciones en un examen vinculado a un curso archivado.';

    /**
     * @throws ValidationException Si el curso está archivado.
     */
    public function asegurarCursoEditable(Curso $curso, ?string $campo = null, ?string $mensaje = null): void
    {
        if ($curso->estaArchivado()) {
            $this->rechazar($campo, $mensaje ?? self::MENSAJE_CURSO_ARCHIVADO);
        }
    }

    /**
     * @param  iterable<int, Curso>|Collection<int, Curso>  $cursos
     *
     * @throws ValidationException Si alguno de los cursos está archivado.
     */
    public function asegurarCursosEditables(iterable $cursos, ?string $campo = null, ?string $mensaje = null): void
    {
        foreach ($cursos as $curso) {
            if ($curso->estaArchivado()) {
                $this->rechazar($campo, $mensaje ?? self::MENSAJE_CURSO_ARCHIVADO);
            }
        }
    }

    /**
     * @throws ValidationException Si el examen tiene algún curso archivado vinculado.
     */
    public function asegurarExamenEditable(Examen $examen, ?string $campo = null, ?string $mensaje = null): void
    {
        $examen->loadMissing('cursos');

        if ($examen->cursos->contains(fn (Curso $curso): bool => $curso->estaArchivado())) {
            $this->rechazar($campo, $mensaje ?? self::MENSAJE_EXAMEN_CON_CURSO_ARCHIVADO);
        }
    }

    /**
     * Indica si el curso permite operaciones de escritura.
     */
    public function cursoEditable(Curso $curso): bool
    {
        return ! $curso->estaArchivado();
    }

    /**
     * Indica si el examen permite operaciones de escritura (ningún curso vinculado archivado).
     */
    public function examenEditable(Examen $examen): bool
    {
        $examen->loadMissing('cursos');

        return ! $examen->cursos->contains(fn (Curso $curso): bool => $curso->estaArchivado());
    }

    /**
     * @throws ValidationException
     */
    private function rechazar(?string $campo, string $mensaje): never
    {
        $clave = $campo ?? 'curso';

        throw ValidationException::withMessages([
            $clave => $mensaje,
        ]);
    }
}

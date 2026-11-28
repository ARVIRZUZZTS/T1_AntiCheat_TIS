<?php

/**
 * @file    Curso.php
 *
 * @description
 * Modelo Eloquent del curso. El estado se almacena como string compatible con
 * el enum `curso_estado` de PostgreSQL/Supabase.
 *
 * @changelog
 * - 2026-11-20  [Equipo T1]  feat: helpers de estado y scopes para cursos
 *   archivados (HU #321 · Task #323).
 */

namespace App\Models;

use App\Enums\CursoEstado;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id_curso
 * @property string $nombre_curso
 * @property int $sis_doc
 * @property ?int $id_ambiente
 * @property ?string $fecha_creacion
 * @property string $estado
 * @property ?int $id_materia Materia del curso (FK a `materia`); null en cursos aún sin migrar.
 * @property ?int $numero_grupo Grupo del curso (1-10); null si no se pudo deducir.
 */
class Curso extends Model
{
    protected $table = 'curso';

    protected $primaryKey = 'id_curso';

    public $timestamps = false;

    public $incrementing = false;

    protected $keyType = 'int';

    protected $fillable = [
        'id_curso',
        'nombre_curso',
        'sis_doc',
        'id_ambiente',
        'fecha_creacion',
        'estado',
        'id_materia',
        'numero_grupo',
    ];

    /** @return BelongsToMany<Estudiante, $this> */
    public function estudiantes(): BelongsToMany
    {
        return $this->belongsToMany(
            Estudiante::class,
            'estudiante_curso',
            'id_curso',
            'sis_estudiante'
        );
    }

    /** @return BelongsToMany<Examen, $this> */
    public function examenes(): BelongsToMany
    {
        return $this->belongsToMany(
            Examen::class,
            'examen_curso',
            'id_curso',
            'id_examen'
        );
    }

    /** @return BelongsTo<Usuario, $this> */
    public function docente(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'sis_doc', 'id_usuario');
    }

    /** @return BelongsTo<Ambiente, $this> */
    public function ambiente(): BelongsTo
    {
        return $this->belongsTo(Ambiente::class, 'id_ambiente');
    }

    /** @return BelongsTo<Materia, $this> */
    public function materia(): BelongsTo
    {
        return $this->belongsTo(Materia::class, 'id_materia', 'id_materia');
    }

    public function estaArchivado(): bool
    {
        return $this->estado === CursoEstado::Archivado->value;
    }

    public function estaEnCurso(): bool
    {
        return $this->estado === CursoEstado::EnCurso->value;
    }

    public function estaFinalizado(): bool
    {
        return $this->estado === CursoEstado::Finalizo->value;
    }

    /**
     * Un curso archivado es estrictamente de solo lectura; los estados
     * `EnCurso` y `Finalizo` conservan sus reglas existentes.
     */
    public function permiteEscritura(): bool
    {
        return ! $this->estaArchivado();
    }

    /** @param Builder<Curso> $query */
    public function scopeArchivados(Builder $query): Builder
    {
        return $query->where('estado', CursoEstado::Archivado->value);
    }

    /** @param Builder<Curso> $query */
    public function scopeNoArchivados(Builder $query): Builder
    {
        return $query->where('estado', '!=', CursoEstado::Archivado->value);
    }

    /**
     * El examen "actual" del curso: el de fecha más reciente entre los
     * ligados al curso. Compartido por los servicios que necesitan
     * resolver a qué examen se refiere una acción (listado con filtros,
     * cambio de estado, etc.) para no duplicar el criterio en cada uno.
     */
    public function examenActual(): ?Examen
    {
        return $this->examenes()
            ->orderByDesc('fecha')
            ->orderByDesc('id_examen')
            ->first();
    }
}

<?php

/**
 * @file    Examen.php
 *
 * @author  Equipo T1 <dev@techone.local>
 *
 * @created 2026-09-24
 *
 * @updated 2026-09-24
 *
 * @description
 * Modelo de la tabla `examen`: mapeo del examen y sus relaciones de
 * persistencia (cursos y estados de estudiantes).
 *
 * @changelog
 * - 2026-09-24  [T1]  feat: creación inicial del modelo.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id_examen
 * @property ?string $fecha
 * @property ?string $hora_inicio
 * @property ?string $hora_fin
 * @property ?int $duracion
 * @property int $creador
 * @property int $tipo_examen
 */
class Examen extends Model
{
    protected $table = 'examen';

    protected $primaryKey = 'id_examen';

    public $timestamps = false;

    public $incrementing = false;

    protected $keyType = 'int';

    protected $fillable = [
        'id_examen',
        'fecha',
        'hora_inicio',
        'hora_fin',
        'duracion',
        'creador',
        'tipo_examen',
    ];

    /** @return BelongsToMany<Curso, $this> */
    public function cursos(): BelongsToMany
    {
        return $this->belongsToMany(
            Curso::class,
            'examen_curso',
            'id_examen',
            'id_curso'
        );
    }

    /** @return HasMany<EstudianteExamen, $this> */
    public function estudianteExamenes(): HasMany
    {
        return $this->hasMany(EstudianteExamen::class, 'id_examen');
    }
}

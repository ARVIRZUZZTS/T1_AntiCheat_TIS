<?php

/**
 * @file    Examen.php
 *
 * @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
 *
 * @created 2026-09-24
 *
 * @updated 2026-09-26
 *
 * @description
 * Modelo de la tabla `examen`: mapeo del examen y sus relaciones de
 * persistencia (cursos, estados de estudiantes y registros de asistencia).
 *
 * @changelog
 * - 2026-09-24  [T1]         feat: creación inicial del modelo.
 * - 2026-09-25  [OchoaCesar] feat: agregar relación registrosAsistencia().
 * - 2026-09-26  [T1]         fix: resolver conflicto de merge sin resolver
 *   dejado en dev por el commit e4ea2fd (marcadores <<<<<<< sin quitar).
 *
 * @see  EstudianteExamen
 * @see  RegistroAsistencia
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

    /**
     * Alias de estudianteExamenes(), con el nombre que ya usa la feature
     * #28 (lista de estudiantes por materia). Se mantienen los dos nombres
     * para no romper ese código ya mergeado a dev; unificar en una limpieza
     * posterior.
     *
     * @return HasMany<EstudianteExamen, $this>
     */
    public function inscripciones(): HasMany
    {
        return $this->hasMany(EstudianteExamen::class, 'id_examen');
    }

    /** @return HasMany<RegistroAsistencia, $this> */
    public function registrosAsistencia(): HasMany
    {
        return $this->hasMany(RegistroAsistencia::class, 'id_examen');
    }
}

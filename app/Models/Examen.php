<?php

/**
 * @file    Examen.php
 *
 * @author  OchoaCesar <cesareduardonick@gmail.com>
 *
 * @created 2026-09-25
 *
 * @updated 2026-09-26
 *
 * @description
 * Modelo Eloquent de la tabla `examen`: representa un examen masivo con su
 * ventana horaria (fecha, hora_inicio, hora_fin, duracion) y sus relaciones de
 * persistencia: cursos asociados, inscripciones (estudiante_examen) y
 * registros de asistencia.
 *
 * @changelog
 * - 2026-09-25  [OchoaCesar]    feat: creación inicial del modelo.
 * - 2026-09-26  [Diego Tejerina] feat: anotaciones @property y relación cursos().
 *
 * @see  Curso
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

    /** @return HasMany<EstudianteExamen, $this> */
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

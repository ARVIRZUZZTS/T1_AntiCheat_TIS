<?php

/**
 * @file    Examen.php
 *
<<<<<<< HEAD
 * @author  OchoaCesar <cesareduardonick@gmail.com>
 *
 * @created 2026-09-25
 *
 * @updated 2026-09-25
 *
 * @description
 * Modelo Eloquent de la tabla `examen`. Representa un examen con su ventana
 * horaria (fecha, hora_inicio, hora_fin, duracion) y expone sus inscripciones
 * (estudiante_examen) y los registros de asistencia asociados.
 *
 * @changelog
 * - 2026-09-25  [OchoaCesar]  feat:  creación inicial del modelo.
 *
 * @see  EstudianteExamen
 * @see  RegistroAsistencia
=======
 * @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
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
>>>>>>> 62d652c84c74e17637946104814b5ef94b449701
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
<<<<<<< HEAD
use Illuminate\Database\Eloquent\Relations\HasMany;

=======
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
>>>>>>> 62d652c84c74e17637946104814b5ef94b449701
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

<<<<<<< HEAD
    public function inscripciones(): HasMany
    {
        return $this->hasMany(EstudianteExamen::class, 'id_examen');
    }

    public function registrosAsistencia(): HasMany
    {
        return $this->hasMany(RegistroAsistencia::class, 'id_examen');
    }
=======
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
>>>>>>> 62d652c84c74e17637946104814b5ef94b449701
}

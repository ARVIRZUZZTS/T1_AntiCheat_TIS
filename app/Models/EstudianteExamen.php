<?php

/**
 * @file    EstudianteExamen.php
 *
<<<<<<< HEAD
 * @author  OchoaCesar <cesareduardonick@gmail.com>
 *
 * @created 2026-09-25
 *
 * @updated 2026-09-25
 *
 * @description
 * Modelo Eloquent de la tabla `estudiante_examen`. Representa la inscripción
 * de un estudiante a un examen con su estado de habilitación
 * (`habilitado` / `deshabilitado`) y el motivo de la deshabilitación cuando
 * corresponde.
 *
 * @changelog
 * - 2026-09-25  [OchoaCesar]  feat:  creación inicial del modelo.
 *
 * @see  Estudiante
 * @see  Examen
=======
 * @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
 *
 * @created 2026-09-24
 *
 * @updated 2026-09-24
 *
 * @description
 * Modelo de la tabla `estudiante_examen`: mapea el estado de habilitación
 * de un estudiante en un examen (estado + motivo).
 *
 * @changelog
 * - 2026-09-24  [T1]  feat: creación inicial del modelo.
>>>>>>> 62d652c84c74e17637946104814b5ef94b449701
 */

namespace App\Models;

<<<<<<< HEAD
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

=======
use App\Enums\EstadoEstudianteExamen;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id_estudiante_examen
 * @property string $sis_estudiante
 * @property int $id_examen
 * @property EstadoEstudianteExamen $estado
 * @property ?string $motivo
 */
>>>>>>> 62d652c84c74e17637946104814b5ef94b449701
class EstudianteExamen extends Model
{
    protected $table = 'estudiante_examen';

    protected $primaryKey = 'id_estudiante_examen';

    public $timestamps = false;

    public $incrementing = false;

    protected $keyType = 'int';

    protected $fillable = [
        'id_estudiante_examen',
        'sis_estudiante',
        'id_examen',
        'estado',
        'motivo',
    ];

<<<<<<< HEAD
=======
    protected function casts(): array
    {
        return [
            'estado' => EstadoEstudianteExamen::class,
        ];
    }

    /** @return BelongsTo<Estudiante, $this> */
>>>>>>> 62d652c84c74e17637946104814b5ef94b449701
    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Estudiante::class, 'sis_estudiante');
    }

<<<<<<< HEAD
=======
    /** @return BelongsTo<Examen, $this> */
>>>>>>> 62d652c84c74e17637946104814b5ef94b449701
    public function examen(): BelongsTo
    {
        return $this->belongsTo(Examen::class, 'id_examen');
    }
}

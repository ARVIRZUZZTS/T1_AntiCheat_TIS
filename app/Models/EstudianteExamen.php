<?php

/**
 * @file    EstudianteExamen.php
 *
 * @author  OchoaCesar <cesareduardonick@gmail.com>
 *
 * @created 2026-09-25
 *
 * @updated 2026-09-26
 *
 * @description
 * Modelo Eloquent de la tabla `estudiante_examen`: mapea la inscripción de un
 * estudiante en un examen con su estado de habilitación (`habilitado` /
 * `deshabilitado`, como enum) y el motivo de la deshabilitación cuando
 * corresponde.
 *
 * @changelog
 * - 2026-09-25  [OchoaCesar]    feat: creación inicial del modelo.
 * - 2026-09-26  [Diego Tejerina] feat: cast del estado a enum y anotaciones @property.
 *
 * @see  Estudiante
 * @see  Examen
 */

namespace App\Models;

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

    protected function casts(): array
    {
        return [
            'estado' => EstadoEstudianteExamen::class,
        ];
    }

    /** @return BelongsTo<Estudiante, $this> */
    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Estudiante::class, 'sis_estudiante');
    }

    /** @return BelongsTo<Examen, $this> */
    public function examen(): BelongsTo
    {
        return $this->belongsTo(Examen::class, 'id_examen');
    }
}

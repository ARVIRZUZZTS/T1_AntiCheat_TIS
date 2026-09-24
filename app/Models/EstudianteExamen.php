<?php

/**
 * @file    EstudianteExamen.php
 *
 * @author  Equipo T1 <dev@techone.local>
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

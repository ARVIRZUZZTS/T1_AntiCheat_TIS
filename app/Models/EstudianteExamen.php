<?php

/**
 * @file    EstudianteExamen.php
 *
 * @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
 *
 * @created 2026-09-24
 *
 * @updated 2026-09-26
 *
 * @description
 * Modelo de la tabla `estudiante_examen`: mapea el estado de habilitación
 * de un estudiante en un examen (estado + motivo), y quién hizo el último
 * cambio y cuándo (modificado_por/fecha_modificacion).
 *
 * @changelog
 * - 2026-09-24  [T1]  feat: creación inicial del modelo.
 * - 2026-09-26  [T1]  fix: resolver conflicto de merge sin resolver dejado en
 *   dev por el commit e4ea2fd (marcadores <<<<<<< sin quitar).
 * - 2026-09-26  [T1]  feat: agregar modificado_por y fecha_modificacion (#27).
 */

namespace App\Models;

use App\Enums\EstadoEstudianteExamen;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id_estudiante_examen
 * @property string $sis_estudiante
 * @property int $id_examen
 * @property EstadoEstudianteExamen $estado
 * @property ?string $motivo
 * @property ?int $modificado_por
 * @property ?Carbon $fecha_modificacion
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
        'modificado_por',
        'fecha_modificacion',
    ];

    protected function casts(): array
    {
        return [
            'estado' => EstadoEstudianteExamen::class,
            'fecha_modificacion' => 'datetime',
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

    /** @return BelongsTo<Usuario, $this> */
    public function modificadoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'modificado_por', 'id_usuario');
    }
}

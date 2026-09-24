<?php

/**
 * @file    RegistroAsistencia.php
 *
 * @author  Equipo T1 <dev@techone.local>
 *
 * @created 2026-09-24
 *
 * @updated 2026-09-24
 *
 * @description
 * Modelo de la tabla `registro_asistencia`: mapea el ingreso de un estudiante
 * a un examen. Es el puente entre el estudiante y la central de riesgos.
 *
 * @changelog
 * - 2026-09-24  [T1]  feat: creación inicial del modelo.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id_ingreso
 * @property ?string $hora_ingreso
 * @property int $id_examen
 * @property string $id_estudiante
 */
class RegistroAsistencia extends Model
{
    protected $table = 'registro_asistencia';

    protected $primaryKey = 'id_ingreso';

    public $timestamps = false;

    public $incrementing = false;

    protected $keyType = 'int';

    protected $fillable = [
        'id_ingreso',
        'hora_ingreso',
        'id_examen',
        'id_estudiante',
    ];

    /** @return BelongsTo<Estudiante, $this> */
    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Estudiante::class, 'id_estudiante', 'sis_estudiante');
    }

    /** @return BelongsTo<Examen, $this> */
    public function examen(): BelongsTo
    {
        return $this->belongsTo(Examen::class, 'id_examen');
    }

    /** @return HasMany<CentralRiesgo, $this> */
    public function centralRiesgos(): HasMany
    {
        return $this->hasMany(CentralRiesgo::class, 'id_ingreso');
    }
}

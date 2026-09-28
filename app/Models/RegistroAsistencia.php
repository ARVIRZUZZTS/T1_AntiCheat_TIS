<?php

/**
 * @file    RegistroAsistencia.php
 *
 * @author  OchoaCesar <cesareduardonick@gmail.com>
 *
 * @created 2026-09-25
 *
 * @updated 2026-09-26
 *
 * @description
 * Modelo Eloquent de la tabla `registro_asistencia`: mapea el ingreso de un
 * estudiante a un examen, con la hora de ingreso y el usuario que lo registró,
 * como puente entre el estudiante y la central de riesgos.
 *
 * @changelog
 * - 2026-09-25  [OchoaCesar]    feat: creación inicial del modelo.
 * - 2026-09-26  [Diego Tejerina] feat: anotaciones @property y relación centralRiesgos().
 *
 * @see  Estudiante
 * @see  Examen
 * @see  Usuario
 * @see  CentralRiesgo
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
 * @property ?int $id_registrador
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
        'id_registrador',
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

    /** @return BelongsTo<Usuario, $this> */
    public function registrador(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_registrador');
    }

    /** @return HasMany<CentralRiesgo, $this> */
    public function centralRiesgos(): HasMany
    {
        return $this->hasMany(CentralRiesgo::class, 'id_ingreso');
    }
}

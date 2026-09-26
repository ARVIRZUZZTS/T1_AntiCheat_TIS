<?php

/**
 * @file    RegistroAsistencia.php
 *
<<<<<<< HEAD
 * @author  OchoaCesar <cesareduardonick@gmail.com>
 *
 * @created 2026-09-25
 *
 * @updated 2026-09-25
 *
 * @description
 * Modelo Eloquent de la tabla `registro_asistencia`. Representa el registro de
 * ingreso de un estudiante a un examen, con la hora de ingreso y el usuario
 * que realizó el registro.
 *
 * @changelog
 * - 2026-09-25  [OchoaCesar]  feat:  creación inicial del modelo.
 *
 * @see  Estudiante
 * @see  Examen
 * @see  Usuario
=======
 * @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
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
>>>>>>> 62d652c84c74e17637946104814b5ef94b449701
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
<<<<<<< HEAD

=======
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id_ingreso
 * @property ?string $hora_ingreso
 * @property int $id_examen
 * @property string $id_estudiante
 */
>>>>>>> 62d652c84c74e17637946104814b5ef94b449701
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
<<<<<<< HEAD
        'id_registrador',
    ];

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Estudiante::class, 'id_estudiante');
    }

=======
    ];

    /** @return BelongsTo<Estudiante, $this> */
    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Estudiante::class, 'id_estudiante', 'sis_estudiante');
    }

    /** @return BelongsTo<Examen, $this> */
>>>>>>> 62d652c84c74e17637946104814b5ef94b449701
    public function examen(): BelongsTo
    {
        return $this->belongsTo(Examen::class, 'id_examen');
    }

<<<<<<< HEAD
    public function registrador(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_registrador');
=======
    /** @return HasMany<CentralRiesgo, $this> */
    public function centralRiesgos(): HasMany
    {
        return $this->hasMany(CentralRiesgo::class, 'id_ingreso');
>>>>>>> 62d652c84c74e17637946104814b5ef94b449701
    }
}

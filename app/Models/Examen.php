<?php

/**
 * @file    Examen.php
 *
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
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function inscripciones(): HasMany
    {
        return $this->hasMany(EstudianteExamen::class, 'id_examen');
    }

    public function registrosAsistencia(): HasMany
    {
        return $this->hasMany(RegistroAsistencia::class, 'id_examen');
    }
}

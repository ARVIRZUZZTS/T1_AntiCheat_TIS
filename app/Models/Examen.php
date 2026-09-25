<?php

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

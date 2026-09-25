<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Estudiante::class, 'id_estudiante');
    }

    public function examen(): BelongsTo
    {
        return $this->belongsTo(Examen::class, 'id_examen');
    }

    public function registrador(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_registrador');
    }
}

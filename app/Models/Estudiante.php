<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Estudiante extends Model
{
    protected $table = 'estudiante';
    protected $primaryKey = 'sis_estudiante';
    public $timestamps = false;
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'sis_estudiante',
        'nombre_estudiante',
        'apellido_estudiante',
        'carrera',
    ];

    public function cursos(): BelongsToMany
    {
        return $this->belongsToMany(
            Curso::class,
            'estudiante_curso',
            'sis_estudiante',
            'id_curso'
        );
    }
}
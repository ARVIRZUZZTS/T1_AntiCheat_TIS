<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $sis_estudiante
 * @property string $nombre_estudiante
 * @property string $apellido_estudiante
 * @property ?string $carrera
 */
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

    /** @return BelongsToMany<Curso, $this> */
    public function cursos(): BelongsToMany
    {
        return $this->belongsToMany(
            Curso::class,
            'estudiante_curso',
            'sis_estudiante',
            'id_curso'
        );
    }

    /** @return HasMany<EstudianteExamen, $this> */
    public function estudianteExamenes(): HasMany
    {
        return $this->hasMany(EstudianteExamen::class, 'sis_estudiante', 'sis_estudiante');
    }

    /** @return HasMany<RegistroAsistencia, $this> */
    public function registrosAsistencia(): HasMany
    {
        return $this->hasMany(RegistroAsistencia::class, 'id_estudiante', 'sis_estudiante');
    }
}

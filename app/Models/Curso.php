<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id_curso
 * @property string $nombre_curso
 * @property int $sis_doc
 * @property ?string $fecha_creacion
 * @property string $estado
 */
class Curso extends Model
{
    protected $table = 'curso';

    protected $primaryKey = 'id_curso';

    public $timestamps = false;

    public $incrementing = false;

    protected $keyType = 'int';

    protected $fillable = [
        'id_curso',
        'nombre_curso',
        'sis_doc',
        'fecha_creacion',
        'estado',
    ];

    /** @return BelongsToMany<Estudiante, $this> */
    public function estudiantes(): BelongsToMany
    {
        return $this->belongsToMany(
            Estudiante::class,
            'estudiante_curso',
            'id_curso',
            'sis_estudiante'
        );
    }

    /** @return BelongsToMany<Examen, $this> */
    public function examenes(): BelongsToMany
    {
        return $this->belongsToMany(
            Examen::class,
            'examen_curso',
            'id_curso',
            'id_examen'
        );
    }

    /** @return BelongsTo<Usuario, $this> */
    public function docente(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'sis_doc', 'id_usuario');
    }
}

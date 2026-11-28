<?php

/**
 * @file    Materia.php
 *
 * @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
 *
 * @created 2026-11-28
 *
 * @description
 * Catálogo de materias. `nombre_normalizado` (sin tildes ni mayúsculas) lleva un
 * índice único para no duplicar la misma materia escrita distinto; `origen`
 * distingue las del catálogo oficial de las que agrega un docente al vuelo.
 *
 * @changelog
 * - 2026-11-28  [Diego Tejerina]  feat: creación inicial del modelo.
 *
 * @see  database/migrations/2026_11_28_000001_crear_materia.php
 * @see  App\Models\Curso
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id_materia
 * @property ?string $codigo
 * @property string $nombre
 * @property string $nombre_normalizado
 * @property ?int $semestre
 * @property string $origen
 * @property ?string $fecha_creacion
 * @property ?int $creado_por
 */
class Materia extends Model
{
    public const ORIGEN_CATALOGO = 'catalogo';

    public const ORIGEN_USUARIO = 'agregada por usuario';

    protected $table = 'materia';

    protected $primaryKey = 'id_materia';

    public $timestamps = false;

    /** @var list<string> */
    protected $fillable = [
        'codigo',
        'nombre',
        'nombre_normalizado',
        'semestre',
        'origen',
        'fecha_creacion',
        'creado_por',
    ];

    /** @return HasMany<Curso, $this> */
    public function cursos(): HasMany
    {
        return $this->hasMany(Curso::class, 'id_materia', 'id_materia');
    }

    /** Materias que son prerrequisito de esta. @return BelongsToMany<Materia, $this> */
    public function prerrequisitos(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'materia_prerrequisito',
            'id_materia',
            'id_prerrequisito',
        );
    }

    /** @return BelongsTo<Usuario, $this> */
    public function creador(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'creado_por', 'id_usuario');
    }
}

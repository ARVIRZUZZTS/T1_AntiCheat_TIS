<?php

/**
 * @file    Rol.php
 *
 * @author  Equipo T1 <dev@techone.local>
 *
 * @created 2026-09-24
 *
 * @updated 2026-09-24
 *
 * @description
 * Modelo de la tabla `rol`: mapea los roles del sistema (docente, auxiliar)
 * y expone sus valores como constantes de dominio.
 *
 * @changelog
 * - 2026-09-24  [T1]  feat: creación inicial del modelo.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id_rol
 * @property string $nombre_rol
 */
class Rol extends Model
{
    public const NOMBRE_DOCENTE = 'docente';

    public const NOMBRE_AUXILIAR = 'auxiliar';

    protected $table = 'rol';

    protected $primaryKey = 'id_rol';

    public $timestamps = false;

    public $incrementing = false;

    protected $keyType = 'int';

    protected $fillable = [
        'id_rol',
        'nombre_rol',
    ];

    /** @return BelongsToMany<Usuario, $this> */
    public function usuarios(): BelongsToMany
    {
        return $this->belongsToMany(
            Usuario::class,
            'rol_usuario',
            'id_rol',
            'id_usuario'
        );
    }
}

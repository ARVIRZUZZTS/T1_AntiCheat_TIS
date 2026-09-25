<?php

/**
 * @file    Usuario.php
 *
 * @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
 *
 * @created 2026-09-24
 *
 * @updated 2026-09-24
 *
 * @description
 * Modelo de la tabla `usuario`: mapea docentes y auxiliares del sistema con
 * sus roles (sirve para el control de permisos por rol).
 *
 * @changelog
 * - 2026-09-24  [T1]  feat: creación inicial del modelo.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id_usuario
 * @property string $cod_sis
 * @property string $contraseña
 * @property string $nombre_usuario
 * @property string $apellido
 */
class Usuario extends Model
{
    protected $table = 'usuario';

    protected $primaryKey = 'id_usuario';

    public $timestamps = false;

    public $incrementing = false;

    protected $keyType = 'int';

    protected $fillable = [
        'id_usuario',
        'cod_sis',
        'contraseña',
        'nombre_usuario',
        'apellido',
    ];

    /** @return BelongsToMany<Rol, $this> */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            Rol::class,
            'rol_usuario',
            'id_usuario',
            'id_rol'
        );
    }
}

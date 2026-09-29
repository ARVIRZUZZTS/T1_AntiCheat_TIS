<?php

/**
 * @file    Usuario.php
 *
 * @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
 *
 * @created 2026-09-24
 *
 * @updated 2026-09-28
 *
 * @description
 * Modelo Eloquent de la tabla `usuario`: mapea docentes y auxiliares del
 * sistema, con sus roles (para el control de permisos) y los registros de
 * asistencia que realizó como registrador.
 *
 * @changelog
 * - 2026-09-24  [T1]         feat: creación inicial del modelo.
 * - 2026-09-25  [OchoaCesar] feat: creación inicial del modelo.
 * - 2026-09-25  [OchoaCesar] feat: agregar relación registrosAsistencia().
 * - 2026-09-26  [Diego Tejerina] feat: anotaciones @property y relación roles().
 * - 2026-09-26  [T1]         fix: resolver conflicto de merge sin resolver
 *   dejado en dev por el commit e4ea2fd (marcadores <<<<<<< sin quitar).
 * - 2026-09-28  [T1]         fix: resolver conflictos de merge al integrar
 *   dev en feature/28 (#28).
 *
 * @see  RegistroAsistencia
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    /** @return HasMany<RegistroAsistencia, $this> */
    public function registrosAsistencia(): HasMany
    {
        return $this->hasMany(RegistroAsistencia::class, 'id_registrador');
    }
}

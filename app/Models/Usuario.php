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
 * Modelo de la tabla `usuario`: mapea docentes y auxiliares del sistema con
 * sus roles (sirve para el control de permisos por rol) y los registros de
 * asistencia que realizó como registrador.
 *
 * Es también la tabla de autenticación: `config/auth.php` apunta su proveedor
 * a esta clase, así que el login es por `cod_sis` y no por email. La columna
 * se llama `password` (no `contraseña`) para no depender del ENIE en el
 * nombre y para que Laravel no necesite `getAuthPasswordName()`.
 *
 * @changelog
 * - 2026-09-24  [T1]         feat: creación inicial del modelo.
 * - 2026-09-25  [OchoaCesar] feat: agregar relación registrosAsistencia().
 * - 2026-09-26  [T1]         fix: resolver conflicto de merge sin resolver
 *   dejado en dev por el commit e4ea2fd (marcadores <<<<<<< sin quitar).
 * - 2026-09-28  [T1]         feat: extender Authenticatable; `usuario` pasa a
 *   ser la tabla de autenticación (login por `cod_sis`).
 * - 2026-09-28  [T1]         fix: `contraseña` -> `password` (fuera el ENIE).
 *
 * @see  RegistroAsistencia
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * @property int $id_usuario
 * @property string $cod_sis
 * @property string $password
 * @property string $nombre_usuario
 * @property string $apellido
 */
class Usuario extends Authenticatable
{
    protected $table = 'usuario';

    protected $primaryKey = 'id_usuario';

    public $timestamps = false;

    public $incrementing = false;

    protected $keyType = 'int';

    /** @var list<string> */
    protected $fillable = [
        'id_usuario',
        'cod_sis',
        'password',
        'nombre_usuario',
        'apellido',
    ];

    /**
     * `usuario` no tiene columna `email`; el login es por `cod_sis`.
     *
     * La tabla `password_reset_tokens` guarda esta misma cadena en su
     * columna `email`, que es lo que espera el broker por defecto.
     */
    public function getEmailForPasswordReset(): string
    {
        return $this->cod_sis;
    }

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

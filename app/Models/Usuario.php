<?php

/**
 * @file    Usuario.php
 *
<<<<<<< HEAD
 * @author  OchoaCesar <cesareduardonick@gmail.com>
 *
 * @created 2026-09-25
 *
 * @updated 2026-09-25
 *
 * @description
 * Modelo Eloquent de la tabla `usuario`. Representa a un usuario del sistema
 * (docente o auxiliar) y permite acceder a los registros de asistencia que
 * realizó como registrador.
 *
 * @changelog
 * - 2026-09-25  [OchoaCesar]  feat:  creación inicial del modelo.
 *
 * @see  RegistroAsistencia
=======
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
>>>>>>> 62d652c84c74e17637946104814b5ef94b449701
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
<<<<<<< HEAD
use Illuminate\Database\Eloquent\Relations\HasMany;

=======
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id_usuario
 * @property string $cod_sis
 * @property string $contraseña
 * @property string $nombre_usuario
 * @property string $apellido
 */
>>>>>>> 62d652c84c74e17637946104814b5ef94b449701
class Usuario extends Model
{
    protected $table = 'usuario';

    protected $primaryKey = 'id_usuario';

    public $timestamps = false;

<<<<<<< HEAD
=======
    public $incrementing = false;

    protected $keyType = 'int';

>>>>>>> 62d652c84c74e17637946104814b5ef94b449701
    protected $fillable = [
        'id_usuario',
        'cod_sis',
        'contraseña',
        'nombre_usuario',
        'apellido',
    ];

<<<<<<< HEAD
    public function registrosAsistencia(): HasMany
    {
        return $this->hasMany(RegistroAsistencia::class, 'id_registrador');
=======
    /** @return BelongsToMany<Rol, $this> */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            Rol::class,
            'rol_usuario',
            'id_usuario',
            'id_rol'
        );
>>>>>>> 62d652c84c74e17637946104814b5ef94b449701
    }
}

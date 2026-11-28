<?php

/**
 * @file    Usuario.php
 *
 * @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
 *
 * @created 2026-09-24
 *
 * @updated 2026-09-28
 * @updated 2026-11-08
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
 * - 2026-11-08  [Valery D. Ortuno P]  feat: `estado_cuenta` (pendiente,
 *   habilitada o rechazada, ver EstadoCuenta): una cuenta del registro no
 *   ingresa hasta que dos docentes la aprueban (HU 26).
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
 * @property ?string $correo
 * @property string $password
 * @property string $nombre_usuario
 * @property string $apellido
 * @property ?string $nombre
 * @property ?string $fecha_creacion
 * @property string $estado_cuenta Valor de App\Enums\EstadoCuenta.
 * @property ?string $carnet Carnet de identidad del auxiliar externo (opcional).
 * @property ?string $tipo_auxiliar 'UMSS' o 'externo'; null si no es auxiliar.
 * @property bool $puede_crear_auxiliares Permiso de coordinador.
 * @property ?int $id_docente_responsable Docente al que queda relacionado el auxiliar.
 * @property ?int $id_creador Usuario que creó la cuenta.
 */
class Usuario extends Authenticatable
{
    protected $table = 'usuario';

    protected $primaryKey = 'id_usuario';

    public $timestamps = false;

    public $incrementing = false;

    protected $keyType = 'int';

    /** La tabla `usuario` no tiene columna `remember_token`. */
    protected $rememberTokenName = null;

    /** @var list<string> */
    protected $fillable = [
        'id_usuario',
        'cod_sis',
        'correo',
        'password',
        'nombre_usuario',
        'apellido',
        'nombre',
        'fecha_creacion',
        'estado_cuenta',
        'carnet',
        'tipo_auxiliar',
        'puede_crear_auxiliares',
        'id_docente_responsable',
        'id_creador',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'puede_crear_auxiliares' => 'boolean',
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

    /**
     * Si el usuario tiene el rol indicado. Usa la relación ya cargada cuando
     * existe para no repetir consultas.
     *
     * @param  string  $nombreRol  Valor de Rol::NOMBRE_*.
     *
     * @return bool  true si tiene el rol.
     */
    public function tieneRol(string $nombreRol): bool
    {
        if ($this->relationLoaded('roles')) {
            return $this->roles->contains('nombre_rol', $nombreRol);
        }

        return $this->roles()->where('nombre_rol', $nombreRol)->exists();
    }

    /** Si el usuario es docente. */
    public function esDocente(): bool
    {
        return $this->tieneRol(Rol::NOMBRE_DOCENTE);
    }

    /** Si el usuario es auxiliar. */
    public function esAuxiliar(): bool
    {
        return $this->tieneRol(Rol::NOMBRE_AUXILIAR);
    }

    /** Si la cuenta fue eliminada de forma lógica (no puede ingresar). */
    public function estaEliminado(): bool
    {
        return $this->estado_cuenta === \App\Enums\EstadoCuenta::Eliminada->value;
    }

    /**
     * Quién puede entrar a "Crear auxiliar" y "Asignar auxiliar": los docentes y
     * los auxiliares con el permiso de coordinador.
     *
     * @return bool  true si puede gestionar auxiliares.
     */
    public function puedeGestionarAuxiliares(): bool
    {
        return $this->esDocente()
            || ($this->esAuxiliar() && (bool) $this->puede_crear_auxiliares);
    }

    /** @return HasMany<RegistroAsistencia, $this> */
    public function registrosAsistencia(): HasMany
    {
        return $this->hasMany(RegistroAsistencia::class, 'id_registrador');
    }

    /** @return HasMany<Examen, $this> */
    public function examenesCreados(): HasMany
    {
        return $this->hasMany(Examen::class, 'creador', 'id_usuario');
    }

    /** @return HasMany<InvitacionExamenCompartido, $this> */
    public function invitacionesEnviadas(): HasMany
    {
        return $this->hasMany(InvitacionExamenCompartido::class, 'id_docente_invitador', 'id_usuario');
    }

    /** @return HasMany<InvitacionExamenCompartido, $this> */
    public function invitacionesRecibidas(): HasMany
    {
        return $this->hasMany(InvitacionExamenCompartido::class, 'id_docente_invitado', 'id_usuario');
    }

    public function configuracionesExamen(): HasMany
    {
        return $this->hasMany(ConfiguracionExamenDocente::class, 'id_docente', 'id_usuario');
    }
}

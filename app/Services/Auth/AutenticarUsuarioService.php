<?php

/**
 * @file    AutenticarUsuarioService.php
 *
 * @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
 *
 * @created 2026-11-27
 *
 * @description
 * Resuelve las credenciales de inicio de sesión en un solo lugar, para que la
 * ruta web (`AuthController`) y la de la interfaz (`Api\LoginController`) usen
 * la misma regla y no se dupliquen.
 *
 * El acceso es por **nombre de usuario** y contraseña (docentes y auxiliares).
 * El mismo campo admite el `cod_sis` como alternativa, para no dejar afuera a
 * ninguna cuenta: primero se busca por nombre de usuario y, si no coincide con
 * ninguno, por `cod_sis`.
 *
 * No toca la sesión ni el estado de la cuenta: solo decide qué usuario
 * corresponde a un identificador y una contraseña. El bloqueo por
 * `estado_cuenta` y el inicio de sesión los resuelve quien llama.
 *
 * @changelog
 * - 2026-11-27  [Diego Tejerina]  feat: login por nombre de usuario con
 *   alternativa por cod_sis.
 *
 * @see  App\Http\Controllers\AuthController
 * @see  App\Http\Controllers\Api\LoginController
 */

namespace App\Services\Auth;

use App\Models\Usuario;
use Illuminate\Support\Facades\Hash;

class AutenticarUsuarioService
{
    /**
     * Devuelve el usuario cuyo correo o `cod_sis` coincide con el identificador
     * y cuya contraseña es correcta, u `null` si no hay coincidencia.
     *
     * @param  string  $identificador  Nombre de usuario (estándar) o `cod_sis` (alternativa).
     * @param  string  $contrasena     Contraseña en claro a verificar contra el hash.
     *
     * @return Usuario|null  El usuario autenticado, o null si las credenciales no son válidas.
     *
     * @author Diego Tejerina <josediegotejerinamolina@gmail.com>
     * @since  2026-11-27
     */
    public function porCredenciales(string $identificador, string $contrasena): ?Usuario
    {
        $identificador = trim($identificador);

        if ($identificador === '' || $contrasena === '') {
            return null;
        }

        $usuario = $this->buscar($identificador);

        if ($usuario === null || ! Hash::check($contrasena, (string) $usuario->password)) {
            return null;
        }

        return $usuario;
    }

    /**
     * Busca por nombre de usuario (sin distinguir mayúsculas ni espacios) y, si
     * no hay coincidencia, por `cod_sis` exacto.
     *
     * @param  string  $identificador  Valor tecleado en el campo de acceso.
     *
     * @return Usuario|null  El usuario encontrado o null.
     */
    private function buscar(string $identificador): ?Usuario
    {
        $porNombreUsuario = Usuario::query()
            ->whereRaw('lower(btrim(nombre_usuario)) = lower(btrim(?))', [$identificador])
            ->first();

        if ($porNombreUsuario !== null) {
            return $porNombreUsuario;
        }

        return Usuario::query()
            ->where('cod_sis', $identificador)
            ->first();
    }
}

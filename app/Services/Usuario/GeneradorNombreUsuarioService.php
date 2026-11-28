<?php

/**
 * @file    GeneradorNombreUsuarioService.php
 *
 * @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
 *
 * @created 2026-11-27
 *
 * @description
 * Genera un nombre de usuario único a partir del nombre y el apellido, con el
 * mismo criterio que ya usa el registro de docentes: `primerNombre.primerApellido`
 * (sin tildes ni ñ); si ya existe, agrega los siguientes apellidos y, si aún
 * choca, un sufijo numérico. Admite un prefijo para distinguir el origen de la
 * cuenta (p. ej. `aux.` para auxiliares → `aux.pedro.quispe`, `aux.pedro.quispe2`).
 *
 * @changelog
 * - 2026-11-27  [Diego Tejerina]  feat: creación inicial (usuario con prefijo).
 *
 * @see  App\Services\Auxiliar\CrearAuxiliarService
 */

namespace App\Services\Usuario;

use App\Models\Usuario;
use Illuminate\Support\Str;

class GeneradorNombreUsuarioService
{
    /** Prefijo de los auxiliares, para reconocer su usuario de un vistazo. */
    public const PREFIJO_AUXILIAR = 'aux.';

    /**
     * Devuelve un nombre de usuario disponible (único en `usuario.nombre_usuario`).
     *
     * @param  string  $nombre    Nombre(s) de la persona.
     * @param  string  $apellido  Apellido(s) de la persona.
     * @param  string  $prefijo   Prefijo a anteponer (p. ej. 'aux.'); vacío para ninguno.
     *
     * @return string  Un nombre de usuario libre, o cadena vacía si faltan datos.
     *
     * @author Diego Tejerina <josediegotejerinamolina@gmail.com>
     * @since  2026-11-27
     */
    public function generar(string $nombre, string $apellido, string $prefijo = ''): string
    {
        $nombres = array_values(array_filter(explode(' ', Str::slug($nombre, ' '))));
        $apellidos = array_values(array_filter(explode(' ', Str::slug($apellido, ' '))));

        if ($nombres === [] || $apellidos === []) {
            return '';
        }

        $candidato = $prefijo.$nombres[0].'.'.$apellidos[0];

        foreach (array_slice($apellidos, 1) as $parte) {
            if (! $this->existe($candidato)) {
                return $candidato;
            }

            $candidato .= '.'.$parte;
        }

        $base = $candidato;
        $numero = 2;
        while ($this->existe($candidato)) {
            $candidato = $base.$numero++;
        }

        return $candidato;
    }

    private function existe(string $nombreUsuario): bool
    {
        return Usuario::query()
            ->whereRaw('lower(btrim(nombre_usuario)) = lower(btrim(?))', [$nombreUsuario])
            ->exists();
    }
}

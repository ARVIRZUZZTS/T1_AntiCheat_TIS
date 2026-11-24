<?php

/**
 * @file    VerificarCorreoInstitucionalService.php
 *
 * @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
 *
 * @created 2026-11-23
 *
 * @description
 * Decide si un correo es elegible para la verificacion automatica por correo
 * electronico (HU 27): lo es cuando su dominio termina en alguno de los
 * dominios institucionales configurados, o cuando coincide exactamente con
 * alguno de los correos de prueba. El resto de correos pasa a la validacion
 * manual de dos docentes (HU 26).
 *
 * Los dominios y correos se leen de `config/registro.php`, que ya los entrega
 * normalizados; aca solo se normaliza el correo a comparar.
 *
 * @changelog
 * - 2026-11-23  [Diego Tejerina]  feat: creacion inicial (HU 27).
 *
 * @see  config/registro.php
 * @see  App\Services\Auth\RegistrarCuentaService
 */

namespace App\Services\Auth;

class VerificarCorreoInstitucionalService
{
    /**
     * Indica si el correo se verifica por correo electronico.
     *
     * @param  string  $correo  Correo del docente que se registra.
     *
     * @return bool  true si es institucional o de prueba; false si es externo.
     *
     * @author Diego Tejerina <josediegotejerinamolina@gmail.com>
     *
     * @since  2026-11-23
     */
    public function esElegible(string $correo): bool
    {
        $correo = mb_strtolower(trim($correo));

        if ($correo === '') {
            return false;
        }

        if (in_array($correo, $this->correosPrueba(), true)) {
            return true;
        }

        foreach ($this->dominiosInstitucionales() as $dominio) {
            if (str_ends_with($correo, $dominio)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private function dominiosInstitucionales(): array
    {
        /** @var list<string> $dominios */
        $dominios = config('registro.dominios_institucionales', []);

        return $dominios;
    }

    /**
     * @return list<string>
     */
    private function correosPrueba(): array
    {
        /** @var list<string> $correos */
        $correos = config('registro.correos_prueba', []);

        return $correos;
    }
}

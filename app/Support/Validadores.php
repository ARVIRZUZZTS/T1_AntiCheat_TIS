<?php

/**
 * @file    Validadores.php
 * @author  David E. Chavez T. <virzuzz12345@gmail.com>
 * @created 2026-11-08
 * @updated 2026-11-08
 *
 * @description
 * Validadores reutilizables del sistema para formularios y componentes
 * Livewire. Centraliza reglas y mensajes de validación para evitar
 * duplicación y mantener consistencia.
 *
 * @changelog
 * - 2026-11-08  [David E. Chavez T.]  feat: creación inicial con reglas
 *   para el alta de auxiliares.
 *
 * @see  App\Livewire\Auxiliares\GestionAuxiliares
 */

namespace App\Support;

/**
 * Reglas y mensajes de validación reutilizables.
 *
 * @package  App\Support
 * @author   David E. Chavez T. <virzuzz12345@gmail.com>
 * @since    2026-11-08
 */
class Validadores
{
    /**
     * Reglas para nombre y apellido.
     *
     * @return array<int, string>
     */
    public static function nombreApellido(): array
    {
        return ['required', 'string', 'min:2', 'max:50', 'regex:/^[\p{L}\s]+$/u'];
    }

    /**
     * Reglas para el código SIS.
     *
     * @return array<int, string>
     */
    public static function codigoSis(): array
    {
        return ['required', 'digits:9'];
    }

    /**
     * Reglas para la contraseña.
     *
     * @return array<int, string>
     */
    public static function contrasena(): array
    {
        return ['required', 'string', 'min:8', 'max:50'];
    }

    /**
     * Reglas completas para el alta de un auxiliar.
     *
     * @return array<string, array<int, string>>
     */
    public static function reglasAuxiliar(): array
    {
        return [
            'nombre' => self::nombreApellido(),
            'apellido' => self::nombreApellido(),
            'codSis' => self::codigoSis(),
            'password' => self::contrasena(),
            'idCurso' => ['required', 'integer'],
        ];
    }

    /**
     * Mensajes de validación para el alta de un auxiliar.
     *
     * @return array<string, string>
     */
    public static function mensajesAuxiliar(): array
    {
        return [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.min' => 'El nombre debe tener al menos 2 letras.',
            'nombre.max' => 'El nombre no debe superar 50 caracteres.',
            'nombre.regex' => 'El nombre solo puede contener letras.',
            'apellido.required' => 'El apellido es obligatorio.',
            'apellido.min' => 'El apellido debe tener al menos 2 letras.',
            'apellido.max' => 'El apellido no debe superar 50 caracteres.',
            'apellido.regex' => 'El apellido solo puede contener letras.',
            'codSis.required' => 'El código SIS es obligatorio.',
            'codSis.digits' => 'El código SIS debe tener 9 dígitos numéricos.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.max' => 'La contraseña no debe superar 50 caracteres.',
            'idCurso.required' => 'Debe seleccionar un curso.',
        ];
    }
}

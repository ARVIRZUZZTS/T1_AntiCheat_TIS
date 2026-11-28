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

use Illuminate\Validation\Rule;

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
     * Reglas para nombre y apellido. Se permiten letras (con tildes y ñ),
     * espacios, apóstrofos y guiones (nombres como "D'Angelo" o "Saavedra-Lima").
     *
     * @return array<int, string>
     */
    public static function nombreApellido(): array
    {
        return ['required', 'string', 'min:2', 'max:50', "regex:/^[\p{L}\s'\\-]+$/u"];
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
     * Reglas del alta de un auxiliar según su tipo. El código SIS es
     * obligatorio para los de la UMSS; el carnet es opcional y solo aplica a los
     * externos.
     *
     * @param  string  $tipo  'UMSS' o 'externo'.
     *
     * @return array<string, array<int, string>>
     */
    public static function reglasAuxiliar(string $tipo): array
    {
        $esUmss = $tipo === \App\Support\DTO\DatosNuevoAuxiliar::TIPO_UMSS;

        return [
            'nombre' => self::nombreApellido(),
            'apellido' => self::nombreApellido(),
            'tipoAuxiliar' => ['required', 'in:UMSS,externo'],
            'codSis' => $esUmss ? self::codigoSis() : ['nullable'],
            // El carnet (cédula) es opcional para cualquier tipo.
            'carnet' => ['nullable', 'digits_between:6,8'],
            'correo' => ['required', 'email', 'max:100'],
            'puedeCrearAuxiliares' => ['boolean'],
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
            'nombre.regex' => 'El nombre solo admite letras, espacios, apóstrofos y guiones.',
            'apellido.required' => 'El apellido es obligatorio.',
            'apellido.min' => 'El apellido debe tener al menos 2 letras.',
            'apellido.max' => 'El apellido no debe superar 50 caracteres.',
            'apellido.regex' => 'El apellido solo admite letras, espacios, apóstrofos y guiones.',
            'tipoAuxiliar.required' => 'Elige el tipo de auxiliar.',
            'tipoAuxiliar.in' => 'Elige el tipo de auxiliar.',
            'codSis.required' => 'El código SIS es obligatorio.',
            'codSis.digits' => 'El código SIS debe tener 9 dígitos numéricos.',
            'carnet.digits_between' => 'El carnet debe tener entre 6 y 8 dígitos.',
            'correo.required' => 'El correo es obligatorio.',
            'correo.email' => 'Ingresa un correo válido.',
            'correo.max' => 'El correo no debe superar 100 caracteres.',
        ];
    }

    /**
     * @param  list<int>  $idsGestion
     * @return array<string, mixed>
     */
    public static function reglasCurso(array $idsGestion, int $anioMinimo): array
    {
        return [
            'materia' => self::reglaMateria(),
            'grupo' => self::reglaGrupo(),
            'gestion' => self::reglaGestion($idsGestion),
            'anio' => self::reglaAnio($anioMinimo),
        ];
    }

    /** @return list<string> */
    public static function reglaMateria(): array
    {
        return ['required', 'string', 'max:120'];
    }

    /** Id de materia del catálogo, elegida en el buscador. @return list<mixed> */
    public static function reglaMateriaId(): array
    {
        return ['required', 'integer', Rule::exists('materia', 'id_materia')];
    }

    /** Código SIS de una materia nueva: opcional, 7 dígitos. @return list<string> */
    public static function reglaCodigoMateria(): array
    {
        return ['nullable', 'string', 'regex:/^[0-9]{7}$/'];
    }

    /** Semestre de una materia nueva: opcional, 1 a 12. @return list<mixed> */
    public static function reglaSemestreMateria(): array
    {
        return ['nullable', 'integer', 'between:1,12'];
    }

    /** Grupo del curso: entero de 1 a 10. @return list<string> */
    public static function reglaGrupo(): array
    {
        return ['required', 'integer', 'between:1,10'];
    }

    /**
     * @param  list<int>  $idsGestion
     * @return list<mixed>
     */
    public static function reglaGestion(array $idsGestion): array
    {
        return ['required', 'integer', Rule::in($idsGestion)];
    }

    /** @return list<string> */
    public static function reglaAnio(int $anioMinimo): array
    {
        return ['required', 'integer', 'digits:4', 'min:'.$anioMinimo];
    }

    /** @return array<string, string> */
    public static function mensajesCurso(int $anioMinimo): array
    {
        return [
            'materia.required' => 'Ingrese la materia.',
            'materia.max' => 'La materia no debe superar los 120 caracteres.',
            'id_materia.required' => 'Seleccione la materia.',
            'id_materia.integer' => 'Seleccione una materia valida.',
            'id_materia.exists' => 'Seleccione una materia valida.',
            'grupo.required' => 'Ingrese el grupo.',
            'grupo.integer' => 'El grupo debe ser un numero del 1 al 10.',
            'grupo.between' => 'El grupo debe ser un numero del 1 al 10.',
            'gestion.required' => 'Seleccione la gestion.',
            'gestion.integer' => 'Seleccione una gestion valida.',
            'gestion.in' => 'Seleccione una gestion valida.',
            'anio.required' => 'Ingrese el año.',
            'anio.integer' => 'Ingrese un año valido.',
            'anio.digits' => 'Ingrese un año valido.',
            'anio.min' => 'El año debe ser '.$anioMinimo.' o posterior.',
        ];
    }

    public static function normalizarMateria(mixed $valor): string
    {
        return (string) preg_replace('/\s+/u', ' ', trim((string) $valor));
    }

    public static function normalizarGrupo(mixed $valor): string
    {
        // El grupo pasa a ser un numero (1-10): se dejan solo digitos.
        return (string) preg_replace('/\D/', '', (string) $valor);
    }

    /**
     * Forma canónica del nombre de una materia para comparar duplicados: sin
     * espacios de más, en minúsculas y sin tildes ("Cálculo I" y "calculo i"
     * son la misma materia).
     *
     * @param  mixed  $valor  Nombre escrito por el usuario.
     *
     * @return string  Nombre normalizado.
     */
    public static function normalizarNombreMateria(mixed $valor): string
    {
        $limpio = mb_strtolower(self::normalizarMateria($valor));

        return strtr($limpio, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'ü' => 'u', 'ñ' => 'n',
        ]);
    }
}

<?php

/**
 * @file    StoreCursoRequest.php
 *
 * @author  Valery D. Ortuno P <valerydariana98@gmail.com>
 *
 * @created 2026-10-24
 *
 * @updated 2026-10-24
 *
 * @description
 * Validación del alta de un curso desde el formulario "Crear curso". Se piden
 * la materia, el grupo, la gestión y el año; la materia y el grupo se guardan
 * juntos como "{materia} - Grupo {grupo}" (separados por " - " para poder
 * dividirlos después si hace falta) y la gestión con su año va a
 * `curso_tipo_gestion`. El año es libre entre 2000 y 2099 (el formulario
 * propone el actual).
 *
 * El grupo es alfanumérico de hasta 3 caracteres (2, 10, 21A) y se guarda en
 * mayúsculas. Con 80 caracteres de materia, el nombre completo no pasa de los
 * 100 que admite `curso.nombre_curso`.
 *
 * @see  App\Http\Controllers\CursoController
 * @see  App\Services\Curso\RegistrarCursoService
 * @see  resources/views/pages/curso-crear.blade.php
 *
 * @changelog
 * - 2026-10-24  [Valery D. Ortuno P]  feat: creación inicial del request.
 */

namespace App\Http\Requests;

use App\Support\DTO\GestionCursoDTO;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCursoRequest extends FormRequest
{
    /** Primer año que se acepta para la gestión del curso. */
    public const ANIO_MINIMO = 2000;

    /** Último año que se acepta: cuatro cifras, sin años sin sentido. */
    public const ANIO_MAXIMO = 2099;

    /**
     * El rol docente ya lo exige el middleware `docente` de la ruta; el curso
     * se crea a nombre del usuario autenticado.
     *
     * @return bool  Si hay un usuario autenticado.
     *
     * @author Valery D. Ortuno P <valerydariana98@gmail.com>
     * @since  2026-10-24
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Quita espacios sobrantes y pasa el grupo a mayúsculas antes de validar,
     * para que "  Calculo   I " y "21a" se guarden limpios.
     *
     * @author Valery D. Ortuno P <valerydariana98@gmail.com>
     * @since  2026-10-24
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'materia' => preg_replace('/\s+/u', ' ', trim((string) $this->input('materia'))),
            'grupo' => mb_strtoupper(trim((string) $this->input('grupo'))),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'materia' => ['required', 'string', 'max:80'],
            'grupo' => ['required', 'regex:/^[A-Z0-9]{1,3}$/'],
            'gestion' => ['required', 'integer', Rule::exists('tipo_gestion', 'id_tipo_gestion')],
            // El año es libre (el formulario propone el actual), pero de cuatro
            // cifras y desde el 2000: un 1999 o un 202 son errores de tipeo.
            'anio' => ['required', 'integer', 'between:'.self::ANIO_MINIMO.','.self::ANIO_MAXIMO],
        ];
    }

    /**
     * Gestión elegida (periodo y año), lista para el servicio de alta.
     *
     * @return GestionCursoDTO  Periodo y año validados.
     *
     * @author Valery D. Ortuno P <valerydariana98@gmail.com>
     * @since  2026-10-24
     */
    public function gestion(): GestionCursoDTO
    {
        return new GestionCursoDTO(
            idTipoGestion: (int) $this->validated('gestion'),
            anio: (int) $this->validated('anio'),
        );
    }


    /**
     * La aplicación está en locale `en`; los mensajes se traducen acá y van
     * sin acentos, como el resto de los textos visibles.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'materia.required' => 'Ingrese la materia.',
            'materia.max' => 'La materia no debe superar los 80 caracteres.',
            'grupo.required' => 'Ingrese el grupo.',
            'grupo.regex' => 'El grupo solo admite letras y numeros, hasta 3 caracteres.',
            'gestion.required' => 'Seleccione la gestion.',
            'gestion.integer' => 'Seleccione una gestion valida.',
            'gestion.exists' => 'Seleccione una gestion valida.',
            'anio.required' => 'Ingrese el año.',
            'anio.integer' => 'Ingrese un año valido.',
            'anio.between' => 'El año debe estar entre '.self::ANIO_MINIMO.' y '.self::ANIO_MAXIMO.'.',
        ];
    }

    /**
     * Nombre de curso concatenado: "{materia} - Grupo {grupo}".
     *
     * @return string  Nombre listo para `curso.nombre_curso`.
     *
     * @author Valery D. Ortuno P <valerydariana98@gmail.com>
     * @since  2026-10-24
     */
    public function nombreCurso(): string
    {
        return $this->validated('materia').' - Grupo '.$this->validated('grupo');
    }
}

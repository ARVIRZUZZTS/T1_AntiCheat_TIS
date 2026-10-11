<?php

/**
 * @file    StoreExamenRequest.php
 *
 * @author  Alex Candia <alex.leonar.candia@gmail.com>
 *
 * @created 2026-10-05
 *
 * @updated 2026-10-10
 *
 * @description
 * Validación del alta de examen del modal de la materia. Los nombres de campo
 * son los que emite el formulario: tipo_examen, fecha (dd/mm/aaaa), hora_inicio
 * (H:i), duracion, y los arrays ambientes[], materiales[] y normas[], más los dos
 * textos de material y normas escritas a mano.
 *
 * El tipo se valida contra {@see TipoExamen}, o sea contra el enum y no contra
 * una lista de textos escrita acá: los valores válidos tienen una sola fuente.
 *
 * @see  App\Http\Controllers\ExamenController
 * @see  App\Services\Examen\RegistrarExamenService
 * @see  resources/views/components/ui/modal-crear-examen.blade.php
 *
 * @changelog
 * - 2026-10-05  [Alex Candia]  feat: creación inicial del request.
 * - 2026-10-10  [Alex Candia]  fix: el tipo pasa de `Rule::enum` a `in:` con los
 *   valores del enum. La regla enum entrega la clave `validation.enum` al
 *   traductor y nunca pasa por messages(), así que su mensaje salía en inglés.
 * - 2026-10-10  [Alex Candia]  fix: la fecha exige cuatro dígitos de año.
 *   `date_format:d/m/Y` acepta `10/12/262` y esa fecha se guardaba como 0262.
 * - 2026-10-10  [Alex Candia]  feat: mensajes claros de hora y fecha inválidas,
 *   y validación del año (`19xx`/`20xx`) con su propio mensaje.
 */

namespace App\Http\Requests;

use App\Enums\TipoExamen;
use App\Services\Examen\RegistrarExamenService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreExamenRequest extends FormRequest
{
    /**
     * El panel todavía no tiene login (#29), así que no hay usuario contra el
     * cual autorizar. Cuando exista la sesión, esta es la regla que decide quién
     * puede crear exámenes.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Valida aparte el año de la fecha.
     *
     * La regla de formato solo mira que sean cuatro dígitos; acá se exige que el
     * año empiece con 19 o 20, para poder dar un mensaje propio ("Ingrese un año
     * válido") en vez del genérico de formato.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $fecha = (string) $this->input('fecha');

            if (! preg_match('/^\d{2}\/\d{2}\/(\d{4})$/', $fecha, $coincidencias)) {
                return;
            }

            $anio = (int) $coincidencias[1];

            if ($anio < 1900 || $anio > 2099) {
                $validator->errors()->add('fecha', 'Ingrese un año válido.');
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // `in:` y no `Rule::enum()` por un motivo concreto: la regla enum
            // pasa la clave `validation.enum` al traductor y salta la búsqueda de
            // messages(), así que el mensaje siempre salía en inglés. La lista
            // sale igual del enum, que sigue siendo la única fuente de valores.
            'tipo_examen' => ['required', 'in:'.implode(',', array_column(TipoExamen::cases(), 'value'))],
            // El regex exige los cuatro dígitos del año: `date_format` acepta
            // `10/12/262` y eso se guardaba como el año 0262.
            'fecha' => ['required', 'regex:/^\d{2}\/\d{2}\/\d{4}$/', 'date_format:d/m/Y'],
            // El regex refuerza el rango 00:00-23:59. `type="time"` no deja
            // escribir una hora inválida, pero el mensaje propio cubre igual un
            // envío que no venga del navegador.
            'hora_inicio' => ['required', 'date_format:H:i', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'],
            'duracion' => ['required', 'integer', 'min:1', 'max:'.RegistrarExamenService::DURACION_MAXIMA],
            // Sin ambiente el examen no se puede monitorear, así que se exige al
            // menos uno.
            'ambientes' => ['required', 'array', 'min:1'],
            'ambientes.*' => ['integer', Rule::exists('ambiente', 'id_ambiente')],
            'materiales' => ['sometimes', 'array'],
            'materiales.*' => ['integer', Rule::exists('material', 'id_material')],
            'normas' => ['sometimes', 'array'],
            'normas.*' => ['integer', Rule::exists('norma', 'id_norma')],
            'materiales_personalizados' => ['nullable', 'string', 'max:300'],
            'normas_personalizadas' => ['nullable', 'string', 'max:300'],
        ];
    }

    /**
     * La aplicación está en locale `en`, así que los mensajes de la validación
     * saldrían en inglés. Se traducen acá, en el único request que los usa, en
     * vez de cambiar el locale global de la aplicación.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tipo_examen.required' => 'Seleccione el tipo de examen.',
            'fecha.required' => 'Ingrese la fecha.',
            'fecha.regex' => 'Ingrese una fecha válida.',
            'fecha.date_format' => 'Ingrese una fecha válida.',
            'hora_inicio.required' => 'Ingrese la hora de inicio.',
            'hora_inicio.date_format' => 'Ingrese una hora válida.',
            'hora_inicio.regex' => 'Ingrese una hora válida.',
            'duracion.required' => 'Ingrese la duración del examen.',
            'duracion.integer' => 'La duración debe estar en minutos, solo números.',
            'duracion.min' => 'La duración debe ser de al menos 1 minuto.',
            'duracion.max' => 'La duración no debe superar los 300 minutos.',
            'ambientes.required' => 'Seleccione al menos un ambiente.',
            'ambientes.min' => 'Seleccione al menos un ambiente.',
            'ambientes.*.exists' => 'Uno de los ambientes seleccionados no existe.',
            'materiales.*.exists' => 'Uno de los materiales seleccionados no existe.',
            'normas.*.exists' => 'Una de las normas seleccionadas no existe.',
            'materiales_personalizados.max' => 'El material personalizado no debe superar los 300 caracteres.',
            'normas_personalizadas.max' => 'La norma personalizada no debe superar los 300 caracteres.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'tipo_examen' => 'tipo de examen',
            'fecha' => 'fecha',
            'hora_inicio' => 'hora de inicio',
            'duracion' => 'duración',
            'ambientes' => 'ambientes',
            'materiales' => 'material permitido',
            'normas' => 'normas',
            'materiales_personalizados' => 'materiales personalizados',
            'normas_personalizadas' => 'normas personalizadas',
        ];
    }
}

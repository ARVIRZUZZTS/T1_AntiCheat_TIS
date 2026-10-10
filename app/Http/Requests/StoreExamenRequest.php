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
 */

namespace App\Http\Requests;

use App\Enums\TipoExamen;
use App\Services\Examen\RegistrarExamenService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'hora_inicio' => ['required', 'date_format:H:i'],
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
            'tipo_examen.required' => 'Elegí el tipo de examen.',
            'tipo_examen.in' => 'El tipo de examen no es válido.',
            'fecha.required' => 'Escribí la fecha de inicio.',
            'fecha.regex' => 'La fecha va en formato dd/mm/aaaa, con cuatro dígitos de año.',
            'fecha.date_format' => 'Esa fecha no existe: revisá el día y el mes.',
            'hora_inicio.required' => 'Escribí la hora de inicio.',
            'hora_inicio.date_format' => 'La hora va en formato hh:mm.',
            'duracion.required' => 'Escribí la duración del examen.',
            'duracion.integer' => 'La duración va en minutos, solo números.',
            'duracion.min' => 'La duración tiene que ser de al menos 1 minuto.',
            'duracion.max' => 'La duración no puede superar los 600 minutos.',
            'ambientes.required' => 'Elegí al menos un ambiente.',
            'ambientes.min' => 'Elegí al menos un ambiente.',
            'ambientes.*.exists' => 'Uno de los ambientes elegidos no existe.',
            'materiales.*.exists' => 'Uno de los materiales elegidos no existe.',
            'normas.*.exists' => 'Una de las normas elegidas no existe.',
            'materiales_personalizados.max' => 'Los materiales personalizados van hasta 300 caracteres.',
            'normas_personalizadas.max' => 'Las normas personalizadas van hasta 300 caracteres.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'tipo_examen' => 'tipo de examen',
            'fecha' => 'fecha de inicio',
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

<?php

/**
 * @file    StoreExamenRequest.php
 *
 * @author  Alex Candia <alex.leonar.candia@gmail.com>
 *
 * @created 2026-10-05
 *
 * @updated 2026-10-10
 * @updated 2026-10-24
 * @updated 2026-10-26
 * @updated 2026-11-04
 *
 * @description
 * Validación del alta de examen del formulario de crear examen. Los nombres de campo
 * son los que emite el formulario: tipo_examen, fecha (dd/mm/aaaa), hora_inicio
 * (H:i), duracion, y los arrays ambientes[], materiales[] y normas[], más los dos
 * textos de material y normas escritas a mano.
 *
 * El tipo se valida contra {@see TipoExamen}, o sea contra el enum y no contra
 * una lista de textos escrita acá: los valores válidos tienen una sola fuente.
 *
 * @see  App\Http\Controllers\ExamenController
 * @see  App\Services\Examen\RegistrarExamenService
 * @see  resources/views/components/ui/form-crear-examen.blade.php
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
 * - 2026-10-10  [Alex Candia]  fix: el piso horario pasa de `after` a
 *   `after_or_equal` (con su clave de mensaje). `after` es estricto, así que
 *   rechazaba las 06:45 exactas, que son una hora válida del rango.
 * - 2026-10-24  [Valery D. Ortuno P]  feat: authorize() exige que el curso sea
 *   del docente autenticado (CursoPolicy), ahora que existe el login.
 * - 2026-10-24  [Valery D. Ortuno P]  feat: no se crean exámenes en cursos
 *   finalizados (CursoPolicy::crearExamen) y los mensajes van sin acentos.
 * - 2026-10-26  [Valery D. Ortuno P]  perf: ambientes, materiales y normas se
 *   validan con `in` contra el catálogo cacheado (CatalogosExamenService)
 *   en vez de `exists`, que hacía una consulta por cada opción marcada.
 * - 2026-11-04  [Valery D. Ortuno P]  fix: no se aceptan fechas ni horas
 *   pasadas (hora de Bolivia, la zona de la aplicación): la fecha anterior a
 *   hoy da "Ingrese una fecha valida." y, si es hoy, la hora que ya pasó da
 *   "Ingrese una hora valida.". La ventana horaria pasa a 06:00-22:00.
 * - 2026-11-07  [Diego Tejerina]  refactor: la regla de "un solo final /
 *   segunda instancia por curso" sale a dos metodos protegidos
 *   (cursosParaUnicoPorTipo/examenExcluidoDeUnicoPorTipo) para que
 *   UpdateExamenRequest la reutilice excluyendo el examen que edita (Task #261).
 *   Sin cambios de comportamiento en el alta.
 */

namespace App\Http\Requests;

use App\Enums\TipoExamen;
use App\Models\Curso;
use App\Services\Examen\CatalogosExamenService;
use App\Services\Examen\RegistrarExamenService;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreExamenRequest extends FormRequest
{
    /**
     * Solo el docente responsable del curso puede crearle exámenes, y solo
     * mientras el curso siga en curso: el rol lo exige el middleware `docente`
     * y la pertenencia y el estado los decide CursoPolicy::crearExamen().
     *
     * @return bool Si el usuario autenticado puede crear exámenes en el curso de la URL.
     *
     * @author Valery D. Ortuno P <valerydariana98@gmail.com>
     *
     * @since  2026-10-24
     */
    public function authorize(): bool
    {
        return $this->user()?->can('crearExamen', $this->route('curso')) ?? false;
    }

    /**
     * Valida aparte el año de la fecha y que el examen no quede en el pasado.
     *
     * - La regla de formato solo mira que sean cuatro dígitos; acá se exige que
     *   el año empiece con 19 o 20, para poder dar un mensaje propio ("Ingrese
     *   un año válido") en vez del genérico de formato.
     * - Una fecha anterior a hoy no es válida y, si es hoy, tampoco una hora que
     *   ya pasó (o es la actual). "Hoy" y "ahora" son de Bolivia: es la zona de
     *   la aplicación (`app.timezone`, America/La_Paz), así que no dependen de
     *   la hora del navegador.
     *
     * Solo se revisa lo que ya pasó el formato: una fecha u hora mal escrita ya
     * tiene su error y no se le suma otro.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $fecha = $this->fechaEscrita();

            if ($fecha === null) {
                return;
            }

            if ($fecha->year < 1900 || $fecha->year > 2099) {
                $validator->errors()->add('fecha', 'Ingrese un año valido.');

                return;
            }

            $ahora = now();

            if ($fecha->lt($ahora->copy()->startOfDay())) {
                $validator->errors()->add('fecha', 'Ingrese una fecha valida.');

                return;
            }

            $tipo = (string) $this->input('tipo_examen');

            if (in_array($tipo, [TipoExamen::ExamenFinal->value, TipoExamen::SegundaInstancia->value], true)) {
                $consulta = DB::table('examen')
                    ->join('examen_curso', 'examen_curso.id_examen', '=', 'examen.id_examen')
                    ->join('tipo_examen', 'tipo_examen.id_tipo_examen', '=', 'examen.tipo_examen')
                    ->whereIn('examen_curso.id_curso', $this->cursosParaUnicoPorTipo())
                    ->where('tipo_examen.nombre_tipo_examen', $tipo);

                // Al editar, el propio examen no cuenta como "ya existe": si no se
                // excluye, un final que se guarda sin cambiar el tipo chocaria
                // consigo mismo. En el alta no hay nada que excluir.
                $excluido = $this->examenExcluidoDeUnicoPorTipo();
                if ($excluido !== null) {
                    $consulta->where('examen.id_examen', '!=', $excluido);
                }

                if ($consulta->exists()) {
                    $validator->errors()->add(
                        'tipo_examen',
                        $tipo === TipoExamen::ExamenFinal->value
                            ? 'Ya existe un examen final para este curso.'
                            : 'Ya existe una segunda instancia para este curso.'
                    );
                }
            }

            $hora = (string) $this->input('hora_inicio');

            if (! $fecha->isSameDay($ahora)
                || $validator->errors()->has('hora_inicio')
                || preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $hora) !== 1) {
                return;
            }

            // Mismo formato H:i en las dos, así que se comparan como texto.
            if ($hora <= $ahora->format('H:i')) {
                $validator->errors()->add('hora_inicio', 'Ingrese una hora valida.');
            }
        });
    }

    /**
     * La fecha del formulario si es una fecha real en dd/mm/aaaa, o null si no
     * (de eso ya avisan las reglas de formato).
     */
    private function fechaEscrita(): ?Carbon
    {
        $texto = (string) $this->input('fecha');

        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $texto) !== 1) {
            return null;
        }

        $fecha = Carbon::createFromFormat('!d/m/Y', $texto);

        // El round-trip descarta fechas como 31/02, que createFromFormat corre
        // a marzo.
        return $fecha !== false && $fecha->format('d/m/Y') === $texto ? $fecha : null;
    }

    /**
     * Cursos sobre los que rige la regla "un solo examen final y una sola
     * segunda instancia por curso". En el alta es el curso de la URL; la
     * edicion lo sobrescribe con los cursos del examen que se edita.
     *
     * @return list<int>
     */
    protected function cursosParaUnicoPorTipo(): array
    {
        $curso = $this->route('curso');

        return [$curso instanceof Curso ? (int) $curso->id_curso : (int) $curso];
    }

    /**
     * Id del examen que NO cuenta en esa regla (el que se esta editando). En el
     * alta no hay ninguno que excluir.
     */
    protected function examenExcluidoDeUnicoPorTipo(): ?int
    {
        return null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(CatalogosExamenService $catalogos): array
    {
        return [
            // `in:` y no `Rule::enum()` por un motivo concreto: la regla enum
            // pasa la clave `validation.enum` al traductor y salta la búsqueda de
            // messages(), así que el mensaje siempre salía en inglés. La lista
            // sale igual del enum, que sigue siendo la única fuente de valores.
            'tipo_examen' => ['required', 'in:'.implode(',', array_column(TipoExamen::cases(), 'value'))],
            // El regex exige los cuatro dígitos del año: `date_format` acepta
            // `10/12/262` y eso se guardaba como el año 0262. El año en sí lo
            // completa el modal con el año en curso.
            'fecha' => ['required', 'regex:/^\d{2}\/\d{2}\/\d{4}$/', 'date_format:d/m/Y'],
            // El regex refuerza el rango 00:00-23:59. `type="time"` no deja
            // escribir una hora inválida, pero el mensaje propio cubre igual un
            // envío que no venga del navegador. El `after_or_equal`/
            // `before_or_equal` es lo que acota a la ventana del centro de
            // exámenes (06:00 a 22:00). Es `after_or_equal` y no `after` porque
            // el piso es una hora válida: con `after`, las 06:00 exactas quedaban
            // rechazadas por el propio límite. Que no sea una hora pasada lo
            // revisa withValidator().
            'hora_inicio' => [
                'required',
                'date_format:H:i',
                'regex:/^([01]\d|2[0-3]):[0-5]\d$/',
                'after_or_equal:'.RegistrarExamenService::HORA_INICIO_MINIMA,
                'before_or_equal:'.RegistrarExamenService::HORA_INICIO_MAXIMA,
            ],
            'duracion' => ['required', 'integer', 'min:1', 'max:'.RegistrarExamenService::DURACION_MAXIMA],
            // Sin ambiente el examen no se puede monitorear, así que se exige al
            // menos uno.
            'ambientes' => ['required', 'array', 'min:1'],
            // `in` contra el catálogo cacheado y no `exists`: `exists` hacía
            // una consulta por cada opción marcada.
            'ambientes.*' => ['integer', Rule::in($catalogos->ids('ambientes'))],
            'materiales' => ['sometimes', 'array'],
            'materiales.*' => ['integer', Rule::in($catalogos->ids('materiales'))],
            'normas' => ['sometimes', 'array'],
            'normas.*' => ['integer', Rule::in($catalogos->ids('normas'))],
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
            'fecha.regex' => 'Ingrese una fecha valida.',
            'fecha.date_format' => 'Ingrese una fecha valida.',
            'hora_inicio.required' => 'Ingrese la hora de inicio.',
            'hora_inicio.date_format' => 'Ingrese una hora valida.',
            'hora_inicio.regex' => 'Ingrese una hora valida.',
            'hora_inicio.after_or_equal' => 'La hora de inicio no puede ser anterior a las '
                .RegistrarExamenService::HORA_INICIO_MINIMA.'.',
            'hora_inicio.before_or_equal' => 'La hora de inicio no puede ser posterior a las '
                .RegistrarExamenService::HORA_INICIO_MAXIMA.'.',
            'duracion.required' => 'Ingrese la duracion del examen.',
            'duracion.integer' => 'La duracion debe estar en minutos, solo numeros.',
            'duracion.min' => 'La duracion debe ser de al menos 1 minuto.',
            'duracion.max' => 'La duracion no debe superar los 300 minutos.',
            'ambientes.required' => 'Seleccione al menos un ambiente.',
            'ambientes.min' => 'Seleccione al menos un ambiente.',
            'ambientes.*.in' => 'Uno de los ambientes seleccionados no existe.',
            'materiales.*.in' => 'Uno de los materiales seleccionados no existe.',
            'normas.*.in' => 'Una de las normas seleccionadas no existe.',
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
            'duracion' => 'duracion',
            'ambientes' => 'ambientes',
            'materiales' => 'material permitido',
            'normas' => 'normas',
            'materiales_personalizados' => 'materiales personalizados',
            'normas_personalizadas' => 'normas personalizadas',
        ];
    }
}

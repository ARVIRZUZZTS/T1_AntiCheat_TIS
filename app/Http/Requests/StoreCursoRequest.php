<?php

namespace App\Http\Requests;

use App\Models\Curso;
use App\Support\DTO\ExamenCursoDTO;
use App\Support\DTO\GestionCursoDTO;
use App\Support\DTO\RegistrarCursoDTO;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCursoRequest extends FormRequest
{
    public const ANIO_MINIMO = 2000;

    public const ANIO_MAXIMO = 2099;

    public function authorize(): bool
    {
        return $this->user()?->can('create', Curso::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $examenes = $this->input('examenes');

        if (is_array($examenes)) {
            $examenes = array_map(function (mixed $examen): mixed {
                if (! is_array($examen)) {
                    return $examen;
                }

                $examen['nombre'] = trim((string) ($examen['nombre'] ?? ''));

                foreach (['normas_personalizadas', 'materiales_personalizados'] as $campo) {
                    if (isset($examen[$campo]) && is_array($examen[$campo])) {
                        $examen[$campo] = array_values(array_filter(
                            array_map(fn (mixed $texto): string => trim((string) $texto), $examen[$campo]),
                            fn (string $texto): bool => $texto !== ''
                        ));
                    }
                }

                return $examen;
            }, $examenes);
        }

        $this->merge([
            'materia' => preg_replace('/\s+/u', ' ', trim((string) $this->input('materia'))),
            'grupo' => mb_strtoupper(trim((string) $this->input('grupo'))),
            'examenes' => $examenes,
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'materia' => ['required', 'string', 'max:80'],
            'grupo' => ['required', 'regex:/^[A-Z0-9]{1,3}$/'],
            'gestion' => ['required', 'integer', Rule::exists('tipo_gestion', 'id_tipo_gestion')],
            'anio' => ['required', 'integer', 'between:'.self::ANIO_MINIMO.','.self::ANIO_MAXIMO],
            'ambiente_id' => ['required', 'integer', Rule::exists('ambiente', 'id_ambiente')],

            'examenes' => ['required', 'array', 'min:1'],
            'examenes.*' => ['array'],
            'examenes.*.nombre' => ['required', 'string', 'max:100'],
            'examenes.*.fecha' => ['nullable', 'date_format:Y-m-d'],
            'examenes.*.normas_ids' => ['sometimes', 'array'],
            'examenes.*.normas_ids.*' => ['integer', 'distinct'],
            'examenes.*.normas_personalizadas' => ['sometimes', 'array'],
            'examenes.*.normas_personalizadas.*' => ['string', 'max:300'],
            'examenes.*.materiales_ids' => ['sometimes', 'array'],
            'examenes.*.materiales_ids.*' => ['integer', 'distinct'],
            'examenes.*.materiales_personalizados' => ['sometimes', 'array'],
            'examenes.*.materiales_personalizados.*' => ['string', 'max:300'],
        ];
    }

    /** @return array<string, string> */
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
            'ambiente_id.required' => 'Seleccione el ambiente.',
            'ambiente_id.exists' => 'Seleccione un ambiente valido.',
            'examenes.required' => 'Registre al menos un examen.',
            'examenes.min' => 'Registre al menos un examen.',
            'examenes.*.nombre.required' => 'Ingrese el nombre de cada examen.',
            'examenes.*.nombre.max' => 'El nombre del examen no debe superar los 100 caracteres.',
            'examenes.*.fecha.date_format' => 'La fecha del examen debe tener el formato aaaa-mm-dd.',
            'examenes.*.normas_ids.*.distinct' => 'No repita una norma en el mismo examen.',
            'examenes.*.materiales_ids.*.distinct' => 'No repita un material en el mismo examen.',
        ];
    }

    public function datos(): RegistrarCursoDTO
    {
        $validados = $this->validated();

        $examenes = array_map(
            fn (array $examen): ExamenCursoDTO => new ExamenCursoDTO(
                nombre: $examen['nombre'],
                fecha: $examen['fecha'] ?? null,
                normasIds: array_values($examen['normas_ids'] ?? []),
                normasPersonalizadas: array_values($examen['normas_personalizadas'] ?? []),
                materialesIds: array_values($examen['materiales_ids'] ?? []),
                materialesPersonalizados: array_values($examen['materiales_personalizados'] ?? []),
            ),
            array_values($validados['examenes'])
        );

        return new RegistrarCursoDTO(
            materia: $validados['materia'],
            grupo: $validados['grupo'],
            gestion: new GestionCursoDTO(
                idTipoGestion: (int) $validados['gestion'],
                anio: (int) $validados['anio'],
            ),
            idAmbiente: (int) $validados['ambiente_id'],
            examenes: $examenes,
        );
    }
}

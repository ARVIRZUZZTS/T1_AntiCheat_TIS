<?php

namespace App\Http\Requests;

use App\Models\Examen;
use App\Services\Invitacion\BuscarDocentesInvitablesService;
use Illuminate\Foundation\Http\FormRequest;

class BuscarDocentesInvitablesRequest extends FormRequest
{
    public function authorize(): bool
    {
        $examen = $this->route('examen');

        return $examen instanceof Examen
            && ($this->user()?->can('invitarDocente', $examen) ?? false);
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['busqueda' => trim((string) $this->query('busqueda', ''))]);
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'busqueda' => [
                'required',
                'string',
                'min:'.BuscarDocentesInvitablesService::BUSQUEDA_MINIMA,
                'max:'.BuscarDocentesInvitablesService::BUSQUEDA_MAXIMA,
            ],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'busqueda.required' => 'Ingrese un nombre o correo para buscar.',
            'busqueda.min' => 'La búsqueda debe tener al menos 2 caracteres.',
            'busqueda.max' => 'La búsqueda no debe superar los 254 caracteres.',
        ];
    }
}

<?php

namespace App\Http\Requests;

use App\Models\Examen;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EnviarInvitacionExamenRequest extends FormRequest
{
    public function authorize(): bool
    {
        $examen = $this->route('examen');

        return $examen instanceof Examen
            && ($this->user()?->can('invitarDocente', $examen) ?? false);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'docente_invitado_id' => [
                'required',
                'integer',
                Rule::exists('usuario', 'id_usuario'),
            ],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'docente_invitado_id.required' => 'Seleccione un docente.',
            'docente_invitado_id.integer' => 'El docente invitado no es válido.',
            'docente_invitado_id.exists' => 'El docente invitado no es válido.',
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuardarConfiguracionExamenDocenteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('configurar', $this->route('examen')) ?? false;
    }

    public function rules(): array
    {
        return [
            'normas' => ['sometimes', 'array'],
            'normas.*' => ['integer', 'distinct', Rule::exists('norma', 'id_norma')],
            'materiales' => ['sometimes', 'array'],
            'materiales.*' => ['integer', 'distinct', Rule::exists('material', 'id_material')],
            'normas_personalizadas' => ['sometimes', 'array'],
            'normas_personalizadas.*' => ['string', 'max:300'],
            'materiales_personalizados' => ['sometimes', 'array'],
            'materiales_personalizados.*' => ['string', 'max:300'],
        ];
    }
}

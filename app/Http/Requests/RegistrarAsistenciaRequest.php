<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegistrarAsistenciaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // TEMPORAL: sin auth
    }

    public function rules(): array
    {
        return [
            'sis_estudiante' => ['required', 'string', 'exists:estudiante,sis_estudiante'],
        ];
    }
}
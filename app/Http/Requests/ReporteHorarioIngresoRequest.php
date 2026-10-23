<?php

/**
 * @file    ReporteHorarioIngresoRequest.php
 *
 * @author  Alex Candia <alex.leonar.candia@gmail.com>
 *
 * @created 2026-10-23
 *
 * @description Valida los filtros del reporte de horarios de ingreso. La
 * pertenencia del curso y del examen se resuelve en el servicio, no aca.
 */

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReporteHorarioIngresoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'id_curso' => ['required', 'integer', 'exists:curso,id_curso'],
            'id_examen' => ['required', 'integer', 'exists:examen,id_examen'],
            'id_aula' => ['nullable', 'integer', 'exists:ambiente,id_ambiente'],
            'hora_desde' => ['nullable', 'date_format:H:i'],
            'hora_hasta' => ['nullable', 'date_format:H:i', 'after_or_equal:hora_desde'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'id_aula' => $this->filled('id_aula') ? $this->input('id_aula') : null,
            'hora_desde' => $this->filled('hora_desde') ? $this->input('hora_desde') : null,
            'hora_hasta' => $this->filled('hora_hasta') ? $this->input('hora_hasta') : null,
        ]);
    }
}

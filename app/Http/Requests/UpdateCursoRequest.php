<?php

namespace App\Http\Requests;

use App\Models\Curso;
use App\Models\Usuario;
use App\Services\Curso\ObtenerCursoEditableService;
use App\Support\Validadores;
use Illuminate\Foundation\Http\FormRequest;
use LogicException;

class UpdateCursoRequest extends FormRequest
{
    private ?Curso $curso = null;

    public function authorize(): bool
    {
        $docente = $this->user();

        if (! $docente instanceof Usuario) {
            return false;
        }

        $this->curso = app(ObtenerCursoEditableService::class)
            ->ejecutar((int) $this->route('curso'), $docente);

        return true;
    }

    public function curso(): Curso
    {
        return $this->curso ?? throw new LogicException('El curso no fue resuelto.');
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'materia' => Validadores::normalizarMateria($this->input('materia')),
            'grupo' => Validadores::normalizarGrupo($this->input('grupo')),
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'materia' => ['required'],
            'grupo' => Validadores::reglaGrupo(),
            'gestion' => ['required'],
            'anio' => ['required'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return Validadores::mensajesCurso($this->anioMinimo());
    }

    public function anioMinimo(): int
    {
        return now()->year;
    }
}

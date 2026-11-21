<?php

namespace App\Http\Requests;

use App\Models\Curso;
use App\Models\Usuario;
use App\Services\Curso\ListarGestionesService;
use App\Services\Curso\ObtenerCursoEditableService;
use App\Support\DTO\ActualizarCursoDTO;
use App\Support\DTO\GestionCursoDTO;
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
    public function rules(ListarGestionesService $gestiones): array
    {
        return [
            'materia' => Validadores::reglaMateria(),
            'grupo' => Validadores::reglaGrupo(),
            'gestion' => Validadores::reglaGestion(array_keys($gestiones->ejecutar())),
            'anio' => Validadores::reglaAnio($this->anioMinimo()),
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

    public function datos(): ActualizarCursoDTO
    {
        return new ActualizarCursoDTO(
            materia: (string) $this->validated('materia'),
            grupo: (string) $this->validated('grupo'),
            gestion: new GestionCursoDTO(
                (int) $this->validated('gestion'),
                (int) $this->validated('anio'),
            ),
        );
    }
}

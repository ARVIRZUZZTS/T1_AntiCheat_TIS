<?php

namespace App\Livewire\Cursos;

use App\Models\Curso;
use App\Services\Curso\AsegurarCursoEditableService;
use App\Services\Curso\CargarListaEstudiantesService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * @file    CargarCsv.php
 *
 * @author  Equipo T1
 *
 * @created 2026-11-21
 *
 * @updated 2026-11-21
 *
 * @description
 * Componente Livewire para cargar una lista de estudiantes a un curso.
 *
 * @changelog
 * - 2026-11-21  [Equipo T1]  feat: carga de lista de estudiantes por Excel/CSV (HU 25).
 */
#[Title('Cargar lista de estudiantes — Anticheat TIS')]
class CargarCsv extends Component
{
    use WithFileUploads;

    #[Locked]
    public Curso $curso;

    public ?\Illuminate\Http\UploadedFile $archivo = null;

    public string $mensajeError = '';

    /**
     * Inicializa el componente con el curso destino.
     *
     * @param  Curso  $curso  Curso al que se cargarán los estudiantes.
     *
     * @throws AuthorizationException Si no puede gestionar el curso.
     * @throws ValidationException Si el curso está archivado.
     */
    public function mount(Curso $curso): void
    {
        Gate::authorize('gestionar', $curso);
        app(AsegurarCursoEditableService::class)->asegurarCursoEditable($curso, 'curso');
        $this->curso = $curso;
    }

    /**
     * Reglas de validación del archivo subido.
     *
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'archivo' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ];
    }

    public function messages(): array
    {
        return [
            'archivo.required' => 'Debe seleccionar un archivo Excel o CSV.',
            'archivo.mimes' => 'Formato de archivo no válido. Suba un archivo Excel o CSV.',
            'archivo.max' => 'El archivo no puede superar los 5 MB.',
        ];
    }

    /**
     * Procesa el archivo subido y redirige a la lista de estudiantes.
     *
     * @param  CargarListaEstudiantesService  $servicio  Servicio de carga.
     */
    public function cargar(CargarListaEstudiantesService $servicio): void
    {
        $this->validate();

        try {
            $resultado = $servicio->ejecutar($this->curso, $this->archivo, auth()->id());

            $mensaje = sprintf(
                'Se cargaron %d estudiantes (%d nuevos, %d ya inscritos, %d en central de riesgos, %d filas omitidas).',
                $resultado->inscripcionesNuevas + $resultado->yaInscritos,
                $resultado->estudiantesNuevos,
                $resultado->yaInscritos,
                $resultado->tramposos,
                count($resultado->sisInvalidos),
            );

            session()->flash('mensaje', $mensaje);

            $this->redirectRoute('cursos.ver-estudiantes', ['curso' => $this->curso->id_curso], navigate: true);
        } catch (\Throwable $error) {
            $this->mensajeError = $error->getMessage();
        }
    }

    /**
     * Cancela la carga y vuelve al detalle del curso.
     */
    public function cancelar()
    {
        return $this->redirectRoute('cursos.detalle', ['curso' => $this->curso->id_curso], navigate: true);
    }

    public function render()
    {
        return view('livewire.cursos.cargar-csv');
    }
}

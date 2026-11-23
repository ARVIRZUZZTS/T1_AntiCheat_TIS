<?php

namespace App\Livewire\Cursos;

use App\Enums\CursoEstado;
use App\Models\Curso;
use App\Services\Curso\AsegurarCursoEditableService;
use App\Services\Curso\CargarListaEstudiantesService;
use App\Support\DTO\CargarListaEstudiantesResultado;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
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
 * @updated 2026-11-23
 *
 * @description
 * Componente Livewire de la subopción «Cargar CSV» (HU 25). Reúne en una sola
 * pantalla el selector de curso (solo cursos activos y gestionables, al estilo
 * de «Habilitar estudiantes»), la zona de carga con arrastrar y soltar (visible
 * solo con un curso elegido) y el modal de confirmación de reemplazo cuando el
 * curso ya tiene estudiantes. Al terminar redirige a «Ver estudiantes» con el
 * curso preseleccionado y un banner con el resumen de la carga.
 *
 * @changelog
 * - 2026-11-21  [Equipo T1]  feat: carga de lista de estudiantes por Excel/CSV (HU 25).
 * - 2026-11-23  [Equipo T1]  feat: selector de curso integrado, drag & drop y
 *   modal de reemplazo; el curso puede llegar preseleccionado por ?curso (HU 25).
 *
 * @see  App\Services\Curso\CargarListaEstudiantesService
 */
#[Title('Cargar lista de estudiantes — Anticheat TIS')]
class CargarCsv extends Component
{
    use WithFileUploads;

    /**
     * Cursos activos del docente, para el selector (id => nombre).
     *
     * @var array<int, string>
     */
    public array $cursos = [];

    public ?int $cursoId = null;

    public ?UploadedFile $archivo = null;

    public string $mensajeError = '';

    public bool $mostrarModalReemplazo = false;

    /**
     * Inicializa el componente. El curso puede llegar preseleccionado por la
     * ruta (?curso=ID); si no, el selector arranca vacío.
     *
     * @param  ?Curso  $curso  Curso preseleccionado (opcional).
     *
     * @throws AuthorizationException Si no puede gestionar el curso recibido.
     * @throws ValidationException Si el curso recibido está archivado.
     */
    public function mount(?Curso $curso = null): void
    {
        $this->cursos = $this->cursosGestionables();

        if ($curso !== null && $curso->exists) {
            Gate::authorize('gestionar', $curso);
            app(AsegurarCursoEditableService::class)->asegurarCursoEditable($curso, 'curso');
            $this->cursoId = (int) $curso->id_curso;
        }
    }

    /**
     * Al cambiar el curso en el selector se descarta el archivo y los avisos:
     * cada curso parte de una carga limpia. Se revalida la selección por si el
     * id llegó manipulado desde el cliente.
     *
     * @throws AuthorizationException Si el curso no es gestionable.
     * @throws ValidationException Si el curso está archivado.
     */
    public function updatedCursoId(): void
    {
        $this->archivo = null;
        $this->mensajeError = '';
        $this->mostrarModalReemplazo = false;

        if ($this->cursoId === null) {
            return;
        }

        $this->asegurarCursoValido($this->cursoSeleccionado());
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

    /**
     * Mensajes de validación del archivo.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'archivo.required' => 'Debe seleccionar un archivo Excel o CSV.',
            'archivo.mimes' => 'Formato de archivo no válido. Suba un archivo Excel o CSV.',
            'archivo.max' => 'El archivo no puede superar los 5 MB.',
        ];
    }

    /**
     * Procesa la carga. Si el curso ya tiene estudiantes, abre el modal de
     * confirmación en vez de guardar; si no, carga directamente.
     *
     * @param  CargarListaEstudiantesService  $servicio  Servicio de carga.
     */
    public function cargar(CargarListaEstudiantesService $servicio): mixed
    {
        $this->validate();
        $curso = $this->cursoSeleccionado();
        $this->asegurarCursoValido($curso);

        if ($this->cursoTieneEstudiantes($curso)) {
            $this->mostrarModalReemplazo = true;

            return null;
        }

        return $this->procesar($servicio, $curso, reemplazar: false);
    }

    /**
     * Confirma el reemplazo total de la lista anterior desde el modal.
     *
     * @param  CargarListaEstudiantesService  $servicio  Servicio de carga.
     */
    public function confirmarReemplazo(CargarListaEstudiantesService $servicio): mixed
    {
        $this->validate();
        $curso = $this->cursoSeleccionado();
        $this->asegurarCursoValido($curso);

        return $this->procesar($servicio, $curso, reemplazar: true);
    }

    /**
     * Cierra el modal de reemplazo sin tocar la base de datos.
     */
    public function cancelarReemplazo(): void
    {
        $this->mostrarModalReemplazo = false;
    }

    /**
     * Cancela la carga y vuelve al detalle del curso (o a la lista si no hay
     * ninguno elegido).
     */
    public function cancelar(): mixed
    {
        if ($this->cursoId === null) {
            return $this->redirectRoute('cursos', navigate: true);
        }

        return $this->redirectRoute('cursos.detalle', ['curso' => $this->cursoId], navigate: true);
    }

    public function render(): mixed
    {
        return view('livewire.cursos.cargar-csv');
    }

    /**
     * Ejecuta la carga, arma el banner de resumen y redirige a «Ver estudiantes».
     *
     * @param  CargarListaEstudiantesService  $servicio  Servicio de carga.
     * @param  Curso  $curso  Curso destino.
     * @param  bool  $reemplazar  Si reemplaza la lista anterior.
     */
    private function procesar(CargarListaEstudiantesService $servicio, Curso $curso, bool $reemplazar): mixed
    {
        try {
            $resultado = $servicio->ejecutar($curso, $this->archivo, (int) auth()->id(), $reemplazar);

            session()->flash('mensaje', $this->resumen($resultado));

            return $this->redirectRoute('cursos.ver-estudiantes', ['curso' => $curso->id_curso], navigate: true);
        } catch (\Throwable $error) {
            $this->mostrarModalReemplazo = false;
            $this->mensajeError = $error->getMessage();

            return null;
        }
    }

    /**
     * Resumen legible de la carga para el banner de éxito.
     *
     * @param  CargarListaEstudiantesResultado  $resultado  Resultado de la carga.
     * @return string Mensaje con inscritos, habilitados, deshabilitados, duplicados y omitidos.
     */
    private function resumen(CargarListaEstudiantesResultado $resultado): string
    {
        $inscritos = $resultado->inscripcionesNuevas + $resultado->yaInscritos;

        return sprintf(
            '%s %d estudiante(s): %d habilitado(s), %d deshabilitado(s) por la central de riesgos. '
            .'Duplicados en el archivo: %d. Filas omitidas: %d.',
            $resultado->reemplazo ? 'Se reemplazó la lista con' : 'Se inscribieron',
            $inscritos,
            $resultado->habilitados,
            $resultado->deshabilitados,
            count($resultado->sisDuplicadosEnArchivo),
            count($resultado->sisInvalidos),
        );
    }

    /**
     * Cursos del docente autenticado que puede gestionar y no están archivados.
     *
     * @return array<int, string>
     */
    private function cursosGestionables(): array
    {
        return Curso::query()
            ->where('sis_doc', (int) auth()->id())
            ->where('estado', '!=', CursoEstado::Archivado->value)
            ->orderBy('nombre_curso')
            ->pluck('nombre_curso', 'id_curso')
            ->all();
    }

    /**
     * Curso elegido en el selector, buscado dentro de los gestionables.
     */
    private function cursoSeleccionado(): ?Curso
    {
        if ($this->cursoId === null || ! array_key_exists($this->cursoId, $this->cursos)) {
            return null;
        }

        return Curso::find($this->cursoId);
    }

    /**
     * Indica si el curso ya tiene al menos un estudiante inscrito.
     *
     * @param  Curso  $curso  Curso a comprobar.
     */
    private function cursoTieneEstudiantes(Curso $curso): bool
    {
        return DB::table('estudiante_curso')->where('id_curso', $curso->id_curso)->exists();
    }

    /**
     * Garantiza que el curso elegido existe, es gestionable y es editable.
     *
     * @param  ?Curso  $curso  Curso elegido (o null).
     *
     * @throws AuthorizationException Si el curso no es gestionable.
     * @throws ValidationException Si el curso no existe o está archivado.
     */
    private function asegurarCursoValido(?Curso $curso): void
    {
        if ($curso === null) {
            $this->cursoId = null;

            throw ValidationException::withMessages([
                'cursoId' => 'Debe seleccionar un curso válido.',
            ]);
        }

        Gate::authorize('gestionar', $curso);
        app(AsegurarCursoEditableService::class)->asegurarCursoEditable($curso, 'cursoId');
    }
}

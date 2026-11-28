<?php

namespace App\Livewire\Cursos;

use App\Enums\CursoEstado;
use App\Models\Curso;
use App\Services\Curso\AsegurarCursoEditableService;
use App\Services\Curso\CargarListaEstudiantesService;
use App\Services\Curso\ListarCursosDocenteService;
use App\Support\DTO\CargarListaEstudiantesResultado;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
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
 * @updated 2026-11-24
 *
 * @description
 * Componente Livewire de la subopción «Cargar CSV» (HU 25). Reúne en una sola
 * pantalla el selector de curso (solo cursos activos y gestionables, al estilo
 * de «Habilitar estudiantes»), la zona de carga con arrastrar y soltar (visible
 * solo con un curso elegido) y el modal de confirmación de adición cuando el
 * curso ya tiene estudiantes: el archivo se previsualiza y el modal informa
 * cuántos estudiantes nuevos se añadirán y cuántos repetidos se ignorarán
 * (si no hay nuevos, avisa y no se realiza ningún cambio). Al terminar muestra
 * el resumen en la misma pantalla y descarta el archivo.
 *
 * @changelog
 * - 2026-11-21  [Equipo T1]  feat: carga de lista de estudiantes por Excel/CSV (HU 25).
 * - 2026-11-23  [Equipo T1]  feat: selector de curso integrado, drag & drop y
 *   modal de reemplazo; el curso puede llegar preseleccionado por ?curso (HU 25).
 * - 2026-11-24  [OchoaCesar]  feat: el modal de reemplazo pasa a ser de adición
 *   con previsualización (nuevos/repetidos) y la carga solo añade estudiantes (HU 25).
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

    public string $mensajeExito = '';

    public bool $mostrarModalAgregados = false;

    public int $nuevosConteo = 0;

    public int $repetidosConteo = 0;

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
            $this->asegurarCursoActivo($curso, 'curso');
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
        $this->mensajeExito = '';
        $this->mostrarModalAgregados = false;
        $this->nuevosConteo = 0;
        $this->repetidosConteo = 0;

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
     * Procesa la carga. Si el curso ya tiene estudiantes, previsualiza el
     * archivo (nuevos vs. repetidos) y abre el modal de confirmación de
     * adición en vez de guardar; si no, carga directamente.
     *
     * @param  CargarListaEstudiantesService  $servicio  Servicio de carga.
     */
    public function cargar(CargarListaEstudiantesService $servicio): void
    {
        $this->validate();
        $curso = $this->cursoSeleccionado();
        $this->asegurarCursoValido($curso);

        if ($this->cursoTieneEstudiantes($curso)) {
            try {
                $previa = $servicio->previsualizar($curso, $this->archivo);
            } catch (ValidationException $error) {
                $this->mensajeError = $error->validator->errors()->first() ?: 'No se pudo leer el archivo.';

                return;
            } catch (InvalidArgumentException $error) {
                $this->mensajeError = $error->getMessage();

                return;
            } catch (\Throwable) {
                $this->mensajeError = 'No se pudo leer el archivo. Inténtalo de nuevo.';

                return;
            }

            $this->mensajeError = '';
            $this->nuevosConteo = $previa->nuevos;
            $this->repetidosConteo = $previa->repetidos;
            $this->mostrarModalAgregados = true;

            return;
        }

        $this->procesar($servicio, $curso);
    }

    /**
     * Confirma la adición de los estudiantes nuevos desde el modal. Si el
     * archivo no trae ninguno nuevo, solo cierra el modal: no se sube nada.
     *
     * @param  CargarListaEstudiantesService  $servicio  Servicio de carga.
     */
    public function confirmarAgregacion(CargarListaEstudiantesService $servicio): void
    {
        if ($this->nuevosConteo === 0) {
            $this->cancelarAgregacion();

            return;
        }

        $this->validate();
        $curso = $this->cursoSeleccionado();
        $this->asegurarCursoValido($curso);

        $this->procesar($servicio, $curso);
    }

    /**
     * Cierra el modal de adición sin tocar la base de datos.
     */
    public function cancelarAgregacion(): void
    {
        $this->mostrarModalAgregados = false;
        $this->nuevosConteo = 0;
        $this->repetidosConteo = 0;
    }

    /**
     * Descarta el archivo y los avisos sin salir de la pantalla de carga.
     */
    public function cancelar(): void
    {
        $this->archivo = null;
        $this->mensajeError = '';
        $this->mensajeExito = '';
        $this->mostrarModalAgregados = false;
        $this->nuevosConteo = 0;
        $this->repetidosConteo = 0;
    }

    public function render(): mixed
    {
        return view('livewire.cursos.cargar-csv');
    }

    /**
     * Ejecuta la carga, arma el banner de resumen y cierra el modal si estaba
     * abierto. La carga solo añade estudiantes; los repetidos se ignoran.
     *
     * @param  CargarListaEstudiantesService  $servicio  Servicio de carga.
     * @param  Curso  $curso  Curso destino.
     */
    private function procesar(CargarListaEstudiantesService $servicio, Curso $curso): void
    {
        try {
            $resultado = $servicio->ejecutar($curso, $this->archivo, (int) auth()->id());

            $this->archivo = null;
            $this->mensajeError = '';
            $this->mostrarModalAgregados = false;
            $this->nuevosConteo = 0;
            $this->repetidosConteo = 0;
            $this->mensajeExito = $this->resumen($resultado);
        } catch (ValidationException $error) {
            $this->mostrarModalAgregados = false;
            $this->mensajeError = $error->validator->errors()->first() ?: 'No se pudo completar la carga.';
        } catch (InvalidArgumentException $error) {
            $this->mostrarModalAgregados = false;
            $this->mensajeError = $error->getMessage();
        } catch (\Throwable) {
            $this->mostrarModalAgregados = false;
            $this->mensajeError = 'No se pudo completar la carga. Inténtalo de nuevo.';
        }
    }

    /**
     * Resumen legible de la carga para el banner de éxito.
     *
     * @param  CargarListaEstudiantesResultado  $resultado  Resultado de la carga.
     * @return string Mensaje con añadidos, habilitados, deshabilitados, repetidos, duplicados y omitidos.
     */
    private function resumen(CargarListaEstudiantesResultado $resultado): string
    {
        return sprintf(
            'Se añadieron %d estudiante(s) nuevo(s): %d habilitado(s), %d deshabilitado(s) por la central de riesgos. '
            .'Repetidos ignorados: %d. Duplicados en el archivo: %d. Filas omitidas: %d.',
            $resultado->inscripcionesNuevas,
            $resultado->habilitados,
            $resultado->deshabilitados,
            $resultado->yaInscritos,
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
        return app(ListarCursosDocenteService::class)
            ->activos((int) auth()->id())
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
        $this->asegurarCursoActivo($curso, 'cursoId');
    }

    private function asegurarCursoActivo(Curso $curso, string $campo): void
    {
        if ($curso->estado !== CursoEstado::EnCurso->value) {
            throw ValidationException::withMessages([
                $campo => 'Solo puedes cargar estudiantes en cursos activos.',
            ]);
        }

        app(AsegurarCursoEditableService::class)->asegurarCursoEditable($curso, $campo);
    }
}

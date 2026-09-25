<?php

/**
 * @file    EstudiantesCurso.php
 *
 * @author  Equipo T1 <dev@techone.local>
 *
 * @created 2026-09-24
 *
 * @updated 2026-09-24
 *
 * @description
 * Componente Livewire de la feature Examenes: lista los estudiantes de un curso
 * con su estado de habilitación en el examen actual (habilitado/deshabilitado)
 * y las observaciones de la central de riesgos (sospechoso/tramposo/
 * pendiente/aula equivocada). Incluye búsqueda por nombre o código SIS,
 * filtros por estado con contadores y paginación; todo sin recargar la página.
 * Es de solo lectura: los estados solo se modifican desde la vista de examen.
 *
 * @see  App\Services\Examen\ListarEstudiantesCursoConEstadoService
 *
 * @changelog
 * - 2026-09-24  [T1]  feat: creación inicial del componente.
 */

namespace App\Livewire\Examenes;

use App\Models\Curso;
use App\Models\Rol;
use App\Models\Usuario;
use App\Services\Examen\ListarEstudiantesCursoConEstadoService;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use InvalidArgumentException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app', [
    'sidebarItems' => [
        ['label' => 'Inicio', 'route' => 'inicio'],
        ['label' => 'Materias', 'route' => 'materias'],
        ['label' => 'Monitor en vivo', 'route' => 'monitoreo'],
    ],
    // Es una vista de detalle: en mobile ya trae su propia flecha de
    // "volver" junto al titulo, asi que el trigger de hamburguesa global
    // sobra (y llegaba a superponerse con el encabezado del curso).
    'sidebarShowTrigger' => false,
])]
#[Title('Estudiantes del curso — Anticheat TIS')]
class EstudiantesCurso extends Component
{
    #[Locked]
    public Curso $curso;

    public string $estado = ListarEstudiantesCursoConEstadoService::FILTRO_TODOS;

    public string $busqueda = '';

    public int $pagina = 1;

    public string $mensajeError = '';

    private const POR_PAGINA = 8;

    private ListarEstudiantesCursoConEstadoService $servicio;

    public function boot(): void
    {
        $this->servicio = app(ListarEstudiantesCursoConEstadoService::class);
    }

    public function mount(Curso $curso): void
    {
        $this->curso = $curso;
    }

    #[Computed]
    public function estudiantes(): LengthAwarePaginator
    {
        $this->mensajeError = '';

        try {
            $this->servicio->validarBusqueda($this->busqueda !== '' ? $this->busqueda : null);

            return $this->servicio->ejecutar(
                $this->curso->id_curso,
                $this->estado,
                $this->busqueda !== '' ? $this->busqueda : null,
                max(1, $this->pagina),
                self::POR_PAGINA,
            );
        } catch (InvalidArgumentException $e) {
            $this->mensajeError = $e->getMessage();

            return $this->paginarVacio();
        }
    }

    #[Computed]
    public function conteos(): array
    {
        return $this->servicio->conteosPorEstado($this->curso->id_curso);
    }

    #[Computed]
    public function filtros(): array
    {
        return [
            ListarEstudiantesCursoConEstadoService::FILTRO_TODOS => 'Todos',
            ListarEstudiantesCursoConEstadoService::FILTRO_HABILITADOS => 'Habilitados',
            ListarEstudiantesCursoConEstadoService::FILTRO_DESHABILITADOS => 'Deshabilitados',
            ListarEstudiantesCursoConEstadoService::FILTRO_SOSPECHOSOS => 'Sospechosos',
            ListarEstudiantesCursoConEstadoService::FILTRO_TRAMPOSOS => 'Tramposos',
        ];
    }

    #[Computed]
    public function esAuxiliar(): bool
    {
        if (! auth()->check()) {
            return false;
        }

        $codSis = (string) (auth()->user()->getAttribute('cod_sis') ?? '');

        if ($codSis === '') {
            return false;
        }

        return Usuario::query()
            ->where('cod_sis', $codSis)
            ->whereHas('roles', fn ($q) => $q->where('nombre_rol', Rol::NOMBRE_AUXILIAR))
            ->exists();
    }

    public function filtrar(string $estado): void
    {
        if (! in_array($estado, ListarEstudiantesCursoConEstadoService::FILTROS_VALIDOS, true)) {
            return;
        }

        $this->estado = $estado;
        $this->pagina = 1;
        $this->mensajeError = '';
    }

    public function buscar(): void
    {
        $this->pagina = 1;
        $this->mensajeError = '';
    }

    public function irPagina(int $pagina): void
    {
        $this->pagina = max(1, $pagina);
    }

    public function render(): View
    {
        return view('livewire.examenes.estudiantes-curso');
    }

    /**
     * Paginador vacío devuelto cuando la búsqueda es inválida.
     */
    private function paginarVacio(): LengthAwarePaginator
    {
        return new LengthAwarePaginator(
            [],
            0,
            self::POR_PAGINA,
            max(1, $this->pagina),
            [
                'path' => LengthAwarePaginator::resolveCurrentPath(),
                'pageName' => 'page',
            ]
        );
    }
}

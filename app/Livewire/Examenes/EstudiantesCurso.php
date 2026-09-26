<?php

/**
 * @file    EstudiantesCurso.php
 *
 * @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
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
use App\Services\Examen\CambiarEstadoEstudianteService;
use App\Services\Examen\ListarEstudiantesCursoConEstadoService;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use InvalidArgumentException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use RuntimeException;

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

    // Estado de los modales de #25 (habilitar) y #26 (inhabilitar) — issue #27.
    // Vive acá porque tabla y modales comparten la misma pantalla; #25/#26
    // solo agregan el HTML condicional a estas propiedades/metodos, sin
    // tocar la logica de persistencia.
    public ?string $sisModalAbierto = null;

    public string $tipoModal = '';

    public string $motivoInhabilitacion = '';

    public bool $motivoValido = false;

    public string $mensajeErrorModal = '';

    private const POR_PAGINA = 8;

    private ListarEstudiantesCursoConEstadoService $servicio;

    private CambiarEstadoEstudianteService $servicioCambioEstado;

    public function boot(): void
    {
        $this->servicio = app(ListarEstudiantesCursoConEstadoService::class);
        $this->servicioCambioEstado = app(CambiarEstadoEstudianteService::class);
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

    /**
     * Abre el modal de #25 (confirmar habilitación) para un estudiante.
     */
    public function abrirModalHabilitar(string $sisEstudiante): void
    {
        $this->sisModalAbierto = $sisEstudiante;
        $this->tipoModal = 'habilitar';
        $this->mensajeErrorModal = '';
    }

    /**
     * Abre el modal de #26 (motivo de inhabilitación) para un estudiante.
     */
    public function abrirModalInhabilitar(string $sisEstudiante): void
    {
        $this->sisModalAbierto = $sisEstudiante;
        $this->tipoModal = 'inhabilitar';
        $this->motivoInhabilitacion = '';
        $this->motivoValido = false;
        $this->mensajeErrorModal = '';
    }

    /**
     * Cierra cualquiera de los dos modales sin tocar el estado persistido
     * (criterio 3 de #27).
     */
    public function cerrarModal(): void
    {
        $this->sisModalAbierto = null;
        $this->tipoModal = '';
        $this->motivoInhabilitacion = '';
        $this->motivoValido = false;
        $this->mensajeErrorModal = '';
    }

    /**
     * Hook de Livewire: se ejecuta en cada tecla del campo de motivo (si la
     * vista de #26 usa wire:model.live). Recalcula $motivoValido para que el
     * botón de confirmar se pueda deshabilitar sin duplicar la regla de
     * validación en la vista (criterio 1 de #27).
     */
    public function updatedMotivoInhabilitacion(): void
    {
        try {
            $this->servicioCambioEstado->validarMotivo($this->motivoInhabilitacion);
            $this->motivoValido = true;
        } catch (InvalidArgumentException) {
            $this->motivoValido = false;
        }
    }

    /**
     * Confirma la habilitación del estudiante con el modal abierto (#25).
     */
    public function confirmarHabilitar(): void
    {
        if ($this->sisModalAbierto === null) {
            return;
        }

        try {
            $this->servicioCambioEstado->habilitar($this->curso, $this->sisModalAbierto, $this->idUsuarioActual());
            $this->cerrarModal();
        } catch (InvalidArgumentException|RuntimeException $e) {
            $this->mensajeErrorModal = $e->getMessage();
        }
    }

    /**
     * Confirma la inhabilitación del estudiante con el modal abierto (#26).
     */
    public function confirmarInhabilitar(): void
    {
        if ($this->sisModalAbierto === null) {
            return;
        }

        try {
            $this->servicioCambioEstado->inhabilitar(
                $this->curso,
                $this->sisModalAbierto,
                $this->motivoInhabilitacion,
                $this->idUsuarioActual()
            );
            $this->cerrarModal();
        } catch (InvalidArgumentException|RuntimeException $e) {
            $this->mensajeErrorModal = $e->getMessage();
        }
    }

    /**
     * Usuario que hace el cambio, para auditoría (modificado_por). Mismo
     * patrón de esAuxiliar(): busca el Usuario por cod_sis del autenticado.
     *
     * TODO(@equipo, 2026-09-26): hoy siempre devuelve null en la práctica,
     * porque auth() todavía no está conectado a la tabla `usuario` (ver
     * revisión de #29) — no hay ningún login real implementado todavía.
     * No bloquea el cambio de estado; solo el campo modificado_por queda
     * vacío hasta que se resuelva esa brecha.
     */
    private function idUsuarioActual(): ?int
    {
        if (! auth()->check()) {
            return null;
        }

        $codSis = (string) (auth()->user()->getAttribute('cod_sis') ?? '');

        if ($codSis === '') {
            return null;
        }

        return Usuario::query()->where('cod_sis', $codSis)->value('id_usuario');
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

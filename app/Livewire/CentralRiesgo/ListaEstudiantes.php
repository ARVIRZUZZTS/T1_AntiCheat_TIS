<?php

/**
 * @file    ListaEstudiantes.php
 *
 * @author  Valery D. Ortuno P. <valerydariana98@gmail.com>
 *
 * @created 2026-10-10
 *
 * @updated 2026-10-10
 *
 * @description
 * Componente Livewire de la central de riesgos (HU 10): lista a los estudiantes
 * sancionados con su código SIS, materia y motivo, y muestra el detalle de la
 * incidencia del que se seleccione. Por defecto la lista va de la incidencia
 * más reciente a la más antigua y cubre toda la institución; se puede ordenar
 * A-Z o acotar a las materias del usuario, y buscar por nombre o código SIS
 * mientras se escribe. Muestra 8 registros por página.
 *
 * El orden, el alcance, la búsqueda y la página son estado del componente, así
 * que abrir y cerrar el detalle no los pierde.
 *
 * @see  App\Services\CentralRiesgo\ListarIncidenciasTramposoService
 * @see  App\Services\CentralRiesgo\ConsultarDetalleIncidenciaService
 * @see  App\Services\Examen\BusquedaEstudianteService
 * @see  resources/views/livewire/central-riesgo/lista-estudiantes.blade.php
 *
 * @changelog
 * - 2026-10-10  [Valery D. Ortuno P]  feat: creación inicial del componente.
 * - 2026-10-11  [Valery D. Ortuno P]  feat: el alcance por defecto pasa a ser
 *   "Toda la institución"; "Mis materias" se muestra a la derecha de ese tab.
 */

namespace App\Livewire\CentralRiesgo;

use App\Livewire\Monitoreo\RegistrarIncidencia;
use App\Services\CentralRiesgo\ConsultarDetalleIncidenciaService;
use App\Services\CentralRiesgo\ListarIncidenciasTramposoService;
use App\Services\Examen\BusquedaEstudianteService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

class ListaEstudiantes extends Component
{
    public string $alcance = ListarIncidenciasTramposoService::ALCANCE_INSTITUCION;

    public bool $ordenAlfabetico = false;

    public string $busqueda = '';

    public int $pagina = 1;

    /**
     * Incidencia (`id_registro`) cuyo detalle está abierto, o null si no hay.
     */
    public ?int $idSeleccionado = null;

    /**
     * Usuario dueño de "Mis materias".
     *
     * Es el autenticado cuando hay sesión. Mientras no exista login (#69) llega
     * por la URL, igual que en el registro de incidencias, y si tampoco viene
     * se usa el mismo usuario por defecto que ese formulario.
     */
    #[Locked]
    public int $usuario = RegistrarIncidencia::USUARIO_POR_DEFECTO;

    private const POR_PAGINA = 8;

    private ListarIncidenciasTramposoService $servicioLista;

    private ConsultarDetalleIncidenciaService $servicioDetalle;

    private BusquedaEstudianteService $servicioBusqueda;

    public function boot(): void
    {
        $this->servicioLista = app(ListarIncidenciasTramposoService::class);
        $this->servicioDetalle = app(ConsultarDetalleIncidenciaService::class);
        $this->servicioBusqueda = app(BusquedaEstudianteService::class);
    }

    public function mount(): void
    {
        $this->usuario = (int) (auth()->id()
            ?? request()->query('usuario', RegistrarIncidencia::USUARIO_POR_DEFECTO));
    }

    /**
     * Página actual de estudiantes sancionados, con el alcance, la búsqueda y
     * el orden vigentes.
     *
     * El servicio entrega la lista completa y acá se corta en páginas: la
     * central lista sanciones confirmadas, no inscritos, así que el total es
     * chico y no justifica otra consulta para contar.
     *
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    #[Computed]
    public function estudiantes(): LengthAwarePaginator
    {
        $incidencias = $this->servicioLista->ejecutar(
            $this->usuario,
            $this->alcance,
            $this->terminoBusqueda(),
            $this->ordenAlfabetico
                ? ListarIncidenciasTramposoService::ORDEN_ALFABETICO
                : ListarIncidenciasTramposoService::ORDEN_RECIENTES,
        );

        // Una página que ya no existe (quedó de un filtro anterior) cae en la
        // última disponible en vez de mostrar una lista vacía.
        $ultimaPagina = max(1, (int) ceil($incidencias->count() / self::POR_PAGINA));
        $pagina = min(max(1, $this->pagina), $ultimaPagina);

        return new LengthAwarePaginator(
            $incidencias->forPage($pagina, self::POR_PAGINA)->values(),
            $incidencias->count(),
            self::POR_PAGINA,
            $pagina,
        );
    }

    /**
     * Detalle de la incidencia seleccionada, o null si no hay ninguna abierta.
     *
     * @return ?array<string, mixed>
     */
    #[Computed]
    public function detalle(): ?array
    {
        if ($this->idSeleccionado === null) {
            return null;
        }

        try {
            return $this->servicioDetalle->ejecutar($this->idSeleccionado);
        } catch (ModelNotFoundException) {
            // La incidencia pudo descartarse mientras la lista estaba abierta.
            return null;
        }
    }

    public function cambiarAlcance(string $alcance): void
    {
        if (! in_array($alcance, ListarIncidenciasTramposoService::ALCANCES_VALIDOS, true)) {
            return;
        }

        $this->alcance = $alcance;
        $this->pagina = 1;
    }

    public function updatedBusqueda(): void
    {
        $this->pagina = 1;
    }

    public function updatedOrdenAlfabetico(): void
    {
        $this->pagina = 1;
    }

    public function irPagina(int $pagina): void
    {
        $this->pagina = max(1, $pagina);
    }

    public function verDetalle(int $idRegistro): void
    {
        $this->idSeleccionado = $idRegistro;
    }

    public function cerrarDetalle(): void
    {
        $this->idSeleccionado = null;
    }

    public function render(): View
    {
        return view('livewire.central-riesgo.lista-estudiantes');
    }

    /**
     * Término listo para el servicio: sin los caracteres que su modo no admite
     * (cifras para el código SIS, letras para el nombre).
     *
     * El input ya los descarta en el navegador; se repite acá porque la lista
     * se filtra en cada tecla y un caracter suelto no debe cortar la búsqueda
     * con un error de validación.
     */
    private function terminoBusqueda(): ?string
    {
        $termino = $this->servicioBusqueda->sanear(trim($this->busqueda));

        return $termino === '' ? null : $termino;
    }
}

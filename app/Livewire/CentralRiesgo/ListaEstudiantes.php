<?php

/**
 * @file    ListaEstudiantes.php
 *
 * @author  Valery D. Ortuno P. <valerydariana98@gmail.com>
 * @author  Alisson D. Alvarado <alvaradoalissondalet@gmail.com>
 *
 * @created 2026-10-10
 *
 * @updated 2026-10-11
 * @updated 2026-10-24
 * @updated 2026-11-09
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
 * - 2026-10-11  [Valery D. Ortuno P]  perf: la lista y el detalle se cachean por
 *   filtro/orden e id con invalidación por versión (cada escritura de una
 *   incidencia sube la versión), así abrir el detalle o repetir una búsqueda no
 *   vuelve a consultar Supabase. Las dos consultas de dominio bajan a una sola
 *   sentencia cada una.
 * - 2026-10-24  [Valery D. Ortuno P]  refactor: se quita el usuario que llegaba
 *   por la URL (`?usuario=`) y el usuario por defecto de antes del login:
 *   "Mis materias" usa siempre el usuario de la sesión.
 * - 2026-11-09  [Alisson D. Alvarado]  feat: revisar incidencias sospechosas del docente.
 */

namespace App\Livewire\CentralRiesgo;

use App\Services\CentralRiesgo\CacheCentralRiesgo;
use App\Services\CentralRiesgo\ConsultarDetalleIncidenciaService;
use App\Services\CentralRiesgo\ListarIncidenciasTramposoService;
use App\Services\CentralRiesgo\ListarIncidenciasSospechosasService;
use App\Services\CentralRiesgo\ResolverIncidenciaService;
use App\Services\Examen\BusquedaEstudianteService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Component;
use InvalidArgumentException;

class ListaEstudiantes extends Component
{
    public const ALCANCE_SOSPECHOSOS = 'sospechosos';

    public string $alcance = ListarIncidenciasTramposoService::ALCANCE_INSTITUCION;

    public bool $ordenAlfabetico = false;

    public string $busqueda = '';

    public int $pagina = 1;

    /**
     * Incidencia (`id_registro`) cuyo detalle está abierto, o null si no hay.
     */
    public ?int $idSeleccionado = null;

    public string $mensajeRevision = '';

    public string $errorRevision = '';

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
        $busqueda = $this->terminoBusqueda();
        $orden = $this->ordenAlfabetico
            ? ListarIncidenciasTramposoService::ORDEN_ALFABETICO
            : ListarIncidenciasTramposoService::ORDEN_RECIENTES;

        // Caché local por filtro y orden: abrir/cerrar el detalle o repetir una
        // búsqueda no vuelve a viajar a Supabase (~1.6 s por request). Cada
        // escritura de una incidencia sube la versión y renueva la clave.
        // "Mis materias" es siempre del usuario de la sesión (la ruta exige
        // login y rol docente), nunca de uno que venga por la URL.
        $usuario = (int) auth()->id();

        // Los pendientes se consultan nuevamente para reflejar revisiones de otros docentes.
        $incidencias = $this->alcance === self::ALCANCE_SOSPECHOSOS
            ? app(ListarIncidenciasSospechosasService::class)->ejecutar($usuario, $busqueda, $this->ordenAlfabetico)
            : Cache::remember(
                CacheCentralRiesgo::claveLista($usuario, $this->alcance, $busqueda, $this->ordenAlfabetico),
                CacheCentralRiesgo::ttlLista(),
                fn () => $this->servicioLista->ejecutar($usuario, $this->alcance, $busqueda, $orden),
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

        // Solo se abre un pendiente que figure en la página autorizada del docente.
        if ($this->alcance === self::ALCANCE_SOSPECHOSOS
            && ! $this->estudiantes->getCollection()->contains('id_registro', $this->idSeleccionado)) {
            return null;
        }

        return Cache::remember(
            CacheCentralRiesgo::claveDetalle($this->idSeleccionado),
            CacheCentralRiesgo::ttlDetalle(),
            function (): ?array {
                try {
                    return $this->servicioDetalle->ejecutar($this->idSeleccionado);
                } catch (ModelNotFoundException) {
                    // La incidencia pudo descartarse mientras la lista estaba abierta.
                    return null;
                }
            },
        );
    }

    public function cambiarAlcance(string $alcance): void
    {
        if (! in_array($alcance, [...ListarIncidenciasTramposoService::ALCANCES_VALIDOS, self::ALCANCE_SOSPECHOSOS], true)) {
            return;
        }

        $this->alcance = $alcance;
        $this->pagina = 1;
        $this->idSeleccionado = null;
        $this->mensajeRevision = $this->errorRevision = '';
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

    /**
     * Resuelve el caso seleccionado mediante el servicio que aplica la policy del curso.
     *
     * @param  string  $accion  Confirmar o rechazar la incidencia.
     * @return void
     * @throws AuthorizationException Si el docente ya no tiene acceso al curso.
     * @author Alisson D. Alvarado <alvaradoalissondalet@gmail.com>
     */
    public function resolverSospechoso(string $accion): void
    {
        if ($this->alcance !== self::ALCANCE_SOSPECHOSOS || $this->idSeleccionado === null
            || ! in_array($accion, ['confirmar', 'rechazar'], true)) {
            return;
        }

        $this->mensajeRevision = $this->errorRevision = '';

        try {
            $servicio = app(ResolverIncidenciaService::class);
            $servicio->{$accion}($this->idSeleccionado, auth()->user());
        } catch (ModelNotFoundException|InvalidArgumentException $error) {
            $this->idSeleccionado = null;
            $this->errorRevision = 'La incidencia ya no está pendiente de revisión.';

            return;
        }

        $this->idSeleccionado = null;
        $this->mensajeRevision = $accion === 'confirmar'
            ? 'Incidencia confirmada correctamente.'
            : 'Incidencia rechazada correctamente.';
        unset($this->estudiantes, $this->detalle);
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

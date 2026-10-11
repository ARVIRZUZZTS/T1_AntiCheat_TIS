<?php

/**
 * @file    Index.php
 *
 * @author  Alisson D. Alvarado <alvaradoalissondalet@gmail.com>
 *
 * @created 2026-10-10
 *
 * @updated 2026-10-11
 *
 * @description
 * Componente Livewire de página completa de la central de riesgos. Lista las
 * incidencias de tipo `tramposo` **confirmadas** (estudiantes con bloqueo
 * vigente) bajo dos alcances: "Mis materias" (solo los exámenes de los cursos
 * del docente) e "Institución" (todos), con búsqueda por nombre o código SIS.
 *
 * La lista completa y el orden A-Z los resuelve
 * {@see ListarIncidenciasTramposoService}, la misma fuente que alimenta el
 * endpoint `GET /api/central-riesgo`; el componente solo aporta las propiedades
 * de UI (alcance y búsqueda) y delega el resto, para no tener dos definiciones
 * del listado.
 *
 * Sustituye a la antigua `pages/central-riesgo.blade.php`, que pintaba datos
 * mockeados: a partir de ahora la vista consume el servicio real.
 *
 * @see  App\Services\CentralRiesgo\ListarIncidenciasTramposoService
 * @see  App\Http\Controllers\Api\CentralRiesgoController
 * @see  resources/views/livewire/central-riesgo/index.blade.php
 *
 * @changelog
 * - 2026-10-10  [Alisson D. Alvarado]  feat: creación del componente; la vista
 *   deja de usar datos mockeados y se conecta al servicio de listado real.
 * - 2026-10-10  [Alisson D. Alvarado]  fix: el alcance arranca en toda la
 *   institución. Sin login, "Mis materias" se resolvía con el usuario por
 *   defecto y la pantalla parecía vacía para quien no fuera ese docente.
 * - 2026-10-11  [Alisson D. Alvarado]  fix: la central solo muestra los
 *   registros confirmados; las pendientes se quedan fuera hasta que se
 *   confirmen.
 */

namespace App\Livewire\CentralRiesgo;

use App\Livewire\Monitoreo\RegistrarIncidencia;
use App\Services\CentralRiesgo\ListarIncidenciasTramposoService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * @author   Alisson D. Alvarado <alvaradoalissondalet@gmail.com>
 *
 * @since    2026-10-10
 */
class Index extends Component
{
    /**
     * Alcance activo del listado.
     *
     * Arranca en toda la institución porque la central de riesgos es una vista
     * de consulta global y todavía no hay login: "Mis materias" depende del
     * usuario en sesión y, sin autenticación, solo mostraría lo del docente
     * por defecto.
     *
     * @var string Uno de ListarIncidenciasTramposoService::ALCANCES_VALIDOS.
     */
    public string $alcance = ListarIncidenciasTramposoService::ALCANCE_INSTITUCION;

    /**
     * Término de búsqueda por nombre o código SIS.
     */
    public string $busqueda = '';

    /**
     * Mensaje de error de la última consulta (alcance o búsqueda inválidos).
     */
    public string $mensajeError = '';

    /**
     * Servicio de dominio que lista las incidencias tramposas.
     */
    private ListarIncidenciasTramposoService $servicio;

    /**
     * Resuelve el servicio en cada ciclo del componente.
     */
    public function boot(): void
    {
        $this->servicio = app(ListarIncidenciasTramposoService::class);
    }

    /**
     * Incidencias tramposas confirmadas que cumplen el alcance y la búsqueda.
     *
     * El servicio entrega todas las tramposas con su estado; la central filtra
     * a las `Confirmado` porque solo esas tienen bloqueo vigente. Cuando la
     * búsqueda o el alcance no son válidos (por ejemplo, alcance mis-materias
     * sin usuario) el servicio lanza InvalidArgumentException: se captura y se
     * expone como mensaje para la vista, devolviendo lista vacía.
     *
     * @return Collection<int, array<string, mixed>> Lista A-Z lista para la vista.
     */
    #[Computed]
    public function incidencias(): Collection
    {
        $this->mensajeError = '';

        try {
            return $this->servicio->ejecutar(
                $this->usuarioActualId(),
                $this->alcance,
                $this->busqueda !== '' ? $this->busqueda : null,
            )
                ->where('estado', 'Confirmado')
                ->values();
        } catch (InvalidArgumentException $e) {
            $this->mensajeError = $e->getMessage();

            return collect();
        }
    }

    /**
     * Cambia el alcance del listado.
     *
     * Ignora valores que no estén en ALCANCES_VALIDOS: el alcance gobernado por
     * el servicio no debe depender de que el navegador envíe solo lo permitido.
     *
     * @param  string  $alcance  Uno de ListarIncidenciasTramposoService::ALCANCES_VALIDOS.
     */
    public function cambiarAlcance(string $alcance): void
    {
        if (! in_array($alcance, ListarIncidenciasTramposoService::ALCANCES_VALIDOS, true)) {
            return;
        }

        $this->alcance = $alcance;
    }

    /**
     * Punto de entrada del buscador: normaliza el término escrito.
     */
    public function buscar(): void
    {
        $this->busqueda = trim($this->busqueda);
    }

    /**
     * Usuario que delimita el alcance mis-materias.
     *
     * No hay login conectado a `usuario` todavía; sin sesión se usa el usuario
     * sembrado por defecto, el mismo placeholder que usa MonitorEnVivo para
     * registrar. En cuanto exista login, `auth()->id()` resuelve el docente real.
     *
     * @return int id_usuario del docente dueño del alcance.
     */
    private function usuarioActualId(): int
    {
        return auth()->check()
            ? (int) auth()->id()
            : RegistrarIncidencia::USUARIO_POR_DEFECTO;
    }

    /**
     * Renderiza la central dentro del layout base de la aplicación.
     *
     * El título se declara como sección en la vista porque el componente delega
     * el layout con `->extends('layouts.app')`.
     *
     * @return View Vista del componente.
     */
    public function render(): View
    {
        return view('livewire.central-riesgo.index')->extends('layouts.app');
    }
}

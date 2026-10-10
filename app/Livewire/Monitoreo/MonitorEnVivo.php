<?php

/**
 * @file    MonitorEnVivo.php
 *
 * @author  David E. Chavez T. <virzuzz12345@gmail.com>
 *
 * @created 2026-10-10
 *
 * @updated 2026-10-10
 *
 * @description
 * Componente Livewire de página completa del monitor en vivo: resuelve el
 * examen más reciente y sus estudiantes con `GenerarReporteAsistenciaService`,
 * los muestra paginados y persiste las acciones que disparan los modales
 * (confirmar o rechazar un ingreso, permitirlo cuando el docente no habilitó o
 * cuando el estudiante está en la central de riesgos, y registrar una
 * incidencia de tramposo).
 *
 * Antes esto era `MonitoreoController` + `pages/monitoreo.blade.php`: la tabla
 * pintaba los estados con placeholders y el botón solo abría el modal, sin
 * guardar nada. Al pasar la pantalla a un componente Livewire, los modales
 * emiten sus eventos y el propio componente los escucha (`#[On]`) y escribe en
 * la base con `RegistroIngresoService`.
 *
 * Los modales despachan con Alpine `$dispatch`; Livewire escucha esos eventos
 * en `window` y los traduce a una llamada al método cuyos parámetros llevan el
 * mismo nombre que las claves del payload (`$sis`, `$hora`, `$motivo`,
 * `$detalle`), así que las firmas de abajo no se pueden renombrar sin tocar los
 * modales.
 *
 * @see  \App\Services\Monitoreo\GenerarReporteAsistenciaService
 * @see  \App\Services\Asistencia\RegistroIngresoService
 * @see  \App\Livewire\Monitoreo\RegistrarIncidencia
 *
 * @changelog
 * - 2026-10-10  [David E. Chavez T.]  feat: creación inicial. La pantalla deja
 *   de ser controlador + vista y pasa a ser componente Livewire de página
 *   completa, con la persistencia del flujo de ingreso conectada a los cinco
 *   modales.
 * - 2026-10-10  [T1]  fix: `#[Computed]` solo cachea con acceso de propiedad.
 *   `examen()`, `estudiantes()` y `conteos()` llamaban `informe()`, y al
 *   llamarlo como método Livewire ejecuta el método real y no el atributo, así
 *   que el reporte se calculaba **tres veces** por página: 28 consultas y 11s.
 *   Ahora leen `$this->informe`, el reporte se calcula una sola vez y la
 *   página baja a 10 consultas y ~5s.
 */

namespace App\Livewire\Monitoreo;

use App\Enums\Motivo;
use App\Enums\TipoInfraccion;
use App\Models\Examen;
use App\Services\Asistencia\RegistroIngresoService;
use App\Services\Monitoreo\GenerarReporteAsistenciaService;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * @author   David E. Chavez T. <virzuzz12345@gmail.com>
 *
 * @since    2026-10-10
 */
class MonitorEnVivo extends Component
{
    /**
     * Examen que sigue el monitor, fijado en `mount()` y no editable desde el
     * navegador.
     */
    #[Locked]
    public int $idExamen = 0;

    /**
     * Página actual de la tabla de estudiantes.
     */
    public int $pagina = 1;

    /**
     * Mensaje de éxito de la última acción, vacío mientras no haya ninguna.
     */
    public string $mensajeExito = '';

    /**
     * Mensaje de error de la última acción, vacío mientras no haya ninguna.
     */
    public string $mensajeError = '';

    /**
     * Servicio que arma el reporte de asistencia del examen.
     */
    private GenerarReporteAsistenciaService $servicioReporte;

    /**
     * Servicio que persiste las acciones del control de ingreso.
     */
    private RegistroIngresoService $servicioIngreso;

    /**
     * Resuelve los servicios en cada ciclo del componente.
     */
    public function boot(): void
    {
        $this->servicioReporte = app(GenerarReporteAsistenciaService::class);
        $this->servicioIngreso = app(RegistroIngresoService::class);
    }

    /**
     * Fija el examen más reciente, que es el que el monitor sigue.
     */
    public function mount(): void
    {
        $examen = Examen::query()
            ->orderByDesc('fecha')
            ->orderByDesc('id_examen')
            ->firstOrFail();

        $this->idExamen = (int) $examen->id_examen;
    }

    /**
     * Reporte de asistencia completo del examen.
     *
     * @return array{examen: Examen, estudiantes: Collection<int, array<string, mixed>>}
     */
    #[Computed]
    public function informe(): array
    {
        return $this->servicioReporte->ejecutar($this->idExamen);
    }

    /**
     * Examen que sigue el monitor.
     */
    #[Computed]
    public function examen(): Examen
    {
        return $this->informe['examen'];
    }

    /**
     * Estudiantes del examen, paginados.
     *
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    #[Computed]
    public function estudiantes(): LengthAwarePaginator
    {
        $todos = $this->informe['estudiantes'];
        $porPagina = (int) config('monitoreo.por_pagina', 10);
        $pagina = max(1, $this->pagina);

        return new LengthAwarePaginator(
            $todos->forPage($pagina, $porPagina)->values(),
            $todos->count(),
            $porPagina,
            $pagina,
            ['path' => LengthAwarePaginator::resolveCurrentPath()],
        );
    }

    /**
     * Conteos de las tarjetas del encabezado.
     *
     * `habilitados` y `deshabilitados` cuentan la habilitación de la
     * inscripción; `sospechosos` y `tramposos` el tipo de incidencia vigente
     * de cada estudiante. Son dos ejes distintos —un tramposo también está
     * habilitado o deshabilitado—, así que las cuatro tarjetas no suman
     * `inscritos`.
     *
     * @return array{inscritos: int, habilitados: int, deshabilitados: int, sospechosos: int, tramposos: int}
     */
    #[Computed]
    public function conteos(): array
    {
        $estudiantes = $this->informe['estudiantes'];

        $tipoIncidencia = fn (array $fila): ?string => $fila['incidencia']['tipo'] ?? null;

        return [
            'inscritos' => $estudiantes->count(),
            'habilitados' => $estudiantes->where('habilitado', true)->count(),
            'deshabilitados' => $estudiantes->where('habilitado', false)->count(),
            'sospechosos' => $estudiantes->filter(fn (array $fila): bool => $tipoIncidencia($fila) === TipoInfraccion::Sospechoso->value)->count(),
            'tramposos' => $estudiantes->filter(fn (array $fila): bool => $tipoIncidencia($fila) === TipoInfraccion::Tramposo->value)->count(),
        ];
    }

    /**
     * Cambia la página visible de la tabla.
     *
     * @param  int  $pagina  Página solicitada por la paginación.
     */
    public function irPagina(int $pagina): void
    {
        $this->pagina = max(1, $pagina);
    }

    /**
     * Confirma el ingreso de un estudiante (modal M1).
     *
     * @param  string  $sis  Código SIS del estudiante.
     * @param  ?string  $hora  Hora mostrada en el modal; el servicio guarda la real.
     */
    #[On('confirmar-ingreso')]
    public function confirmarIngreso(string $sis, ?string $hora = null): void
    {
        $this->ejecutarAccion(
            fn () => $this->servicioIngreso->registrar($sis, $this->idExamen, $this->registradorId()),
            'Ingreso registrado correctamente.',
        );
    }

    /**
     * Rechaza el ingreso de un estudiante: queda deshabilitado (M1/M3/M4).
     *
     * @param  string  $sis  Código SIS del estudiante.
     */
    #[On('rechazar-ingreso')]
    public function rechazarIngreso(string $sis): void
    {
        $this->ejecutarAccion(
            fn () => $this->servicioIngreso->rechazar($sis, $this->idExamen, $this->registradorId()),
            'Ingreso rechazado.',
        );
    }

    /**
     * Permite el ingreso de un estudiante no habilitado (M3/M4).
     *
     * @param  string  $sis  Código SIS del estudiante.
     */
    #[On('permitir-ingreso')]
    public function permitirIngreso(string $sis): void
    {
        $this->ejecutarAccion(
            fn () => $this->servicioIngreso->permitir($sis, $this->idExamen, $this->registradorId()),
            'Ingreso permitido y estudiante habilitado.',
        );
    }

    /**
     * Registra una incidencia de tramposo (modal M5).
     *
     * @param  string  $sis  Código SIS del estudiante.
     * @param  ?string  $motivo  Valor de un caso de `Motivo`.
     * @param  ?string  $detalle  Descripción libre del hecho.
     */
    #[On('registrar-tramposo')]
    public function registrarTramposo(string $sis, ?string $motivo = null, ?string $detalle = null): void
    {
        $motivoEnum = Motivo::tryFrom((string) $motivo);

        if ($motivoEnum === null) {
            $this->limpiarMensajes();
            $this->mensajeError = 'Seleccione un motivo válido de la lista.';

            return;
        }

        $this->ejecutarAccion(
            fn () => $this->servicioIngreso->registrarIncidencia(
                $sis,
                $this->idExamen,
                $this->registradorId(),
                $motivoEnum,
                $detalle,
            ),
            'Incidencia registrada en la central de riesgos.',
        );
    }

    /**
     * Renderiza el monitor dentro del layout base de la aplicación.
     *
     * El título va como sección en la vista, no con `#[Title]`, porque el
     * componente delega el layout con `->extends('layouts.app')`: Livewire
     * coloca el contenido en la sección `content` y el layout pinta el `h1`
     * con el `@yield('title')`.
     */
    public function render(): View
    {
        return view('livewire.monitoreo.monitor-en-vivo')->extends('layouts.app');
    }

    /**
     * Ejecuta una acción de persistencia y deja el resultado en los mensajes.
     *
     * @param  callable(): mixed  $accion  Escritura a realizar.
     * @param  string  $exito  Mensaje de éxito.
     */
    private function ejecutarAccion(callable $accion, string $exito): void
    {
        $this->limpiarMensajes();

        try {
            $accion();
            $this->mensajeExito = $exito;
        } catch (InvalidArgumentException $e) {
            $this->mensajeError = $e->getMessage();
        }
    }

    /**
     * Limpia los mensajes de la acción anterior.
     */
    private function limpiarMensajes(): void
    {
        $this->mensajeExito = '';
        $this->mensajeError = '';
    }

    /**
     * Usuario que queda como registrador de la acción.
     *
     * No hay login conectado a `usuario` todavía, así que sin sesión se usa el
     * primer usuario sembrado, el mismo por defecto que usa
     * `RegistrarIncidencia`.
     */
    private function registradorId(): int
    {
        return auth()->check()
            ? (int) auth()->id()
            : RegistrarIncidencia::USUARIO_POR_DEFECTO;
    }
}

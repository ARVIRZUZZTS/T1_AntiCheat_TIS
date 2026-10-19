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
 * - 2026-10-12  [Diego Tejerina]  fix: filtro ('todos'/'pendientes'/
 *   'tramposos') y búsqueda pasan de un script en el navegador (que ocultaba
 *   filas ya pintadas, por eso la página traía los 50 inscritos de una sola
 *   vez) a propiedades Livewire que filtran `$this->informe['estudiantes']`
 *   antes de paginar. `config('monitoreo.por_pagina')` baja de 50 a un
 *   número chico de verdad: la tabla ya no depende de traer el curso
 *   completo para que el filtro/búsqueda funcionen. No toca
 *   confirmarIngreso/rechazarIngreso/permitirIngreso/registrarTramposo.
 * - 2026-10-12  [Diego Tejerina]  perf: `informe()` se cachea un minuto por
 *   examen. Cada tecla del buscador es un request de Livewire nuevo y, sin
 *   caché, recalculaba las ~10 consultas del informe completo en cada una,
 *   sintiéndose muy lento. Se invalida en `ejecutarAccion()` y también desde
 *   `RegistrarIncidencia::registrar()`, que puede escribir en el mismo
 *   examen desde el buscador de registro de ingreso.
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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
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
     * Filtro activo de la tabla: 'todos', 'pendientes' o 'tramposos'.
     */
    public string $filtro = 'todos';

    /**
     * Término de búsqueda por nombre o código SIS.
     */
    public string $busqueda = '';

    /**
     * Vista activa del monitor: 'ingreso' (control de ingreso) o 'monitor'
     * (control durante el examen).
     */
    public string $modoVista = 'ingreso';

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
     * Prefijo de la clave de caché del informe de asistencia, una por examen.
     *
     * Pública porque `RegistrarIncidencia::registrar()` también escribe en
     * `central_riesgo` para un examen que este monitor puede estar mostrando
     * (origen `ORIGEN_MONITOREO`), y tiene que poder invalidar la misma clave.
     */
    public const CACHE_INFORME_PREFIJO = 'monitoreo.informe.';

    /**
     * Minutos que se cachea el informe antes de volver a consultar la base.
     */
    private const CACHE_INFORME_MINUTOS = 1;

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
        $ahora = now();

        $consulta = Examen::query();

        if (auth()->check() && auth()->user()->roles->contains('nombre_rol', \App\Models\Rol::NOMBRE_AUXILIAR)) {
            $idAuxiliar = (int) auth()->id();
            $cursosAsignados = \Illuminate\Support\Facades\DB::table('auxiliar_curso')
                ->where('id_auxiliar', $idAuxiliar)
                ->pluck('id_curso');

            $consulta->whereHas('cursos', function ($query) use ($cursosAsignados): void {
                $query->whereIn('id_curso', $cursosAsignados);
            });
        }

        $examen = $consulta
            ->where(function ($query) use ($ahora): void {
                $query->where('fecha', '>', $ahora->toDateString())
                    ->orWhere(function ($subquery) use ($ahora): void {
                        $subquery->where('fecha', $ahora->toDateString())
                            ->where('hora_inicio', '>=', $ahora->format('H:i:s'));
                    });
            })
            ->orderBy('fecha')
            ->orderBy('hora_inicio')
            ->first();

        if ($examen === null) {
            $fallback = Examen::query();

            if (auth()->check() && auth()->user()->roles->contains('nombre_rol', \App\Models\Rol::NOMBRE_AUXILIAR)) {
                $idAuxiliar = (int) auth()->id();
                $cursosAsignados = \Illuminate\Support\Facades\DB::table('auxiliar_curso')
                    ->where('id_auxiliar', $idAuxiliar)
                    ->pluck('id_curso');

                $fallback->whereHas('cursos', function ($query) use ($cursosAsignados): void {
                    $query->whereIn('id_curso', $cursosAsignados);
                });
            }

            $examen = $fallback
                ->orderByDesc('fecha')
                ->orderByDesc('id_examen')
                ->firstOrFail();
        }

        $this->idExamen = (int) $examen->id_examen;
    }

    /**
     * Reporte de asistencia completo del examen, cacheado un minuto.
     *
     * `#[Computed]` solo memoiza dentro del mismo request: cada tecla del
     * buscador (`wire:model.live.debounce`) es un request nuevo, así que sin
     * esta caché el filtro/búsqueda recalculaba el informe completo —y sus
     * ~10 consultas— en cada una. Se invalida en `ejecutarAccion()` para que
     * una escritura propia (confirmar/rechazar/permitir ingreso, registrar
     * tramposo) se vea de inmediato y no quede pisada por una versión vieja.
     *
     * @return array{examen: Examen, estudiantes: Collection<int, array<string, mixed>>}
     */
    #[Computed]
    public function informe(): array
    {
        return Cache::remember(
            self::CACHE_INFORME_PREFIJO.$this->idExamen,
            now()->addMinutes(self::CACHE_INFORME_MINUTOS),
            fn (): array => $this->servicioReporte->ejecutar($this->idExamen),
        );
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
     * Estudiantes del examen que pasan el filtro y la búsqueda actuales,
     * paginados.
     *
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    #[Computed]
    public function estudiantes(): LengthAwarePaginator
    {
        $todos = $this->estudiantesFiltrados();
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
     * Aplica el filtro de estado y la búsqueda por nombre/SIS sobre el
     * informe completo, antes de paginar.
     *
     * Antes esto lo hacía un script en el navegador, ocultando filas ya
     * pintadas: por eso la página traía los 50 inscritos de una sola vez
     * (`config('monitoreo.por_pagina')` era 50). Al paginar de a pocos en el
     * servidor, el filtro y la búsqueda tienen que viajar con la consulta
     * para seguir alcanzando a todo el curso y no solo a la página visible.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function estudiantesFiltrados(): Collection
    {
        $todos = $this->informe['estudiantes'];

        if ($this->modoVista === 'ingreso') {
            $todos = $todos->whereIn('estado_asistencia', ['pendiente', 'presente'])->values();
        }

        $filtrados = match ($this->filtro) {
            'pendientes' => $todos->where('estado_asistencia', 'pendiente'),
            'habilitados' => $todos->where('habilitado', true),
            'deshabilitados' => $todos->where('habilitado', false),
            'central-riesgos' => $todos->filter(
                fn (array $fila): bool => ($fila['incidencia'] ?? null) !== null
            ),
            'tramposos' => $todos->filter(
                fn (array $fila): bool => ($fila['incidencia']['tipo'] ?? null) === TipoInfraccion::Tramposo->value
            ),
            default => $todos,
        };

        $termino = Str::lower(trim($this->busqueda));

        if ($termino !== '') {
            $filtrados = $filtrados
                ->filter(function (array $fila) use ($termino): bool {
                    $texto = Str::lower(trim($fila['sis'].' '.$fila['nombre'].' '.$fila['apellido']));

                    return str_contains($texto, $termino);
                })
                ->values();
        }

        return $filtrados
            ->sort(function (array $a, array $b): int {
                if ($this->modoVista === 'ingreso') {
                    // Pendientes primero, luego ingresados.
                    $prioridadA = $a['estado_asistencia'] === 'pendiente' ? 0 : 1;
                    $prioridadB = $b['estado_asistencia'] === 'pendiente' ? 0 : 1;
                } else {
                    // Monitor: alfabético, pendientes al final.
                    $prioridadA = $a['estado_asistencia'] === 'pendiente' ? 1 : 0;
                    $prioridadB = $b['estado_asistencia'] === 'pendiente' ? 1 : 0;
                }

                if ($prioridadA !== $prioridadB) {
                    return $prioridadA <=> $prioridadB;
                }

                $nombreA = Str::lower(trim(($a['nombre'] ?? '').' '.($a['apellido'] ?? '')));
                $nombreB = Str::lower(trim(($b['nombre'] ?? '').' '.($b['apellido'] ?? '')));

                return $nombreA <=> $nombreB;
            })
            ->values();
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
    /**
     * Cambia la vista activa del monitor (ingreso / monitor) y vuelve a la
     * primera página.
     */
    /**
     * Ajusta la hora del examen actual para pruebas (mock). Se elimina en
     * producción.
     */
    public function ajustarExamenPrueba(int $minutos): void
    {
        $examen = $this->examen;
        $nuevaHora = now()->addMinutes($minutos);

        $examen->fecha = $nuevaHora->toDateString();
        $examen->hora_inicio = $nuevaHora->format('H:i:s');
        $examen->save();

        Cache::forget(self::CACHE_INFORME_PREFIJO.$this->idExamen);
    }

    public function cambiarVista(string $vista): void
    {
        if (! in_array($vista, ['ingreso', 'monitor'], true)) {
            return;
        }

        $this->modoVista = $vista;
        $this->pagina = 1;
    }

    public function irPagina(int $pagina): void
    {
        $this->pagina = max(1, $pagina);
    }

    /**
     * Cambia el filtro de estado de la tabla y vuelve a la primera página.
     *
     * @param  string  $filtro  'todos', 'pendientes' o 'tramposos'.
     */
    public function filtrar(string $filtro): void
    {
        if (! in_array($filtro, ['todos', 'pendientes', 'habilitados', 'deshabilitados', 'central-riesgos', 'tramposos'], true)) {
            return;
        }

        $this->filtro = $filtro;
        $this->pagina = 1;
    }

    /**
     * `wire:model.live.debounce.350ms="busqueda"` llama a este hook solo con
     * cada tecla (con el debounce ya aplicado): vuelve a la primera página
     * para que un resultado nuevo no quede mostrando una página vacía.
     */
    public function updatedBusqueda(): void
    {
        $this->pagina = 1;
    }

    /**
     * Enter dentro del buscador sigue sin botón "Buscar" visible, pero el
     * `<form>` necesita *algún* `wire:submit` para no hacer un submit nativo
     * (recargaría la página): `busqueda` ya viaja sola por `wire:model.live`,
     * así que acá alcanza con repetir el mismo reseteo de página.
     */
    public function buscar(): void
    {
        $this->pagina = 1;
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
    public function estadoAcceso(): string
    {
        $examen = $this->examen;
        $inicio = \Carbon\Carbon::parse($examen->fecha.' '.$examen->hora_inicio);
        $fin = $examen->hora_fin
            ? \Carbon\Carbon::parse($examen->fecha.' '.$examen->hora_fin)
            : $inicio->copy()->addMinutes((int) $examen->duracion);
        $apertura = $inicio->copy()->subMinutes(15);
        $ahora = now();

        if ($ahora < $apertura) {
            return 'proximo';
        }
        if ($ahora < $inicio) {
            return 'registro_abierto';
        }
        if ($ahora <= $fin) {
            return 'en_curso';
        }

        return 'finalizado';
    }

    public function minutosParaInicio(): int
    {
        $examen = $this->examen;
        $inicio = \Carbon\Carbon::parse($examen->fecha.' '.$examen->hora_inicio);

        return (int) now()->diffInMinutes($inicio, false);
    }

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
            Cache::forget(self::CACHE_INFORME_PREFIJO.$this->idExamen);
            $this->mensajeExito = $exito;
        } catch (InvalidArgumentException $e) {
            $this->mensajeError = $e->getMessage();
        }
    }

    /**
     * Limpia los mensajes de la acción anterior.
     *
     * Pública (no solo de uso interno de `ejecutarAccion()`): la vista la
     * llama también desde un `setTimeout` de Alpine para que el aviso
     * "Listo" no se quede para siempre en pantalla.
     */
    public function limpiarMensajes(): void
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

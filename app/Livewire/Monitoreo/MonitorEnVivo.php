<?php

/**
 * @file    MonitorEnVivo.php
 *
 * @author  David E. Chavez T. <virzuzz12345@gmail.com>
 *
 * @created 2026-10-10
 *
 * @updated 2026-10-10
 * @updated 2026-10-25
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
 * @see  \App\Services\CentralRiesgo\RegistrarIncidenciaService
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
 * - 2026-10-25  [Valery D. Ortuno P]  feat: el registro de incidencias pasa a
 *   RegistrarIncidenciaService (HU 5): motivos según la vista, tipo según el
 *   rol (auxiliar = sospechoso), modal de confirmación y registro
 *   de estudiantes no inscritos o no registrados (abrirRegistroNoInscrito(),
 *   consultarEstudiante(), registrarNoInscrito()), que reemplaza al botón
 *   "Reportar SIS" que llevaba a la página de registro, ya retirada; si se
 *   buscó un nombre, llega repartido entre nombres y apellidos.
 */

namespace App\Livewire\Monitoreo;

use App\Enums\TipoInfraccion;
use App\Models\Estudiante;
use App\Models\Examen;
use App\Models\Rol;
use App\Models\Usuario;
use App\Services\Asistencia\RegistroIngresoService;
use App\Services\CentralRiesgo\RegistrarIncidenciaService;
use App\Services\Monitoreo\GenerarReporteAsistenciaService;
use App\Support\DTO\RegistroIncidenciaDTO;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
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

    #[Locked]
    public int $idDocenteConfiguracion = 0;

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
     * True mientras se muestra el modal de confirmación de una incidencia
     * recién registrada; vuelve a false al presionar "Aceptar".
     */
    public bool $incidenciaRegistrada = false;

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
     * Pública para que otras pantallas que escriban en el mismo examen puedan
     * invalidar la misma clave.
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

        $consulta = $this->consultaExamenesAccesibles();

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
            $fallback = $this->consultaExamenesAccesibles();

            $examen = $fallback
                ->orderByDesc('fecha')
                ->orderByDesc('id_examen')
                ->firstOrFail();
        }

        $this->idExamen = (int) $examen->id_examen;
        $this->idDocenteConfiguracion = $this->resolverDocenteConfiguracion($examen);

        if (auth()->check()) {
            Gate::authorize('monitorear', $examen);
        }
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
            $this->claveCacheInforme(),
            now()->addMinutes(self::CACHE_INFORME_MINUTOS),
            fn (): array => $this->servicioReporte->ejecutar(
                $this->idExamen,
                idDocente: $this->idDocenteConfiguracion,
            ),
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

        Cache::forget($this->claveCacheInforme());
    }

    public function seleccionarExamen(int $idExamen): void
    {
        $examen = $this->consultaExamenesAccesibles()
            ->where('id_examen', $idExamen)
            ->first();

        if ($examen === null) {
            return;
        }

        $this->idExamen = (int) $examen->id_examen;
        $this->idDocenteConfiguracion = $this->resolverDocenteConfiguracion($examen);
        Cache::forget($this->claveCacheInforme());
        $this->pagina = 1;
        $this->busqueda = '';
        $this->filtro = 'todos';
    }

    public function proximosExamenes(): array
    {
        $examenes = $this->consultaExamenesAccesibles()
            ->with('cursos')
            ->where('fecha', '>=', now()->toDateString())
            ->orderBy('fecha')
            ->orderBy('hora_inicio')
            ->limit(5)
            ->get();

        return $examenes->map(function (Examen $examen): array {
            $cursoIds = $examen->cursos->pluck('id_curso');

            $auxiliares = 0;
            if ($cursoIds->isNotEmpty()) {
                $auxiliares = DB::table('auxiliar_curso')
                    ->whereIn('id_curso', $cursoIds)
                    ->distinct()
                    ->count('id_auxiliar');
            }

            return [
                'id' => $examen->id_examen,
                'materias' => $examen->cursos->pluck('nombre_curso')->implode(', '),
                'fecha' => $examen->fecha,
                'hora' => substr((string) $examen->hora_inicio, 0, 5),
                'auxiliares' => $auxiliares,
            ];
        })->all();
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
     * Abre el registro de incidencia de un estudiante que no está en la lista
     * del examen (no inscrito o no registrado en la base). Si lo buscado son
     * dígitos, el SIS llega precargado; si es un nombre, llegan precargados
     * los nombres y apellidos (separarNombreCompleto()).
     *
     * @author Valery D. Ortuno P <valerydariana98@gmail.com>
     * @since  2026-10-25
     */
    public function abrirRegistroNoInscrito(): void
    {
        $termino = trim($this->busqueda);
        $esSis = preg_match('/^\d+$/', $termino) === 1;

        $this->dispatch(
            'abrir-modal-no-inscrito',
            estudiante: $this->datosEstudiante($esSis ? $termino : ''),
            sugerido: $this->separarNombreCompleto($esSis ? '' : $termino),
        );
    }

    /**
     * Reparte un nombre completo buscado entre nombres y apellidos: con 2
     * palabras, 1 y 1; con 3, 1 nombre y 2 apellidos; con 4, 2 y 2; con 5 o
     * más, los 2 primeros son nombres y el resto apellidos.
     *
     * @param  string  $nombreCompleto  Lo escrito en el buscador.
     * @return array{nombre: string, apellido: string}
     *
     * @author Valery D. Ortuno P <valerydariana98@gmail.com>
     * @since  2026-10-25
     */
    private function separarNombreCompleto(string $nombreCompleto): array
    {
        $palabras = preg_split('/\s+/', trim($nombreCompleto), -1, PREG_SPLIT_NO_EMPTY);
        $cantidadNombres = count($palabras) <= 3 ? 1 : 2;

        return [
            'nombre' => implode(' ', array_slice($palabras, 0, $cantidadNombres)),
            'apellido' => implode(' ', array_slice($palabras, $cantidadNombres)),
        ];
    }

    /**
     * El modal del no inscrito pregunta por un SIS mientras se escribe: si
     * existe muestra sus nombres y apellidos; si no, los pide. Responde con
     * un evento para que el componente UI no dependa de Livewire.
     *
     * @param  string  $sis  Código SIS escrito en el modal.
     *
     * @author Valery D. Ortuno P <valerydariana98@gmail.com>
     * @since  2026-10-25
     */
    #[On('consultar-no-inscrito')]
    public function consultarEstudiante(string $sis): void
    {
        $this->dispatch('estudiante-no-inscrito-consultado', estudiante: $this->datosEstudiante($sis));
    }

    /**
     * @return array{sis: string, existe: bool, nombre: string, apellido: string, verificado: bool}
     */
    private function datosEstudiante(string $sis): array
    {
        $sis = trim($sis);
        $estudiante = $sis === '' ? null : Estudiante::query()->find($sis);

        return [
            'sis' => $sis,
            'existe' => $estudiante !== null,
            'nombre' => (string) ($estudiante?->nombre_estudiante ?? ''),
            'apellido' => (string) ($estudiante?->apellido_estudiante ?? ''),
            'verificado' => (bool) ($estudiante?->verificado ?? true),
        ];
    }

    /**
     * Rol con el que registra el usuario, para mostrar el estado en los modales:
     * un auxiliar registra "En revision" y un docente "Confirmado".
     *
     * @author Valery D. Ortuno P <valerydariana98@gmail.com>
     * @since  2026-10-25
     */
    #[Computed]
    public function registraComoAuxiliar(): bool
    {
        return RegistrarIncidenciaService::esAuxiliar($this->registrador());
    }

    /** Cierra el modal de confirmación del registro (botón Aceptar). */
    #[On('cerrar-resumen-incidencia')]
    public function cerrarResumen(): void
    {
        $this->incidenciaRegistrada = false;
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
            fn () => $this->servicioIngreso->registrar(
                $sis,
                $this->idExamen,
                $this->registradorId(),
                $this->idDocenteConfiguracion,
            ),
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
            fn () => $this->servicioIngreso->rechazar(
                $sis,
                $this->idExamen,
                $this->registradorId(),
                idDocente: $this->idDocenteConfiguracion,
            ),
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
            fn () => $this->servicioIngreso->permitir(
                $sis,
                $this->idExamen,
                $this->registradorId(),
                $this->idDocenteConfiguracion,
            ),
            'Ingreso permitido y estudiante habilitado.',
        );
    }

    /**
     * Registro rápido de incidencia de un estudiante de la lista (modal M5),
     * con los motivos de la vista activa (ingreso o monitor).
     *
     * @param  string  $sis  Código SIS del estudiante.
     * @param  ?string  $motivo  Valor de un caso de `Motivo`.
     * @param  ?string  $detalle  Descripción libre del hecho.
     */
    #[On('registrar-tramposo')]
    public function registrarTramposo(string $sis, ?string $motivo = null, ?string $detalle = null): void
    {
        $this->registrarIncidencia(new RegistroIncidenciaDTO(
            sis: $sis,
            idExamen: $this->idExamen,
            origen: $this->modoVista,
            motivo: (string) $motivo,
            detalle: $detalle,
        ));
    }

    /**
     * Registro de incidencia de un estudiante que no está en la lista del
     * examen. Si el SIS no existe en la base, se da de alta con los nombres y
     * apellidos que se escribieron (como no verificado).
     *
     * @author Valery D. Ortuno P <valerydariana98@gmail.com>
     * @since  2026-10-25
     */
    #[On('registrar-no-inscrito')]
    public function registrarNoInscrito(
        string $sis,
        ?string $motivo = null,
        ?string $detalle = null,
        ?string $nombre = null,
        ?string $apellido = null,
    ): void {
        $this->registrarIncidencia(new RegistroIncidenciaDTO(
            sis: $sis,
            idExamen: $this->idExamen,
            origen: $this->modoVista,
            motivo: (string) $motivo,
            detalle: $detalle,
            nombre: $nombre,
            apellido: $apellido,
        ));
    }

    /**
     * Registra con RegistrarIncidenciaService y abre el modal de confirmación;
     * un dato inválido queda en el mensaje de error.
     */
    private function registrarIncidencia(RegistroIncidenciaDTO $datos): void
    {
        $this->limpiarMensajes();
        $this->incidenciaRegistrada = false;

        try {
            app(RegistrarIncidenciaService::class)->ejecutar(
                $datos,
                $this->registrador(),
                $this->idDocenteConfiguracion,
            );
        } catch (InvalidArgumentException $e) {
            $this->mensajeError = $e->getMessage();

            return;
        }

        Cache::forget($this->claveCacheInforme());
        $this->incidenciaRegistrada = true;
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
        $inicio = Carbon::parse($examen->fecha.' '.$examen->hora_inicio);
        $fin = $examen->hora_fin
            ? Carbon::parse($examen->fecha.' '.$examen->hora_fin)
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
        $inicio = Carbon::parse($examen->fecha.' '.$examen->hora_inicio);

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
            Cache::forget($this->claveCacheInforme());
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
     * La ruta exige sesión; sin ella (pruebas que montan el componente solo)
     * se usa el creador del examen, en lugar del usuario por defecto que
     * tenía la página de registro de incidencias que se retiró.
     */
    private function registradorId(): int
    {
        return (int) $this->registrador()->id_usuario;
    }

    /** Usuario registrador como modelo (el servicio de incidencias lee sus roles). */
    private function registrador(): Usuario
    {
        return auth()->user() ?? Usuario::query()->findOrFail((int) $this->examen->creador);
    }

    private function consultaExamenesAccesibles()
    {
        $consulta = Examen::query();

        if (! auth()->check()) {
            return $consulta;
        }

        $usuario = auth()->user();
        if ($usuario->roles->contains('nombre_rol', Rol::NOMBRE_DOCENTE)) {
            return $consulta
                ->paraDocente((int) $usuario->id_usuario)
                ->whereHas('cursos', fn ($cursos) => $cursos
                    ->where('curso.sis_doc', $usuario->id_usuario));
        }

        $cursosAsignados = DB::table('auxiliar_curso')
            ->where('id_auxiliar', $usuario->id_usuario)
            ->pluck('id_curso');

        return $consulta->whereHas(
            'cursos',
            fn ($cursos) => $cursos->whereIn('curso.id_curso', $cursosAsignados)
        );
    }

    private function resolverDocenteConfiguracion(Examen $examen): int
    {
        if (auth()->check() && auth()->user()->roles->contains('nombre_rol', Rol::NOMBRE_DOCENTE)) {
            return (int) auth()->id();
        }

        if (auth()->check()) {
            $docente = DB::table('examen_curso as ec')
                ->join('auxiliar_curso as ac', 'ac.id_curso', '=', 'ec.id_curso')
                ->join('curso as c', 'c.id_curso', '=', 'ec.id_curso')
                ->where('ec.id_examen', $examen->id_examen)
                ->where('ac.id_auxiliar', auth()->id())
                ->orderBy('c.id_curso')
                ->value('c.sis_doc');

            if ($docente !== null) {
                return (int) $docente;
            }
        }

        return (int) $examen->creador;
    }

    private function claveCacheInforme(): string
    {
        return self::CACHE_INFORME_PREFIJO.$this->idExamen.'.docente.'.$this->idDocenteConfiguracion;
    }
}

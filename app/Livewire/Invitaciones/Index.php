<?php

/**
 * @file    Index.php
 *
 * @author  David E. Chavez T. <virzuzz12345@gmail.com>
 *
 * @created 2026-11-08
 *
 * @updated 2026-11-08
 * @updated 2026-11-22
 *
 * @description
 * Componente Livewire de invitaciones a exámenes compartidos. Ya no tiene un
 * conmutador de pestañas interno: cada subopción del sidebar (Enviar / Aceptar
 * invitación) es su propia ruta y fija la vista con el parámetro `tab` (valor
 * por defecto de la ruta). "Aceptar invitación" lista las recibidas, ordenables
 * de la más reciente a la más antigua.
 *
 * Enviar (HU 131): el docente elige uno de sus cursos activos y un examen
 * futuro de ese curso (con fecha y hora), busca al docente mientras escribe y
 * envía; ve el resumen de lo enviado y las invitaciones de ese examen, con
 * "Cancelar" en las pendientes.
 *
 * Aceptar (HU 132): al aceptar elige a cuales de sus cursos activos asociar el
 * examen (uno o varios); las aceptadas que quedaron sin curso se pueden
 * asociar despues. Los métodos que llama el modal devuelven el error a mostrar
 * ('' si salió bien).
 *
 * @changelog
 * - 2026-11-08  [David E. Chavez T.]  feat: creación inicial.
 * - 2026-11-15  [Diego Tejerina]  refactor: se separa en dos páginas (Enviar /
 *   Aceptar invitación); el `tab` llega por la ruta, se quita el conmutador y
 *   se agrega el orden (reciente / antigua) de las recibidas.
 * - 2026-11-22  [Valery D. Ortuno P]  feat: Enviar lista solo los cursos activos
 *   propios y los examenes futuros con fecha y hora (los recien creados se
 *   distinguen), busca mientras se escribe, muestra el resumen de lo enviado y
 *   las invitaciones del examen con "Cancelar" (HU 131). Aceptar pide los
 *   cursos a los que se asocia el examen y las aceptadas sin curso se pueden
 *   asociar (HU 132).
 * - 2026-11-22  [Valery D. Ortuno P]  refactor: aceptar y asociar son dos pasos
 *   del mismo modal (aceptar y despues elegir el curso, o "Mas tarde"), con
 *   mensajes de lo que pasa; los examenes del curso salen en una sola
 *   consulta.
 * - 2026-11-23  [Valery D. Ortuno P]  fix: el selector de curso de Enviar
 *   muestra solo cursos activos con examenes programados (los demas no
 *   llevaban a ningun examen y parecia que no cargaba).
 *
 * @see  App\Services\Invitacion\ListarInvitacionesRecibidasService
 * @see  App\Services\Invitacion\AsociarCursosInvitacionService
 */

namespace App\Livewire\Invitaciones;

use App\Enums\CursoEstado;
use App\Enums\TipoExamen;
use App\Models\Examen;
use App\Models\InvitacionExamenCompartido;
use App\Services\Invitacion\AsociarCursosInvitacionService;
use App\Services\Invitacion\BuscarDocentesInvitablesService;
use App\Services\Invitacion\CancelarInvitacionExamenService;
use App\Services\Invitacion\EnviarInvitacionExamenService;
use App\Services\Invitacion\ListarInvitacionesExamenService;
use App\Services\Invitacion\ListarInvitacionesRecibidasService;
use App\Services\Invitacion\ResponderInvitacionExamenService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Index extends Component
{
    public string $tab = 'recibir';

    public int $idCurso = 0;

    public int $idExamen = 0;

    public string $busquedaDocente = '';

    public ?int $docenteSeleccionado = null;

    public string $mensajeExito = '';

    public string $mensajeError = '';

    /** Resumen de la ultima invitacion enviada: examen y docente invitado. */
    public array $resumenEnvio = [];

    public array $invitacionesRecibidas = [];

    /** Aceptadas cuyo examen aun no esta en ningun curso propio. */
    public array $aceptadasSinCurso = [];

    /** Cursos activos propios: para enviar y para asociar al aceptar. */
    public array $cursos = [];

    public array $examenes = [];

    public array $docentes = [];

    /** Invitaciones enviadas del examen elegido. */
    public array $enviadas = [];

    /** Orden de las recibidas: 'reciente' (por defecto) o 'antigua'. */
    public string $ordenRecibidas = 'reciente';

    public function mount(
        AsociarCursosInvitacionService $asociar,
        ListarInvitacionesRecibidasService $recibidas,
        string $tab = 'recibir',
    ): void {
        // El sidebar decide la vista: Enviar fija 'enviar', Aceptar fija
        // 'recibir' (ver los `->defaults('tab', ...)` de las rutas).
        $this->tab = $tab === 'enviar' ? 'enviar' : 'recibir';

        // Enviar: solo cursos activos propios con algun examen programado (los
        // demas no llevan a ningun examen al que invitar). Aceptar: todos los
        // cursos activos propios, para asociar el examen compartido.
        $this->cursos = $this->tab === 'enviar'
            ? $this->cursosConExamenesProgramados()
            : $asociar->cursosDisponibles(auth()->user());

        if ($this->tab === 'recibir') {
            $this->invitacionesRecibidas = $recibidas->ejecutar(auth()->user())->all();
            $this->aplicarOrden();
            $this->aceptadasSinCurso = $asociar->aceptadasSinCurso(auth()->user());
        }
    }

    public function updatedIdCurso(): void
    {
        $this->idExamen = 0;
        $this->examenes = [];
        $this->reiniciarDocente();
        $this->enviadas = [];

        if ($this->idCurso <= 0 || ! collect($this->cursos)->contains('id', $this->idCurso)) {
            return;
        }

        // Solo examenes futuros (a uno que ya empezo no se puede invitar), en
        // una sola consulta. La fecha y hora en la etiqueta distinguen los del
        // mismo tipo, como un examen recien creado.
        $ahora = now();
        $this->examenes = DB::table('examen as e')
            ->join('examen_curso as ec', 'ec.id_examen', '=', 'e.id_examen')
            ->leftJoin('tipo_examen as t', 't.id_tipo_examen', '=', 'e.tipo_examen')
            ->where('ec.id_curso', $this->idCurso)
            ->where(fn ($consulta) => $consulta
                ->where('e.fecha', '>', $ahora->toDateString())
                ->orWhere(fn ($hoy) => $hoy
                    ->where('e.fecha', $ahora->toDateString())
                    ->where('e.hora_inicio', '>', $ahora->format('H:i:s'))))
            ->orderBy('e.fecha')
            ->orderBy('e.hora_inicio')
            ->get(['e.id_examen', 'e.nombre_examen', 'e.fecha', 'e.hora_inicio', 't.nombre_tipo_examen'])
            ->map(fn (object $examen): array => [
                'id' => (int) $examen->id_examen,
                'nombre' => $this->etiquetaExamen($examen),
            ])
            ->all();
    }

    public function updatedIdExamen(): void
    {
        $this->reiniciarDocente();
        $this->cargarEnviadas();
    }

    public function updatedBusquedaDocente(): void
    {
        $this->buscarDocentes(app(BuscarDocentesInvitablesService::class));
    }

    public function ordenarRecibidas(string $orden): void
    {
        $this->ordenRecibidas = $orden === 'antigua' ? 'antigua' : 'reciente';
        $this->aplicarOrden();
    }

    public function cargarRecibidas(ListarInvitacionesRecibidasService $servicio): void
    {
        $this->invitacionesRecibidas = $servicio->ejecutar(auth()->user())->all();
        $this->aplicarOrden();
        $this->aceptadasSinCurso = app(AsociarCursosInvitacionService::class)->aceptadasSinCurso(auth()->user());
    }

    /**
     * Ordena las recibidas por fecha de envío. `enviada_en` es ISO 8601, que
     * ordena cronológicamente como texto.
     */
    private function aplicarOrden(): void
    {
        usort($this->invitacionesRecibidas, function (array $a, array $b): int {
            $fechaA = (string) ($a['enviada_en'] ?? '');
            $fechaB = (string) ($b['enviada_en'] ?? '');

            return $this->ordenRecibidas === 'antigua'
                ? strcmp($fechaA, $fechaB)
                : strcmp($fechaB, $fechaA);
        });
    }

    public function buscarDocentes(BuscarDocentesInvitablesService $servicio): void
    {
        $this->mensajeError = '';
        $this->docenteSeleccionado = null;

        if ($this->idExamen <= 0 || mb_strlen(trim($this->busquedaDocente)) < 2) {
            $this->docentes = [];

            return;
        }

        $examen = Examen::query()->findOrFail($this->idExamen);
        $this->docentes = $servicio->ejecutar(auth()->user(), $examen, trim($this->busquedaDocente))->values()->all();
    }

    public function seleccionarDocente(int $idDocente): void
    {
        $this->docenteSeleccionado = collect($this->docentes)->contains('id', $idDocente) ? $idDocente : null;
    }

    public function enviar(EnviarInvitacionExamenService $servicio): void
    {
        $this->mensajeExito = '';
        $this->mensajeError = '';
        $this->resumenEnvio = [];

        if ($this->idExamen <= 0 || $this->docenteSeleccionado === null) {
            $this->mensajeError = 'Debe seleccionar un examen y un docente.';

            return;
        }

        $examen = Examen::query()->findOrFail($this->idExamen);

        try {
            $resultado = $servicio->ejecutar(auth()->user(), $examen, $this->docenteSeleccionado);
        } catch (ValidationException $error) {
            $this->mensajeError = (string) collect($error->errors())->flatten()->first();

            return;
        }

        $this->mensajeExito = 'Invitacion enviada con exito';
        $this->resumenEnvio = [
            'examen' => collect($this->examenes)->firstWhere('id', $this->idExamen)['nombre'] ?? '',
            'curso' => collect($this->cursos)->firstWhere('id', $this->idCurso)['nombre'] ?? '',
            'docente' => $resultado['invitado']['nombre'] ?? '',
        ];
        $this->reiniciarDocente();
        $this->cargarEnviadas();
    }

    public function cancelar(int $idInvitacion, CancelarInvitacionExamenService $servicio): void
    {
        $this->mensajeExito = '';
        $this->mensajeError = '';
        $this->resumenEnvio = [];

        $invitacion = InvitacionExamenCompartido::query()->findOrFail($idInvitacion);

        try {
            $servicio->cancelar(auth()->user(), $invitacion);
        } catch (ValidationException $error) {
            $this->mensajeError = (string) collect($error->errors())->flatten()->first();

            return;
        }

        $this->mensajeExito = 'Invitacion cancelada.';
        $this->cargarEnviadas();
    }

    /**
     * Acepta la invitacion (paso 1). Despues el modal pide los cursos.
     *
     * @return string Error para el modal, o '' si salio bien.
     */
    public function aceptar(int $idInvitacion): string
    {
        return $this->sobreInvitacion($idInvitacion, function (InvitacionExamenCompartido $invitacion): void {
            app(ResponderInvitacionExamenService::class)->aceptar(auth()->user(), $invitacion);
        });
    }

    /**
     * Asocia el examen de una invitacion aceptada a los cursos elegidos e
     * inscribe a sus estudiantes (paso 2, o despues desde la lista).
     *
     * @param  list<int>  $idsCursos
     * @return array{error: string, cursos: string} Error para el modal y los
     *                                              cursos asociados.
     */
    public function asociarCursos(int $idInvitacion, array $idsCursos): array
    {
        $error = $this->sobreInvitacion($idInvitacion, function (InvitacionExamenCompartido $invitacion) use ($idsCursos): void {
            app(AsociarCursosInvitacionService::class)->asociar(auth()->user(), $invitacion, $idsCursos);
        });

        $cursos = collect($this->cursos)
            ->whereIn('id', array_map('intval', $idsCursos))
            ->pluck('nombre')
            ->implode(', ');

        return ['error' => $error, 'cursos' => $error === '' ? $cursos : ''];
    }

    public function rechazar(int $idInvitacion, ResponderInvitacionExamenService $servicio): void
    {
        $this->mensajeExito = '';
        $this->mensajeError = '';

        $invitacion = InvitacionExamenCompartido::query()->findOrFail($idInvitacion);

        try {
            $servicio->rechazar(auth()->user(), $invitacion);
        } catch (ValidationException $error) {
            $this->mensajeError = (string) collect($error->errors())->flatten()->first();

            return;
        }

        $this->mensajeExito = 'Invitacion rechazada.';
        $this->cargarRecibidas(app(ListarInvitacionesRecibidasService::class));
        $this->dispatch('invitaciones-pendientes', pendientes: $this->contarPendientes());
    }

    public function render(): View
    {
        return view('livewire.invitaciones.index')->extends('layouts.app');
    }

    /**
     * Corre una accion sobre una invitacion recibida por el docente y
     * refresca las listas. Devuelve el error para el modal ('' si salio bien).
     */
    private function sobreInvitacion(int $idInvitacion, callable $accion): string
    {
        $this->mensajeExito = '';
        $this->mensajeError = '';

        $invitacion = InvitacionExamenCompartido::query()
            ->where('id_docente_invitado', auth()->id())
            ->find($idInvitacion);

        if ($invitacion === null) {
            return 'La invitacion ya no existe.';
        }

        try {
            $accion($invitacion);
        } catch (ValidationException $error) {
            return (string) collect($error->errors())->flatten()->first();
        }

        $this->cargarRecibidas(app(ListarInvitacionesRecibidasService::class));
        $this->dispatch('invitaciones-pendientes', pendientes: $this->contarPendientes());

        return '';
    }

    private function cargarEnviadas(): void
    {
        $this->enviadas = [];

        if ($this->idExamen <= 0) {
            return;
        }

        $examen = Examen::query()->find($this->idExamen);

        if ($examen !== null && auth()->user()->can('invitarDocente', $examen)) {
            $this->enviadas = app(ListarInvitacionesExamenService::class)->ejecutar(auth()->user(), $examen)->values()->all();
        }
    }

    /**
     * Cursos activos propios (no archivados ni finalizados) con al menos un
     * examen futuro, en una sola consulta.
     *
     * @return list<array{id: int, nombre: string}>
     */
    private function cursosConExamenesProgramados(): array
    {
        $ahora = now();

        return DB::table('curso as c')
            ->where('c.sis_doc', auth()->id())
            ->where('c.estado', CursoEstado::EnCurso->value)
            ->whereExists(fn ($consulta) => $consulta->from('examen_curso as ec')
                ->join('examen as e', 'e.id_examen', '=', 'ec.id_examen')
                ->whereColumn('ec.id_curso', 'c.id_curso')
                ->where(fn ($futuro) => $futuro
                    ->where('e.fecha', '>', $ahora->toDateString())
                    ->orWhere(fn ($hoy) => $hoy
                        ->where('e.fecha', $ahora->toDateString())
                        ->where('e.hora_inicio', '>', $ahora->format('H:i:s'))))
                ->selectRaw('1'))
            ->orderBy('c.nombre_curso')
            ->get(['c.id_curso', 'c.nombre_curso'])
            ->map(fn (object $curso): array => ['id' => (int) $curso->id_curso, 'nombre' => (string) $curso->nombre_curso])
            ->all();
    }

    private function reiniciarDocente(): void
    {
        $this->busquedaDocente = '';
        $this->docentes = [];
        $this->docenteSeleccionado = null;
    }

    /** "Examen parcial · 06/12/2026 06:00". */
    private function etiquetaExamen(object $examen): string
    {
        $nombre = $examen->nombre_examen
            ?? TipoExamen::etiquetaDe($examen->nombre_tipo_examen)
            ?? 'Examen #'.$examen->id_examen;
        $cuando = Carbon::parse($examen->fecha)->format('d/m/Y')
            .($examen->hora_inicio ? ' '.substr((string) $examen->hora_inicio, 0, 5) : '');

        return $nombre.' · '.$cuando;
    }

    private function contarPendientes(): int
    {
        return app(ListarInvitacionesRecibidasService::class)->contarPendientes(auth()->user());
    }
}

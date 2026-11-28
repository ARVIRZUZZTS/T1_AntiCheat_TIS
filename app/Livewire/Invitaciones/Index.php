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
 * @updated 2026-11-23
 *
 * @description
 * Componente Livewire de invitaciones a exámenes compartidos. Ya no tiene un
 * conmutador de pestañas interno: cada subopción del sidebar (Enviar / Aceptar
 * invitación) es su propia ruta y fija la vista con el parámetro `tab` (valor
 * por defecto de la ruta).
 *
 * Enviar (HU 131): el docente elige uno de sus cursos activos con examenes
 * programados y un examen futuro de ese curso (con fecha y hora), busca al
 * docente mientras escribe y envía; ve el resumen de lo enviado y las
 * invitaciones de ese examen con su estado.
 *
 * Aceptar (HU 132): una sola lista con las invitaciones pendientes (Aceptar /
 * Rechazar) y las aceptadas que aun no tienen curso (Asociar a un curso).
 * Aceptar pide directamente los cursos activos a los que se asocia el examen.
 * Los métodos que llama el modal devuelven el error a mostrar ('' si salió
 * bien).
 *
 * Cada viaje a la base remota pesa (~400 ms), asi que las listas salen en una
 * consulta y, tras una accion, se actualizan en memoria en vez de recargarse.
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
 * - 2026-11-23  [Valery D. Ortuno P]  fix: el selector de curso de Enviar
 *   muestra solo cursos activos con examenes programados (los demas no
 *   llevaban a ningun examen y parecia que no cargaba).
 * - 2026-11-23  [Valery D. Ortuno P]  refactor: Aceptar asocia el curso en el
 *   mismo paso, una sola lista para pendientes y aceptadas sin curso, y menos
 *   consultas en cada accion (listas en una consulta y actualizadas en
 *   memoria).
 * - 2026-11-23  [Valery D. Ortuno P]  fix: Enviar solo ofrece cursos y
 *   examenes futuros creados por el docente: solo el anfitrion invita.
 * - 2026-11-23  [Valery D. Ortuno P]  refactor: `rechazar()` y `cancelar()`
 *   devuelven el error para el modal de confirmacion de la vista (antes el
 *   confirm del navegador y un aviso en la pagina).
 * - 2026-11-28  [Valery D. Ortuno P]  refactor: se quita `cancelar()`: una
 *   invitacion enviada ya no se cancela, la acepta o rechaza el invitado.
 *
 * @see  App\Services\Invitacion\AsociarCursosInvitacionService
 * @see  App\Services\Invitacion\EnviarInvitacionExamenService
 */

namespace App\Livewire\Invitaciones;

use App\Enums\CursoEstado;
use App\Enums\InvitacionEstado;
use App\Enums\TipoExamen;
use App\Models\Examen;
use App\Services\Invitacion\AsociarCursosInvitacionService;
use App\Services\Invitacion\BuscarDocentesInvitablesService;
use App\Services\Invitacion\EnviarInvitacionExamenService;
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

    /** Resumen de la ultima invitacion enviada: curso, examen y docente. */
    public array $resumenEnvio = [];

    /** Pendientes y aceptadas sin curso propio (pantalla Aceptar). */
    public array $invitaciones = [];

    /** Cursos del docente: con examenes programados (Enviar) o activos (Aceptar). */
    public array $cursos = [];

    /** Examenes futuros del curso elegido: id, etiqueta y datos del examen. */
    public array $examenes = [];

    public array $docentes = [];

    /** Invitaciones enviadas del examen elegido. */
    public array $enviadas = [];

    public function mount(AsociarCursosInvitacionService $servicio, string $tab = 'recibir'): void
    {
        // El sidebar decide la vista: Enviar fija 'enviar', Aceptar fija
        // 'recibir' (ver los `->defaults('tab', ...)` de las rutas).
        $this->tab = $tab === 'enviar' ? 'enviar' : 'recibir';

        if ($this->tab === 'enviar') {
            // Solo cursos activos con examenes programados: los demas no
            // llevan a ningun examen al que invitar.
            $this->cursos = $this->cursosConExamenesProgramados();

            return;
        }

        $this->cursos = $servicio->cursosDisponibles(auth()->user());
        $this->invitaciones = $servicio->listar(auth()->user());
    }

    public function updatedIdCurso(): void
    {
        $this->idExamen = 0;
        $this->examenes = [];
        $this->reiniciarDocente();
        $this->enviadas = [];
        $this->resumenEnvio = [];

        if ($this->idCurso <= 0 || ! collect($this->cursos)->contains('id', $this->idCurso)) {
            return;
        }

        // Solo examenes futuros (a uno que ya empezo no se puede invitar). La
        // fecha y hora en la etiqueta distinguen los del mismo tipo.
        $ahora = now();
        $this->examenes = DB::table('examen as e')
            ->join('examen_curso as ec', 'ec.id_examen', '=', 'e.id_examen')
            ->leftJoin('tipo_examen as t', 't.id_tipo_examen', '=', 'e.tipo_examen')
            ->where('ec.id_curso', $this->idCurso)
            // Solo el anfitrion invita: un examen compartido que acepto no sale.
            ->where('e.creador', auth()->id())
            ->where(fn ($consulta) => $this->futuro($consulta, $ahora))
            ->orderBy('e.fecha')
            ->orderBy('e.hora_inicio')
            ->get(['e.id_examen', 'e.creador', 'e.nombre_examen', 'e.fecha', 'e.hora_inicio', 'e.duracion', 'e.tipo_examen', 't.nombre_tipo_examen'])
            ->map(fn (object $examen): array => [
                'id' => (int) $examen->id_examen,
                'nombre' => $this->etiquetaExamen($examen),
                // Datos del examen para no volver a consultarlo al buscar o enviar.
                'datos' => [
                    'id_examen' => (int) $examen->id_examen,
                    'creador' => (int) $examen->creador,
                    'nombre_examen' => $examen->nombre_examen,
                    'fecha' => Carbon::parse($examen->fecha)->toDateString(),
                    'hora_inicio' => $examen->hora_inicio,
                    'duracion' => $examen->duracion,
                    'tipo_examen' => $examen->tipo_examen,
                ],
            ])
            ->all();
    }

    public function updatedIdExamen(): void
    {
        $this->reiniciarDocente();
        $this->resumenEnvio = [];
        $this->cargarEnviadas();
    }

    public function updatedBusquedaDocente(): void
    {
        $this->mensajeError = '';
        $this->docenteSeleccionado = null;
        $examen = $this->examenElegido();

        if ($examen === null || mb_strlen(trim($this->busquedaDocente)) < 2) {
            $this->docentes = [];

            return;
        }

        $this->docentes = app(BuscarDocentesInvitablesService::class)
            ->ejecutar(auth()->user(), $examen, trim($this->busquedaDocente))
            ->values()
            ->all();
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
        $examen = $this->examenElegido();

        if ($examen === null || $this->docenteSeleccionado === null) {
            $this->mensajeError = 'Debe seleccionar un examen y un docente.';

            return;
        }

        try {
            $resultado = $servicio->ejecutar(auth()->user(), $examen, $this->docenteSeleccionado);
        } catch (ValidationException $error) {
            $this->mensajeError = (string) collect($error->errors())->flatten()->first();

            return;
        }

        $this->mensajeExito = 'Invitacion enviada con exito';
        $this->resumenEnvio = [
            'curso' => collect($this->cursos)->firstWhere('id', $this->idCurso)['nombre'] ?? '',
            'examen' => collect($this->examenes)->firstWhere('id', $this->idExamen)['nombre'] ?? '',
            'docente' => $resultado['invitado']['nombre'] ?? '',
        ];
        // La nueva va primera en la lista, sin volver a consultarla.
        array_unshift($this->enviadas, [
            'id' => (int) $resultado['id'],
            'estado' => InvitacionEstado::Pendiente->value,
            'enviada_en' => now()->format('d/m/Y'),
            'invitado' => $resultado['invitado']['nombre'] ?? '',
        ]);
        $this->reiniciarDocente();
    }

    /**
     * Acepta la invitacion y asocia el examen a los cursos elegidos.
     *
     * @param  list<int>  $idsCursos
     * @return string Error para el modal, o '' si salio bien.
     */
    public function aceptar(int $idInvitacion, array $idsCursos): string
    {
        return $this->responder(
            fn (AsociarCursosInvitacionService $servicio) => $servicio->aceptar(auth()->user(), $idInvitacion, $idsCursos),
            $idInvitacion,
            'Invitacion aceptada. El examen quedo en '.$this->nombresCursos($idsCursos).'.',
        );
    }

    /**
     * Asocia una invitacion ya aceptada a los cursos elegidos.
     *
     * @param  list<int>  $idsCursos
     * @return string Error para el modal, o '' si salio bien.
     */
    public function asociar(int $idInvitacion, array $idsCursos): string
    {
        return $this->responder(
            fn (AsociarCursosInvitacionService $servicio) => $servicio->asociar(auth()->user(), $idInvitacion, $idsCursos),
            $idInvitacion,
            'El examen quedo en '.$this->nombresCursos($idsCursos).'.',
        );
    }

    /**
     * Rechaza una invitacion. La confirmacion y el aviso "Invitacion
     * rechazada." son modales de la vista.
     *
     * @return string Error para el modal, o '' si salio bien.
     */
    public function rechazar(int $idInvitacion): string
    {
        return $this->responder(
            fn (AsociarCursosInvitacionService $servicio) => $servicio->rechazar(auth()->user(), $idInvitacion),
            $idInvitacion,
            '',
        );
    }

    public function render(): View
    {
        return view('livewire.invitaciones.index')->extends('layouts.app');
    }

    /**
     * Corre la respuesta, quita la invitacion de la lista (ya no esta
     * pendiente ni sin curso) y actualiza el contador del sidebar.
     */
    private function responder(callable $accion, int $idInvitacion, string $exito): string
    {
        $this->mensajeExito = '';
        $this->mensajeError = '';

        try {
            $accion(app(AsociarCursosInvitacionService::class));
        } catch (ValidationException $error) {
            return (string) collect($error->errors())->flatten()->first();
        }

        $this->mensajeExito = $exito;
        $this->invitaciones = array_values(array_filter(
            $this->invitaciones,
            fn (array $invitacion): bool => $invitacion['id'] !== $idInvitacion,
        ));
        $this->dispatch('invitaciones-pendientes', pendientes: count(array_filter(
            $this->invitaciones,
            fn (array $invitacion): bool => $invitacion['estado'] === InvitacionEstado::Pendiente->value,
        )));

        return '';
    }

    /** @param list<int> $idsCursos */
    private function nombresCursos(array $idsCursos): string
    {
        return collect($this->cursos)
            ->whereIn('id', array_map('intval', $idsCursos))
            ->pluck('nombre')
            ->implode(', ');
    }

    /**
     * Examen elegido sin volver a consultarlo: sus datos vienen de la lista
     * (creador para el permiso, fecha y hora para validar que sea futuro).
     */
    private function examenElegido(): ?Examen
    {
        $examen = collect($this->examenes)->firstWhere('id', $this->idExamen);

        if ($examen === null) {
            return null;
        }

        $modelo = (new Examen)->forceFill($examen['datos']);
        $modelo->exists = true;

        return $modelo;
    }

    /** Invitaciones que el docente envio para el examen elegido, en una consulta. */
    private function cargarEnviadas(): void
    {
        $this->enviadas = [];

        if ($this->examenElegido() === null) {
            return;
        }

        $this->enviadas = DB::table('invitacion_examen_compartido as i')
            ->join('usuario as u', 'u.id_usuario', '=', 'i.id_docente_invitado')
            ->where('i.id_examen', $this->idExamen)
            ->where('i.id_docente_invitador', auth()->id())
            ->orderByDesc('i.created_at')
            ->get(['i.id_invitacion', 'i.estado', 'i.created_at', 'u.nombre_usuario', 'u.apellido'])
            ->map(fn (object $fila): array => [
                'id' => (int) $fila->id_invitacion,
                'estado' => (string) $fila->estado,
                'enviada_en' => $fila->created_at ? Carbon::parse($fila->created_at)->format('d/m/Y') : '',
                'invitado' => trim($fila->nombre_usuario.' '.$fila->apellido),
            ])
            ->all();
    }

    /**
     * Cursos activos propios (no archivados ni finalizados) con al menos un
     * examen futuro creado por el docente, en una sola consulta.
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
                ->where('e.creador', auth()->id())
                ->where(fn ($futuro) => $this->futuro($futuro, $ahora))
                ->selectRaw('1'))
            ->orderBy('c.nombre_curso')
            ->get(['c.id_curso', 'c.nombre_curso'])
            ->map(fn (object $curso): array => ['id' => (int) $curso->id_curso, 'nombre' => (string) $curso->nombre_curso])
            ->all();
    }

    /** Examen futuro: fecha posterior a hoy, o de hoy con hora aun no pasada. */
    private function futuro($consulta, Carbon $ahora)
    {
        return $consulta
            ->where('e.fecha', '>', $ahora->toDateString())
            ->orWhere(fn ($hoy) => $hoy
                ->where('e.fecha', $ahora->toDateString())
                ->where('e.hora_inicio', '>', $ahora->format('H:i:s')));
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
}

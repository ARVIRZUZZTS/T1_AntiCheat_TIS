<?php

/**
 * @file    AsociarCursosInvitacionService.php
 *
 * @author  Valery D. Ortuno P <valerydariana98@gmail.com>
 *
 * @created 2026-11-22
 *
 * @description
 * Asocia un examen compartido a los cursos del docente invitado (HU 132).
 *
 * Un examen compartido pertenece a los cursos de cada docente: si es del curso
 * 1 del docente X y del curso 2 del docente Y, queda en los dos (`examen_curso`)
 * y cada docente inscribe a sus estudiantes con su propia configuracion.
 *
 *  - `asociar`: asocia el examen de una invitacion aceptada a los cursos
 *    elegidos (uno o varios). La pantalla lo pide justo despues de aceptar;
 *    si el docente elige "Mas tarde", queda en `aceptadasSinCurso`.
 *  - `cursosDisponibles`: los cursos activos propios del invitado.
 *  - `aceptadasSinCurso`: invitaciones aceptadas cuyo examen todavia no esta
 *    en ningun curso del invitado.
 *
 * La asociacion de cada curso la hace VincularCursoExamenCompartidoService,
 * que ya inscribe a los estudiantes del curso.
 *
 * @changelog
 * - 2026-11-22  [Valery D. Ortuno P]  feat: creacion inicial (HU 132).
 *
 * @see  App\Services\Invitacion\ResponderInvitacionExamenService
 * @see  App\Services\Examen\VincularCursoExamenCompartidoService
 */

namespace App\Services\Invitacion;

use App\Enums\CursoEstado;
use App\Enums\InvitacionEstado;
use App\Enums\TipoExamen;
use App\Models\Curso;
use App\Models\InvitacionExamenCompartido;
use App\Models\Usuario;
use App\Services\Examen\VincularCursoExamenCompartidoService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AsociarCursosInvitacionService
{
    public const MENSAJE_SIN_CURSOS = 'Elige al menos uno de tus cursos.';

    public const MENSAJE_CURSO_INVALIDO = 'Elige solo tus cursos activos.';

    public const MENSAJE_NO_ACEPTADA = 'La invitacion todavia no fue aceptada.';

    public function __construct(
        private readonly VincularCursoExamenCompartidoService $vincular,
    ) {}

    /**
     * Asocia una invitacion ya aceptada a los cursos elegidos.
     *
     * @param  list<int>  $idsCursos
     *
     * @throws ValidationException
     */
    public function asociar(Usuario $invitado, InvitacionExamenCompartido $invitacion, array $idsCursos): void
    {
        if ((int) $invitacion->id_docente_invitado !== (int) $invitado->id_usuario
            || $invitacion->estado !== InvitacionEstado::Aceptada) {
            throw ValidationException::withMessages(['invitacion' => self::MENSAJE_NO_ACEPTADA]);
        }

        $cursos = $this->cursosElegidos($invitado, $idsCursos);

        DB::transaction(fn () => $this->vincularCursos($invitado, $invitacion, $cursos));
    }

    /**
     * Cursos activos propios del docente, para elegir a cuales asociar.
     *
     * @return list<array{id: int, nombre: string}>
     */
    public function cursosDisponibles(Usuario $docente): array
    {
        return Curso::query()
            ->where('sis_doc', $docente->id_usuario)
            ->where('estado', CursoEstado::EnCurso->value)
            ->orderBy('nombre_curso')
            ->get(['id_curso', 'nombre_curso'])
            ->map(fn (Curso $curso): array => ['id' => (int) $curso->id_curso, 'nombre' => (string) $curso->nombre_curso])
            ->all();
    }

    /**
     * Invitaciones aceptadas cuyo examen no esta en ningun curso del docente.
     *
     * @return list<array{id: int, examen: string, invitador: string}>
     */
    public function aceptadasSinCurso(Usuario $docente): array
    {
        // Una sola consulta (cada viaje a la base remota pesa).
        return DB::table('invitacion_examen_compartido as i')
            ->join('examen as e', 'e.id_examen', '=', 'i.id_examen')
            ->leftJoin('tipo_examen as t', 't.id_tipo_examen', '=', 'e.tipo_examen')
            ->join('usuario as u', 'u.id_usuario', '=', 'i.id_docente_invitador')
            ->where('i.id_docente_invitado', $docente->id_usuario)
            ->where('i.estado', InvitacionEstado::Aceptada->value)
            ->whereNotExists(fn ($consulta) => $consulta->from('examen_curso as ec')
                ->join('curso as c', 'c.id_curso', '=', 'ec.id_curso')
                ->whereColumn('ec.id_examen', 'i.id_examen')
                ->where('c.sis_doc', $docente->id_usuario)
                ->selectRaw('1'))
            ->orderByDesc('i.created_at')
            ->get(['i.id_invitacion', 'e.id_examen', 'e.nombre_examen', 'e.fecha', 'e.hora_inicio', 't.nombre_tipo_examen', 'u.nombre_usuario', 'u.apellido'])
            ->map(fn (object $fila): array => [
                'id' => (int) $fila->id_invitacion,
                'examen' => $this->etiquetaExamen($fila),
                'invitador' => trim($fila->nombre_usuario.' '.$fila->apellido),
            ])
            ->all();
    }

    /**
     * Valida que los cursos elegidos sean activos y propios.
     *
     * @param  list<int>  $idsCursos
     * @return Collection<int, Curso>
     *
     * @throws ValidationException
     */
    private function cursosElegidos(Usuario $docente, array $idsCursos): Collection
    {
        $ids = collect($idsCursos)->map(fn ($id): int => (int) $id)->filter()->unique()->values();

        if ($ids->isEmpty()) {
            throw ValidationException::withMessages(['cursos' => self::MENSAJE_SIN_CURSOS]);
        }

        $cursos = Curso::query()
            ->whereIn('id_curso', $ids)
            ->where('sis_doc', $docente->id_usuario)
            ->where('estado', CursoEstado::EnCurso->value)
            ->get();

        if ($cursos->count() !== $ids->count()) {
            throw ValidationException::withMessages(['cursos' => self::MENSAJE_CURSO_INVALIDO]);
        }

        return $cursos;
    }

    /** @param Collection<int, Curso> $cursos */
    private function vincularCursos(Usuario $invitado, InvitacionExamenCompartido $invitacion, Collection $cursos): void
    {
        // VincularCursoExamenCompartidoService revisa el permiso (configurar),
        // que el curso sea del docente y que nada este archivado.
        $examen = $invitacion->examen()->firstOrFail();

        foreach ($cursos as $curso) {
            $this->vincular->ejecutar($invitado, $examen, $curso);
        }
    }

    /** "Examen parcial · 06/12/2026 06:00". */
    private function etiquetaExamen(object $fila): string
    {
        $nombre = $fila->nombre_examen
            ?? TipoExamen::etiquetaDe($fila->nombre_tipo_examen)
            ?? 'Examen #'.$fila->id_examen;
        $cuando = $fila->fecha
            ? ' · '.Carbon::parse($fila->fecha)->format('d/m/Y').($fila->hora_inicio ? ' '.substr((string) $fila->hora_inicio, 0, 5) : '')
            : '';

        return $nombre.$cuando;
    }
}

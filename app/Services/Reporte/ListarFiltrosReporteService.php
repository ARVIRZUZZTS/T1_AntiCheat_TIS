<?php

/**
 * @file    ListarFiltrosReporteService.php
 *
 * @author  Alex Candia <alex.leonar.candia@gmail.com>
 *
 * @created 2026-10-21
 *
 * @description
 * Arma los datos de los dos selectores de la pantalla de reportes: la lista de
 * materias (`id_curso => nombre_curso`) y los exámenes de cada curso
 * (`id_curso => [id, etiqueta]`). La etiqueta del examen ya viene legible
 * ("Examen final — 20/10/2026"), resuelta con el enum de tipo y la fecha.
 *
 * Solo devuelve las materias y exámenes que le corresponden al usuario:
 * - Docente: los cursos donde `curso.sis_doc` es su `id_usuario`.
 * - Auxiliar: los cursos asignados en `auxiliar_curso`.
 * - Con ambos roles: la unión de los dos conjuntos.
 * - Sin ninguno de esos roles: nada.
 *
 * Todo se resuelve en pocas consultas agrupadas (una por roles, una por
 * materias, una por exámenes): nunca una consulta por curso, aunque la lista de
 * materias crezca.
 *
 * La lógica vive acá y no en `routes/web.php` porque las rutas son solo la
 * capa HTTP de entrada: no deben contener consultas ni reglas de negocio
 * (ver arquitectura.md, secciones 4 y 8).
 *
 * @see  App\Services\Curso\ListarMateriasConMetricasService
 * @see  App\Enums\TipoExamen
 * @see  App\Models\Rol
 * @see  resources/views/pages/reportes.blade.php
 *
 * @changelog
 * - 2026-10-21  [Alex Candia]  feat: creación inicial del servicio.
 * - 2026-10-21  [Alex Candia]  feat: filtrar materias y exámenes por el usuario
 *   autenticado (cursos del docente y cursos asignados al auxiliar).
 */

namespace App\Services\Reporte;

use App\Enums\TipoExamen;
use App\Models\Curso;
use App\Models\Rol;
use App\Models\Usuario;
use App\Services\Curso\ListarMateriasConMetricasService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ListarFiltrosReporteService
{
    public function __construct(
        private readonly ListarMateriasConMetricasService $listarMaterias,
    ) {}

    /**
     * Datos listos para los selectores de la vista de reportes.
     *
     * @return array{
     *     opcionesMaterias: array<int, string>,
     *     examenesPorCurso: array<int, list<array{id: int, etiqueta: string}>>
     * }
     */
    public function ejecutar(?Usuario $usuario): array
    {
        $roles = $usuario === null
            ? collect()
            : $usuario->roles()->pluck('nombre_rol');
        $idsCursos = $usuario === null
            ? collect()
            : $this->idsCursosDelUsuario($usuario, $roles);

        return [
            'opcionesMaterias' => $this->opcionesMaterias($idsCursos),
            'examenesPorCurso' => $this->examenesPorCurso(
                $idsCursos,
                $roles->contains(Rol::NOMBRE_DOCENTE) ? $usuario : null,
            ),
        ];
    }

    /**
     * IDs de los cursos que le corresponden al usuario según sus roles.
     * Docente ve los cursos que dicta (`sis_doc`); auxiliar, los que tiene
     * asignados en `auxiliar_curso`. Si tiene ambos roles, se unen.
     *
     * @param  Collection<int, string>  $roles
     * @return Collection<int, int>
     */
    private function idsCursosDelUsuario(Usuario $usuario, Collection $roles): Collection
    {
        $ids = collect();

        if ($roles->contains(Rol::NOMBRE_DOCENTE)) {
            $ids = $ids->merge(
                Curso::query()
                    ->where('sis_doc', $usuario->id_usuario)
                    ->pluck('id_curso')
            );
        }

        if ($roles->contains(Rol::NOMBRE_AUXILIAR)) {
            $ids = $ids->merge(
                DB::table('auxiliar_curso')
                    ->where('id_auxiliar', $usuario->id_usuario)
                    ->pluck('id_curso')
            );
        }

        return $ids->map(fn ($id): int => (int) $id)->unique()->values();
    }

    /**
     * Materias en el formato que espera `x-ui.select` (id => nombre), tomadas
     * del mismo servicio que usa la página `/materias` pero acotadas a los
     * cursos del usuario.
     *
     * @param  Collection<int, int>  $idsCursos
     * @return array<int, string>
     */
    private function opcionesMaterias(Collection $idsCursos): array
    {
        return $this->listarMaterias->ejecutar($idsCursos->all())
            ->mapWithKeys(fn (array $fila): array => [
                $fila['curso']->id_curso => $fila['curso']->nombre_curso,
            ])
            ->all();
    }

    /**
     * Exámenes de los cursos del usuario en una sola consulta, agrupados por
     * curso. Un examen puede estar compartido entre cursos, por eso se agrupa
     * por la fila de `examen_curso` y no por `id_examen`.
     *
     * @param  Collection<int, int>  $idsCursos
     * @return array<int, list<array{id: int, etiqueta: string}>>
     */
    private function examenesPorCurso(Collection $idsCursos, ?Usuario $docente): array
    {
        if ($idsCursos->isEmpty()) {
            return [];
        }

        return DB::table('examen')
            ->join('examen_curso', 'examen_curso.id_examen', '=', 'examen.id_examen')
            ->leftJoin('tipo_examen', 'tipo_examen.id_tipo_examen', '=', 'examen.tipo_examen')
            ->whereIn('examen_curso.id_curso', $idsCursos)
            ->when($docente !== null, fn ($consulta) => $consulta->where(
                fn ($acceso) => $acceso
                    ->where('examen.creador', $docente->id_usuario)
                    ->orWhereExists(fn ($invitacion) => $invitacion
                        ->select(DB::raw('1'))
                        ->from('invitacion_examen_compartido')
                        ->whereColumn('invitacion_examen_compartido.id_examen', 'examen.id_examen')
                        ->where('invitacion_examen_compartido.id_docente_invitado', $docente->id_usuario)
                        ->where('invitacion_examen_compartido.estado', 'aceptado'))
            ))
            ->select(
                'examen_curso.id_curso',
                'examen.id_examen',
                'examen.fecha',
                'tipo_examen.nombre_tipo_examen',
            )
            ->orderByDesc('examen.fecha')
            ->get()
            ->groupBy('id_curso')
            ->map(fn ($examenesDelCurso): array => $examenesDelCurso
                ->map(fn (object $examen): array => [
                    'id' => (int) $examen->id_examen,
                    'etiqueta' => $this->etiquetaExamen($examen),
                ])
                ->values()
                ->all())
            ->all();
    }

    /**
     * Etiqueta legible del examen: tipo (mayúscula inicial) + fecha.
     *
     * @param  object{nombre_tipo_examen: ?string, fecha: ?string}  $examen
     */
    private function etiquetaExamen(object $examen): string
    {
        $tipo = TipoExamen::etiquetaDe($examen->nombre_tipo_examen) ?? 'Sin tipo';
        $fecha = $examen->fecha === null
            ? 'Sin fecha'
            : Carbon::parse($examen->fecha)->format('d/m/Y');

        return $tipo.' — '.$fecha;
    }
}

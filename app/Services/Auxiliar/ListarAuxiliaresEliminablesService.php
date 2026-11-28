<?php

/**
 * @file    ListarAuxiliaresEliminablesService.php
 *
 * @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
 *
 * @created 2026-11-27
 *
 * @description
 * Datos de la página "Eliminar auxiliar": los auxiliares que el usuario
 * autenticado puede eliminar, con su correo, tipo (UMSS / Externo), documento
 * (SIS o carnet), asignaciones vigentes, estado y fecha de creación.
 *
 * Alcance (igual que UsuarioPolicy::eliminar): el docente ve a los auxiliares de
 * los que es responsable (`id_docente_responsable`); el coordinador solo a los
 * que él creó (`id_creador`). Nunca a sí mismo ni a los eliminados.
 *
 * Las asignaciones y el estado (Habilitado = con asignación activa; Deshabilitado
 * = sin ninguna) se derivan de los cursos del docente responsable, con las mismas
 * consultas del repositorio que usa "Ver lista" (sin N+1): una por las cuentas,
 * una por las asignaciones de curso y una por las de examen.
 *
 * @changelog
 * - 2026-11-27  [Diego Tejerina]  feat: creación inicial.
 *
 * @see  App\Repositories\AuxiliarCursoRepository
 * @see  App\Livewire\Auxiliares\EliminarAuxiliar
 */

namespace App\Services\Auxiliar;

use App\Enums\EstadoCuenta;
use App\Models\Rol;
use App\Models\Usuario;
use App\Repositories\AuxiliarCursoRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ListarAuxiliaresEliminablesService
{
    public function __construct(
        private readonly AuxiliarCursoRepository $repositorio,
    ) {
    }

    /**
     * @param  Usuario  $viewer  Usuario autenticado (docente o coordinador).
     * @return array{
     *     auxiliares: list<array{id: int, nombreCompleto: string, correo: string, tipo: string, documento: string, asignaciones: list<string>, cursos: list<string>, estado: string}>,
     *     cursos: list<string>
     * }
     */
    public function ejecutar(Usuario $viewer): array
    {
        $idDocente = $this->docenteDeContexto($viewer);

        $asignaciones = $this->repositorio->asignacionesActivas($idDocente)->groupBy('id_auxiliar');
        $asignacionesExamen = $this->repositorio->asignacionesExamen($idDocente)->groupBy('id_auxiliar');

        $auxiliares = $this->cuentas($viewer)
            ->map(function (object $auxiliar) use ($asignaciones, $asignacionesExamen): array {
                $cursos = $asignaciones->get($auxiliar->id_usuario, collect())
                    ->pluck('nombre_curso')->unique()->values()->all();

                $examenes = $asignacionesExamen->get($auxiliar->id_usuario, collect())
                    ->unique('id_examen')
                    ->map(fn (object $examen): string => $this->etiquetaExamen($examen))
                    ->values()->all();

                $nombre = trim((string) ($auxiliar->nombre !== null && trim((string) $auxiliar->nombre) !== ''
                    ? $auxiliar->nombre
                    : $auxiliar->nombre_usuario));
                $esUmss = $auxiliar->tipo_auxiliar === 'UMSS' || $auxiliar->tipo_auxiliar === null;

                $asignacionesVigentes = array_merge(
                    array_map(fn (string $c): string => 'Curso: '.$c, $cursos),
                    array_map(fn (string $x): string => 'Examen: '.$x, $examenes),
                );

                return [
                    'id' => (int) $auxiliar->id_usuario,
                    'nombreCompleto' => trim($nombre.' '.$auxiliar->apellido),
                    'correo' => trim((string) $auxiliar->correo),
                    'tipo' => $esUmss ? 'UMSS' : 'Externo',
                    'documento' => $esUmss
                        ? (string) $auxiliar->cod_sis
                        : trim((string) $auxiliar->carnet),
                    'asignaciones' => $asignacionesVigentes,
                    'cursos' => $cursos,
                    'estado' => ($cursos === [] && $examenes === []) ? 'Deshabilitado' : 'Habilitado',
                ];
            })
            ->values()
            ->all();

        return [
            'auxiliares' => $auxiliares,
            'cursos' => $this->cursosDelDocente($idDocente),
        ];
    }

    /**
     * Cuentas de auxiliar que el viewer puede eliminar: las que es responsable
     * (docente) o las que creó (coordinador), sin las eliminadas ni la suya.
     *
     * @return \Illuminate\Support\Collection<int, \stdClass>
     */
    private function cuentas(Usuario $viewer): \Illuminate\Support\Collection
    {
        $columna = $viewer->esDocente() ? 'u.id_docente_responsable' : 'u.id_creador';

        return DB::table('usuario as u')
            ->join('rol_usuario as ru', 'ru.id_usuario', '=', 'u.id_usuario')
            ->join('rol as r', 'r.id_rol', '=', 'ru.id_rol')
            ->where('r.nombre_rol', Rol::NOMBRE_AUXILIAR)
            ->where('u.estado_cuenta', '!=', EstadoCuenta::Eliminada->value)
            ->where($columna, (int) $viewer->id_usuario)
            ->where('u.id_usuario', '!=', (int) $viewer->id_usuario)
            ->orderBy('u.apellido')
            ->orderBy('u.nombre_usuario')
            ->get([
                'u.id_usuario', 'u.cod_sis', 'u.nombre_usuario', 'u.nombre', 'u.apellido',
                'u.correo', 'u.carnet', 'u.tipo_auxiliar',
            ]);
    }

    /** Docente cuyos cursos definen las asignaciones: el propio o el responsable del coordinador. */
    private function docenteDeContexto(Usuario $viewer): int
    {
        if ($viewer->esDocente()) {
            return (int) $viewer->id_usuario;
        }

        return $viewer->id_docente_responsable !== null ? (int) $viewer->id_docente_responsable : 0;
    }

    /** Cursos del docente, para el filtro por curso. @return list<string> */
    private function cursosDelDocente(int $idDocente): array
    {
        return DB::table('curso')
            ->where('sis_doc', $idDocente)
            ->orderBy('nombre_curso')
            ->pluck('nombre_curso')
            ->map(fn (mixed $nombre): string => (string) $nombre)
            ->unique()
            ->values()
            ->all();
    }

    /** Etiqueta corta de un examen asignado: "Examen parcial · 22/11/2026". */
    private function etiquetaExamen(object $examen): string
    {
        $tipo = trim((string) ($examen->nombre_tipo_examen ?? ''));
        $tipo = $tipo === '' ? 'Examen' : ucfirst($tipo);
        $fecha = $examen->fecha !== null
            ? Carbon::parse($examen->fecha)->format('d/m/Y')
            : 'sin fecha';

        return $tipo.' · '.$fecha;
    }
}

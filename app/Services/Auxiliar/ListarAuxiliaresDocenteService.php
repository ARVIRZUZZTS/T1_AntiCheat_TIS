<?php

/**
 * @file    ListarAuxiliaresDocenteService.php
 *
 * @author  OchoaCesar <cesareduardonick@gmail.com>
 *
 * @created 2026-11-08
 *
 * @updated 2026-11-08
 *
 * @description
 * Servicio de dominio que lista todas las cuentas de auxiliar con sus cursos
 * activos del docente y su estado derivado de esas asignaciones.
 *
 * El repositorio entrega las cuentas y las asignaciones activas en dos consultas
 * (sin N+1); este servicio las proyecta al contrato de la lista —`id`, `nombre`,
 * `apellido`, `codigoSis`, `curso`, `estado`— con dos valores posibles:
 *
 *  - `ACTIVO`    si el auxiliar está asignado a al menos un curso del docente.
 *  - `INACTIVO`  si solo existe su cuenta, sin asignaciones activas.
 *
 * El estado grabado en `auxiliar_curso` ya no se muestra: una baja deja de ser
 * un estado de la lista y simplemente deja de contar como asignación.
 *
 * @changelog
 * - 2026-11-08  [OchoaCesar]  feat: creación inicial del servicio con
 *   normalización de estado a ACTIVO/BAJA.
 * - 2026-11-24  [Equipo T1]  refactor: el estado sale de las asignaciones
 *   activas (ACTIVO/INACTIVO) y se listan también las cuentas sin curso.
 * - 2026-11-08  [OchoaCesar]  fix: la docblock ya no referencia
 *   FuenteAuxiliares (la pestaña "Ver auxiliares" se retiró).
 *
 * @see  App\Repositories\AuxiliarCursoRepository
 * @see  App\Http\Controllers\Api\AuxiliaresDocenteController
 */

namespace App\Services\Auxiliar;

use App\Repositories\AuxiliarCursoRepository;
use App\Support\NombreCurso;
use Illuminate\Support\Carbon;

class ListarAuxiliaresDocenteService
{
    public function __construct(
        private readonly AuxiliarCursoRepository $repositorio,
    ) {}

    /**
     * Auxiliares del docente con el contrato de la fuente de la lista.
     *
     * @param  int  $idDocente  ID del usuario docente (`curso.sis_doc`).
     * @return list<array{id: int, nombre: string, apellido: string, nombreCompleto: string, usuario: string, correo: string, carnet: string, codigoSis: string, curso: string, cursos: list<string>, examenes: list<string>, coordinador: bool, estado: string}>
     */
    public function ejecutar(int $idDocente): array
    {
        $asignaciones = $this->repositorio->asignacionesActivas($idDocente)
            ->groupBy('id_auxiliar');
        $asignacionesExamen = $this->repositorio->asignacionesExamen($idDocente)
            ->groupBy('id_auxiliar');

        return $this->repositorio->auxiliares()
            ->map(function (object $auxiliar) use ($asignaciones, $asignacionesExamen): array {
                $cursos = $asignaciones->get($auxiliar->id_usuario, collect())
                    ->map(fn (object $asignacion): string => NombreCurso::conGestion(
                        (string) $asignacion->nombre_curso,
                        $asignacion->nombre_tipo_gestion !== null ? (string) $asignacion->nombre_tipo_gestion : null,
                        $asignacion->anio !== null ? (int) $asignacion->anio : null,
                    ))
                    ->unique()
                    ->values()
                    ->all();

                $examenes = $asignacionesExamen->get($auxiliar->id_usuario, collect())
                    ->unique('id_examen')
                    ->map(fn (object $examen): string => $this->etiquetaExamen($examen))
                    ->values()
                    ->all();

                $nombre = (string) ($auxiliar->nombre !== null && trim((string) $auxiliar->nombre) !== ''
                    ? $auxiliar->nombre
                    : $auxiliar->nombre_usuario);
                $correo = trim((string) $auxiliar->correo);

                return [
                    'id' => (int) $auxiliar->id_usuario,
                    'nombre' => $nombre,
                    'apellido' => (string) $auxiliar->apellido,
                    'nombreCompleto' => trim($nombre.' '.$auxiliar->apellido),
                    // El usuario de acceso es el nombre de usuario generado.
                    'usuario' => (string) $auxiliar->nombre_usuario,
                    'correo' => $correo,
                    'carnet' => trim((string) $auxiliar->carnet),
                    'codigoSis' => (string) $auxiliar->cod_sis,
                    'curso' => implode(', ', $cursos),
                    'cursos' => $cursos,
                    'examenes' => $examenes,
                    'coordinador' => (bool) $auxiliar->puede_crear_auxiliares,
                    'estado' => ($cursos === [] && $examenes === []) ? 'INACTIVO' : 'ACTIVO',
                ];
            })
            ->sortBy(fn (array $fila): string => ($fila['estado'] === 'INACTIVO' ? '1 ' : '0 ')
                .mb_strtolower($fila['apellido']).' '.mb_strtolower($fila['nombre']))
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

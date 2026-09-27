<?php
/**
 * @file    ConsultarEstadoEstudianteService.php
 *
 * @author  Alex Candia <alex.leonar.candia@gmail.com>
 *
 * @created 2026-09-26
 *
 * @updated 2026-09-26
 *
 * @description
 * Servicio para consultar el estado de un estudiante para el registro de asistencia en un examen.
 *
 * @changelog
 * - 2026-09-26  [T1]  feat: creación inicial del Servicio.
 */
namespace App\Services\Asistencia;

use App\Enums\EstadoEstudianteExamen;
use App\Models\CentralRiesgo;
use App\Models\Estudiante;
use App\Models\EstudianteExamen;
use App\Models\Examen;
use App\Models\RegistroAsistencia;
use App\Support\DTO\EstadoEstudianteDTO;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ConsultarEstadoEstudianteService
{
    public function consultar(string $sisEstudiante, int $idExamen): EstadoEstudianteDTO
    {
        $estudiante = $this->obtenerEstudiante($sisEstudiante);
        $nombre = $estudiante->nombre_estudiante . ' ' . $estudiante->apellido_estudiante;
        $estudianteExamen = $this->obtenerEstudianteExamen($sisEstudiante, $idExamen);

        if ($estudianteExamen === null) {
            return EstadoEstudianteDTO::sinRegistro($sisEstudiante, $nombre);
        }

        if ($estudianteExamen->estado === EstadoEstudianteExamen::Deshabilitado) {
            return EstadoEstudianteDTO::inhabilitado(
                sis: $sisEstudiante,
                nombre: $nombre,
                motivo: $estudianteExamen->motivo,
            );
        }

        $riesgo = $this->obtenerRiesgoActivo($sisEstudiante);

        if ($riesgo !== null) {
            return EstadoEstudianteDTO::enRiesgo(
                sis: $sisEstudiante,
                nombre: $nombre,
                motivo: $riesgo->detalle_motivo,
                fecha: $riesgo->fecha_registro?->format('d/m/Y'),
                tipoInfraccion: $riesgo->tipo_infraccion,
            );
        }

        return $this->construirCasoNormal($sisEstudiante, $nombre, $idExamen);
    }

    private function obtenerEstudiante(string $sis): Estudiante
    {
        return Estudiante::findOrFail($sis);
    }

    private function obtenerEstudianteExamen(string $sis, int $idExamen): ?EstudianteExamen
    {
        return EstudianteExamen::query()
            ->where('sis_estudiante', $sis)
            ->where('id_examen', $idExamen)
            ->first();
    }

    private function obtenerRiesgoActivo(string $sis): ?CentralRiesgo
    {
        return CentralRiesgo::query()
            ->join('registro_asistencia', 'registro_asistencia.id_ingreso', '=', 'central_riesgo.id_ingreso')
            ->where('registro_asistencia.id_estudiante', $sis)
            ->orderByDesc('central_riesgo.fecha_registro')
            ->select('central_riesgo.*')
            ->first();
    }

    private function construirCasoNormal(string $sis, string $nombre, int $idExamen): EstadoEstudianteDTO
    {
        $examen = Examen::with('tipoExamen')->findOrFail($idExamen);

        $asistencia = RegistroAsistencia::query()
            ->where('id_estudiante', $sis)
            ->where('id_examen', $idExamen)
            ->first();

        return EstadoEstudianteDTO::normal(
            sis: $sis,
            nombre: $nombre,
            examen: $examen,
            yaRegistro: $asistencia !== null,
            horaRegistro: $asistencia?->hora_ingreso?->format('H:i'),
        );
    }
}
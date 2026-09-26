<?php

/**
 * @file    RegistroIngresoService.php
 *
 * @author  Alex Candia <alex.leonar.candia@gmail.com>
 *
 * @created 2026-09-26
 *
 * @updated 2026-09-26
 *
 * @description
 * Servicio para registrar el ingreso de un estudiante a un examen.
 *
 * @changelog
 * - 2026-09-26  [T1]  feat: creación inicial del Servicio.
 */

namespace App\Services\Asistencia;

use App\Enums\EstadoEstudianteExamen;
use App\Exceptions\EstudianteNoHabilitadoException;
use App\Exceptions\IngresoDuplicadoException;
use App\Models\EstudianteExamen;
use App\Models\RegistroAsistencia;
use Illuminate\Support\Facades\DB;

class RegistrarIngresoService
{
    public function registrar(string $sisEstudiante, int $idExamen, int $idRegistrador): void
    {
        DB::transaction(function () use ($sisEstudiante, $idExamen, $idRegistrador) {
            $this->verificarHabilitado($sisEstudiante, $idExamen);
            $this->verificarNoDuplicado($sisEstudiante, $idExamen);

            RegistroAsistencia::create([
                'id_estudiante' => $sisEstudiante,
                'id_examen' => $idExamen,
                'id_registrador' => $idRegistrador,
                'hora_ingreso' => now()->format('H:i'),
            ]);
        });
    }

    private function verificarHabilitado(string $sis, int $idExamen): void
    {
        $estado = EstudianteExamen::query()
            ->where('sis_estudiante', $sis)
            ->where('id_examen', $idExamen)
            ->value('estado');

        if ($estado !== EstadoEstudianteExamen::HABILITADO) {
            throw EstudianteNoHabilitadoException::paraEstudiante($sis);
        }
    }

    private function verificarNoDuplicado(string $sis, int $idExamen): void
    {
        $existe = RegistroAsistencia::query()
            ->where('id_estudiante', $sis)
            ->where('id_examen', $idExamen)
            ->exists();

        if ($existe) {
            throw IngresoDuplicadoException::paraEstudiante($sis);
        }
    }
}
<?php

namespace App\Actions;

use App\Events\AsistenciaRegistrada;
use App\Models\Estudiante;
use App\Models\RegistroAsistencia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
class RegistrarAsistencia
{
    public function __invoke(int $idExamen, string $sisEstudiante): RegistroAsistencia
    {
        $registro = DB::transaction(function () use ($idExamen, $sisEstudiante) {
            $estudiante = Estudiante::findOrFail($sisEstudiante);

            $registro = RegistroAsistencia::create([
                'id_ingreso'    => $this->siguienteId(),
                'hora_ingreso'  => now()->format('H:i'),
                'id_examen'     => $idExamen,
                'id_estudiante' => $sisEstudiante,
                'id_registrador' => 3
            ]);

            return $registro;
        });

        // Emitir el evento FUERA de la transacción
        // (así solo se emite si el commit fue exitoso)
        $estudiante = Estudiante::find($sisEstudiante);

        Log::info('Emitiendo AsistenciaRegistrada', [
            'idExamen' => $idExamen,
            'sis'      => $sisEstudiante,
        ]);

        AsistenciaRegistrada::dispatch(
            $idExamen,
            $sisEstudiante,
            "{$estudiante->nombre_estudiante} {$estudiante->apellido_estudiante}",
            $registro->hora_ingreso,
            "3"
        );

        return $registro;
    }

    private function siguienteId(): int
    {
        return (int) RegistroAsistencia::max('id_ingreso') + 1;
    }
}
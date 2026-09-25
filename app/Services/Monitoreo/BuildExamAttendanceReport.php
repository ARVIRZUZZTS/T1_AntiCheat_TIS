<?php

namespace App\Services\Monitoreo;

use App\Models\AttendanceRecord;
use App\Models\Enrollment;
use App\Models\Exam;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class BuildExamAttendanceReport
{
    public function build(Exam $exam, ?Carbon $now = null): Collection
    {
        $now = $now ?? Carbon::now();
        $start = Carbon::parse($exam->fecha.' '.$exam->hora_inicio);
        $end = Carbon::parse($exam->fecha.' '.$exam->hora_fin);

        $records = AttendanceRecord::query()
            ->where('id_examen', $exam->id_examen)
            ->with('registrar')
            ->get()
            ->keyBy('id_estudiante');

        return Enrollment::query()
            ->where('id_examen', $exam->id_examen)
            ->with('student')
            ->get()
            ->map(function (Enrollment $enrollment) use ($records, $now, $start, $end) {
                $student = $enrollment->student;
                $record = $records->get($student->sis_estudiante);

                [$status, $observation] = $this->resolveAttendance($enrollment, $record, $now, $start, $end);

                return [
                    'sis' => $student->sis_estudiante,
                    'nombre' => $student->nombre_estudiante,
                    'apellido' => $student->apellido_estudiante,
                    'registrador' => $status === 'presente' && $record?->registrar
                        ? trim($record->registrar->nombre_usuario.' '.$record->registrar->apellido)
                        : null,
                    'estado_asistencia' => $status,
                    'observaciones' => $observation,
                ];
            });
    }

    private function resolveAttendance(Enrollment $enrollment, ?AttendanceRecord $record, Carbon $now, Carbon $start, Carbon $end): array
    {
        if ($enrollment->estado === 'deshabilitado') {
            return ['rechazado', $enrollment->motivo];
        }

        if ($now->lt($start)) {
            return ['ausente', 'habilitado'];
        }

        if ($record !== null) {
            return ['presente', 'habilitado'];
        }

        if ($now->lte($end)) {
            return ['pendiente', 'habilitado'];
        }

        return ['ausente', 'habilitado'];
    }
}

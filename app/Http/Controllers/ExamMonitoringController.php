<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Services\Monitoreo\BuildExamAttendanceReport;
use Carbon\Carbon;

class ExamMonitoringController extends Controller
{
    public function attendance(Exam $exam, BuildExamAttendanceReport $report)
    {
        return response()->json([
            'examen' => [
                'id_examen' => $exam->id_examen,
                'fecha' => $exam->fecha,
                'hora_inicio' => $exam->hora_inicio,
                'hora_fin' => $exam->hora_fin,
                'duracion' => $exam->duracion,
            ],
            'server_time' => Carbon::now()->toDateTimeString(),
            'estudiantes' => $report->build($exam),
        ]);
    }
}

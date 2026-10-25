<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('estudiante_examen', function (Blueprint $tabla): void {
            $tabla->unsignedInteger('id_configuracion_examen')->nullable();
            $tabla->integer('id_curso')->nullable();
            $tabla->index('id_configuracion_examen', 'estudiante_examen_config_index');
            $tabla->index('id_curso', 'estudiante_examen_curso_index');
            $tabla->foreign('id_configuracion_examen', 'fk_ee_configuracion')
                ->references('id_configuracion_examen')->on('configuracion_examen_docente');
            $tabla->foreign('id_curso', 'fk_ee_curso')
                ->references('id_curso')->on('curso');
        });

        Schema::table('registro_asistencia', function (Blueprint $tabla): void {
            $tabla->integer('id_estudiante_examen')->nullable();
            $tabla->index('id_estudiante_examen', 'registro_asistencia_inscripcion_index');
            $tabla->foreign('id_estudiante_examen', 'fk_ra_inscripcion')
                ->references('id_estudiante_examen')->on('estudiante_examen');
        });

        Schema::table('central_riesgo', function (Blueprint $tabla): void {
            $tabla->integer('id_estudiante_examen')->nullable();
            $tabla->index('id_estudiante_examen', 'central_riesgo_inscripcion_index');
            $tabla->foreign('id_estudiante_examen', 'fk_cr_inscripcion')
                ->references('id_estudiante_examen')->on('estudiante_examen');
        });

        $this->completarInscripciones();
        $this->completarReferencias();

        Schema::table('estudiante_examen', function (Blueprint $tabla): void {
            $tabla->unique(
                ['id_examen', 'id_configuracion_examen', 'sis_estudiante'],
                'estudiante_examen_docente_estudiante_unique'
            );
        });
        Schema::table('registro_asistencia', function (Blueprint $tabla): void {
            $tabla->unique('id_estudiante_examen', 'registro_asistencia_inscripcion_unique');
        });
    }

    public function down(): void
    {
        Schema::table('central_riesgo', function (Blueprint $tabla): void {
            $tabla->dropForeign('fk_cr_inscripcion');
            $tabla->dropIndex('central_riesgo_inscripcion_index');
            $tabla->dropColumn('id_estudiante_examen');
        });
        Schema::table('registro_asistencia', function (Blueprint $tabla): void {
            $tabla->dropUnique('registro_asistencia_inscripcion_unique');
            $tabla->dropForeign('fk_ra_inscripcion');
            $tabla->dropIndex('registro_asistencia_inscripcion_index');
            $tabla->dropColumn('id_estudiante_examen');
        });
        Schema::table('estudiante_examen', function (Blueprint $tabla): void {
            $tabla->dropUnique('estudiante_examen_docente_estudiante_unique');
            $tabla->dropForeign('fk_ee_configuracion');
            $tabla->dropForeign('fk_ee_curso');
            $tabla->dropIndex('estudiante_examen_config_index');
            $tabla->dropIndex('estudiante_examen_curso_index');
            $tabla->dropColumn(['id_configuracion_examen', 'id_curso']);
        });
    }

    private function completarInscripciones(): void
    {
        $configuraciones = DB::table('configuracion_examen_docente')
            ->get()
            ->keyBy(fn (object $fila): string => $fila->id_examen.'-'.$fila->id_docente);

        DB::table('estudiante_examen')
            ->orderBy('id_estudiante_examen')
            ->chunkById(200, function ($inscripciones) use ($configuraciones): void {
                foreach ($inscripciones as $inscripcion) {
                    $cursos = DB::table('examen_curso as ec')
                        ->join('estudiante_curso as ecur', function ($join) use ($inscripcion): void {
                            $join->on('ecur.id_curso', '=', 'ec.id_curso')
                                ->where('ecur.sis_estudiante', $inscripcion->sis_estudiante);
                        })
                        ->join('curso as c', 'c.id_curso', '=', 'ec.id_curso')
                        ->where('ec.id_examen', $inscripcion->id_examen)
                        ->orderBy('ec.id_curso')
                        ->get(['ec.id_curso', 'c.sis_doc']);

                    if ($cursos->pluck('sis_doc')->unique()->count() > 1) {
                        throw new RuntimeException(
                            'La inscripción '.$inscripcion->id_estudiante_examen
                            .' pertenece a cursos de distintos docentes; requiere mapeo manual.'
                        );
                    }

                    $curso = $cursos->first();
                    $idDocente = $curso?->sis_doc;

                    if ($idDocente === null) {
                        $idDocente = DB::table('examen')
                            ->where('id_examen', $inscripcion->id_examen)
                            ->value('creador');
                    }

                    $configuracion = $configuraciones->get($inscripcion->id_examen.'-'.$idDocente);
                    if ($configuracion === null) {
                        throw new RuntimeException(
                            'No se encontró configuración para la inscripción '.$inscripcion->id_estudiante_examen.'.'
                        );
                    }

                    DB::table('estudiante_examen')
                        ->where('id_estudiante_examen', $inscripcion->id_estudiante_examen)
                        ->update([
                            'id_configuracion_examen' => $configuracion->id_configuracion_examen,
                            'id_curso' => $curso?->id_curso,
                        ]);
                }
            }, 'id_estudiante_examen');
    }

    private function completarReferencias(): void
    {
        foreach (['registro_asistencia' => 'id_estudiante', 'central_riesgo' => 'sis_estudiante'] as $tabla => $columnaSis) {
            $columnaId = $tabla === 'registro_asistencia' ? 'id_ingreso' : 'id_registro';

            DB::table($tabla)->orderBy($columnaId)->chunkById(200, function ($filas) use ($tabla, $columnaSis, $columnaId): void {
                foreach ($filas as $fila) {
                    $consulta = DB::table('estudiante_examen')
                        ->where('id_examen', $fila->id_examen)
                        ->where('sis_estudiante', $fila->{$columnaSis});

                    if ($tabla === 'central_riesgo' && $fila->id_curso !== null) {
                        $consulta->where('id_curso', $fila->id_curso);
                    }

                    $ids = $consulta->pluck('id_estudiante_examen');
                    if ($ids->count() > 1) {
                        throw new RuntimeException(
                            'No se puede asociar '.$tabla.' '.$fila->{$columnaId}
                            .' porque coincide con más de una inscripción.'
                        );
                    }
                    if ($ids->count() === 1) {
                        DB::table($tabla)->where($columnaId, $fila->{$columnaId})->update([
                            'id_estudiante_examen' => $ids->first(),
                        ]);
                    }
                }
            }, $columnaId);
        }
    }
};

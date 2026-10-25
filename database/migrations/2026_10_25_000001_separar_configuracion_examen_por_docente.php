<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('configuracion_examen_docente', function (Blueprint $tabla): void {
            $tabla->increments('id_configuracion_examen');
            $tabla->integer('id_examen');
            $tabla->integer('id_docente');
            $tabla->timestamps();

            $tabla->unique(['id_examen', 'id_docente'], 'config_examen_docente_unique');
            $tabla->foreign('id_examen', 'fk_config_examen')
                ->references('id_examen')->on('examen');
            $tabla->foreign('id_docente', 'fk_config_docente')
                ->references('id_usuario')->on('usuario');
        });

        Schema::create('configuracion_examen_norma', function (Blueprint $tabla): void {
            $tabla->unsignedInteger('id_configuracion_examen');
            $tabla->integer('id_norma');
            $tabla->primary(['id_configuracion_examen', 'id_norma'], 'config_examen_norma_pk');
            $tabla->foreign('id_configuracion_examen', 'fk_config_norma_config')
                ->references('id_configuracion_examen')->on('configuracion_examen_docente')
                ->cascadeOnDelete();
            $tabla->foreign('id_norma', 'fk_config_norma_norma')
                ->references('id_norma')->on('norma');
        });

        Schema::create('configuracion_examen_material', function (Blueprint $tabla): void {
            $tabla->unsignedInteger('id_configuracion_examen');
            $tabla->integer('id_material');
            $tabla->primary(['id_configuracion_examen', 'id_material'], 'config_examen_material_pk');
            $tabla->foreign('id_configuracion_examen', 'fk_config_material_config')
                ->references('id_configuracion_examen')->on('configuracion_examen_docente')
                ->cascadeOnDelete();
            $tabla->foreign('id_material', 'fk_config_material_material')
                ->references('id_material')->on('material');
        });

        Schema::create('configuracion_norma_personalizada', function (Blueprint $tabla): void {
            $tabla->increments('id_configuracion_norma');
            $tabla->unsignedInteger('id_configuracion_examen');
            $tabla->integer('numero_norma');
            $tabla->text('descripcion_norma');
            $tabla->unique(['id_configuracion_examen', 'numero_norma'], 'config_norma_numero_unique');
            $tabla->foreign('id_configuracion_examen', 'fk_config_norma_personalizada')
                ->references('id_configuracion_examen')->on('configuracion_examen_docente')
                ->cascadeOnDelete();
        });

        Schema::create('configuracion_material_personalizado', function (Blueprint $tabla): void {
            $tabla->increments('id_configuracion_material');
            $tabla->unsignedInteger('id_configuracion_examen');
            $tabla->integer('numero_material');
            $tabla->text('descripcion_material');
            $tabla->unique(['id_configuracion_examen', 'numero_material'], 'config_material_numero_unique');
            $tabla->foreign('id_configuracion_examen', 'fk_config_material_personalizado')
                ->references('id_configuracion_examen')->on('configuracion_examen_docente')
                ->cascadeOnDelete();
        });

        $this->crearConfiguracionesExistentes();
        $this->copiarConfiguracionExistente();
    }

    public function down(): void
    {
        if ($this->hayConfiguracionesDivergentes()) {
            throw new RuntimeException(
                'No se puede revertir: existen configuraciones independientes por docente.'
            );
        }

        Schema::dropIfExists('configuracion_material_personalizado');
        Schema::dropIfExists('configuracion_norma_personalizada');
        Schema::dropIfExists('configuracion_examen_material');
        Schema::dropIfExists('configuracion_examen_norma');
        Schema::dropIfExists('configuracion_examen_docente');
    }

    private function crearConfiguracionesExistentes(): void
    {
        $participantes = DB::table('examen')
            ->select('id_examen', DB::raw('creador as id_docente'))
            ->union(
                DB::table('examen_curso as ec')
                    ->join('curso as c', 'c.id_curso', '=', 'ec.id_curso')
                    ->select('ec.id_examen', DB::raw('c.sis_doc as id_docente'))
            )
            ->union(
                DB::table('invitacion_examen_compartido')
                    ->where('estado', 'aceptado')
                    ->select('id_examen', DB::raw('id_docente_invitado as id_docente'))
            )
            ->get()
            ->unique(fn (object $fila): string => $fila->id_examen.'-'.$fila->id_docente)
            ->map(fn (object $fila): array => [
                'id_examen' => (int) $fila->id_examen,
                'id_docente' => (int) $fila->id_docente,
                'created_at' => now(),
                'updated_at' => now(),
            ])
            ->values()
            ->all();

        if ($participantes !== []) {
            DB::table('configuracion_examen_docente')->insertOrIgnore($participantes);
        }
    }

    private function copiarConfiguracionExistente(): void
    {
        $configuraciones = DB::table('configuracion_examen_docente')->get();

        foreach ($configuraciones as $configuracion) {
            $idConfiguracion = (int) $configuracion->id_configuracion_examen;
            $idExamen = (int) $configuracion->id_examen;

            $normas = DB::table('examen_norma')->where('id_examen', $idExamen)->pluck('id_norma');
            foreach ($normas as $idNorma) {
                DB::table('configuracion_examen_norma')->insertOrIgnore([
                    'id_configuracion_examen' => $idConfiguracion,
                    'id_norma' => (int) $idNorma,
                ]);
            }

            $materiales = DB::table('examen_material_permitido')
                ->where('id_examen', $idExamen)
                ->pluck('id_material_permitido');
            foreach ($materiales as $idMaterial) {
                DB::table('configuracion_examen_material')->insertOrIgnore([
                    'id_configuracion_examen' => $idConfiguracion,
                    'id_material' => (int) $idMaterial,
                ]);
            }

            if (Schema::hasTable('norma_personalizada')) {
                foreach (DB::table('norma_personalizada')->where('id_examen', $idExamen)->get() as $norma) {
                    DB::table('configuracion_norma_personalizada')->insertOrIgnore([
                        'id_configuracion_examen' => $idConfiguracion,
                        'numero_norma' => (int) $norma->numero_norma,
                        'descripcion_norma' => $norma->descripcion_norma,
                    ]);
                }
            }

            if (Schema::hasTable('material_personalizado')) {
                foreach (DB::table('material_personalizado')->where('id_examen', $idExamen)->get() as $material) {
                    DB::table('configuracion_material_personalizado')->insertOrIgnore([
                        'id_configuracion_examen' => $idConfiguracion,
                        'numero_material' => (int) $material->numero_material,
                        'descripcion_material' => $material->descripcion_material,
                    ]);
                }
            }
        }
    }

    private function hayConfiguracionesDivergentes(): bool
    {
        return DB::table('configuracion_examen_docente')
            ->select('id_examen')
            ->groupBy('id_examen')
            ->havingRaw('COUNT(*) > 1')
            ->exists();
    }
};

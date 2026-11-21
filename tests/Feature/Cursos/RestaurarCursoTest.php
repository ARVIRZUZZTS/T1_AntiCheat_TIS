<?php

/**
 * @file    RestaurarCursoTest.php
 *
 * @author  Equipo T1
 *
 * @created 2026-11-20
 *
 * @description
 * Pruebas de restauración de cursos archivados (HU #322 · Task #325): solo el
 * docente dueño puede listar y restaurar sus cursos archivados, el curso vuelve
 * a `EnCurso` con permisos de escritura, reaparece en listados activos y
 * conserva intactas sus relaciones históricas.
 */

namespace Tests\Feature\Cursos;

use App\Enums\CursoEstado;
use App\Models\Curso;
use App\Models\Usuario;
use App\Services\Curso\AsegurarCursoEditableService;
use App\Services\Curso\ListarCursosDocenteService;
use App\Services\Curso\RestaurarCursoService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RestaurarCursoTest extends TestCase
{
    use DatabaseTransactions;

    private const DOCENTE = 998101;
    private const OTRO = 998102;

    private const CURSO_ARCHIVADO = 998101;
    private const CURSO_EN_CURSO = 998102;
    private const CURSO_FINALIZADO = 998103;
    private const CURSO_OTRO_ARCHIVADO = 998104;

    private const ESTUDIANTE = '998101';
    private const EXAMEN = 998101;

    protected function setUp(): void
    {
        parent::setUp();

        $rol = DB::table('rol')->where('nombre_rol', 'docente')->value('id_rol');
        $gestion = DB::table('tipo_gestion')->value('id_tipo_gestion');
        $tipoExamen = DB::table('tipo_examen')->value('id_tipo_examen');

        $this->assertNotNull($rol);
        $this->assertNotNull($gestion);
        $this->assertNotNull($tipoExamen);

        foreach ([self::DOCENTE, self::OTRO] as $id) {
            DB::table('usuario')->insert([
                'id_usuario' => $id,
                'cod_sis' => 'DOC'.$id,
                'password' => 'x',
                'nombre_usuario' => 'Docente'.$id,
                'apellido' => (string) $id,
            ]);
            DB::table('rol_usuario')->insert(['id_usuario' => $id, 'id_rol' => $rol]);
        }

        $this->crearCurso(self::CURSO_ARCHIVADO, 'Curso Archivado Propio', self::DOCENTE, 'Archivado', $gestion);
        $this->crearCurso(self::CURSO_EN_CURSO, 'Curso En Curso Propio', self::DOCENTE, 'EnCurso', $gestion);
        $this->crearCurso(self::CURSO_FINALIZADO, 'Curso Finalizado Propio', self::DOCENTE, 'Finalizo', $gestion);
        $this->crearCurso(self::CURSO_OTRO_ARCHIVADO, 'Curso Archivado Ajeno', self::OTRO, 'Archivado', $gestion);

        DB::table('estudiante')->insert([
            'sis_estudiante' => self::ESTUDIANTE,
            'nombre_estudiante' => 'Ana',
            'apellido_estudiante' => 'Restaurar',
        ]);
        DB::table('estudiante_curso')->insert([
            'sis_estudiante' => self::ESTUDIANTE,
            'id_curso' => self::CURSO_ARCHIVADO,
        ]);

        DB::table('examen')->insert([
            'id_examen' => self::EXAMEN,
            'fecha' => now()->toDateString(),
            'hora_inicio' => '10:00:00',
            'duracion' => 60,
            'creador' => self::DOCENTE,
            'tipo_examen' => $tipoExamen,
        ]);
        DB::table('examen_curso')->insert([
            'id_examen' => self::EXAMEN,
            'id_curso' => self::CURSO_ARCHIVADO,
        ]);

        DB::table('central_riesgo')->insert([
            'id_registro' => 998101,
            'sis_estudiante' => self::ESTUDIANTE,
            'id_examen' => self::EXAMEN,
            'id_curso' => self::CURSO_ARCHIVADO,
            'id_registrador' => self::DOCENTE,
            'fecha_registro' => now()->toDateTimeString(),
            'tipo_infraccion' => 'sospechoso',
        ]);
    }

    private function crearCurso(int $id, string $nombre, int $docente, string $estado, int $gestion): void
    {
        DB::table('curso')->insert([
            'id_curso' => $id,
            'nombre_curso' => $nombre,
            'sis_doc' => $docente,
            'estado' => $estado,
            'fecha_creacion' => now()->toDateString(),
        ]);
        DB::table('curso_tipo_gestion')->insert([
            'id_curso' => $id,
            'id_tg' => $gestion,
            'anio' => now()->year,
        ]);
    }

    public function test_dueno_puede_listar_sus_cursos_archivados_en_restaurar(): void
    {
        $docente = Usuario::findOrFail(self::DOCENTE);

        $this->actingAs($docente)
            ->get(route('cursos.restaurar'))
            ->assertOk()
            ->assertSee('Curso Archivado Propio')
            ->assertDontSee('Curso En Curso Propio')
            ->assertDontSee('Curso Finalizado Propio')
            ->assertDontSee('Curso Archivado Ajeno');
    }

    public function test_dueno_puede_restaurar_curso_archivado_a_en_curso(): void
    {
        $docente = Usuario::findOrFail(self::DOCENTE);
        $curso = Curso::findOrFail(self::CURSO_ARCHIVADO);

        $this->actingAs($docente)
            ->post(route('cursos.restaurar.store', $curso))
            ->assertRedirect(route('cursos.restaurar'))
            ->assertSessionHas('mensaje', RestaurarCursoService::MENSAJE_EXITO);

        $this->assertSame(CursoEstado::EnCurso->value, Curso::findOrFail(self::CURSO_ARCHIVADO)->estado);
    }

    public function test_curso_restaurado_vuelve_a_listados_activos_y_rehabilita_escritura(): void
    {
        $docente = Usuario::findOrFail(self::DOCENTE);
        $curso = Curso::findOrFail(self::CURSO_ARCHIVADO);

        $this->actingAs($docente)->post(route('cursos.restaurar.store', $curso));

        $restaurado = Curso::findOrFail(self::CURSO_ARCHIVADO);
        $examen = \App\Models\Examen::findOrFail(self::EXAMEN);

        $this->assertTrue($restaurado->permiteEscritura());
        $this->assertTrue(Gate::forUser($docente)->allows('crearExamen', $restaurado));

        $editable = app(AsegurarCursoEditableService::class);
        $this->assertTrue($editable->cursoEditable($restaurado));
        $this->assertTrue($editable->examenEditable($examen));
        $editable->asegurarCursoEditable($restaurado, 'curso');
        $editable->asegurarExamenEditable($examen, 'examen');

        $activos = app(ListarCursosDocenteService::class)->activos(self::DOCENTE);
        $this->assertTrue($activos->pluck('id_curso')->contains(self::CURSO_ARCHIVADO));

        $metricas = app(ListarCursosDocenteService::class)->conMetricas(self::DOCENTE);
        $this->assertTrue($metricas->pluck('curso.id_curso')->contains(self::CURSO_ARCHIVADO));
    }

    public function test_al_restaurarse_se_conservan_estudiantes_examenes_e_incidencias(): void
    {
        $docente = Usuario::findOrFail(self::DOCENTE);
        $curso = Curso::findOrFail(self::CURSO_ARCHIVADO);

        $estudiantesAntes = DB::table('estudiante_curso')->where('id_curso', self::CURSO_ARCHIVADO)->count();
        $examenesAntes = DB::table('examen_curso')->where('id_curso', self::CURSO_ARCHIVADO)->count();
        $incidenciasAntes = DB::table('central_riesgo')->where('id_curso', self::CURSO_ARCHIVADO)->count();

        $this->actingAs($docente)->post(route('cursos.restaurar.store', $curso));

        $this->assertSame($estudiantesAntes, DB::table('estudiante_curso')->where('id_curso', self::CURSO_ARCHIVADO)->count());
        $this->assertSame($examenesAntes, DB::table('examen_curso')->where('id_curso', self::CURSO_ARCHIVADO)->count());
        $this->assertSame($incidenciasAntes, DB::table('central_riesgo')->where('id_curso', self::CURSO_ARCHIVADO)->count());

        $this->assertDatabaseHas('estudiante_curso', [
            'sis_estudiante' => self::ESTUDIANTE,
            'id_curso' => self::CURSO_ARCHIVADO,
        ]);
        $this->assertDatabaseHas('examen_curso', [
            'id_examen' => self::EXAMEN,
            'id_curso' => self::CURSO_ARCHIVADO,
        ]);
        $this->assertDatabaseHas('central_riesgo', [
            'id_registro' => 998101,
            'id_curso' => self::CURSO_ARCHIVADO,
            'id_examen' => self::EXAMEN,
        ]);
    }

    public function test_no_dueno_no_puede_restaurar_curso(): void
    {
        $otro = Usuario::findOrFail(self::OTRO);
        $curso = Curso::findOrFail(self::CURSO_ARCHIVADO);

        $this->actingAs($otro)
            ->post(route('cursos.restaurar.store', $curso))
            ->assertForbidden();

        $this->assertSame(CursoEstado::Archivado->value, Curso::findOrFail(self::CURSO_ARCHIVADO)->estado);
    }

    public function test_no_se_puede_restaurar_curso_que_no_esta_archivado(): void
    {
        $docente = Usuario::findOrFail(self::DOCENTE);
        $curso = Curso::findOrFail(self::CURSO_EN_CURSO);

        $this->actingAs($docente)
            ->post(route('cursos.restaurar.store', $curso))
            ->assertForbidden();
    }

    public function test_pagina_restaurar_muestra_estado_vacio_sin_archivados(): void
    {
        DB::table('curso')->where('id_curso', self::CURSO_ARCHIVADO)->update(['estado' => CursoEstado::EnCurso->value]);
        DB::table('curso')->where('id_curso', self::CURSO_OTRO_ARCHIVADO)->update(['estado' => CursoEstado::EnCurso->value]);

        $docente = Usuario::findOrFail(self::DOCENTE);

        $this->actingAs($docente)
            ->get(route('cursos.restaurar'))
            ->assertOk()
            ->assertSee('No tienes cursos archivados');
    }
}

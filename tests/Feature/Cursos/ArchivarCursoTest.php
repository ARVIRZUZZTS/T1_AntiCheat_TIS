<?php

/**
 * @file    ArchivarCursoTest.php
 *
 * @author  Equipo T1
 *
 * @created 2026-11-20
 *
 * @description
 * Pruebas de archivado de cursos (HU #321 · Task #323): solo el docente dueño
 * puede archivar cursos en estado `EnCurso`, el curso pasa a solo lectura,
 * se conserva el historial en `/archivados`, se ve en Ver cursos (filtro
 * Archivados) y se excluye de los selectores activos, bloqueando escrituras
 * incluso en exámenes compartidos.
 *
 * @changelog
 * - 2026-11-23  [Valery D. Ortuno P]  test: el curso archivado sale en Ver
 *   cursos (filtro Archivados) pero no en los selectores activos.
 */

namespace Tests\Feature\Cursos;

use App\Enums\CursoEstado;
use App\Models\Curso;
use App\Models\Examen;
use App\Models\Usuario;
use App\Services\Curso\ArchivarCursoService;
use App\Services\Curso\AsegurarCursoEditableService;
use App\Services\Curso\ListarCursosDocenteService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ArchivarCursoTest extends TestCase
{
    use DatabaseTransactions;

    private const DOCENTE = 997101;

    private const OTRO = 997102;

    private const CURSO_EN_CURSO = 997101;

    private const CURSO_FINALIZADO = 997102;

    private const CURSO_OTRO = 997103;

    private const ESTUDIANTE = '997101';

    private const EXAMEN = 997101;

    private const EXAMEN_COMPARTIDO = 997102;

    private const CURSO_COMPARTIDO = 997104;

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

        $this->crearCurso(self::CURSO_EN_CURSO, 'Curso En Curso', self::DOCENTE, 'EnCurso', $gestion);
        $this->crearCurso(self::CURSO_FINALIZADO, 'Curso Finalizado', self::DOCENTE, 'Finalizo', $gestion);
        $this->crearCurso(self::CURSO_OTRO, 'Curso Ajeno', self::OTRO, 'EnCurso', $gestion);

        DB::table('estudiante')->insert([
            'sis_estudiante' => self::ESTUDIANTE,
            'nombre_estudiante' => 'Ana',
            'apellido_estudiante' => 'Prueba',
        ]);
        DB::table('estudiante_curso')->insert([
            'sis_estudiante' => self::ESTUDIANTE,
            'id_curso' => self::CURSO_EN_CURSO,
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
            'id_curso' => self::CURSO_EN_CURSO,
        ]);

        DB::table('central_riesgo')->insert([
            'id_registro' => 997101,
            'sis_estudiante' => self::ESTUDIANTE,
            'id_examen' => self::EXAMEN,
            'id_curso' => self::CURSO_EN_CURSO,
            'id_registrador' => self::DOCENTE,
            'fecha_registro' => now()->toDateTimeString(),
            'tipo_infraccion' => 'sospechoso',
        ]);

        // Examen compartido entre curso a archivar y otro curso activo.
        $this->crearCurso(self::CURSO_COMPARTIDO, 'Curso Compartido Activo', self::DOCENTE, 'EnCurso', $gestion);
        DB::table('examen')->insert([
            'id_examen' => self::EXAMEN_COMPARTIDO,
            'fecha' => now()->toDateString(),
            'hora_inicio' => '12:00:00',
            'duracion' => 60,
            'creador' => self::DOCENTE,
            'tipo_examen' => $tipoExamen,
        ]);
        DB::table('examen_curso')->insert(['id_examen' => self::EXAMEN_COMPARTIDO, 'id_curso' => self::CURSO_EN_CURSO]);
        DB::table('examen_curso')->insert(['id_examen' => self::EXAMEN_COMPARTIDO, 'id_curso' => self::CURSO_COMPARTIDO]);
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

    public function test_dueno_puede_archivar_curso_en_curso(): void
    {
        $docente = Usuario::findOrFail(self::DOCENTE);
        $curso = Curso::findOrFail(self::CURSO_EN_CURSO);

        $this->actingAs($docente)
            ->post(route('cursos.archivar.store', $curso))
            ->assertRedirect(route('cursos.archivar-restaurar'))
            ->assertSessionHas('mensaje', ArchivarCursoService::MENSAJE_EXITO);

        $this->assertSame(CursoEstado::Archivado->value, Curso::findOrFail(self::CURSO_EN_CURSO)->estado);
    }

    public function test_no_dueno_no_puede_archivar_curso(): void
    {
        $otro = Usuario::findOrFail(self::OTRO);
        $curso = Curso::findOrFail(self::CURSO_EN_CURSO);

        $this->actingAs($otro)
            ->post(route('cursos.archivar.store', $curso))
            ->assertForbidden();

        $this->assertSame(CursoEstado::EnCurso->value, Curso::findOrFail(self::CURSO_EN_CURSO)->estado);
    }

    public function test_no_se_puede_archivar_curso_que_no_esta_en_curso(): void
    {
        $docente = Usuario::findOrFail(self::DOCENTE);
        $curso = Curso::findOrFail(self::CURSO_FINALIZADO);

        $this->actingAs($docente)
            ->post(route('cursos.archivar.store', $curso))
            ->assertForbidden();
    }

    public function test_curso_archivado_sale_en_ver_cursos_pero_no_en_selectores_activos(): void
    {
        DB::table('curso')->where('id_curso', self::CURSO_EN_CURSO)->update(['estado' => CursoEstado::Archivado->value]);

        // Ver cursos lo muestra (filtro Archivados); los selectores activos no.
        $filas = app(ListarCursosDocenteService::class)->conMetricas(self::DOCENTE);
        $this->assertTrue($filas->pluck('curso.id_curso')->contains(self::CURSO_EN_CURSO));

        $activos = app(ListarCursosDocenteService::class)->activos(self::DOCENTE);
        $this->assertFalse($activos->pluck('id_curso')->contains(self::CURSO_EN_CURSO));
    }

    public function test_pagina_archivados_muestra_historial_de_solo_lectura(): void
    {
        DB::table('curso')->where('id_curso', self::CURSO_EN_CURSO)->update(['estado' => CursoEstado::Archivado->value]);
        $docente = Usuario::findOrFail(self::DOCENTE);

        $this->actingAs($docente)
            ->get(route('cursos.archivados'))
            ->assertOk()
            ->assertSee('Curso En Curso')
            ->assertSee('Solo lectura')
            ->assertSee('Ana Prueba')
            ->assertSee((string) self::EXAMEN)
            ->assertSee('sospechoso');
    }

    public function test_bloquea_escritura_en_curso_archivado(): void
    {
        DB::table('curso')->where('id_curso', self::CURSO_EN_CURSO)->update(['estado' => CursoEstado::Archivado->value]);
        $curso = Curso::findOrFail(self::CURSO_EN_CURSO);
        $servicio = app(AsegurarCursoEditableService::class);

        try {
            $servicio->asegurarCursoEditable($curso, 'curso');
            $this->fail('Se esperaba ValidationException.');
        } catch (ValidationException $e) {
            $this->assertSame(
                AsegurarCursoEditableService::MENSAJE_CURSO_ARCHIVADO,
                $e->errors()['curso'][0] ?? null
            );
        }
    }

    public function test_bloquea_escritura_en_examen_compartido_si_un_curso_vinculado_esta_archivado(): void
    {
        DB::table('curso')->where('id_curso', self::CURSO_EN_CURSO)->update(['estado' => CursoEstado::Archivado->value]);
        $examen = Examen::findOrFail(self::EXAMEN_COMPARTIDO);
        $servicio = app(AsegurarCursoEditableService::class);

        $this->assertFalse($servicio->examenEditable($examen));

        try {
            $servicio->asegurarExamenEditable($examen, 'examen');
            $this->fail('Se esperaba ValidationException.');
        } catch (ValidationException $e) {
            $this->assertSame(
                AsegurarCursoEditableService::MENSAJE_EXAMEN_CON_CURSO_ARCHIVADO,
                $e->errors()['examen'][0] ?? null
            );
        }
    }

    public function test_cargar_csv_bloquea_curso_archivado(): void
    {
        DB::table('curso')->where('id_curso', self::CURSO_EN_CURSO)->update(['estado' => CursoEstado::Archivado->value]);
        $docente = Usuario::findOrFail(self::DOCENTE);

        $response = $this->actingAs($docente)
            ->get(route('cursos.cargar-csv', ['curso' => self::CURSO_EN_CURSO]));

        $this->assertSame(302, $response->getStatusCode());
        $response->assertSessionHasErrors('curso');
    }
}

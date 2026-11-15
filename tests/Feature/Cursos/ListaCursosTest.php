<?php

/**
 * @file    ListaCursosTest.php
 *
 * @author  Diegootm <josediegotejerinamolina@gmail.com>
 *
 * @created 2026-11-06
 *
 * @updated 2026-11-08
 * @updated 2026-11-09
 *
 * @description
 * Pruebas de la lista de cursos del docente: solo cursos propios (las
 * invitaciones son por examen y no aparecen en la lista), resumen con
 * métricas, estados vacíos y detalle con invitaciones pendientes o
 * rechazadas.
 *
 * @changelog
 * - 2026-11-06  [Diegootm]  test: creación inicial del listado de cursos.
 * - 2026-11-08  [OchoaCesar]  fix: fixtures compatibles con el esquema
 *   actual — nombres únicos en los usuarios de prueba (índice
 *   usuario_nombre_usuario_unique de la migración HU 26) y columnas
 *   sis_estudiante/id_examen/fecha_registro en la incidencia de ejemplo
 *   (NOT NULL en una BD recién migrada según 2026_10_10_000004).
 * - 2026-11-09  [Diegootm]  test: la lista ya no incluye los cursos
 *   invitados ni el filtro "Invitados" (las invitaciones son por examen) y
 *   se cubre el filtro "Finalizados" del criterio de aceptación.
 *
 * @see  App\Services\Curso\ListarCursosDocenteService
 */

namespace Tests\Feature\Cursos;

use App\Models\Usuario;
use App\Services\Curso\ListarCursosDocenteService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ListaCursosTest extends TestCase
{
    use DatabaseTransactions;

    private const DOCENTE = 996101;

    private const OTRO = 996102;

    private const VACIO = 996103;

    private const CURSO_PROPIO = 996101;

    private const CURSO_FINALIZADO = 996102;

    private const CURSO_INVITADO = 996103;

    private const CURSO_PENDIENTE = 996104;

    private const CURSO_RECHAZADO = 996105;

    private const CURSO_INVITADO_FINALIZADO = 996106;

    private const EXAMEN_PROXIMO = 996101;

    protected function setUp(): void
    {
        parent::setUp();

        $rol = DB::table('rol')->where('nombre_rol', 'docente')->value('id_rol');
        $tipo = DB::table('tipo_examen')->value('id_tipo_examen');
        $gestion = DB::table('tipo_gestion')->value('id_tipo_gestion');

        $this->assertNotNull($rol);
        $this->assertNotNull($tipo);
        $this->assertNotNull($gestion);

        foreach ([self::DOCENTE, self::OTRO, self::VACIO] as $id) {
            DB::table('usuario')->insert([
                'id_usuario' => $id, 'cod_sis' => 'DOC'.$id,
                'password' => 'x', 'nombre_usuario' => 'Docente'.$id, 'apellido' => (string) $id,
            ]);
            DB::table('rol_usuario')->insert(['id_usuario' => $id, 'id_rol' => $rol]);
        }

        foreach ([
            [self::CURSO_PROPIO, 'Álgebra', self::DOCENTE, 'EnCurso'],
            [self::CURSO_FINALIZADO, 'Historia', self::DOCENTE, 'Finalizo'],
            [self::CURSO_INVITADO, 'Física', self::OTRO, 'EnCurso'],
            [self::CURSO_PENDIENTE, 'Pendiente oculta', self::OTRO, 'EnCurso'],
            [self::CURSO_RECHAZADO, 'Rechazada oculta', self::OTRO, 'Finalizo'],
            [self::CURSO_INVITADO_FINALIZADO, 'Literatura', self::OTRO, 'Finalizo'],
        ] as [$id, $nombre, $docente, $estado]) {
            DB::table('curso')->insert([
                'id_curso' => $id, 'nombre_curso' => $nombre,
                'sis_doc' => $docente, 'estado' => $estado,
                'fecha_creacion' => now()->toDateString(),
            ]);
            DB::table('curso_tipo_gestion')->insert([
                'id_curso' => $id, 'id_tg' => $gestion, 'anio' => now()->year,
            ]);
        }

        foreach ([
            [self::EXAMEN_PROXIMO, self::CURSO_PROPIO, now()->addDay()],
            [996102, self::CURSO_PROPIO, now()->addDays(3)],
            [996103, self::CURSO_PROPIO, now()->subDay()],
            [996104, self::CURSO_INVITADO, now()->addDays(2)],
            [996107, self::CURSO_INVITADO, now()->addDays(4)],
            [996105, self::CURSO_PENDIENTE, now()->addDays(2)],
            [996106, self::CURSO_RECHAZADO, now()->addDays(2)],
            [996108, self::CURSO_INVITADO_FINALIZADO, now()->addDays(2)],
        ] as [$id, $curso, $fecha]) {
            DB::table('examen')->insert([
                'id_examen' => $id, 'fecha' => $fecha->toDateString(),
                'hora_inicio' => '12:00:00', 'duracion' => 90,
                'creador' => $curso === self::CURSO_PROPIO ? self::DOCENTE : self::OTRO,
                'tipo_examen' => $tipo,
            ]);
            DB::table('examen_curso')->insert(['id_examen' => $id, 'id_curso' => $curso]);
        }

        foreach ([
            [996101, 996104, 'aceptado'],
            [996102, 996105, 'pendiente'],
            [996103, 996106, 'rechazado'],
            [996104, 996107, 'aceptado'],
            [996105, 996108, 'aceptado'],
        ] as [$id, $examen, $estado]) {
            DB::table('invitacion_examen_compartido')->insert([
                'id_invitacion' => $id, 'id_docente_invitador' => self::OTRO,
                'id_docente_invitado' => self::DOCENTE, 'id_examen' => $examen,
                'estado' => $estado, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        foreach (['996101', '996102', '996103'] as $sis) {
            DB::table('estudiante')->insert([
                'sis_estudiante' => $sis, 'nombre_estudiante' => 'Estudiante',
                'apellido_estudiante' => $sis,
            ]);
            DB::table('estudiante_curso')->insert([
                'sis_estudiante' => $sis, 'id_curso' => self::CURSO_PROPIO,
            ]);
        }
        foreach ([
            [996101, '996101', self::EXAMEN_PROXIMO, 'habilitado'],
            [996102, '996102', self::EXAMEN_PROXIMO, 'deshabilitado'],
            [996103, '996103', 996102, 'habilitado'],
        ] as [$id, $sis, $examen, $estado]) {
            DB::table('estudiante_examen')->insert([
                'id_estudiante_examen' => $id, 'sis_estudiante' => $sis,
                'id_examen' => $examen, 'id_curso' => self::CURSO_PROPIO,
                'estado' => $estado,
            ]);
        }

        DB::table('registro_asistencia')->insert([
            'id_ingreso' => 996101, 'id_examen' => self::EXAMEN_PROXIMO,
            'id_estudiante' => '996101', 'id_registrador' => self::DOCENTE,
        ]);
        DB::table('central_riesgo')->insert([
            'id_registro' => 996101,
            'id_registrador' => self::DOCENTE, 'id_curso' => self::CURSO_PROPIO,
            'tipo_infraccion' => 'sospechoso',
            // El esquema real (Supabase) no tiene id_ingreso en central_riesgo
            // y estas columnas son NOT NULL.
            'sis_estudiante' => '996101',
            'id_examen' => self::EXAMEN_PROXIMO,
            'fecha_registro' => now()->toDateTimeString(),
        ]);
    }

    public function test_lista_solo_los_cursos_propios_con_gestion(): void
    {
        $filas = app(ListarCursosDocenteService::class)->conMetricas(self::DOCENTE);

        $this->assertEqualsCanonicalizing(
            [self::CURSO_PROPIO, self::CURSO_FINALIZADO],
            $filas->pluck('curso.id_curso')->all(),
        );
        $this->assertStringContainsString((string) now()->year, $filas->first()['gestion']);
    }

    public function test_resumen_usa_el_proximo_examen_y_sus_estudiantes_e_incidencias(): void
    {
        $filas = app(ListarCursosDocenteService::class)->conMetricas(self::DOCENTE);
        $propio = $filas->firstWhere('curso.id_curso', self::CURSO_PROPIO);
        $finalizado = $filas->firstWhere('curso.id_curso', self::CURSO_FINALIZADO);

        $this->assertSame(self::EXAMEN_PROXIMO, $propio['proximo_examen']->id_examen);
        $this->assertSame(2, $propio['inscritos']);
        $this->assertSame(1, $propio['habilitados']);
        $this->assertSame(1, $propio['deshabilitados']);
        $this->assertSame(1, $propio['incidencias']);
        $this->assertNull($finalizado['proximo_examen']);
    }

    public function test_la_pagina_muestra_filtros_resumen_y_estados_vacios(): void
    {
        $this->actingAs(Usuario::findOrFail(self::DOCENTE))
            ->get(route('cursos'))
            ->assertOk()
            ->assertSee('Todos 2')
            ->assertSee('Activos 1')
            ->assertSee('Finalizados 1')
            ->assertDontSee('Invitados')
            ->assertDontSee('Física')
            ->assertDontSee('Literatura')
            ->assertSee('Próximo examen:')
            ->assertSee('No hay exámenes programados')
            ->assertDontSee('Pendiente oculta')
            ->assertDontSee('Rechazada oculta');

        $this->actingAs(Usuario::findOrFail(self::VACIO))
            ->get(route('cursos'))
            ->assertOk()
            ->assertSee('No tienes cursos registrados');
    }

    public function test_detalle_no_admite_invitacion_pendiente_ni_rechazada(): void
    {
        $this->actingAs(Usuario::findOrFail(self::DOCENTE));

        $this->get(route('cursos.detalle', self::CURSO_INVITADO))->assertOk();
        $this->get(route('cursos.detalle', self::CURSO_PENDIENTE))->assertForbidden();
        $this->get(route('cursos.detalle', self::CURSO_RECHAZADO))->assertForbidden();

        $this->actingAs(Usuario::findOrFail(self::VACIO))
            ->get(route('cursos.detalle', self::CURSO_PROPIO))
            ->assertForbidden();
    }
}

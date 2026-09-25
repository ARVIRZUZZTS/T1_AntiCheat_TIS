<?php

/**
 * @file    ListarEstudiantesCursoFiltroTest.php
 *
 * @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
 *
 * @created 2026-09-25
 *
 * @updated 2026-09-25
 *
 * @description
 * Pruebas de integración del filtrado de estudiantes por estado (issue #29),
 * contra el endpoint /api/cursos/{id}/estudiantes/estado. El esquema de estas
 * tablas no tiene migraciones Eloquent (se crea vía
 * docker/postgres/init/001_create_schema.sql), por eso se usa
 * DatabaseTransactions en vez de RefreshDatabase: cada test crea sus propios
 * datos con IDs dedicados (rango 900000+) y se revierten al terminar, sin
 * tocar los datos reales de desarrollo. Requiere el contenedor de Docker
 * levantado (ver INSTALACION_DOCKER.md).
 *
 * @see  App\Services\Examen\ListarEstudiantesCursoConEstadoService
 * @see  App\Http\Controllers\Api\EstudianteExamenController
 *
 * @changelog
 * - 2026-09-25  [T1]  test: creación inicial.
 */

namespace Tests\Feature\Examenes;

use App\Models\CentralRiesgo;
use App\Models\Curso;
use App\Models\Estudiante;
use App\Models\EstudianteExamen;
use App\Models\Examen;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ListarEstudiantesCursoFiltroTest extends TestCase
{
    use DatabaseTransactions;

    private const ID_CURSO = 900001;

    private const ID_EXAMEN = 900001;

    private const ID_TIPO_EXAMEN = 900001;

    private const ID_DOCENTE = 900001;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('tipo_examen')->insert([
            'id_tipo_examen' => self::ID_TIPO_EXAMEN,
            'nombre_tipo_examen' => 'PP',
        ]);

        DB::table('usuario')->insert([
            'id_usuario' => self::ID_DOCENTE,
            'cod_sis' => 'TESTDOC1',
            'contraseña' => 'x',
            'nombre_usuario' => 'Docente',
            'apellido' => 'De Prueba',
        ]);

        Curso::create([
            'id_curso' => self::ID_CURSO,
            'nombre_curso' => 'Curso de Prueba',
            'sis_doc' => self::ID_DOCENTE,
            'fecha_creacion' => now()->toDateString(),
            'estado' => 'EnCurso',
        ]);

        Examen::create([
            'id_examen' => self::ID_EXAMEN,
            'fecha' => now()->toDateString(),
            'hora_inicio' => '08:00',
            'hora_fin' => '10:00',
            'duracion' => 120,
            'creador' => self::ID_DOCENTE,
            'tipo_examen' => self::ID_TIPO_EXAMEN,
        ]);

        DB::table('examen_curso')->insert([
            'id_examen' => self::ID_EXAMEN,
            'id_curso' => self::ID_CURSO,
        ]);

        // Habilitado sin observacion, Deshabilitado sin observacion,
        // Habilitado+Sospechoso, Deshabilitado+Sospechoso, Habilitado+Tramposo,
        // y uno sin ninguna fila de estudiante_examen (sin estado).
        $this->crearEstudiante('90000001', 'habilitado', null, null);
        $this->crearEstudiante('90000002', 'deshabilitado', 'No aprobo laboratorio', null);
        $this->crearEstudiante('90000003', 'habilitado', null, 'sospechoso');
        $this->crearEstudiante('90000004', 'deshabilitado', 'No confianza', 'sospechoso');
        $this->crearEstudiante('90000005', 'habilitado', null, 'tramposo');
        $this->crearEstudiante('90000006', null, null, null);
    }

    private function crearEstudiante(string $sis, ?string $estado, ?string $motivo, ?string $infraccion): void
    {
        static $idEstudianteExamen = 900000;
        static $idIngreso = 900000;
        static $idRiesgo = 900000;

        Estudiante::create([
            'sis_estudiante' => $sis,
            'nombre_estudiante' => 'Test',
            'apellido_estudiante' => $sis,
            'carrera' => 'Sistemas',
        ]);

        DB::table('estudiante_curso')->insert([
            'sis_estudiante' => $sis,
            'id_curso' => self::ID_CURSO,
        ]);

        if ($estado !== null) {
            $idEstudianteExamen++;

            EstudianteExamen::create([
                'id_estudiante_examen' => $idEstudianteExamen,
                'sis_estudiante' => $sis,
                'id_examen' => self::ID_EXAMEN,
                'estado' => $estado,
                'motivo' => $motivo,
            ]);
        }

        if ($infraccion !== null) {
            $idIngreso++;

            DB::table('registro_asistencia')->insert([
                'id_ingreso' => $idIngreso,
                'hora_ingreso' => '08:05',
                'id_examen' => self::ID_EXAMEN,
                'id_estudiante' => $sis,
            ]);

            $idRiesgo++;

            CentralRiesgo::create([
                'id_registro' => $idRiesgo,
                'id_ingreso' => $idIngreso,
                'id_registrador' => self::ID_DOCENTE,
                'detalle_motivo' => 'Motivo de prueba',
                'fecha_registro' => now()->toDateString(),
                'tipo_infraccion' => $infraccion,
            ]);
        }
    }

    private function endpoint(string $query = ''): string
    {
        return '/api/cursos/'.self::ID_CURSO.'/estudiantes/estado'.($query !== '' ? '?'.$query : '');
    }

    public function test_filtro_habilitados_solo_muestra_habilitados(): void
    {
        $response = $this->getJson($this->endpoint('estado=habilitados'));

        $response->assertOk();
        $sis = collect($response->json('datos'))->pluck('sis')->sort()->values()->all();
        $this->assertEquals(['90000001', '90000003', '90000005'], $sis);
    }

    public function test_filtro_deshabilitados_solo_muestra_deshabilitados(): void
    {
        $response = $this->getJson($this->endpoint('estado=deshabilitados'));

        $response->assertOk();
        $sis = collect($response->json('datos'))->pluck('sis')->sort()->values()->all();
        $this->assertEquals(['90000002', '90000004'], $sis);
    }

    /**
     * Punto A de la revisión de #29: sospechosos incluye tanto habilitados
     * como deshabilitados, las dos dimensiones no deben mezclarse.
     */
    public function test_filtro_sospechosos_incluye_habilitados_y_deshabilitados(): void
    {
        $response = $this->getJson($this->endpoint('estado=sospechosos'));

        $response->assertOk();
        $sis = collect($response->json('datos'))->pluck('sis')->sort()->values()->all();
        $this->assertEquals(['90000003', '90000004'], $sis);
    }

    public function test_filtro_tramposos_solo_muestra_tramposos(): void
    {
        $response = $this->getJson($this->endpoint('estado=tramposos'));

        $response->assertOk();
        $sis = collect($response->json('datos'))->pluck('sis')->all();
        $this->assertEquals(['90000005'], $sis);
    }

    /**
     * Punto D de la revisión de #29: los conteos deben reflejar el curso
     * completo, no la página actual (acá se pide de a 2 por página).
     */
    public function test_conteos_reflejan_el_curso_completo_no_la_pagina(): void
    {
        $response = $this->getJson($this->endpoint('por_pagina=2'));

        $response->assertOk();
        $response->assertJsonPath('conteos.todos', 6);
        $response->assertJsonPath('conteos.habilitados', 3);
        $response->assertJsonPath('conteos.deshabilitados', 2);
        $response->assertJsonPath('conteos.sospechosos', 2);
        $response->assertJsonPath('conteos.tramposos', 1);
    }

    /**
     * Punto C de la revisión de #29: un estudiante sin fila en
     * estudiante_examen cuenta en "todos" pero no en habilitados/deshabilitados.
     */
    public function test_estudiante_sin_estado_no_cuenta_como_habilitado_ni_deshabilitado(): void
    {
        $sisTodos = collect($this->getJson($this->endpoint())->json('datos'))->pluck('sis')->all();
        $this->assertContains('90000006', $sisTodos);

        $sisHabilitados = collect($this->getJson($this->endpoint('estado=habilitados'))->json('datos'))->pluck('sis')->all();
        $sisDeshabilitados = collect($this->getJson($this->endpoint('estado=deshabilitados'))->json('datos'))->pluck('sis')->all();
        $this->assertNotContains('90000006', $sisHabilitados);
        $this->assertNotContains('90000006', $sisDeshabilitados);
    }

    /**
     * Punto F de la revisión de #29: filtro + paginación combinados siguen
     * devolviendo el subconjunto correcto en la página siguiente.
     */
    public function test_filtro_y_paginacion_combinados(): void
    {
        $response = $this->getJson($this->endpoint('estado=habilitados&por_pagina=2&pagina=2'));

        $response->assertOk();
        $response->assertJsonPath('paginacion.total', 3);
        $sis = collect($response->json('datos'))->pluck('sis')->all();
        $this->assertCount(1, $sis);
        $this->assertContains($sis[0], ['90000001', '90000003', '90000005']);
    }

    public function test_filtro_invalido_devuelve_422(): void
    {
        $this->getJson($this->endpoint('estado=xyz'))->assertStatus(422);
    }

    public function test_busqueda_invalida_devuelve_422(): void
    {
        $this->getJson($this->endpoint('busqueda='.urlencode('a@b')))->assertStatus(422);
    }

    public function test_busqueda_por_nombre_y_apellido_juntos_encuentra_al_estudiante(): void
    {
        Estudiante::create([
            'sis_estudiante' => '90000099',
            'nombre_estudiante' => 'Diego',
            'apellido_estudiante' => 'Camacho',
            'carrera' => 'Sistemas',
        ]);

        DB::table('estudiante_curso')->insert([
            'sis_estudiante' => '90000099',
            'id_curso' => self::ID_CURSO,
        ]);

        $response = $this->getJson($this->endpoint('busqueda='.urlencode('Diego Camacho')));

        $response->assertOk();
        $sis = collect($response->json('datos'))->pluck('sis')->all();
        $this->assertEquals(['90000099'], $sis);
    }

    public function test_curso_inexistente_devuelve_404(): void
    {
        $this->getJson('/api/cursos/999999999/estudiantes/estado')->assertStatus(404);
    }
}

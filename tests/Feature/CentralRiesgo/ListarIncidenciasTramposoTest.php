<?php

/**
 * @file    ListarIncidenciasTramposoTest.php
 *
 * @author  T1 - Tech One SRL
 *
 * @created 2026-10-09
 *
 * @updated 2026-10-09
 *
 * @description
 * Pruebas de integración del endpoint /api/central-riesgo: listado de
 * incidencias `tramposo` con orden por defecto, orden alfabético, filtro por
 * alcance, búsqueda y paginación de 8 en 8.
 *
 * El esquema de estas tablas viene de una migración con SQL crudo
 * (database/migrations/2026_09_27_000001_migracion_servidor_oficial.php),
 * por eso se usa DatabaseTransactions en vez de RefreshDatabase: cada test
 * crea sus propios datos con IDs dedicados (rango 900000+) y se revierten al
 * terminar, sin tocar los datos reales de desarrollo.
 *
 * Los valores de `tipo_examen`, de `central_riesgo.motivo` (enum con espacios)
 * y las columnas de `usuario` siguen el esquema de la base real de
 * desarrollo, que es distinto al de los `.sql` del repositorio.
 *
 * @see  App\Services\CentralRiesgo\ListarIncidenciasTramposoService
 * @see  App\Http\Controllers\Api\CentralRiesgoController
 *
 * @changelog
 * - 2026-10-09  [T1]  test: creación inicial.
 */

namespace Tests\Feature\CentralRiesgo;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ListarIncidenciasTramposoTest extends TestCase
{
    use DatabaseTransactions;

    private const ID_TIPO_EXAMEN = 900001;

    private const DOCENTE_A = 900001;

    private const DOCENTE_B = 900002;

    private const DOCENTE_C = 900003;

    private const CURSO_A = 900001;

    private const CURSO_B = 900002;

    private const CURSO_C = 900003;

    private const EXAMEN_A = 900001;

    private const EXAMEN_B = 900002;

    private const EXAMEN_C = 900003;

    private const EXAMEN_D = 900004;

    protected function setUp(): void
    {
        parent::setUp();

        // El listado es institucional (no se acota a un curso), así que las
        // filas semilla de `central_riesgo` contaminarían los conteos y el
        // orden. Se vacía la tabla dentro de la transacción de DatabaseTransactions:
        // el DELETE se revierte al terminar cada test y los datos reales de
        // desarrollo quedan intactos. `notificacion_docente` tiene FK hacia
        // `central_riesgo`, así que se vacía primero.
        DB::table('notificacion_docente')->delete();
        DB::table('central_riesgo')->delete();

        DB::table('tipo_examen')->insert([
            'id_tipo_examen' => self::ID_TIPO_EXAMEN,
            'nombre_tipo_examen' => 'examen parcial',
        ]);

        foreach (
            [
                [self::DOCENTE_A, 'DOCA'],
                [self::DOCENTE_B, 'DOCB'],
                [self::DOCENTE_C, 'DOCC'],
            ] as [$id, $sis]
        ) {
            DB::table('usuario')->insert([
                'id_usuario' => $id,
                'cod_sis' => $sis,
                'password' => 'x',
                'nombre_usuario' => 'Docente',
                'apellido' => 'De Prueba '.$id,
            ]);
        }

        $this->crearCurso(self::CURSO_A, 'Calculo I', self::DOCENTE_A);
        $this->crearCurso(self::CURSO_B, 'Fisica I', self::DOCENTE_B);
        $this->crearCurso(self::CURSO_C, 'Quimica II', self::DOCENTE_C);

        $this->crearExamen(self::EXAMEN_A, [self::CURSO_A]);
        $this->crearExamen(self::EXAMEN_B, [self::CURSO_B]);
        $this->crearExamen(self::EXAMEN_C, [self::CURSO_C]);
        $this->crearExamen(self::EXAMEN_D, [self::CURSO_A, self::CURSO_B]);

        // Ana: tramposo en Calculo I (su curso).
        $this->crearEstudiante('900000001', 'Ana', 'Zapata Ruiz', [self::CURSO_A]);
        $this->crearIncidencia('900000001', self::EXAMEN_A, 'tramposo', '2026-10-01 08:30:00');

        // Luis: tramposo en Calculo I, fecha más reciente.
        $this->crearEstudiante('900000002', 'Luis', 'Alvarez Gomez', [self::CURSO_A]);
        $this->crearIncidencia('900000002', self::EXAMEN_A, 'tramposo', '2026-10-03 10:00:00');

        // Maria: tramposo en Fisica I, curso de otro docente.
        $this->crearEstudiante('900000003', 'Maria', 'Bernales Soto', [self::CURSO_B]);
        $this->crearIncidencia('900000003', self::EXAMEN_B, 'tramposo', '2026-10-02 09:15:00');

        // Pedro: sospechoso, no debe aparecer en el listado.
        $this->crearEstudiante('900000004', 'Pedro', 'Acosta Lima', [self::CURSO_A]);
        $this->crearIncidencia('900000004', self::EXAMEN_A, 'sospechoso', '2026-10-04 11:00:00');

        // Carla: tramposo en Calculo I, mismo apellido que Ana para el orden A-Z.
        $this->crearEstudiante('900000005', 'Carla', 'Zapata Ruiz', [self::CURSO_A]);
        $this->crearIncidencia('900000005', self::EXAMEN_A, 'tramposo', '2026-09-30 12:00:00');

        // Rosa: tramposo en Quimica II sin estar inscrita en el curso del
        // examen, la materia queda en el primer curso del examen (fallback).
        $this->crearEstudiante('900000006', 'Rosa', 'Isla Mena', [self::CURSO_A]);
        $this->crearIncidencia('900000006', self::EXAMEN_C, 'tramposo', '2026-09-29 15:45:00');

        // Jaime: tramposo en un examen con dos cursos (A y B); el examen A es
        // el de menor id, pero su curso es Fisica I, que es el que debe salir.
        $this->crearEstudiante('900000007', 'Jaime', 'Paredes Rojas', [self::CURSO_B]);
        $this->crearIncidencia('900000007', self::EXAMEN_D, 'tramposo', '2026-09-28 09:00:00');
    }

    private function crearCurso(int $id, string $nombre, int $docente): void
    {
        DB::table('curso')->insert([
            'id_curso' => $id,
            'nombre_curso' => $nombre,
            'sis_doc' => $docente,
            'fecha_creacion' => now()->toDateString(),
            'estado' => 'EnCurso',
        ]);
    }

    private function crearExamen(int $id, array $cursos): void
    {
        DB::table('examen')->insert([
            'id_examen' => $id,
            'fecha' => now()->toDateString(),
            'hora_inicio' => '08:00',
            'hora_fin' => '10:00',
            'duracion' => 120,
            'creador' => self::DOCENTE_A,
            'tipo_examen' => self::ID_TIPO_EXAMEN,
        ]);

        foreach ($cursos as $idCurso) {
            DB::table('examen_curso')->insert([
                'id_examen' => $id,
                'id_curso' => $idCurso,
            ]);
        }
    }

    private function crearEstudiante(string $sis, string $nombre, string $apellido, array $cursos): void
    {
        DB::table('estudiante')->insert([
            'sis_estudiante' => $sis,
            'nombre_estudiante' => $nombre,
            'apellido_estudiante' => $apellido,
            'carrera' => 'Sistemas',
        ]);

        foreach ($cursos as $idCurso) {
            DB::table('estudiante_curso')->insert([
                'sis_estudiante' => $sis,
                'id_curso' => $idCurso,
            ]);
        }
    }

    private function crearIncidencia(string $sis, int $idExamen, string $tipo, string $fecha): void
    {
        static $idRiesgo = 900000;

        $idRiesgo++;

        DB::table('central_riesgo')->insert([
            'id_registro' => $idRiesgo,
            'id_registrador' => self::DOCENTE_A,
            'detalle_motivo' => 'Motivo de prueba '.$sis,
            'fecha_registro' => $fecha,
            'tipo_infraccion' => $tipo,
            'id_examen' => $idExamen,
            'sis_estudiante' => $sis,
            'motivo' => 'uso de dispositivos electronicos',
        ]);
    }

    private function endpoint(string $query = ''): string
    {
        return '/api/central-riesgo'.($query !== '' ? '?'.$query : '');
    }

    private function sisDeLaPagina(string $query = ''): array
    {
        return collect($this->getJson($this->endpoint($query))->json('datos'))
            ->pluck('sis')
            ->all();
    }

    public function test_por_defecto_ordena_del_mas_reciente_al_mas_antiguo(): void
    {
        $response = $this->getJson($this->endpoint());

        $response->assertOk();
        $this->assertEquals(
            ['900000002', '900000003', '900000001', '900000005', '900000006', '900000007'],
            $this->sisDeLaPagina()
        );
    }

    public function test_solo_devuelve_incidencias_tramposas(): void
    {
        $this->assertNotContains('900000004', $this->sisDeLaPagina());
    }

    public function test_orden_az_ordena_por_primer_apellido_luego_segundo_y_nombre(): void
    {
        $response = $this->getJson($this->endpoint('orden=az'));

        $response->assertOk();
        $this->assertEquals(
            ['900000002', '900000003', '900000006', '900000007', '900000001', '900000005'],
            $this->sisDeLaPagina('orden=az')
        );
    }

    public function test_alcance_mis_materias_solo_muestra_los_cursos_del_usuario(): void
    {
        $this->assertEquals(
            ['900000002', '900000001', '900000005', '900000007'],
            $this->sisDeLaPagina('alcance=mis-materias&usuario='.self::DOCENTE_A)
        );
    }

    public function test_alcance_toda_la_institucion_muestra_todos(): void
    {
        $this->assertEquals(
            ['900000002', '900000003', '900000001', '900000005', '900000006', '900000007'],
            $this->sisDeLaPagina('alcance=toda-la-institucion')
        );
    }

    public function test_alcance_mis_materias_sin_usuario_devuelve_422(): void
    {
        $this->getJson($this->endpoint('alcance=mis-materias'))->assertStatus(422);
    }

    public function test_alcance_invalido_devuelve_422(): void
    {
        $this->getJson($this->endpoint('alcance=xyz'))->assertStatus(422);
    }

    public function test_busqueda_por_codigo_sis(): void
    {
        $response = $this->getJson($this->endpoint('busqueda=900000002'));

        $response->assertOk();
        $this->assertEquals(['900000002'], $this->sisDeLaPagina('busqueda=900000002'));
    }

    public function test_busqueda_por_nombre(): void
    {
        $this->assertEquals(['900000001'], $this->sisDeLaPagina('busqueda=Ana'));
    }

    public function test_busqueda_sin_coincidencias_devuelve_lista_vacia(): void
    {
        $response = $this->getJson($this->endpoint('busqueda=Zzzz'));

        $response->assertOk();
        $this->assertEquals([], $response->json('datos'));
        $this->assertEquals(0, $response->json('paginacion.total'));
        $this->assertEquals('No se encontraron resultados para la búsqueda', $response->json('mensaje'));
    }

    public function test_busqueda_invalida_devuelve_422(): void
    {
        $this->getJson($this->endpoint('busqueda=2021a'))->assertStatus(422);
    }

    public function test_paginacion_devuelve_maximo_ocho_por_pagina_y_total_de_paginas(): void
    {
        $this->crearEstudiante('900000008', 'Sara', 'Mena Torres', [self::CURSO_A]);
        $this->crearIncidencia('900000008', self::EXAMEN_A, 'tramposo', '2026-09-27 08:00:00');
        $this->crearEstudiante('900000009', 'Hugo', 'Mena Torres', [self::CURSO_A]);
        $this->crearIncidencia('900000009', self::EXAMEN_A, 'tramposo', '2026-09-26 08:00:00');
        $this->crearEstudiante('900000010', 'Ivan', 'Soto Ramos', [self::CURSO_A]);
        $this->crearIncidencia('900000010', self::EXAMEN_A, 'tramposo', '2026-09-25 08:00:00');

        $pagina1 = $this->getJson($this->endpoint());

        $pagina1->assertOk();
        $this->assertCount(8, $pagina1->json('datos'));
        $this->assertEquals(9, $pagina1->json('paginacion.total'));
        $this->assertEquals(2, $pagina1->json('paginacion.ultima_pagina'));

        $pagina2 = $this->getJson($this->endpoint('pagina=2'));

        $pagina2->assertOk();
        $this->assertCount(1, $pagina2->json('datos'));
        $this->assertEquals('900000010', $pagina2->json('datos.0.sis'));
    }

    public function test_materia_es_el_curso_del_estudiante_dentro_del_examen(): void
    {
        $datos = collect($this->getJson($this->endpoint('busqueda=900000001'))->json('datos'));

        $this->assertEquals('Calculo I', $datos->first()['materia']);
    }

    public function test_materia_elige_el_curso_del_estudiante_y_no_el_primero_del_examen(): void
    {
        $datos = collect($this->getJson($this->endpoint('busqueda=900000007'))->json('datos'));

        $this->assertEquals('Fisica I', $datos->first()['materia']);
    }

    public function test_materia_usa_el_primer_curso_del_examen_cuando_el_estudiante_no_pertenece_a_ninguno(): void
    {
        $datos = collect($this->getJson($this->endpoint('busqueda=900000006'))->json('datos'));

        $this->assertEquals('Quimica II', $datos->first()['materia']);
    }

    public function test_devuelve_los_campos_esperados_por_registro(): void
    {
        $response = $this->getJson($this->endpoint('busqueda=900000001'));

        $response->assertOk();
        $response->assertJsonPath('datos.0.nombre', 'Ana');
        $response->assertJsonPath('datos.0.apellido', 'Zapata Ruiz');
        $response->assertJsonPath('datos.0.sis', '900000001');
        $response->assertJsonPath('datos.0.materia', 'Calculo I');
        $response->assertJsonPath('datos.0.motivo', 'uso de dispositivos electronicos');
        $response->assertJsonPath('datos.0.fecha', '2026-10-01 08:30:00');
    }
}

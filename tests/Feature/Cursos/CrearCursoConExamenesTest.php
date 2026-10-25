<?php

namespace Tests\Feature\Cursos;

use App\Models\Examen;
use App\Models\Rol;
use App\Models\Usuario;
use App\Services\Examen\ListarExamenesCursoService;
use App\Services\Examen\RegistrarExamenService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class CrearCursoConExamenesTest extends TestCase
{
    use DatabaseTransactions;

    private const ID_DOCENTE = 900501;

    private const ID_DOCENTE_2 = 900502;

    private const ID_AMBIENTE = 900510;

    private const ID_NORMA = 900520;

    private const ID_MATERIAL = 900530;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usuario')->insert([
            ['id_usuario' => self::ID_DOCENTE, 'cod_sis' => 'DOC900501', 'password' => 'x', 'nombre_usuario' => 'Docente', 'apellido' => 'Curso'],
            ['id_usuario' => self::ID_DOCENTE_2, 'cod_sis' => 'DOC900502', 'password' => 'x', 'nombre_usuario' => 'Otro', 'apellido' => 'Docente'],
        ]);

        DB::table('rol_usuario')->insert([
            ['id_usuario' => self::ID_DOCENTE, 'id_rol' => $this->idRol(Rol::NOMBRE_DOCENTE)],
            ['id_usuario' => self::ID_DOCENTE_2, 'id_rol' => $this->idRol(Rol::NOMBRE_DOCENTE)],
        ]);

        DB::table('ambiente')->insert([
            'id_ambiente' => self::ID_AMBIENTE,
            'nombre_ambiente' => 'Aula de HU-16',
        ]);
        DB::table('norma')->insert([
            'id_norma' => self::ID_NORMA,
            'detalle_norma' => 'Norma de HU-16',
        ]);
        DB::table('material')->insert([
            'id_material' => self::ID_MATERIAL,
            'descripcion' => 'Material de HU-16',
        ]);

        $this->actingAs(Usuario::findOrFail(self::ID_DOCENTE));
    }

    public function test_crea_el_curso_completo_y_devuelve_el_resumen(): void
    {
        $respuesta = $this->post(route('cursos.store'), $this->datosValidos());

        $respuesta->assertRedirect(route('cursos'))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('resumen.materia', 'Fisica I')
            ->assertSessionHas('resumen.grupo', '1')
            ->assertSessionHas('resumen.ambiente.id', self::ID_AMBIENTE)
            ->assertSessionHas('resumen.ambiente.nombre', 'Aula de HU-16')
            ->assertSessionHas('resumen.cantidad_examenes', 2);

        $curso = DB::table('curso')
            ->where('sis_doc', self::ID_DOCENTE)
            ->where('nombre_curso', 'Fisica I - Grupo 1')
            ->first();

        $this->assertNotNull($curso);
        $this->assertSame(self::ID_AMBIENTE, (int) $curso->id_ambiente);
        $this->assertSame((int) $curso->id_curso, session('resumen')['id_curso']);

        $examenes = DB::table('examen')
            ->join('examen_curso', 'examen_curso.id_examen', '=', 'examen.id_examen')
            ->where('examen_curso.id_curso', $curso->id_curso)
            ->orderBy('examen.id_examen')
            ->get(['examen.id_examen', 'examen.nombre_examen', 'examen.fecha', 'examen.tipo_examen', 'examen.creador']);

        $this->assertCount(2, $examenes);
        $this->assertSame('Primer parcial', $examenes[0]->nombre_examen);
        $this->assertSame('2026-11-20', $examenes[0]->fecha);
        $this->assertNull($examenes[0]->tipo_examen);
        $this->assertSame(self::ID_DOCENTE, (int) $examenes[0]->creador);
        $this->assertSame('Examen final', $examenes[1]->nombre_examen);
        $this->assertNull($examenes[1]->fecha);

        $idExamen = (int) $examenes[0]->id_examen;
        $this->assertDatabaseHas('examen_ambiente', ['id_examen' => $idExamen, 'id_ambiente' => self::ID_AMBIENTE]);
        $this->assertDatabaseHas('examen_norma', ['id_examen' => $idExamen, 'id_norma' => self::ID_NORMA]);
        $this->assertDatabaseHas('examen_material_permitido', ['id_examen' => $idExamen, 'id_material_permitido' => self::ID_MATERIAL]);
        $this->assertDatabaseHas('norma_personalizada', ['id_examen' => $idExamen, 'numero_norma' => 1, 'descripcion_norma' => 'No usar celular']);
        $this->assertDatabaseHas('material_personalizado', ['id_examen' => $idExamen, 'numero_material' => 1, 'descripcion_material' => 'Calculadora cientifica']);

        $listado = app(ListarExamenesCursoService::class)->ejecutar((int) $curso->id_curso);
        $this->assertSame('Primer parcial', $listado[0]['nombre']);
        $this->assertSame('Primer parcial', $listado[0]['tipo']);
    }

    public function test_valida_cada_campo_obligatorio_en_el_servidor(): void
    {
        $casos = [
            ['materia', ['materia' => '']],
            ['grupo', ['grupo' => '']],
            ['gestion', ['gestion' => null]],
            ['anio', ['anio' => null]],
            ['ambiente_id', ['ambiente_id' => null]],
            ['examenes', ['examenes' => []]],
        ];

        foreach ($casos as [$campo, $cambio]) {
            $this->post(route('cursos.store'), $this->datosValidos($cambio))
                ->assertSessionHasErrors($campo);
        }

        $datos = $this->datosValidos();
        $datos['examenes'][0]['nombre'] = '';

        $this->post(route('cursos.store'), $datos)
            ->assertSessionHasErrors('examenes.0.nombre');

        $this->assertDatabaseMissing('curso', [
            'sis_doc' => self::ID_DOCENTE,
            'nombre_curso' => 'Fisica I - Grupo 1',
        ]);
    }

    public function test_rechaza_catalogos_inexistentes_sin_crear_el_curso(): void
    {
        $datos = $this->datosValidos();
        $datos['examenes'][0]['normas_ids'] = [999999];

        $this->post(route('cursos.store'), $datos)
            ->assertSessionHasErrors('examenes.0.normas_ids');

        $this->assertDatabaseMissing('curso', ['nombre_curso' => 'Fisica I - Grupo 1']);
    }

    public function test_revierte_el_curso_si_falla_un_examen(): void
    {
        $gestionesAntes = DB::table('curso_tipo_gestion')->count();
        $llamadas = 0;
        $this->mock(RegistrarExamenService::class, function ($mock) use (&$llamadas): void {
            $mock->shouldReceive('crearParaCurso')
                ->twice()
                ->andReturnUsing(function () use (&$llamadas): Examen {
                    $llamadas++;

                    if ($llamadas === 2) {
                        throw new RuntimeException('Fallo controlado del segundo examen.');
                    }

                    return new Examen;
                });
        });

        $this->withoutExceptionHandling();

        try {
            $this->post(route('cursos.store'), $this->datosValidos());
            $this->fail('Se esperaba el fallo controlado.');
        } catch (RuntimeException $error) {
            $this->assertSame('Fallo controlado del segundo examen.', $error->getMessage());
        }

        $this->assertDatabaseMissing('curso', ['nombre_curso' => 'Fisica I - Grupo 1']);
        $this->assertSame($gestionesAntes, DB::table('curso_tipo_gestion')->count());
    }

    public function test_usa_el_docente_autenticado_y_no_un_id_enviado(): void
    {
        $datos = $this->datosValidos(['docente_id' => self::ID_DOCENTE_2]);

        $this->post(route('cursos.store'), $datos)->assertSessionHasNoErrors();

        $this->assertDatabaseHas('curso', [
            'nombre_curso' => 'Fisica I - Grupo 1',
            'sis_doc' => self::ID_DOCENTE,
        ]);
        $this->assertDatabaseMissing('curso', [
            'nombre_curso' => 'Fisica I - Grupo 1',
            'sis_doc' => self::ID_DOCENTE_2,
        ]);
    }

    public function test_rechaza_el_duplicado_del_mismo_docente_y_gestion(): void
    {
        $this->post(route('cursos.store'), $this->datosValidos())->assertSessionHasNoErrors();

        $this->post(route('cursos.store'), $this->datosValidos())
            ->assertSessionHasErrors([
                'materia' => 'Ya tiene un curso con esa materia, grupo y gestion.',
            ]);

        $this->assertSame(1, DB::table('curso')
            ->where('sis_doc', self::ID_DOCENTE)
            ->where('nombre_curso', 'Fisica I - Grupo 1')
            ->count());
    }

    public function test_otro_docente_puede_registrar_el_mismo_curso(): void
    {
        $this->post(route('cursos.store'), $this->datosValidos())->assertSessionHasNoErrors();
        $this->actingAs(Usuario::findOrFail(self::ID_DOCENTE_2));

        $this->post(route('cursos.store'), $this->datosValidos())
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('curso', [
            'sis_doc' => self::ID_DOCENTE_2,
            'nombre_curso' => 'Fisica I - Grupo 1',
        ]);
    }

    /** @return array<string, mixed> */
    private function datosValidos(array $cambios = []): array
    {
        return array_merge([
            'materia' => 'Fisica I',
            'grupo' => '1',
            'gestion' => $this->idGestion('segundo semestre'),
            'anio' => now()->year,
            'ambiente_id' => self::ID_AMBIENTE,
            'examenes' => [
                [
                    'nombre' => 'Primer parcial',
                    'fecha' => '2026-11-20',
                    'normas_ids' => [self::ID_NORMA],
                    'normas_personalizadas' => ['No usar celular'],
                    'materiales_ids' => [self::ID_MATERIAL],
                    'materiales_personalizados' => ['Calculadora cientifica'],
                ],
                [
                    'nombre' => 'Examen final',
                    'fecha' => null,
                    'normas_ids' => [],
                    'normas_personalizadas' => [],
                    'materiales_ids' => [],
                    'materiales_personalizados' => [],
                ],
            ],
        ], $cambios);
    }

    private function idGestion(string $nombre): int
    {
        return (int) DB::table('tipo_gestion')
            ->where('nombre_tipo_gestion', $nombre)
            ->value('id_tipo_gestion');
    }

    private function idRol(string $nombre): int
    {
        return (int) DB::table('rol')
            ->where('nombre_rol', $nombre)
            ->value('id_rol');
    }
}

<?php

/**
 * @file    CrearCursoTest.php
 *
 * @author  Valery D. Ortuno P <valerydariana98@gmail.com>
 *
 * @created 2026-10-24
 *
 * @updated 2026-11-04
 * @updated 2026-11-09
 *
 * @description
 * Pruebas de integración del alta de curso (POST /cursos) contra el esquema
 * real de desarrollo. Como el resto de pruebas sobre tablas del dominio, usa
 * DatabaseTransactions con IDs dedicados (rango 900400+), así cada prueba se
 * revierte al terminar.
 *
 * Cubre que el curso se guarde activo, a nombre del docente, con el nombre
 * "{materia} - Grupo {grupo}" y su gestión con año, las validaciones de
 * cada campo y que no se cree dos veces el mismo curso. La materia de prueba
 * es propia ("Prueba Alta Curso") para no chocar con cursos reales: el
 * repetido se busca entre todos los cursos.
 *
 * @see  App\Http\Controllers\CursoController
 * @see  App\Http\Requests\StoreCursoRequest
 * @see  App\Services\Curso\RegistrarCursoService
 *
 * @changelog
 * - 2026-10-24  [Valery D. Ortuno P]  test: creación inicial.
 * - 2026-11-04  [Valery D. Ortuno P]  test: el año va desde el actual (sin
 *   tope) y no se repite un curso en la misma gestión y año; sí en otra
 *   gestión o en otro año.
 * - 2026-11-09  [Diegootm]  fix: usuarios de prueba con nombre propio
 *   ("DocenteAltaCurso") — el genérico "Docente" choca con el índice único
 *   usuario_nombre_usuario_unique de la base compartida de Supabase.
 */

namespace Tests\Feature\Cursos;

use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CrearCursoTest extends TestCase
{
    use DatabaseTransactions;

    private const ID_DOCENTE = 900401;

    private const ID_AUXILIAR = 900402;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usuario')->insert([
            ['id_usuario' => self::ID_DOCENTE, 'cod_sis' => 'DOC900401', 'password' => 'x', 'nombre_usuario' => 'DocenteAltaCurso', 'apellido' => 'Alta Curso'],
            ['id_usuario' => self::ID_AUXILIAR, 'cod_sis' => 'AUX900402', 'password' => 'x', 'nombre_usuario' => 'AuxiliarAltaCurso', 'apellido' => 'Alta Curso'],
        ]);

        DB::table('rol_usuario')->insert([
            ['id_usuario' => self::ID_DOCENTE, 'id_rol' => $this->idRol(Rol::NOMBRE_DOCENTE)],
            ['id_usuario' => self::ID_AUXILIAR, 'id_rol' => $this->idRol(Rol::NOMBRE_AUXILIAR)],
        ]);

        $this->actingAs(Usuario::findOrFail(self::ID_DOCENTE));
    }

    public function test_el_formulario_carga_con_las_gestiones(): void
    {
        $this->get(route('cursos.crear'))
            ->assertOk()
            ->assertSee('Primer semestre')
            ->assertSee('Verano');
    }

    public function test_crea_el_curso_activo_con_su_gestion(): void
    {
        $this->post(route('cursos.store'), $this->datosValidos())
            ->assertRedirect(route('cursos'))
            ->assertSessionHas('mensaje', 'Curso creado con exito');

        $curso = DB::table('curso')->where('sis_doc', self::ID_DOCENTE)->first();

        $this->assertNotNull($curso);
        $this->assertSame('Prueba Alta Curso - Grupo 21A', $curso->nombre_curso);
        $this->assertSame('EnCurso', $curso->estado);
        $this->assertSame(now()->toDateString(), $curso->fecha_creacion);
        $this->assertDatabaseHas('curso_tipo_gestion', [
            'id_curso' => $curso->id_curso,
            'id_tg' => $this->idGestion('segundo semestre'),
            'anio' => now()->year,
        ]);
    }

    /** El año va desde el actual, sin tope: no se crean cursos de años pasados. */
    public function test_el_anio_va_desde_el_actual(): void
    {
        $actual = now()->year;

        $this->post(route('cursos.store'), $this->datosValidos(['anio' => $actual + 80]))
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('curso_tipo_gestion', ['anio' => $actual + 80]);

        $this->post(route('cursos.store'), $this->datosValidos(['anio' => $actual - 1]))
            ->assertSessionHasErrors(['anio' => 'El año debe ser '.$actual.' o posterior.']);
        $this->post(route('cursos.store'), $this->datosValidos(['anio' => 12026]))
            ->assertSessionHasErrors(['anio' => 'Ingrese un año valido.']);
    }

    /** El mismo curso (materia, grupo, gestión y año) no se crea dos veces. */
    public function test_no_crea_un_curso_que_ya_existe(): void
    {
        $this->post(route('cursos.store'), $this->datosValidos())->assertSessionHasNoErrors();

        // Mismo curso, aunque cambien las mayúsculas de la materia.
        $this->post(route('cursos.store'), $this->datosValidos(['materia' => 'PRUEBA alta curso']))
            ->assertSessionHasErrors(['curso' => 'Este curso ya existe en la gestion y el año elegidos.']);

        $this->assertSame(1, DB::table('curso')->where('sis_doc', self::ID_DOCENTE)->count());
    }

    /** El mismo nombre sí puede repetirse en otra gestión o en otro año. */
    public function test_el_mismo_nombre_se_puede_en_otra_gestion_u_otro_anio(): void
    {
        $this->post(route('cursos.store'), $this->datosValidos())->assertSessionHasNoErrors();

        $this->post(route('cursos.store'), $this->datosValidos(['gestion' => $this->idGestion('invierno')]))
            ->assertSessionHasNoErrors();
        $this->post(route('cursos.store'), $this->datosValidos(['anio' => now()->year + 1]))
            ->assertSessionHasNoErrors();

        $this->assertSame(3, DB::table('curso')->where('sis_doc', self::ID_DOCENTE)->count());
    }

    public function test_limpia_espacios_y_pasa_el_grupo_a_mayusculas(): void
    {
        $this->post(route('cursos.store'), $this->datosValidos([
            'materia' => '  Prueba    Alta Curso ',
            'grupo' => '21a',
        ]))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('curso', [
            'sis_doc' => self::ID_DOCENTE,
            'nombre_curso' => 'Prueba Alta Curso - Grupo 21A',
        ]);
    }

    public function test_exige_materia_grupo_y_gestion(): void
    {
        $this->post(route('cursos.store'), [])
            ->assertSessionHasErrors([
                'materia' => 'Ingrese la materia.',
                'grupo' => 'Ingrese el grupo.',
                'gestion' => 'Seleccione la gestion.',
                'anio' => 'Ingrese el año.',
            ]);

        $this->assertDatabaseMissing('curso', ['sis_doc' => self::ID_DOCENTE]);
    }

    public function test_el_grupo_es_alfanumerico_de_hasta_3(): void
    {
        $mensaje = 'El grupo solo admite letras y numeros, hasta 3 caracteres.';

        $this->post(route('cursos.store'), $this->datosValidos(['grupo' => '21AB']))
            ->assertSessionHasErrors(['grupo' => $mensaje]);
        $this->post(route('cursos.store'), $this->datosValidos(['grupo' => '2-A']))
            ->assertSessionHasErrors(['grupo' => $mensaje]);
    }

    public function test_la_materia_no_supera_80_caracteres(): void
    {
        $this->post(route('cursos.store'), $this->datosValidos(['materia' => str_repeat('a', 81)]))
            ->assertSessionHasErrors(['materia' => 'La materia no debe superar los 80 caracteres.']);
    }

    public function test_la_gestion_debe_existir(): void
    {
        $this->post(route('cursos.store'), $this->datosValidos(['gestion' => 999]))
            ->assertSessionHasErrors(['gestion' => 'Seleccione una gestion valida.']);
    }

    public function test_un_auxiliar_no_puede_crear_cursos(): void
    {
        $this->actingAs(Usuario::findOrFail(self::ID_AUXILIAR));

        $this->get(route('cursos.crear'))->assertForbidden();
        $this->post(route('cursos.store'), $this->datosValidos())->assertForbidden();
    }

    /**
     * @param  array<string, mixed>  $sobrescribir
     * @return array<string, mixed>
     */
    private function datosValidos(array $sobrescribir = []): array
    {
        return array_merge([
            'materia' => 'Prueba Alta Curso',
            'grupo' => '21A',
            'gestion' => $this->idGestion('segundo semestre'),
            'anio' => now()->year,
        ], $sobrescribir);
    }

    private function idGestion(string $nombre): int
    {
        return (int) DB::table('tipo_gestion')->where('nombre_tipo_gestion', $nombre)->value('id_tipo_gestion');
    }

    private function idRol(string $nombre): int
    {
        return (int) DB::table('rol')->where('nombre_rol', $nombre)->value('id_rol');
    }
}

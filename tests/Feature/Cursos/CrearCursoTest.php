<?php

/**
 * @file    CrearCursoTest.php
 *
 * @author  Valery D. Ortuno P <valerydariana98@gmail.com>
 *
 * @created 2026-10-24
 *
 * @description
 * Pruebas de integración del alta de curso (POST /cursos) contra el esquema
 * real de desarrollo. Como el resto de pruebas sobre tablas del dominio, usa
 * DatabaseTransactions con IDs dedicados (rango 900400+), así cada prueba se
 * revierte al terminar.
 *
 * Cubre que el curso se guarde activo, a nombre del docente, con el nombre
 * "{materia} - Grupo {grupo}" y su gestión con año, y las validaciones de
 * cada campo.
 *
 * @see  App\Http\Controllers\CursoController
 * @see  App\Http\Requests\StoreCursoRequest
 * @see  App\Services\Curso\RegistrarCursoService
 *
 * @changelog
 * - 2026-10-24  [Valery D. Ortuno P]  test: creación inicial.
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
            ['id_usuario' => self::ID_DOCENTE, 'cod_sis' => 'DOC900401', 'password' => 'x', 'nombre_usuario' => 'Docente', 'apellido' => 'Alta Curso'],
            ['id_usuario' => self::ID_AUXILIAR, 'cod_sis' => 'AUX900402', 'password' => 'x', 'nombre_usuario' => 'Auxiliar', 'apellido' => 'Alta Curso'],
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
        $this->assertSame('Calculo I - Grupo 21A', $curso->nombre_curso);
        $this->assertSame('EnCurso', $curso->estado);
        $this->assertSame(now()->toDateString(), $curso->fecha_creacion);
        $this->assertSame(1, (int) $curso->id_ambiente);
        $this->assertDatabaseHas('curso_tipo_gestion', [
            'id_curso' => $curso->id_curso,
            'id_tg' => $this->idGestion('segundo semestre'),
            'anio' => now()->year,
        ]);
    }

    /** El año es libre entre 2000 y 2099: sirve para cursos futuros. */
    public function test_el_anio_es_libre_entre_2000_y_2099(): void
    {
        $mensaje = 'El año debe estar entre 2000 y 2099.';

        $this->post(route('cursos.store'), $this->datosValidos(['anio' => 2035]))
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('curso_tipo_gestion', ['anio' => 2035]);

        $this->post(route('cursos.store'), $this->datosValidos(['anio' => 1999]))
            ->assertSessionHasErrors(['anio' => $mensaje]);
        $this->post(route('cursos.store'), $this->datosValidos(['anio' => 2100]))
            ->assertSessionHasErrors(['anio' => $mensaje]);
    }

    public function test_limpia_espacios_y_pasa_el_grupo_a_mayusculas(): void
    {
        $this->post(route('cursos.store'), $this->datosValidos([
            'materia' => '  Calculo    I ',
            'grupo' => '21a',
        ]))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('curso', [
            'sis_doc' => self::ID_DOCENTE,
            'nombre_curso' => 'Calculo I - Grupo 21A',
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
            'materia' => 'Calculo I',
            'grupo' => '21A',
            'gestion' => $this->idGestion('segundo semestre'),
            'anio' => now()->year,
            'ambiente_id' => 1,
            'examenes' => [
                [
                    'nombre' => 'Primer parcial',
                    'fecha' => null,
                ],
            ],
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

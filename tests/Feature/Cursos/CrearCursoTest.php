<?php

/**
 * @file    CrearCursoTest.php
 *
 * @author  Valery D. Ortuno P <valerydariana98@gmail.com>
 *
 * @created 2026-10-24
 *
 * @updated 2026-11-28
 *
 * @description
 * Pruebas de integración del alta de curso (POST /cursos) contra el esquema
 * real de desarrollo (DatabaseTransactions, IDs dedicados 900400+). La materia
 * se elige por `id_materia` (del catálogo) y el grupo es un número del 1 al 10;
 * el nombre del curso se arma solo como "Materia - Grupo N". Cubre el alta, las
 * validaciones, el duplicado (materia+grupo+gestión+año) y el tope de 5 materias
 * por docente.
 *
 * @see  App\Http\Controllers\CursoController
 * @see  App\Http\Requests\StoreCursoRequest
 * @see  App\Services\Curso\RegistrarCursoService
 *
 * @changelog
 * - 2026-10-24  [Valery D. Ortuno P]  test: creación inicial.
 * - 2026-11-04  [Valery D. Ortuno P]  test: año desde el actual; no se repite.
 * - 2026-11-28  [Diego Tejerina]  test: materia por id, grupo numérico (1-10),
 *   nombre autogenerado, duplicado por materia+grupo+gestión+año y tope de 5.
 */

namespace Tests\Feature\Cursos;

use App\Models\Materia;
use App\Models\Rol;
use App\Models\Usuario;
use App\Support\Validadores;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CrearCursoTest extends TestCase
{
    use DatabaseTransactions;

    private const ID_DOCENTE = 900401;

    private const ID_AUXILIAR = 900402;

    private int $idMateria;

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

        $this->idMateria = $this->crearMateria('Prueba Alta Curso');

        $this->actingAs(Usuario::findOrFail(self::ID_DOCENTE));
    }

    public function test_el_formulario_carga_con_las_gestiones(): void
    {
        $this->get(route('cursos.crear'))
            ->assertOk()
            ->assertSee('Primer semestre')
            ->assertSee('Verano');
    }

    public function test_crea_el_curso_activo_con_su_gestion_y_nombre_autogenerado(): void
    {
        $this->post(route('cursos.store'), $this->datosValidos())
            ->assertRedirect(route('cursos'))
            ->assertSessionHas('mensaje', 'Curso creado con exito');

        $curso = DB::table('curso')->where('sis_doc', self::ID_DOCENTE)->first();

        $this->assertNotNull($curso);
        $this->assertSame('Prueba Alta Curso - Grupo 3', $curso->nombre_curso);
        $this->assertSame($this->idMateria, (int) $curso->id_materia);
        $this->assertSame(3, (int) $curso->numero_grupo);
        $this->assertSame('EnCurso', $curso->estado);
        $this->assertSame(now()->toDateString(), $curso->fecha_creacion);
        $this->assertDatabaseHas('curso_tipo_gestion', [
            'id_curso' => $curso->id_curso,
            'id_tg' => $this->idGestion('segundo semestre'),
            'anio' => now()->year,
        ]);
        // La materia queda ligada al docente.
        $this->assertDatabaseHas('docente_materia', [
            'id_docente' => self::ID_DOCENTE,
            'id_materia' => $this->idMateria,
        ]);
    }

    public function test_el_anio_va_desde_el_actual(): void
    {
        $actual = now()->year;

        $this->post(route('cursos.store'), $this->datosValidos(['anio' => $actual + 80]))
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('curso_tipo_gestion', ['anio' => $actual + 80]);

        $this->post(route('cursos.store'), $this->datosValidos(['anio' => $actual - 1]))
            ->assertSessionHasErrors(['anio' => 'El año debe ser '.$actual.' o posterior.']);
    }

    public function test_no_crea_un_curso_que_ya_existe(): void
    {
        $this->post(route('cursos.store'), $this->datosValidos())->assertSessionHasNoErrors();

        $this->post(route('cursos.store'), $this->datosValidos())
            ->assertSessionHasErrors(['curso' => 'Ya existe un curso de Prueba Alta Curso grupo 3 en esta gestion.']);

        $this->assertSame(1, DB::table('curso')->where('sis_doc', self::ID_DOCENTE)->count());
    }

    public function test_el_mismo_curso_se_puede_en_otra_gestion_u_otro_anio(): void
    {
        $this->post(route('cursos.store'), $this->datosValidos())->assertSessionHasNoErrors();

        $this->post(route('cursos.store'), $this->datosValidos(['gestion' => $this->idGestion('invierno')]))
            ->assertSessionHasNoErrors();
        $this->post(route('cursos.store'), $this->datosValidos(['anio' => now()->year + 1]))
            ->assertSessionHasNoErrors();

        $this->assertSame(3, DB::table('curso')->where('sis_doc', self::ID_DOCENTE)->count());
    }

    public function test_exige_materia_grupo_gestion_y_anio(): void
    {
        $this->post(route('cursos.store'), [])
            ->assertSessionHasErrors([
                'id_materia' => 'Seleccione la materia.',
                'grupo' => 'Ingrese el grupo.',
                'gestion' => 'Seleccione la gestion.',
                'anio' => 'Ingrese el año.',
            ]);

        $this->assertDatabaseMissing('curso', ['sis_doc' => self::ID_DOCENTE]);
    }

    public function test_el_grupo_debe_ser_un_numero_del_1_al_10(): void
    {
        $mensaje = 'El grupo debe ser un numero del 1 al 10.';

        $this->post(route('cursos.store'), $this->datosValidos(['grupo' => 0]))
            ->assertSessionHasErrors(['grupo' => $mensaje]);
        $this->post(route('cursos.store'), $this->datosValidos(['grupo' => 11]))
            ->assertSessionHasErrors(['grupo' => $mensaje]);
        $this->post(route('cursos.store'), $this->datosValidos(['grupo' => 'A']))
            ->assertSessionHasErrors(['grupo' => 'Ingrese el grupo.']);

        $this->assertDatabaseMissing('curso', ['sis_doc' => self::ID_DOCENTE]);
    }

    public function test_la_materia_debe_existir(): void
    {
        $this->post(route('cursos.store'), $this->datosValidos(['id_materia' => 999999]))
            ->assertSessionHasErrors(['id_materia' => 'Seleccione una materia valida.']);
    }

    public function test_la_gestion_debe_existir(): void
    {
        $this->post(route('cursos.store'), $this->datosValidos(['gestion' => 999]))
            ->assertSessionHasErrors(['gestion' => 'Seleccione una gestion valida.']);
    }

    public function test_un_docente_puede_tener_mas_de_cinco_materias(): void
    {
        // Ya no hay tope: seis cursos de seis materias distintas se crean todos.
        for ($i = 1; $i <= 6; $i++) {
            $this->post(route('cursos.store'), $this->datosValidos([
                'id_materia' => $this->crearMateria('Materia Sin Tope '.$i),
                'grupo' => $i,
            ]))->assertSessionHasNoErrors();
        }

        $this->assertSame(6, DB::table('curso')->where('sis_doc', self::ID_DOCENTE)->count());
        $this->assertSame(6, DB::table('docente_materia')->where('id_docente', self::ID_DOCENTE)->count());
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
            'id_materia' => $this->idMateria,
            'grupo' => 3,
            'gestion' => $this->idGestion('segundo semestre'),
            'anio' => now()->year,
        ], $sobrescribir);
    }

    private function crearMateria(string $nombre): int
    {
        return (int) Materia::query()->create([
            'codigo' => null,
            'nombre' => $nombre,
            'nombre_normalizado' => Validadores::normalizarNombreMateria($nombre),
            'semestre' => null,
            'origen' => Materia::ORIGEN_CATALOGO,
            'fecha_creacion' => now(),
        ])->id_materia;
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

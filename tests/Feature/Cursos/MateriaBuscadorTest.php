<?php

/**
 * @file    MateriaBuscadorTest.php
 *
 * @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
 *
 * @created 2026-11-28
 *
 * @description
 * Pruebas del endpoint "Agregar materia" del buscador de Crear curso
 * (POST api.materias.crear): agrega una materia nueva y rechaza duplicados por
 * nombre normalizado y por código, además de validar el formato del código.
 *
 * @changelog
 * - 2026-11-28  [Diego Tejerina]  test: creación inicial.
 */

namespace Tests\Feature\Cursos;

use App\Models\Materia;
use App\Models\Rol;
use App\Models\Usuario;
use App\Support\Validadores;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MateriaBuscadorTest extends TestCase
{
    use DatabaseTransactions;

    private const DOCENTE = 900601;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usuario')->insert([
            'id_usuario' => self::DOCENTE, 'cod_sis' => 'DOC900601', 'password' => 'x',
            'nombre_usuario' => 'DocenteMateria', 'apellido' => 'Buscador',
        ]);
        DB::table('rol_usuario')->insert([
            'id_usuario' => self::DOCENTE,
            'id_rol' => (int) DB::table('rol')->where('nombre_rol', Rol::NOMBRE_DOCENTE)->value('id_rol'),
        ]);

        $this->actingAs(Usuario::findOrFail(self::DOCENTE), 'web');
    }

    public function test_agrega_una_materia_nueva(): void
    {
        $this->postJson(route('api.materias.crear'), [
            'nombre' => 'Topicos Avanzados ZZ',
            'codigo' => '1230007',
            'semestre' => 7,
        ])
            ->assertCreated()
            ->assertJsonPath('materia.nombre', 'Topicos Avanzados ZZ')
            ->assertJsonPath('materia.codigo', '1230007')
            ->assertJsonPath('materia.semestre', 7);

        $this->assertDatabaseHas('materia', [
            'codigo' => '1230007',
            'nombre_normalizado' => 'topicos avanzados zz',
            'origen' => Materia::ORIGEN_USUARIO,
            'creado_por' => self::DOCENTE,
        ]);
    }

    public function test_rechaza_nombre_vacio(): void
    {
        $this->postJson(route('api.materias.crear'), ['nombre' => ''])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['nombre']);
    }

    public function test_el_codigo_debe_tener_7_digitos(): void
    {
        $this->postJson(route('api.materias.crear'), ['nombre' => 'Materia X QQ', 'codigo' => '123'])
            ->assertStatus(422)
            ->assertJsonPath('errors.codigo.0', 'El codigo debe tener 7 digitos.');
    }

    public function test_rechaza_duplicada_por_nombre_normalizado(): void
    {
        $this->crearMateria('Materia Dup QQ', '1239990');

        // Mismo nombre con tildes y mayúsculas distintas: es la misma materia.
        $this->postJson(route('api.materias.crear'), ['nombre' => '  matéria   DUP qq '])
            ->assertStatus(422)
            ->assertJsonPath('errors.nombre.0', 'Esta materia ya existe.');
    }

    public function test_rechaza_duplicada_por_codigo(): void
    {
        $this->crearMateria('Materia Codigo QQ', '1239991');

        $this->postJson(route('api.materias.crear'), ['nombre' => 'Otra Totalmente Distinta QQ', 'codigo' => '1239991'])
            ->assertStatus(422)
            ->assertJsonPath('errors.codigo.0', 'Esta materia ya existe.');
    }

    private function crearMateria(string $nombre, string $codigo): void
    {
        Materia::query()->create([
            'codigo' => $codigo,
            'nombre' => $nombre,
            'nombre_normalizado' => Validadores::normalizarNombreMateria($nombre),
            'origen' => Materia::ORIGEN_USUARIO,
            'fecha_creacion' => now(),
        ]);
    }
}

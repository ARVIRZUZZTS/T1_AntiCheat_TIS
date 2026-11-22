<?php

/**
 * @file    ArchivarCursoVistaTest.php
 *
 * @author  Equipo T1
 *
 * @created 2026-11-21
 *
 * @description
 * Pruebas de la vista Livewire «Archivar curso»: listado de cursos activos,
 * apertura del modal con el curso elegido, cancelación sin cambios,
 * confirmación, notificación y desaparición del curso de la lista.
 */

declare(strict_types=1);

namespace Tests\Feature\Cursos;

use App\Enums\CursoEstado;
use App\Livewire\Cursos\ArchivarCurso;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class ArchivarCursoVistaTest extends TestCase
{
    use DatabaseTransactions;

    private const DOCENTE = 997201;

    private const CURSO = 997201;

    private const OTRO_CURSO = 997202;

    protected function setUp(): void
    {
        parent::setUp();

        $rol = DB::table('rol')->where('nombre_rol', 'docente')->value('id_rol');
        $this->assertNotNull($rol);

        DB::table('usuario')->insert([
            'id_usuario' => self::DOCENTE,
            'cod_sis' => 'DOC997201',
            'password' => 'x',
            'nombre_usuario' => 'Docente',
            'apellido' => 'Vista',
        ]);
        DB::table('rol_usuario')->insert(['id_usuario' => self::DOCENTE, 'id_rol' => $rol]);

        $this->crearCurso(self::CURSO, 'Curso En Curso', CursoEstado::EnCurso);
        $this->crearCurso(self::OTRO_CURSO, 'Otro Curso', CursoEstado::EnCurso);
    }

    private function crearCurso(int $id, string $nombre, CursoEstado $estado): void
    {
        DB::table('curso')->insert([
            'id_curso' => $id,
            'nombre_curso' => $nombre,
            'sis_doc' => self::DOCENTE,
            'estado' => $estado->value,
            'fecha_creacion' => now()->toDateString(),
        ]);
    }

    public function test_lista_cursos_activos_con_boton_archivar(): void
    {
        $this->actingAs(Usuario::findOrFail(self::DOCENTE));

        Livewire::test(ArchivarCurso::class)
            ->assertSee('Curso En Curso')
            ->assertSee('Otro Curso')
            ->assertSee('wire:click="abrirModal('.self::CURSO.')"', false)
            ->assertSee('Archivar');
    }

    public function test_abrir_modal_muestra_el_nombre_del_curso_elegido(): void
    {
        $this->actingAs(Usuario::findOrFail(self::DOCENTE));

        Livewire::test(ArchivarCurso::class)
            ->call('abrirModal', self::CURSO)
            ->assertSet('modalAbierto', true)
            ->assertSet('cursoId', self::CURSO)
            ->assertSet('cursoNombre', 'Curso En Curso')
            ->assertSee('Archivar curso')
            ->assertSee('Curso En Curso');
    }

    public function test_cancelar_cierra_el_modal_sin_cambiar_el_estado(): void
    {
        $this->actingAs(Usuario::findOrFail(self::DOCENTE));

        Livewire::test(ArchivarCurso::class)
            ->call('abrirModal', self::CURSO)
            ->call('cerrarModal')
            ->assertSet('modalAbierto', false)
            ->assertSet('cursoId', null)
            ->assertSet('cursoNombre', '')
            ->assertDontSee('titulo-archivar-curso', false)
            ->assertDontSee('Curso archivado con éxito');

        $this->assertSame(
            CursoEstado::EnCurso->value,
            DB::table('curso')->where('id_curso', self::CURSO)->value('estado'),
        );
    }

    public function test_confirmar_archiva_el_curso_y_cierra_el_modal(): void
    {
        $this->actingAs(Usuario::findOrFail(self::DOCENTE));

        Livewire::test(ArchivarCurso::class)
            ->call('abrirModal', self::CURSO)
            ->call('archivar')
            ->assertSet('modalAbierto', false)
            ->assertSet('archivando', false)
            ->assertSet('mostrarExito', true)
            ->assertSee('Curso archivado con éxito')
            ->assertDontSee('Curso En Curso');

        $this->assertSame(
            CursoEstado::Archivado->value,
            DB::table('curso')->where('id_curso', self::CURSO)->value('estado'),
        );
    }

    public function test_no_archiva_dos_veces_mientras_esta_procesando(): void
    {
        $this->actingAs(Usuario::findOrFail(self::DOCENTE));

        Livewire::test(ArchivarCurso::class)
            ->call('abrirModal', self::CURSO)
            ->set('archivando', true)
            ->call('archivar')
            ->assertSet('archivando', true);

        $this->assertSame(
            CursoEstado::EnCurso->value,
            DB::table('curso')->where('id_curso', self::CURSO)->value('estado'),
        );
    }

    public function test_muestra_error_si_el_curso_ya_no_esta_en_curso(): void
    {
        $this->actingAs(Usuario::findOrFail(self::DOCENTE));

        $componente = Livewire::test(ArchivarCurso::class)
            ->call('abrirModal', self::CURSO);

        DB::table('curso')
            ->where('id_curso', self::CURSO)
            ->update(['estado' => CursoEstado::Archivado->value]);

        $componente->call('archivar')
            ->assertSet('modalAbierto', true)
            ->assertSet('errorArchivar', 'No tienes permiso para archivar este curso.');
    }
}

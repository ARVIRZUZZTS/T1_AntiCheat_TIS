<?php

/**
 * @file    RestaurarCursoVistaTest.php
 *
 * @author  Equipo T1
 *
 * @created 2026-11-21
 *
 * @description
 * Pruebas de la vista «Restaurar curso»: listado filtrado, modal,
 * confirmación, avisos y accesibilidad. Refs #326.
 */

declare(strict_types=1);

namespace Tests\Feature\Cursos;

use App\Enums\CursoEstado;
use App\Livewire\Cursos\RestaurarCurso;
use App\Models\Usuario;
use App\Services\Curso\ListarCursosDocenteService;
use App\Services\Curso\RestaurarCursoService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class RestaurarCursoVistaTest extends TestCase
{
    use DatabaseTransactions;

    private const DOCENTE = 998301;

    private const OTRO = 998302;

    private const ARCHIVADO = 998301;

    private const EN_CURSO = 998302;

    private const AJENO_ARCHIVADO = 998303;

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([self::DOCENTE, self::OTRO] as $id) {
            DB::table('usuario')->insert([
                'id_usuario' => $id,
                'cod_sis' => 'DOC'.$id,
                'password' => 'x',
                'nombre_usuario' => 'Docente'.$id,
                'apellido' => 'Restaurar',
            ]);
        }

        $this->crearCurso(self::ARCHIVADO, 'A Archivado Propio', self::DOCENTE, CursoEstado::Archivado->value);
        $this->crearCurso(self::EN_CURSO, 'B En Curso Propio', self::DOCENTE, CursoEstado::EnCurso->value);
        $this->crearCurso(self::AJENO_ARCHIVADO, 'C Archivado Ajeno', self::OTRO, CursoEstado::Archivado->value);
    }

    private function crearCurso(int $id, string $nombre, int $docente, string $estado): void
    {
        DB::table('curso')->insert([
            'id_curso' => $id,
            'nombre_curso' => $nombre,
            'sis_doc' => $docente,
            'estado' => $estado,
            'fecha_creacion' => now()->toDateString(),
        ]);
    }

    public function test_servicio_devuelve_solo_archivados_propios_ordenados(): void
    {
        $cursos = app(ListarCursosDocenteService::class)->archivados(self::DOCENTE);

        $this->assertSame([self::ARCHIVADO], array_map('intval', $cursos->pluck('id_curso')->all()));
        $this->assertSame(CursoEstado::Archivado->value, $cursos->first()->estado);
    }

    public function test_boton_abre_modal_y_cancelar_lo_cierra(): void
    {
        $this->actingAs(Usuario::findOrFail(self::DOCENTE));

        Livewire::test(RestaurarCurso::class)
            ->assertSee('A Archivado Propio')
            ->assertDontSeeHtml('aria-modal')
            ->call('abrirModal', self::ARCHIVADO, 'A Archivado Propio')
            ->assertSet('modalAbierto', true)
            ->assertSet('cursoNombre', 'A Archivado Propio')
            ->assertSeeHtml('aria-modal')
            ->assertSee('¿Restaurar el curso')
            ->call('cancelar')
            ->assertSet('modalAbierto', false)
            ->assertDontSeeHtml('aria-modal');
    }

    public function test_confirmar_restaura_curso_y_actualiza_lista(): void
    {
        $this->actingAs(Usuario::findOrFail(self::DOCENTE));

        Livewire::test(RestaurarCurso::class)
            ->call('abrirModal', self::ARCHIVADO, 'A Archivado Propio')
            ->call('confirmarRestauracion')
            ->assertSet('modalAbierto', false)
            ->assertSet('cursoId', null)
            ->assertDispatched('restauracion-curso-exitosa', mensaje: RestaurarCursoService::MENSAJE_EXITO)
            ->assertDontSee('A Archivado Propio');

        $this->assertSame(
            CursoEstado::EnCurso->value,
            DB::table('curso')->where('id_curso', self::ARCHIVADO)->value('estado'),
        );
    }
}

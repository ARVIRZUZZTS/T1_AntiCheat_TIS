<?php

/**
 * @file    ArchivarRestaurarCursoVistaTest.php
 *
 * @author  OchoaCesar <cesareduardonick@gmail.com>
 *
 * @created 2026-11-22
 *
 * @updated 2026-11-23
 *
 * @description
 * Pruebas de la página única «Archivar/Restaurar cursos»: una sola lista con
 * los cursos activos primero y los archivados al final, la gestión bajo el
 * título de cada curso, la acción «Archivar» en rojo y «Restaurar» en gris,
 * apertura y cierre de cada modal, confirmación
 * con sus servicios, avisos de éxito y error y el trap de foco del modal de
 * archivar.
 */

declare(strict_types=1);

namespace Tests\Feature\Cursos;

use App\Enums\CursoEstado;
use App\Livewire\Cursos\ArchivarRestaurarCurso;
use App\Models\Usuario;
use App\Services\Curso\ArchivarCursoService;
use App\Services\Curso\ListarCursosDocenteService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class ArchivarRestaurarCursoVistaTest extends TestCase
{
    use DatabaseTransactions;

    private const DOCENTE = 997201;

    private const OTRO = 997202;

    private const CURSO = 997201;

    private const OTRO_CURSO = 997202;

    private const ARCHIVADO = 997203;

    private const AJENO_ARCHIVADO = 997204;

    protected function setUp(): void
    {
        parent::setUp();

        $rol = DB::table('rol')->where('nombre_rol', 'docente')->value('id_rol');
        $gestion = DB::table('tipo_gestion')->value('id_tipo_gestion');
        $this->assertNotNull($rol);
        $this->assertNotNull($gestion);

        foreach ([self::DOCENTE, self::OTRO] as $id) {
            DB::table('usuario')->insert([
                'id_usuario' => $id,
                'cod_sis' => 'DOC'.$id,
                'password' => 'x',
                'nombre_usuario' => 'Docente',
                'apellido' => 'Vista '.$id,
            ]);
            DB::table('rol_usuario')->insert(['id_usuario' => $id, 'id_rol' => $rol]);
        }

        $this->crearCurso(self::CURSO, 'Curso En Curso', self::DOCENTE, CursoEstado::EnCurso, (int) $gestion);
        $this->crearCurso(self::OTRO_CURSO, 'Otro Curso', self::DOCENTE, CursoEstado::EnCurso);
        $this->crearCurso(self::ARCHIVADO, 'Curso Archivado Propio', self::DOCENTE, CursoEstado::Archivado, (int) $gestion);
        $this->crearCurso(self::AJENO_ARCHIVADO, 'Curso Archivado Ajeno', self::OTRO, CursoEstado::Archivado, (int) $gestion);
    }

    private function crearCurso(int $id, string $nombre, int $docente, CursoEstado $estado, ?int $gestion = null): void
    {
        DB::table('curso')->insert([
            'id_curso' => $id,
            'nombre_curso' => $nombre,
            'sis_doc' => $docente,
            'estado' => $estado->value,
            'fecha_creacion' => now()->toDateString(),
        ]);

        if ($gestion !== null) {
            DB::table('curso_tipo_gestion')->insert([
                'id_curso' => $id,
                'id_tg' => $gestion,
                'anio' => now()->year,
            ]);
        }
    }

    public function test_servicio_archivables_devuelve_solo_cursos_propios_en_curso_ordenados(): void
    {
        $servicio = app(ListarCursosDocenteService::class);

        $this->assertSame(
            ['Curso En Curso', 'Otro Curso'],
            $servicio->archivables(self::DOCENTE)->pluck('nombre_curso')->all(),
        );

        DB::table('curso')
            ->where('id_curso', self::CURSO)
            ->update(['estado' => CursoEstado::Archivado->value]);

        $this->assertSame(
            ['Otro Curso'],
            $servicio->archivables(self::DOCENTE)->pluck('nombre_curso')->all(),
        );
        $this->assertTrue($servicio->archivables(999999)->isEmpty());
    }

    public function test_servicio_archivados_devuelve_solo_archivados_propios_ordenados(): void
    {
        $cursos = app(ListarCursosDocenteService::class)->archivados(self::DOCENTE);

        $this->assertSame(
            ['Curso Archivado Propio'],
            $cursos->pluck('nombre_curso')->all(),
        );
        $this->assertSame(CursoEstado::Archivado->value, $cursos->first()->estado);
    }

    public function test_lista_unica_muestra_todos_los_cursos_y_archivados_al_final(): void
    {
        $this->actingAs(Usuario::findOrFail(self::DOCENTE));

        $html = Livewire::test(ArchivarRestaurarCurso::class)
            ->assertSee('Curso En Curso')
            ->assertSee('Otro Curso')
            ->assertSee('Curso Archivado Propio')
            ->assertDontSee('Curso Archivado Ajeno')
            ->html();

        $this->assertMatchesRegularExpression(
            '/Curso En Curso[\s\S]*Otro Curso[\s\S]*Curso Archivado Propio/',
            $html,
        );
    }

    public function test_acciones_son_texto_rojo_archivar_y_gris_restaurar(): void
    {
        $this->actingAs(Usuario::findOrFail(self::DOCENTE));

        $html = Livewire::test(ArchivarRestaurarCurso::class)->html();

        $this->assertMatchesRegularExpression(
            '/<button[^>]*text-fg-danger-strong[^>]*aria-label="Archivar curso Curso En Curso"[^>]*>[\s\S]{0,80}?Archivar<\/button>/',
            $html,
        );
        $this->assertMatchesRegularExpression(
            '/<button[^>]*text-body-subtle[^>]*aria-label="Restaurar curso Curso Archivado Propio"[^>]*>[\s\S]{0,80}?Restaurar<\/button>/',
            $html,
        );
    }

    public function test_cada_curso_muestra_su_gestion_debajo_del_titulo(): void
    {
        $gestiones = app(ListarCursosDocenteService::class)
            ->gestionesPorCurso([self::CURSO, self::ARCHIVADO, self::OTRO_CURSO]);

        $textoGestion = $gestiones->get(self::CURSO);
        $this->assertNotNull($textoGestion);
        $this->assertSame($textoGestion, $gestiones->get(self::ARCHIVADO));
        $this->assertNull($gestiones->get(self::OTRO_CURSO));

        $this->actingAs(Usuario::findOrFail(self::DOCENTE));

        $html = Livewire::test(ArchivarRestaurarCurso::class)
            ->assertSee($textoGestion)
            ->assertSee('Gestion no registrada')
            ->html();

        $texto = preg_quote($textoGestion, '/');
        $this->assertMatchesRegularExpression('/Curso En Curso[\s\S]{0,250}?'.$texto.'/', $html);
        $this->assertMatchesRegularExpression('/Curso Archivado Propio[\s\S]{0,250}?'.$texto.'/', $html);
        $this->assertMatchesRegularExpression('/Otro Curso[\s\S]{0,250}?Gestion no registrada/', $html);
    }

    public function test_pagina_muestra_la_lista_combinada_sin_parametro_de_pestana(): void
    {
        $this->actingAs(Usuario::findOrFail(self::DOCENTE))
            ->get(route('cursos.archivar-restaurar'))
            ->assertOk()
            ->assertSee('Curso En Curso')
            ->assertSee('Curso Archivado Propio')
            ->assertDontSee('Curso Archivado Ajeno');
    }

    public function test_abrir_un_modal_cierra_el_otro(): void
    {
        $this->actingAs(Usuario::findOrFail(self::DOCENTE));

        Livewire::test(ArchivarRestaurarCurso::class)
            ->call('abrirModalArchivar', self::CURSO)
            ->assertSet('modalArchivarAbierto', true)
            ->assertSet('modalAbierto', false)
            ->call('abrirModalRestaurar', self::ARCHIVADO, 'Curso Archivado Propio')
            ->assertSet('modalAbierto', true)
            ->assertSet('modalArchivarAbierto', false)
            ->call('abrirModalArchivar', self::CURSO)
            ->assertSet('modalArchivarAbierto', true)
            ->assertSet('modalAbierto', false);
    }

    public function test_muestra_estado_vacio_sin_cursos_propios(): void
    {
        DB::table('curso')->where('sis_doc', self::DOCENTE)->delete();

        $this->actingAs(Usuario::findOrFail(self::DOCENTE));

        Livewire::test(ArchivarRestaurarCurso::class)
            ->assertSee('No tienes cursos para archivar ni restaurar.')
            ->assertDontSee('wire:click="abrirModalArchivar(', false)
            ->assertDontSee('wire:click=\'abrirModalRestaurar(', false);
    }

    public function test_vista_incluye_foco_inicial_retorno_y_trap_de_tab(): void
    {
        $codigo = (string) file_get_contents(resource_path('views/livewire/cursos/archivar-restaurar-curso.blade.php'));

        $this->assertStringContainsString("\$wire.\$watch('modalArchivarAbierto'", $codigo);
        $this->assertStringContainsString("event.key !== 'Tab'", $codigo);
        $this->assertStringContainsString("getElementById('archivar-curso-cancelar')", $codigo);
        $this->assertStringContainsString('botonOrigenArchivar.focus()', $codigo);
    }

    public function test_abrir_modal_archivar_muestra_el_nombre_del_curso_elegido(): void
    {
        $this->actingAs(Usuario::findOrFail(self::DOCENTE));

        Livewire::test(ArchivarRestaurarCurso::class)
            ->call('abrirModalArchivar', self::CURSO)
            ->assertSet('modalArchivarAbierto', true)
            ->assertSet('cursoId', self::CURSO)
            ->assertSet('cursoNombre', 'Curso En Curso')
            ->assertSee('id="modal-archivar-curso"', false)
            ->assertSee('Archivar curso')
            ->assertSee('Curso En Curso');
    }

    public function test_cerrar_modal_archivar_sin_cambiar_el_estado(): void
    {
        $this->actingAs(Usuario::findOrFail(self::DOCENTE));

        Livewire::test(ArchivarRestaurarCurso::class)
            ->call('abrirModalArchivar', self::CURSO)
            ->call('cerrarModal')
            ->assertSet('modalArchivarAbierto', false)
            ->assertSet('cursoId', null)
            ->assertSet('cursoNombre', '')
            ->assertDontSee('titulo-archivar-curso', false)
            ->assertDontSee('Curso archivado con éxito');

        $this->assertSame(
            CursoEstado::EnCurso->value,
            DB::table('curso')->where('id_curso', self::CURSO)->value('estado'),
        );
    }

    public function test_confirmar_archiva_el_curso_y_muestra_el_aviso(): void
    {
        $this->actingAs(Usuario::findOrFail(self::DOCENTE));

        Livewire::test(ArchivarRestaurarCurso::class)
            ->call('abrirModalArchivar', self::CURSO)
            ->call('archivar')
            ->assertSet('modalArchivarAbierto', false)
            ->assertSet('archivando', false)
            ->assertSet('mostrarExito', true)
            ->assertSee('role="status"', false)
            ->assertSee('Curso archivado con éxito')
            ->assertSee('Curso En Curso');

        $this->assertSame(
            CursoEstado::Archivado->value,
            DB::table('curso')->where('id_curso', self::CURSO)->value('estado'),
        );
    }

    public function test_no_archiva_dos_veces_mientras_esta_procesando(): void
    {
        $this->actingAs(Usuario::findOrFail(self::DOCENTE));

        Livewire::test(ArchivarRestaurarCurso::class)
            ->call('abrirModalArchivar', self::CURSO)
            ->set('archivando', true)
            ->call('archivar')
            ->assertSet('archivando', true);

        $this->assertSame(
            CursoEstado::EnCurso->value,
            DB::table('curso')->where('id_curso', self::CURSO)->value('estado'),
        );
    }

    public function test_archivar_muestra_error_si_el_curso_ya_no_esta_en_curso(): void
    {
        $this->actingAs(Usuario::findOrFail(self::DOCENTE));

        $componente = Livewire::test(ArchivarRestaurarCurso::class)
            ->call('abrirModalArchivar', self::CURSO);

        DB::table('curso')
            ->where('id_curso', self::CURSO)
            ->update(['estado' => CursoEstado::Archivado->value]);

        $componente->call('archivar')
            ->assertSet('modalArchivarAbierto', true)
            ->assertSet('errorArchivar', 'No tienes permiso para archivar este curso.');
    }

    public function test_boton_abre_modal_restaurar_y_cancelar_lo_cierra(): void
    {
        $this->actingAs(Usuario::findOrFail(self::DOCENTE));

        Livewire::test(ArchivarRestaurarCurso::class)
            ->assertDontSeeHtml('aria-modal')
            ->call('abrirModalRestaurar', self::ARCHIVADO, 'Curso Archivado Propio')
            ->assertSet('modalAbierto', true)
            ->assertSet('cursoNombre', 'Curso Archivado Propio')
            ->assertSeeHtml('aria-modal')
            ->assertSee('¿Restaurar el curso')
            ->call('cancelar')
            ->assertSet('modalAbierto', false)
            ->assertDontSeeHtml('aria-modal');
    }

    public function test_confirmar_restaura_curso_y_muestra_el_aviso(): void
    {
        $this->actingAs(Usuario::findOrFail(self::DOCENTE));

        Livewire::test(ArchivarRestaurarCurso::class)
            ->call('abrirModalRestaurar', self::ARCHIVADO, 'Curso Archivado Propio')
            ->call('confirmarRestauracion')
            ->assertSet('modalAbierto', false)
            ->assertSet('cursoId', null)
            ->assertSet('mostrarExito', true)
            ->assertSee('Curso restaurado con éxito')
            ->assertSee('Curso Archivado Propio');

        $this->assertSame(
            CursoEstado::EnCurso->value,
            DB::table('curso')->where('id_curso', self::ARCHIVADO)->value('estado'),
        );
    }

    public function test_docente_no_dueno_recibe_error_al_restaurar(): void
    {
        $this->actingAs(Usuario::findOrFail(self::OTRO));

        Livewire::test(ArchivarRestaurarCurso::class)
            ->call('abrirModalRestaurar', self::ARCHIVADO, 'Curso Archivado Propio')
            ->call('confirmarRestauracion')
            ->assertSet('modalAbierto', false)
            ->assertSee('No tienes permiso para restaurar este curso.');

        $this->assertSame(
            CursoEstado::Archivado->value,
            DB::table('curso')->where('id_curso', self::ARCHIVADO)->value('estado'),
        );
    }

    public function test_curso_no_archivado_recibe_error_al_restaurar(): void
    {
        $this->actingAs(Usuario::findOrFail(self::DOCENTE));

        Livewire::test(ArchivarRestaurarCurso::class)
            ->call('abrirModalRestaurar', self::CURSO, 'Curso En Curso')
            ->call('confirmarRestauracion')
            ->assertSet('modalAbierto', false)
            ->assertSee('Solo se pueden restaurar cursos en estado Archivado.');

        $this->assertSame(
            CursoEstado::EnCurso->value,
            DB::table('curso')->where('id_curso', self::CURSO)->value('estado'),
        );
    }

    public function test_aviso_de_exito_se_puede_cerrar(): void
    {
        $this->actingAs(Usuario::findOrFail(self::DOCENTE));

        Livewire::test(ArchivarRestaurarCurso::class)
            ->call('abrirModalArchivar', self::CURSO)
            ->call('archivar')
            ->assertSet('mostrarExito', true)
            ->call('cerrarAviso')
            ->assertSet('mostrarExito', false)
            ->assertSet('mensajeExito', '')
            ->assertDontSee('Curso archivado con éxito');
    }

    public function test_aviso_de_exito_del_redirect_del_post_se_muestra_en_la_lista(): void
    {
        $this->actingAs(Usuario::findOrFail(self::DOCENTE))
            ->withSession(['mensaje' => ArchivarCursoService::MENSAJE_EXITO])
            ->get(route('cursos.archivar-restaurar'))
            ->assertOk()
            ->assertSee('Curso archivado con éxito');
    }
}

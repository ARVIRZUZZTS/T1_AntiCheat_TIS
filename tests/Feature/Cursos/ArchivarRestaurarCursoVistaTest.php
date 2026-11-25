<?php

/**
 * @file    ArchivarRestaurarCursoVistaTest.php
 *
 * @author  OchoaCesar <cesareduardonick@gmail.com>
 *
 * @created 2026-11-22
 *
 * @updated 2026-11-23
 * @updated 2026-11-24
 *
 * @description
 * Pruebas de la página única «Archivar/Restaurar cursos»: una sola lista con
 * los cursos activos primero y los archivados al final, la gestión bajo el
 * título de cada curso, la acción «Archivar» en rojo y «Restaurar» en gris,
 * apertura y cierre de cada modal, confirmación
 * con sus servicios, avisos de éxito y error y el trap de foco del modal de
 * archivar. Los activos se limitan a los de la gestión del año actual (sin
 * gestión o de otro año no se listan); los archivados no se filtran.
 *
 * @changelog
 * - 2026-11-24  [OchoaCesar]  test: activos solo de la gestión del año actual;
 *   caso nulo de gestión pasa a un curso archivado (Refs #326).
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

    private const SIN_GESTION = 997205;

    private const ANIO_ANTERIOR = 997206;

    private const ARCHIVADO_SIN_GESTION = 997207;

    protected function setUp(): void
    {
        parent::setUp();

        $rol = DB::table('rol')->where('nombre_rol', 'docente')->value('id_rol');
        $gestion = DB::table('tipo_gestion')->value('id_tipo_gestion');
        $gestionOtra = DB::table('tipo_gestion')
            ->where('id_tipo_gestion', '!=', $gestion)
            ->value('id_tipo_gestion');
        $this->assertNotNull($rol);
        $this->assertNotNull($gestion);
        $this->assertNotNull($gestionOtra);

        foreach ([self::DOCENTE, self::OTRO] as $id) {
            DB::table('usuario')->insert([
                'id_usuario' => $id,
                'cod_sis' => 'DOC'.$id,
                'password' => 'x',
                'nombre_usuario' => 'Docente'.$id,
                'apellido' => 'Vista '.$id,
            ]);
            DB::table('rol_usuario')->insert(['id_usuario' => $id, 'id_rol' => $rol]);
        }

        $this->crearCurso(self::CURSO, 'Curso En Curso', self::DOCENTE, CursoEstado::EnCurso, (int) $gestion);
        $this->crearCurso(self::OTRO_CURSO, 'Otro Curso', self::DOCENTE, CursoEstado::EnCurso, (int) $gestionOtra);
        $this->crearCurso(self::SIN_GESTION, 'Curso Sin Gestion', self::DOCENTE, CursoEstado::EnCurso);
        $this->crearCurso(self::ANIO_ANTERIOR, 'Curso Anio Anterior', self::DOCENTE, CursoEstado::EnCurso, (int) $gestion, now()->year - 1);
        $this->crearCurso(self::ARCHIVADO, 'Curso Archivado Propio', self::DOCENTE, CursoEstado::Archivado, (int) $gestion);
        $this->crearCurso(self::ARCHIVADO_SIN_GESTION, 'Curso Archivado Sin Gestion', self::DOCENTE, CursoEstado::Archivado);
        $this->crearCurso(self::AJENO_ARCHIVADO, 'Curso Archivado Ajeno', self::OTRO, CursoEstado::Archivado, (int) $gestion);
    }

    private function crearCurso(int $id, string $nombre, int $docente, CursoEstado $estado, ?int $gestion = null, ?int $anio = null): void
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
                'anio' => $anio ?? now()->year,
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

    public function test_servicio_archivables_excluye_cursos_sin_gestion_y_del_anio_anterior(): void
    {
        $nombres = app(ListarCursosDocenteService::class)
            ->archivables(self::DOCENTE)
            ->pluck('nombre_curso')
            ->all();

        $this->assertSame(['Curso En Curso', 'Otro Curso'], $nombres);
        $this->assertNotContains('Curso Sin Gestion', $nombres);
        $this->assertNotContains('Curso Anio Anterior', $nombres);
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
            ->assertSee('Curso Archivado Sin Gestion')
            ->assertDontSee('Curso Sin Gestion')
            ->assertDontSee('Curso Anio Anterior')
            ->assertDontSee('Curso Archivado Ajeno')
            ->html();

        $this->assertMatchesRegularExpression(
            '/Curso En Curso[\s\S]*Otro Curso[\s\S]*Curso Archivado Propio/',
            $html,
        );
    }

    public function test_solo_se_muestran_los_activos_de_la_gestion_del_anio_actual(): void
    {
        $this->actingAs(Usuario::findOrFail(self::DOCENTE));

        Livewire::test(ArchivarRestaurarCurso::class)
            ->assertSee('Curso En Curso')
            ->assertSee('Otro Curso')
            ->assertSee('Curso Archivado Propio')
            ->assertDontSee('Curso Sin Gestion')
            ->assertDontSee('Curso Anio Anterior');
    }

    public function test_la_tabla_muestra_la_cantidad_de_estudiantes(): void
    {
        DB::table('estudiante')->insert([
            'sis_estudiante' => '997201',
            'nombre_estudiante' => 'Ana',
            'apellido_estudiante' => 'Prueba',
            'carrera' => 'Sistemas',
        ]);
        DB::table('estudiante_curso')->insert([
            'sis_estudiante' => '997201',
            'id_curso' => self::CURSO,
        ]);

        $this->actingAs(Usuario::findOrFail(self::DOCENTE));

        $html = Livewire::test(ArchivarRestaurarCurso::class)
            ->assertSee('ESTUDIANTES')
            ->html();

        $this->assertMatchesRegularExpression(
            '/Curso En Curso[\s\S]{0,400}?<td class="px-4 py-2\.5 text-body">1<\/td>/',
            $html,
        );
    }

    public function test_acciones_son_texto_rojo_archivar_y_gris_restaurar(): void
    {
        $this->actingAs(Usuario::findOrFail(self::DOCENTE));

        $html = Livewire::test(ArchivarRestaurarCurso::class)->html();

        $this->assertMatchesRegularExpression(
            '/<button[^>]*text-fg-danger-strong[^>]*aria-label="Archivar curso Curso En Curso"[^>]*>[\s\S]{0,80}?Archivar\s*<\/button>/',
            $html,
        );
        $this->assertMatchesRegularExpression(
            '/<button[^>]*text-body-subtle[^>]*aria-label="Restaurar curso Curso Archivado Propio"[^>]*>[\s\S]{0,80}?Restaurar\s*<\/button>/',
            $html,
        );
    }

    public function test_cada_curso_muestra_su_gestion_debajo_del_titulo(): void
    {
        $gestiones = app(ListarCursosDocenteService::class)
            ->gestionesPorCurso([self::CURSO, self::ARCHIVADO, self::OTRO_CURSO, self::ARCHIVADO_SIN_GESTION]);

        $textoGestion = $gestiones->get(self::CURSO);
        $this->assertNotNull($textoGestion);
        $this->assertSame($textoGestion, $gestiones->get(self::ARCHIVADO));
        $this->assertNotNull($gestiones->get(self::OTRO_CURSO));
        $this->assertNotSame($textoGestion, $gestiones->get(self::OTRO_CURSO));
        $this->assertNull($gestiones->get(self::ARCHIVADO_SIN_GESTION));

        $this->actingAs(Usuario::findOrFail(self::DOCENTE));

        $html = Livewire::test(ArchivarRestaurarCurso::class)
            ->assertSee($textoGestion)
            ->assertSee('Gestion no registrada')
            ->html();

        $texto = preg_quote($textoGestion, '/');
        $this->assertMatchesRegularExpression('/Curso En Curso[\s\S]{0,250}?'.$texto.'/', $html);
        $this->assertMatchesRegularExpression('/Curso Archivado Propio[\s\S]{0,250}?'.$texto.'/', $html);
        $this->assertMatchesRegularExpression('/Curso Archivado Sin Gestion[\s\S]{0,250}?Gestion no registrada/', $html);
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
        DB::table('curso_tipo_gestion')
            ->whereIn('id_curso', [self::CURSO, self::OTRO_CURSO, self::ANIO_ANTERIOR, self::ARCHIVADO])
            ->delete();
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

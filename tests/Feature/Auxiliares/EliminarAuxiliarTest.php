<?php

/**
 * @file    EliminarAuxiliarTest.php
 *
 * @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
 *
 * @created 2026-11-27
 *
 * @description
 * Pruebas de la subsección "Eliminar auxiliar": filtros (búsqueda, materia,
 * fechas, estado), eliminación lógica (el registro sigue en la BD), bloqueo por
 * examen en curso, permisos (docente, coordinador con creados propios y ajenos,
 * auxiliar sin permiso), login bloqueado y que el eliminado desaparece de las
 * demás vistas.
 *
 * @changelog
 * - 2026-11-27  [Diego Tejerina]  feat: creación inicial.
 * - 2026-11-28  [Valery D. Ortuno P]  test: solo se elimina al auxiliar sin nada
 *   asociado (cursos activos o anteriores, examenes); el aviso sale en su fila.
 *   Se quitan los tests de los filtros de estado y materia.
 */

namespace Tests\Feature\Auxiliares;

use App\Enums\EstadoCuenta;
use App\Livewire\Auxiliares\EliminarAuxiliar;
use App\Models\Usuario;
use App\Repositories\AuxiliarCursoRepository;
use App\Services\Auxiliar\AccesoAuxiliarService;
use App\Services\Auxiliar\EliminarAuxiliarService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class EliminarAuxiliarTest extends TestCase
{
    use DatabaseTransactions;

    private const DOCENTE = 801;

    private const OTRO_DOCENTE = 802;

    private const COORD = 803;

    private const AUX_A = 804;      // del docente, con asignación activa (Habilitado)

    private const AUX_B = 805;      // creado por el coordinador

    private const AUX_C = 806;      // de otro docente (ajeno)

    private const AUX_SIN = 807;    // del docente, sin asignaciones (Deshabilitado)

    private const AUX_PLANO = 808;  // auxiliar sin permiso de coordinador

    private const CURSO = 901;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-27 10:00:00');

        DB::table('usuario')->insert([
            $this->docente(self::DOCENTE, 'doc801@umss.edu'),
            $this->docente(self::OTRO_DOCENTE, 'doc802@umss.edu'),
            // Coordinador: auxiliar con permiso, creado por el docente 801.
            $this->auxiliar(self::COORD, 'coord803@ej.com', self::DOCENTE, self::DOCENTE, '2026-08-01', true),
            $this->auxiliar(self::AUX_A, 'auxa804@ej.com', self::DOCENTE, self::DOCENTE, '2026-09-01'),
            $this->auxiliar(self::AUX_B, 'auxb805@ej.com', self::DOCENTE, self::COORD, '2026-09-15'),
            $this->auxiliar(self::AUX_C, 'auxc806@ej.com', self::OTRO_DOCENTE, self::OTRO_DOCENTE, '2026-09-20'),
            $this->auxiliar(self::AUX_SIN, 'auxsin807@ej.com', self::DOCENTE, self::DOCENTE, '2026-10-15'),
            $this->auxiliar(self::AUX_PLANO, 'auxp808@ej.com', self::DOCENTE, self::DOCENTE, '2026-10-20'),
        ]);

        DB::table('rol_usuario')->insert([
            ['id_usuario' => self::DOCENTE, 'id_rol' => 1],
            ['id_usuario' => self::OTRO_DOCENTE, 'id_rol' => 1],
            ['id_usuario' => self::COORD, 'id_rol' => 2],
            ['id_usuario' => self::AUX_A, 'id_rol' => 2],
            ['id_usuario' => self::AUX_B, 'id_rol' => 2],
            ['id_usuario' => self::AUX_C, 'id_rol' => 2],
            ['id_usuario' => self::AUX_SIN, 'id_rol' => 2],
            ['id_usuario' => self::AUX_PLANO, 'id_rol' => 2],
        ]);

        DB::table('curso')->insert([
            'id_curso' => self::CURSO, 'nombre_curso' => 'Calculo I', 'sis_doc' => self::DOCENTE,
            'fecha_creacion' => '2026-02-01', 'estado' => 'EnCurso',
        ]);

        // AUX_A está asignado activo al curso → Habilitado.
        DB::table('auxiliar_curso')->insert([
            'id_auxiliar' => self::AUX_A, 'id_curso' => self::CURSO, 'estado' => AccesoAuxiliarService::ESTADO_ACTIVO,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_el_docente_elimina_logicamente_y_el_registro_sigue_en_la_bd(): void
    {
        Livewire::actingAs(Usuario::findOrFail(self::DOCENTE))
            ->test(EliminarAuxiliar::class)
            ->call('abrirModal', self::AUX_SIN)
            ->assertSet('modalAbierto', true)
            ->call('eliminar')
            ->assertSet('mostrarExito', true)
            ->assertSet('mensajeExito', EliminarAuxiliarService::MENSAJE_EXITO);

        // El registro NO se borra: sigue en la BD, marcado como eliminada.
        $auxiliar = Usuario::findOrFail(self::AUX_SIN);
        $this->assertSame(EstadoCuenta::Eliminada->value, $auxiliar->estado_cuenta);
        $this->assertTrue($auxiliar->estaEliminado());
    }

    public function test_no_se_puede_eliminar_con_cursos_activos(): void
    {
        // AUX_A esta asignado activo a Calculo I (en curso).
        Livewire::actingAs(Usuario::findOrFail(self::DOCENTE))
            ->test(EliminarAuxiliar::class)
            ->call('abrirModal', self::AUX_A)
            ->assertSet('modalAbierto', false)
            ->assertSet('avisoId', self::AUX_A)
            ->assertSet('avisoMensaje', EliminarAuxiliarService::MENSAJE_CURSOS_ACTIVOS);

        // El servidor tambien lo rechaza aunque se intente.
        try {
            app(EliminarAuxiliarService::class)->ejecutar(Usuario::findOrFail(self::DOCENTE), Usuario::findOrFail(self::AUX_A));
            $this->fail('Se elimino un auxiliar con cursos activos.');
        } catch (\Illuminate\Validation\ValidationException $error) {
            $this->assertSame([EliminarAuxiliarService::MENSAJE_CURSOS_ACTIVOS], $error->errors()['auxiliar']);
        }
        $this->assertSame('habilitada', Usuario::findOrFail(self::AUX_A)->estado_cuenta);
    }

    public function test_no_se_puede_eliminar_a_quien_estuvo_asignado_a_un_curso_o_examen(): void
    {
        // AUX_SIN tuvo un curso (asignacion dada de baja); AUX_PLANO, un examen.
        DB::table('auxiliar_curso')->insert([
            'id_auxiliar' => self::AUX_SIN, 'id_curso' => self::CURSO, 'estado' => 'Baja',
        ]);
        DB::table('examen')->insert([
            'id_examen' => 950, 'fecha' => '2026-11-01', 'hora_inicio' => '09:30:00',
            'duracion' => 120, 'creador' => self::DOCENTE, 'tipo_examen' => 1,
        ]);
        DB::table('auxiliar_examen')->insert([
            'id_auxiliar' => self::AUX_PLANO, 'id_examen' => 950,
            'ver_ingreso' => true, 'ver_monitor' => true, 'asignado_por' => self::DOCENTE,
        ]);

        $componente = Livewire::actingAs(Usuario::findOrFail(self::DOCENTE))->test(EliminarAuxiliar::class);
        foreach ([self::AUX_SIN, self::AUX_PLANO] as $id) {
            $componente->call('abrirModal', $id)
                ->assertSet('modalAbierto', false)
                ->assertSet('avisoMensaje', EliminarAuxiliarService::MENSAJE_CON_HISTORIAL);
            $this->assertSame('habilitada', Usuario::findOrFail($id)->estado_cuenta);
        }
    }

    public function test_el_coordinador_solo_elimina_a_los_que_creo(): void
    {
        $coord = Usuario::findOrFail(self::COORD);

        // Puede con el que él creó (AUX_B), no con el del docente (AUX_A) ni el ajeno.
        $this->assertTrue(Gate::forUser($coord)->allows('eliminar', Usuario::findOrFail(self::AUX_B)));
        $this->assertFalse(Gate::forUser($coord)->allows('eliminar', Usuario::findOrFail(self::AUX_A)));
        $this->assertFalse(Gate::forUser($coord)->allows('eliminar', Usuario::findOrFail(self::AUX_C)));

        // Su lista solo muestra a AUX_B.
        $ids = Livewire::actingAs($coord)->test(EliminarAuxiliar::class)
            ->instance()->filtradas()->pluck('id')->all();
        $this->assertSame([self::AUX_B], $ids);
    }

    public function test_el_docente_elimina_a_sus_auxiliares_pero_no_a_los_ajenos(): void
    {
        $docente = Usuario::findOrFail(self::DOCENTE);

        $this->assertTrue(Gate::forUser($docente)->allows('eliminar', Usuario::findOrFail(self::AUX_A)));
        $this->assertTrue(Gate::forUser($docente)->allows('eliminar', Usuario::findOrFail(self::AUX_B)));
        // AUX_C es responsabilidad de otro docente.
        $this->assertFalse(Gate::forUser($docente)->allows('eliminar', Usuario::findOrFail(self::AUX_C)));
    }

    public function test_nadie_puede_eliminarse_a_si_mismo(): void
    {
        $coord = Usuario::findOrFail(self::COORD);
        $this->assertFalse(Gate::forUser($coord)->allows('eliminar', $coord));
    }

    public function test_un_auxiliar_sin_permiso_recibe_403_por_url(): void
    {
        $this->actingAs(Usuario::findOrFail(self::AUX_PLANO))
            ->get(route('auxiliares.eliminar'))
            ->assertForbidden();

        // El docente sí entra.
        $this->actingAs(Usuario::findOrFail(self::DOCENTE))
            ->get(route('auxiliares.eliminar'))
            ->assertOk();

        // El coordinador también.
        $this->actingAs(Usuario::findOrFail(self::COORD))
            ->get(route('auxiliares.eliminar'))
            ->assertOk();
    }

    public function test_un_auxiliar_eliminado_no_puede_iniciar_sesion(): void
    {
        app(EliminarAuxiliarService::class)->ejecutar(
            Usuario::findOrFail(self::DOCENTE),
            Usuario::findOrFail(self::AUX_SIN),
        );

        $this->post(route('login.store'), [
            'nombreUsuario' => 'Usuario'.self::AUX_SIN,
            'password' => 'secreto123',
        ])->assertSessionHasErrors(['cod_sis' => 'Tu cuenta fue eliminada.']);

        $this->assertGuest();
    }

    public function test_el_eliminado_desaparece_de_las_demas_vistas(): void
    {
        app(EliminarAuxiliarService::class)->ejecutar(
            Usuario::findOrFail(self::DOCENTE),
            Usuario::findOrFail(self::AUX_SIN),
        );

        // Ver lista (repositorio compartido) ya no lo trae.
        $ids = app(AuxiliarCursoRepository::class)->auxiliares()->pluck('id_usuario')->all();
        $this->assertNotContains(self::AUX_SIN, $ids);

        // Asignar auxiliar: tampoco aparece.
        $this->actingAs(Usuario::findOrFail(self::DOCENTE))
            ->get(route('auxiliares.asignar'))
            ->assertOk()
            ->assertDontSee('Usuario807');

        // Y sale de la propia lista de "Eliminar auxiliar".
        $ids = Livewire::actingAs(Usuario::findOrFail(self::DOCENTE))->test(EliminarAuxiliar::class)
            ->instance()->filtradas()->pluck('id')->all();
        $this->assertNotContains(self::AUX_SIN, $ids);
    }

    public function test_busqueda_por_correo_o_curso(): void
    {
        $componente = Livewire::actingAs(Usuario::findOrFail(self::DOCENTE))->test(EliminarAuxiliar::class);

        // Busqueda por correo.
        $this->assertSame(
            [self::AUX_B],
            $componente->set('busqueda', 'auxb805')->instance()->filtradas()->pluck('id')->all(),
        );

        // Busqueda por curso: solo AUX_A tiene Calculo I.
        $this->assertSame(
            [self::AUX_A],
            $componente->set('busqueda', 'calculo')->instance()->filtradas()->pluck('id')->all(),
        );

        // Buscar vuelve a la pagina 1.
        $componente->set('pagina', 3)->set('busqueda', 'x')->assertSet('pagina', 1);
    }

    private function docente(int $id, string $correo): array
    {
        return [
            'id_usuario' => $id, 'cod_sis' => 'DOC'.$id, 'correo' => $correo,
            'password' => Hash::make('secreto123'), 'nombre_usuario' => 'Usuario'.$id,
            'nombre' => 'Usuario'.$id, 'apellido' => 'Docente', 'fecha_creacion' => '2026-01-01 08:00:00',
            'estado_cuenta' => 'habilitada', 'puede_crear_auxiliares' => false,
            'tipo_auxiliar' => null, 'id_docente_responsable' => null, 'id_creador' => null,
        ];
    }

    private function auxiliar(int $id, string $correo, int $responsable, int $creador, string $fecha, bool $coordinador = false): array
    {
        return [
            'id_usuario' => $id, 'cod_sis' => 'AUX'.$id, 'correo' => $correo,
            'password' => Hash::make('secreto123'), 'nombre_usuario' => 'Usuario'.$id,
            'nombre' => 'Usuario'.$id, 'apellido' => 'Prueba', 'fecha_creacion' => $fecha.' 08:00:00',
            'estado_cuenta' => 'habilitada', 'tipo_auxiliar' => 'UMSS',
            'puede_crear_auxiliares' => $coordinador,
            'id_docente_responsable' => $responsable, 'id_creador' => $creador,
        ];
    }
}

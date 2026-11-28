<?php

/**
 * @file    ListaAuxiliaresVistaTest.php
 *
 * @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
 *
 * @created 2026-11-27
 *
 * @description
 * Lista de auxiliares: muestra usuario, carnet, correo, asignaciones y la
 * etiqueta "Coordinador", y el botón "Reenviar credenciales" regenera la
 * contraseña y la envía al correo.
 *
 * @changelog
 * - 2026-11-27  [Diego Tejerina]  feat: creación inicial.
 *
 * @see  App\Livewire\Auxiliares\GestionAuxiliares
 * @see  App\Services\Auxiliar\ReenviarCredencialesAuxiliarService
 */

namespace Tests\Feature\Auxiliares;

use App\Livewire\Auxiliares\GestionAuxiliares;
use App\Mail\CredencialesAuxiliarMail;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class ListaAuxiliaresVistaTest extends TestCase
{
    use DatabaseTransactions;

    private const DOCENTE = 1101;

    private const AUX_ASIGNADO = 1102;

    private const COORDINADOR = 1103;

    private const OTRO_DOCENTE = 1104;

    private const AUX_AJENO = 1105;

    private const CURSO = 3001;

    private const CURSO_AJENO = 3002;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();

        $clave = Hash::make('secreto123');
        DB::table('usuario')->insert(['id_usuario' => self::DOCENTE, 'cod_sis' => 'DOC1101', 'correo' => 'doc1101@umss.edu', 'password' => $clave, 'nombre_usuario' => 'Doc', 'nombre' => 'Doc', 'apellido' => 'Uno', 'estado_cuenta' => 'habilitada', 'puede_crear_auxiliares' => false, 'tipo_auxiliar' => null, 'carnet' => null]);
        DB::table('usuario')->insert(['id_usuario' => self::AUX_ASIGNADO, 'cod_sis' => '202300001', 'correo' => 'auxa@ejemplo.com', 'password' => $clave, 'nombre_usuario' => 'Ana', 'nombre' => 'Ana Maria', 'apellido' => 'Perez Lopez', 'estado_cuenta' => 'habilitada', 'puede_crear_auxiliares' => false, 'tipo_auxiliar' => 'UMSS', 'carnet' => null]);
        DB::table('usuario')->insert(['id_usuario' => self::COORDINADOR, 'cod_sis' => 'EXT-1103', 'correo' => 'coordb@ejemplo.com', 'password' => $clave, 'nombre_usuario' => 'Beto', 'nombre' => 'Beto', 'apellido' => 'Gomez', 'estado_cuenta' => 'habilitada', 'puede_crear_auxiliares' => true, 'tipo_auxiliar' => 'externo', 'carnet' => '5551234']);
        DB::table('usuario')->insert(['id_usuario' => self::OTRO_DOCENTE, 'cod_sis' => 'DOC1104', 'correo' => 'doc1104@umss.edu', 'password' => $clave, 'nombre_usuario' => 'Otra', 'nombre' => 'Otra', 'apellido' => 'Docente', 'estado_cuenta' => 'habilitada', 'puede_crear_auxiliares' => false, 'tipo_auxiliar' => null, 'carnet' => null]);
        DB::table('usuario')->insert(['id_usuario' => self::AUX_AJENO, 'cod_sis' => 'EXT-1105', 'correo' => 'auxc@ejemplo.com', 'password' => $clave, 'nombre_usuario' => 'Luis', 'nombre' => 'Luis', 'apellido' => 'Ajeno', 'estado_cuenta' => 'habilitada', 'puede_crear_auxiliares' => false, 'tipo_auxiliar' => 'externo', 'carnet' => '9999999']);

        DB::table('rol_usuario')->insert([
            ['id_usuario' => self::DOCENTE, 'id_rol' => 1],
            ['id_usuario' => self::AUX_ASIGNADO, 'id_rol' => 2],
            ['id_usuario' => self::COORDINADOR, 'id_rol' => 2],
            ['id_usuario' => self::AUX_AJENO, 'id_rol' => 2],
        ]);

        DB::table('curso')->insert([
            ['id_curso' => self::CURSO, 'nombre_curso' => 'Calculo I', 'sis_doc' => self::DOCENTE, 'fecha_creacion' => '2026-02-01', 'estado' => 'EnCurso'],
            ['id_curso' => self::CURSO_AJENO, 'nombre_curso' => 'Calculo I', 'sis_doc' => self::OTRO_DOCENTE, 'fecha_creacion' => '2026-02-01', 'estado' => 'EnCurso'],
        ]);
        DB::table('auxiliar_curso')->insert([
            ['id_auxiliar' => self::AUX_ASIGNADO, 'id_curso' => self::CURSO, 'estado' => 'Activo'],
            ['id_auxiliar' => self::AUX_AJENO, 'id_curso' => self::CURSO_AJENO, 'estado' => 'Activo'],
        ]);
    }

    public function test_la_lista_muestra_solo_nombre_correo_usuario_y_ver_detalle(): void
    {
        $this->actingAs(Usuario::findOrFail(self::DOCENTE));

        Livewire::test(GestionAuxiliares::class)
            ->assertSee('Ana Maria Perez Lopez')
            ->assertSee('auxa@ejemplo.com')
            ->assertSee('Ana')
            ->assertSee('Ver detalle')
            ->assertDontSee('Curso: Calculo I')
            ->assertDontSee('5551234')
            ->assertDontSee('Coordinador')
            // El reenvío se moverá a "Editar auxiliar"; ya no está en la lista.
            ->assertDontSee('Reenviar credenciales');
    }

    public function test_reenviar_credenciales_resetea_la_contrasena_y_la_envia(): void
    {
        $this->actingAs(Usuario::findOrFail(self::DOCENTE));
        $hashAnterior = Usuario::findOrFail(self::AUX_ASIGNADO)->password;

        Livewire::test(GestionAuxiliares::class)
            ->call('reenviarCredenciales', self::AUX_ASIGNADO)
            ->assertSet('mensajeReenvio', 'Credenciales reenviadas a auxa@ejemplo.com');

        $auxiliar = Usuario::findOrFail(self::AUX_ASIGNADO);
        $this->assertNotSame($hashAnterior, $auxiliar->password);
        $this->assertFalse(Hash::check('secreto123', $auxiliar->password));

        Mail::assertSent(CredencialesAuxiliarMail::class, fn (CredencialesAuxiliarMail $correo): bool => $correo->hasTo('auxa@ejemplo.com')
            && Hash::check($correo->contrasenaPlana, $auxiliar->password));
    }

    public function test_la_busqueda_encuentra_por_correo(): void
    {
        $this->actingAs(Usuario::findOrFail(self::DOCENTE));

        Livewire::test(GestionAuxiliares::class)
            ->set('busqueda', 'auxa@ejemplo.com')
            ->assertSee('Ana Maria Perez Lopez')
            ->assertDontSee('Beto Gomez');
    }

    public function test_la_lista_y_el_detalle_muestran_gestion_tipo_y_codigo_sis(): void
    {
        $idGestion = (int) DB::table('tipo_gestion')->value('id_tipo_gestion');
        $nombreGestion = Str::ucfirst((string) DB::table('tipo_gestion')
            ->where('id_tipo_gestion', $idGestion)
            ->value('nombre_tipo_gestion'));
        $anio = now()->year;
        $etiqueta = 'Calculo I - '.$nombreGestion.' '.$anio;

        DB::table('curso_tipo_gestion')->insert([
            'id_curso' => self::CURSO,
            'id_tg' => $idGestion,
            'anio' => $anio,
        ]);

        $this->actingAs(Usuario::findOrFail(self::DOCENTE));

        Livewire::test(GestionAuxiliares::class)
            ->assertDontSee('Curso: '.$etiqueta)
            ->call('verDetalle', self::AUX_ASIGNADO)
            ->assertSee($etiqueta);
    }

    public function test_la_busqueda_encuentra_por_tipo_de_auxiliar(): void
    {
        $this->actingAs(Usuario::findOrFail(self::DOCENTE));

        Livewire::test(GestionAuxiliares::class)
            ->set('busqueda', 'umss')
            ->assertSee('Ana Maria Perez Lopez')
            ->assertDontSee('Beto Gomez');
    }

    public function test_el_docente_no_ve_ni_abre_detalle_de_auxiliares_ajenos(): void
    {
        $this->actingAs(Usuario::findOrFail(self::DOCENTE));

        Livewire::test(GestionAuxiliares::class)
            ->assertDontSee('Luis Ajeno')
            ->assertDontSee('auxc@ejemplo.com')
            ->call('verDetalle', self::AUX_AJENO)
            ->assertSet('detalleAbierto', false);
    }
}

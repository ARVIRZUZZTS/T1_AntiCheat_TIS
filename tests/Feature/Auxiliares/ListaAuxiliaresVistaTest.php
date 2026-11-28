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
use Livewire\Livewire;
use Tests\TestCase;

class ListaAuxiliaresVistaTest extends TestCase
{
    use DatabaseTransactions;

    private const DOCENTE = 1101;

    private const AUX_ASIGNADO = 1102;

    private const COORDINADOR = 1103;

    private const CURSO = 3001;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();

        $clave = Hash::make('secreto123');
        DB::table('usuario')->insert(['id_usuario' => self::DOCENTE, 'cod_sis' => 'DOC1101', 'correo' => 'doc1101@umss.edu', 'password' => $clave, 'nombre_usuario' => 'Doc', 'nombre' => 'Doc', 'apellido' => 'Uno', 'estado_cuenta' => 'habilitada', 'puede_crear_auxiliares' => false, 'tipo_auxiliar' => null, 'carnet' => null]);
        DB::table('usuario')->insert(['id_usuario' => self::AUX_ASIGNADO, 'cod_sis' => '202300001', 'correo' => 'auxa@ejemplo.com', 'password' => $clave, 'nombre_usuario' => 'Ana', 'nombre' => 'Ana Maria', 'apellido' => 'Perez Lopez', 'estado_cuenta' => 'habilitada', 'puede_crear_auxiliares' => false, 'tipo_auxiliar' => 'UMSS', 'carnet' => null]);
        DB::table('usuario')->insert(['id_usuario' => self::COORDINADOR, 'cod_sis' => 'EXT-1103', 'correo' => 'coordb@ejemplo.com', 'password' => $clave, 'nombre_usuario' => 'Beto', 'nombre' => 'Beto', 'apellido' => 'Gomez', 'estado_cuenta' => 'habilitada', 'puede_crear_auxiliares' => true, 'tipo_auxiliar' => 'externo', 'carnet' => '5551234']);

        DB::table('rol_usuario')->insert([
            ['id_usuario' => self::DOCENTE, 'id_rol' => 1],
            ['id_usuario' => self::AUX_ASIGNADO, 'id_rol' => 2],
            ['id_usuario' => self::COORDINADOR, 'id_rol' => 2],
        ]);

        DB::table('curso')->insert(['id_curso' => self::CURSO, 'nombre_curso' => 'Calculo I', 'sis_doc' => self::DOCENTE, 'fecha_creacion' => '2026-02-01', 'estado' => 'EnCurso']);
        DB::table('auxiliar_curso')->insert(['id_auxiliar' => self::AUX_ASIGNADO, 'id_curso' => self::CURSO, 'estado' => 'Activo']);
    }

    public function test_la_lista_muestra_usuario_carnet_correo_asignaciones_y_coordinador(): void
    {
        $this->actingAs(Usuario::findOrFail(self::DOCENTE));

        Livewire::test(GestionAuxiliares::class)
            ->assertSee('Ana Maria Perez Lopez')
            ->assertSee('auxa@ejemplo.com')
            ->assertSee('Curso: Calculo I')
            ->assertSee('Coordinador')
            ->assertSee('5551234')
            ->assertSee('coordb@ejemplo.com')
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
            ->set('busqueda', 'coordb@ejemplo.com')
            ->assertSee('Beto Gomez')
            ->assertDontSee('Ana Maria Perez Lopez');
    }
}

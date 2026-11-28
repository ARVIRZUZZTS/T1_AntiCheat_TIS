<?php

/**
 * @file    CrearAuxiliarTest.php
 *
 * @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
 *
 * @created 2026-11-27
 *
 * @description
 * Alta de auxiliar: creación UMSS y externa, generación del código interno
 * EXT-, contraseña con y sin documento, duplicados (SIS, carnet, correo),
 * validaciones y envío de credenciales por correo.
 *
 * @changelog
 * - 2026-11-27  [Diego Tejerina]  feat: creación inicial.
 * - 2026-11-28  [Valery D. Ortuno P]  test: crear auxiliar ya no da el permiso
 *   de coordinador; el usuario de acceso se ve en vivo. El carnet de identidad
 *   es obligatorio (salen los casos sin carnet con contraseña aleatoria).
 *
 * @see  App\Livewire\Auxiliares\CrearAuxiliar
 * @see  App\Services\Auxiliar\CrearAuxiliarService
 */

namespace Tests\Feature\Auxiliares;

use App\Livewire\Auxiliares\CrearAuxiliar;
use App\Mail\AuxiliarCreadoMail;
use App\Mail\CredencialesAuxiliarMail;
use App\Models\Rol;
use App\Models\Usuario;
use App\Services\Auxiliar\CrearAuxiliarService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class CrearAuxiliarTest extends TestCase
{
    use DatabaseTransactions;

    private const DOCENTE = 701;

    private const COORDINADOR = 702;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();

        DB::table('usuario')->insert([
            $this->filaUsuario(self::DOCENTE, 'DOC701', 'docente701@umss.edu', false, null),
            $this->filaUsuario(self::COORDINADOR, 'AUX702', 'coord702@ejemplo.com', true, self::DOCENTE),
        ]);
        DB::table('rol_usuario')->insert([
            ['id_usuario' => self::DOCENTE, 'id_rol' => 1],
            ['id_usuario' => self::COORDINADOR, 'id_rol' => 2],
        ]);
    }

    public function test_crea_un_auxiliar_umss_con_cedula_y_envia_credenciales(): void
    {
        $this->actingAs(Usuario::findOrFail(self::DOCENTE));

        Livewire::test(CrearAuxiliar::class)
            ->set('nombre', 'Manual Jorge')
            ->set('apellido', 'Camacho Flores')
            ->set('tipoAuxiliar', 'UMSS')
            ->set('codSis', '947938612')
            ->set('carnet', '1234567')
            ->set('correo', 'nuevo.aux@ejemplo.com')
            ->call('revisar')
            ->assertSet('confirmando', true)
            ->assertSet('usuarioPreview', 'aux.manual.camacho')
            ->call('confirmar')
            ->assertRedirect(route('auxiliares'));

        $auxiliar = Usuario::query()->where('correo', 'nuevo.aux@ejemplo.com')->firstOrFail();
        $this->assertSame(
            ['id' => $auxiliar->id_usuario, 'credencialesEnviadas' => true],
            session('auxiliarCreado'),
        );
        $this->assertSame('947938612', $auxiliar->cod_sis);
        $this->assertSame('UMSS', $auxiliar->tipo_auxiliar);
        $this->assertSame('1234567', $auxiliar->carnet);
        // Usuario generado con prefijo de auxiliar (el acceso es por usuario).
        $this->assertSame('aux.manual.camacho', $auxiliar->nombre_usuario);
        $this->assertSame('habilitada', $auxiliar->estado_cuenta);
        $this->assertSame(self::DOCENTE, (int) $auxiliar->id_docente_responsable);
        $this->assertSame(self::DOCENTE, (int) $auxiliar->id_creador);
        $this->assertTrue($auxiliar->esAuxiliar());
        // La contraseña se genera con la cédula (no con el SIS).
        $this->assertTrue(Hash::check('AUX-MJ1234567CF', $auxiliar->password));

        Mail::assertSent(CredencialesAuxiliarMail::class, fn (CredencialesAuxiliarMail $correo): bool => $correo->hasTo('nuevo.aux@ejemplo.com')
            && $correo->contrasenaPlana === 'AUX-MJ1234567CF');
    }

    public function test_sin_carnet_de_identidad_no_se_crea(): void
    {
        $this->actingAs(Usuario::findOrFail(self::DOCENTE));

        // El carnet de identidad es obligatorio para UMSS y para externos.
        foreach ([['UMSS', '123123123'], ['externo', '']] as [$tipo, $sis]) {
            Livewire::test(CrearAuxiliar::class)
                ->set('nombre', 'Rosa')->set('apellido', 'Vega')
                ->set('tipoAuxiliar', $tipo)->set('codSis', $sis)
                ->set('carnet', '')
                ->set('correo', 'sin.carnet.'.$tipo.'@ejemplo.com')
                ->call('revisar')
                ->assertHasErrors(['carnet' => 'required'])
                ->assertSet('confirmando', false);
        }

        $this->assertDatabaseMissing('usuario', ['correo' => 'sin.carnet.UMSS@ejemplo.com']);
        Mail::assertNothingSent();
    }

    public function test_crea_un_auxiliar_externo_con_codigo_interno_y_carnet(): void
    {
        $this->actingAs(Usuario::findOrFail(self::DOCENTE));

        Livewire::test(CrearAuxiliar::class)
            ->set('nombre', 'Juan')
            ->set('apellido', 'Mamani')
            ->set('tipoAuxiliar', 'externo')
            ->set('carnet', '9479386')
            ->set('correo', 'externo@gmail.com')
            ->call('revisar')
            ->call('confirmar')
            ->assertRedirect(route('auxiliares'));

        $auxiliar = Usuario::query()->where('correo', 'externo@gmail.com')->firstOrFail();
        $this->assertSame('EXT-'.$auxiliar->id_usuario, $auxiliar->cod_sis);
        $this->assertSame('externo', $auxiliar->tipo_auxiliar);
        $this->assertSame('9479386', $auxiliar->carnet);
        $this->assertTrue(Hash::check('AUX-JJ9479386MM', $auxiliar->password));
    }

    public function test_el_usuario_generado_agrega_numero_si_ya_existe(): void
    {
        $this->actingAs(Usuario::findOrFail(self::DOCENTE));
        // Ya existe aux.ana.perez: el nuevo debe quedar aux.ana.perez2.
        DB::table('usuario')->insert(['nombre_usuario' => 'aux.ana.perez'] + $this->filaUsuario(720, 'EXT-720', 'ocupado@ejemplo.com', false, null));
        DB::table('rol_usuario')->insert(['id_usuario' => 720, 'id_rol' => 2]);

        Livewire::test(CrearAuxiliar::class)
            ->set('nombre', 'Ana')->set('apellido', 'Perez')
            ->set('tipoAuxiliar', 'externo')
            ->set('correo', 'ana.nueva@ejemplo.com')
            ->set('carnet', '7100001')
            ->call('revisar')->call('confirmar');

        $auxiliar = Usuario::query()->where('correo', 'ana.nueva@ejemplo.com')->firstOrFail();
        $this->assertSame('aux.ana.perez2', $auxiliar->nombre_usuario);
    }

    public function test_el_creador_recibe_aviso_sin_la_contrasena(): void
    {
        $this->actingAs(Usuario::findOrFail(self::DOCENTE));

        Livewire::test(CrearAuxiliar::class)
            ->set('nombre', 'Ana')->set('apellido', 'Perez')
            ->set('tipoAuxiliar', 'UMSS')->set('codSis', '111111111')
            ->set('correo', 'avisa@ejemplo.com')
            ->set('carnet', '7100002')
            ->call('revisar')->call('confirmar');

        Mail::assertSent(AuxiliarCreadoMail::class, fn (AuxiliarCreadoMail $correo): bool => $correo->hasTo('docente701@umss.edu'));
        // El aviso no lleva contraseña: el mailable no la conoce.
        $this->assertFalse(property_exists(AuxiliarCreadoMail::class, 'contrasenaPlana'));
    }

    public function test_cuando_crea_un_coordinador_avisa_al_docente_responsable(): void
    {
        $this->actingAs(Usuario::findOrFail(self::COORDINADOR));

        Livewire::test(CrearAuxiliar::class)
            ->set('nombre', 'Luis')->set('apellido', 'Gomez')
            ->set('tipoAuxiliar', 'UMSS')->set('codSis', '222222222')
            ->set('correo', 'delcoord@ejemplo.com')
            ->set('carnet', '7100003')
            ->call('revisar')->call('confirmar');

        $auxiliar = Usuario::query()->where('correo', 'delcoord@ejemplo.com')->firstOrFail();
        $this->assertSame(self::DOCENTE, (int) $auxiliar->id_docente_responsable);
        // Un coordinador no otorga el permiso.
        $this->assertFalse((bool) $auxiliar->puede_crear_auxiliares);

        Mail::assertSent(AuxiliarCreadoMail::class, fn (AuxiliarCreadoMail $correo): bool => $correo->hasTo('coord702@ejemplo.com'));
        Mail::assertSent(AuxiliarCreadoMail::class, fn (AuxiliarCreadoMail $correo): bool => $correo->hasTo('docente701@umss.edu'));
    }

    public function test_crear_auxiliar_no_da_el_permiso_de_coordinador_y_muestra_el_usuario_en_vivo(): void
    {
        $this->actingAs(Usuario::findOrFail(self::DOCENTE));

        Livewire::test(CrearAuxiliar::class)
            ->set('nombre', 'Coord')->set('apellido', 'Nuevo')
            // El usuario de acceso se ve apenas hay nombre y apellido.
            ->assertSee('aux.coord.nuevo')
            ->assertDontSee('Puede crear y asignar auxiliares')
            ->set('tipoAuxiliar', 'UMSS')->set('codSis', '333333333')
            ->set('correo', 'coordinador.nuevo@ejemplo.com')
            ->set('carnet', '7100004')
            ->call('revisar')->call('confirmar');

        // El permiso de coordinador se dara en las asignaciones, no al crear.
        $auxiliar = Usuario::query()->where('correo', 'coordinador.nuevo@ejemplo.com')->firstOrFail();
        $this->assertFalse((bool) $auxiliar->puede_crear_auxiliares);
    }

    public function test_cancelar_la_confirmacion_no_crea_y_cierra_el_modal(): void
    {
        $this->actingAs(Usuario::findOrFail(self::DOCENTE));

        Livewire::test(CrearAuxiliar::class)
            ->set('nombre', 'Ana')->set('apellido', 'Perez')
            ->set('tipoAuxiliar', 'UMSS')->set('codSis', '555555555')
            ->set('correo', 'cancela@ejemplo.com')
            ->set('carnet', '7100005')
            ->call('revisar')
            ->assertSet('confirmando', true)
            ->call('cancelarConfirmacion')
            ->assertSet('confirmando', false);

        $this->assertDatabaseMissing('usuario', ['correo' => 'cancela@ejemplo.com']);
        Mail::assertNothingSent();
    }

    public function test_el_sis_duplicado_no_crea_y_marca_el_campo(): void
    {
        $this->actingAs(Usuario::findOrFail(self::DOCENTE));

        Livewire::test(CrearAuxiliar::class)
            ->set('nombre', 'Ana')->set('apellido', 'Perez')
            ->set('tipoAuxiliar', 'UMSS')->set('codSis', 'DOC701')
            ->set('correo', 'otro@ejemplo.com')
            ->set('carnet', '7100006')
            ->call('revisar')
            ->assertHasErrors('codSis')
            ->assertSet('confirmando', false);
    }

    public function test_el_correo_duplicado_no_crea_y_marca_el_campo(): void
    {
        $this->actingAs(Usuario::findOrFail(self::DOCENTE));

        Livewire::test(CrearAuxiliar::class)
            ->set('nombre', 'Ana')->set('apellido', 'Perez')
            ->set('tipoAuxiliar', 'UMSS')->set('codSis', '444444444')
            ->set('correo', 'docente701@umss.edu')
            ->set('carnet', '7100007')
            ->call('revisar')
            ->assertHasErrors('correo')
            ->assertSet('confirmando', false);
    }

    public function test_el_carnet_duplicado_no_crea_y_marca_el_campo(): void
    {
        $this->actingAs(Usuario::findOrFail(self::DOCENTE));
        DB::table('usuario')->where('id_usuario', self::COORDINADOR)->update(['carnet' => '5551234']);

        Livewire::test(CrearAuxiliar::class)
            ->set('nombre', 'Ana')->set('apellido', 'Perez')
            ->set('tipoAuxiliar', 'externo')->set('carnet', '5551234')
            ->set('correo', 'externo2@ejemplo.com')
            ->call('revisar')
            ->assertHasErrors('carnet')
            ->assertSet('confirmando', false);
    }

    public function test_valida_campos_obligatorios_y_formatos(): void
    {
        $this->actingAs(Usuario::findOrFail(self::DOCENTE));

        Livewire::test(CrearAuxiliar::class)
            ->set('nombre', 'A')
            ->set('apellido', '')
            ->set('tipoAuxiliar', 'UMSS')
            ->set('codSis', '123')
            ->set('correo', 'no-es-correo')
            ->set('carnet', '7100008')
            ->call('revisar')
            ->assertHasErrors(['nombre', 'apellido', 'codSis', 'correo']);
    }

    public function test_el_carnet_externo_exige_entre_5_y_10_digitos(): void
    {
        $this->actingAs(Usuario::findOrFail(self::DOCENTE));

        Livewire::test(CrearAuxiliar::class)
            ->set('nombre', 'Ana')->set('apellido', 'Perez')
            ->set('tipoAuxiliar', 'externo')->set('carnet', '123')
            ->set('correo', 'externo3@ejemplo.com')
            ->call('revisar')
            ->assertHasErrors('carnet');
    }

    public function test_si_el_correo_falla_la_cuenta_igual_se_crea(): void
    {
        $this->actingAs(Usuario::findOrFail(self::DOCENTE));

        // Un fallo de envío no debe revertir el alta.
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('smtp caido'));

        $resultado = app(CrearAuxiliarService::class)->crear(
            new \App\Support\DTO\DatosNuevoAuxiliar('Ana', 'Perez', 'UMSS', '999999999', '', 'fallo@ejemplo.com', false),
            Usuario::findOrFail(self::DOCENTE),
        );

        $this->assertFalse($resultado->credencialesEnviadas);
        $this->assertDatabaseHas('usuario', ['correo' => 'fallo@ejemplo.com']);
    }

    /**
     * @return array<string, mixed>
     */
    private function filaUsuario(int $id, string $codSis, string $correo, bool $coordinador, ?int $responsable): array
    {
        return [
            'id_usuario' => $id,
            'cod_sis' => $codSis,
            'correo' => $correo,
            'password' => Hash::make('secreto123'),
            'nombre_usuario' => 'Usuario'.$id,
            'nombre' => 'Usuario'.$id,
            'apellido' => 'Prueba',
            'fecha_creacion' => '2026-09-01 08:00:00',
            'estado_cuenta' => 'habilitada',
            'puede_crear_auxiliares' => $coordinador,
            'tipo_auxiliar' => $coordinador ? 'UMSS' : null,
            'id_docente_responsable' => $responsable,
        ];
    }
}

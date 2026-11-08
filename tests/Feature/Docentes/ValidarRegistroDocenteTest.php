<?php

/**
 * @file    ValidarRegistroDocenteTest.php
 *
 * @author  Valery D. Ortuno P <valerydariana98@gmail.com>
 *
 * @created 2026-11-08
 *
 * @description
 * Pruebas de la validación del registro de nuevos docentes (HU 26): la
 * pantalla "Docentes" (cards, contador "X de 2 verificaciones", aprobaciones,
 * lista vacía), aprobar y desaprobar (con motivo), quién puede validar, y el
 * login de una cuenta pendiente o rechazada.
 *
 * Usan SQLite en memoria con un esquema mínimo y corren la migración de la
 * HU, así que no leen ni escriben en la base de desarrollo.
 *
 * @changelog
 * - 2026-11-08  [Valery D. Ortuno P]  test: creación inicial (HU 26).
 *
 * @see App\Livewire\Docentes\ValidarRegistros
 * @see App\Services\Docente\ValidarRegistroDocenteService
 * @see App\Services\Docente\ListarSolicitudesRegistroService
 */

namespace Tests\Feature\Docentes;

use App\Enums\EstadoCuenta;
use App\Livewire\Docentes\ValidarRegistros;
use App\Models\Usuario;
use App\Services\Docente\ListarSolicitudesRegistroService;
use App\Services\Docente\ValidarRegistroDocenteService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class ValidarRegistroDocenteTest extends TestCase
{
    private const ROBERTO = 1;

    private const PATRICIA = 2;

    private const AUXILIAR = 4;

    private const MARIA = 10;

    private const CARLOS = 11;

    private const CONTRASENA = 'secreto123';

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.connections.registro_pruebas' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
        ]]);
        DB::setDefaultConnection('registro_pruebas');
        // El login compara con btrim(), que SQLite no trae.
        DB::connection()->getPdo()->sqliteCreateFunction('btrim', fn (?string $texto) => $texto === null ? null : trim($texto), 1);
        $this->crearEsquema();
        $this->crearDatos();
        (require database_path('migrations/2026_11_08_000001_validacion_registro_docente.php'))->up();
        ListarSolicitudesRegistroService::olvidarPendientes();
    }

    protected function tearDown(): void
    {
        DB::purge('registro_pruebas');
        parent::tearDown();
    }

    public function test_la_migracion_deja_pendientes_y_docentes_las_cuentas_sin_rol(): void
    {
        $this->assertSame(EstadoCuenta::Pendiente->value, $this->estado(self::MARIA));
        $this->assertSame(EstadoCuenta::Pendiente->value, $this->estado(self::CARLOS));
        $this->assertSame(EstadoCuenta::Habilitada->value, $this->estado(self::ROBERTO));
        $this->assertSame(EstadoCuenta::Habilitada->value, $this->estado(self::AUXILIAR));

        // El registro solo crea docentes: las pendientes ya tienen el rol.
        $this->assertSame(['docente'], Usuario::findOrFail(self::MARIA)->roles()->pluck('nombre_rol')->all());
        $this->assertSame(['auxiliar'], Usuario::findOrFail(self::AUXILIAR)->roles()->pluck('nombre_rol')->all());
    }

    public function test_revertir_y_volver_a_migrar_deja_las_mismas_cuentas_pendientes(): void
    {
        $migracion = require database_path('migrations/2026_11_08_000001_validacion_registro_docente.php');
        $migracion->down();
        $migracion->up();

        $this->assertSame(EstadoCuenta::Pendiente->value, $this->estado(self::MARIA));
        $this->assertSame(EstadoCuenta::Habilitada->value, $this->estado(self::ROBERTO));
        $this->assertSame(1, DB::table('rol_usuario')->where('id_usuario', self::MARIA)->count());
    }

    public function test_muestra_una_card_por_docente_pendiente_con_sus_datos(): void
    {
        $this->comoDocente(self::ROBERTO)
            ->assertSeeInOrder(['Maria Fernanda Lopez', 'Carlos Rojas'])
            ->assertSee('Pendiente')
            ->assertSee('maria.lopez@umss.edu')
            ->assertSee('202301310')
            ->assertSee('01/11/2026 09:14')
            ->assertSee('0 de 2 verificaciones')
            ->assertSee('Aun no hay aprobaciones')
            ->assertSee('2 pendientes');
    }

    public function test_la_pantalla_docentes_muestra_el_contador_real_en_el_sidebar(): void
    {
        $this->actingAs(Usuario::with('roles')->findOrFail(self::ROBERTO))
            ->get(route('docentes'))
            ->assertOk()
            ->assertSee('Solicitudes de registro de docentes')
            ->assertSee('x-data="{ n: 2 }"', false);
    }

    public function test_sin_docentes_pendientes_muestra_el_mensaje(): void
    {
        DB::table('usuario')->whereIn('id_usuario', [self::MARIA, self::CARLOS])->update(['estado_cuenta' => EstadoCuenta::Habilitada->value]);

        $this->comoDocente(self::ROBERTO)
            ->assertSee('No hay docentes pendientes de validacion')
            ->assertDontSee('verificaciones');
    }

    public function test_aprobar_suma_una_verificacion_y_agrega_al_docente_a_la_lista(): void
    {
        $this->comoDocente(self::ROBERTO)
            ->call('aprobar', self::MARIA)
            ->assertSee('Aprobaste el registro de Maria Fernanda Lopez (1 de 2 verificaciones).')
            ->assertSee('1 de 2 verificaciones')
            ->assertSee('Roberto Silva')
            ->assertSee('Ya aprobaste');

        $this->assertSame(EstadoCuenta::Pendiente->value, $this->estado(self::MARIA));
    }

    public function test_la_segunda_aprobacion_habilita_la_cuenta_y_quita_la_card(): void
    {
        app(ValidarRegistroDocenteService::class)->aprobar(self::MARIA, self::ROBERTO);

        $this->comoDocente(self::PATRICIA)
            ->assertSee('1 de 2 verificaciones')
            ->call('aprobar', self::MARIA)
            ->assertSee('Registro exitoso: la cuenta de Maria Fernanda Lopez quedo habilitada.')
            ->assertDontSee('maria.lopez@umss.edu')
            ->assertDispatched('solicitudes-registro', pendientes: 1);

        $this->assertSame(EstadoCuenta::Habilitada->value, $this->estado(self::MARIA));
        $this->assertTrue(Usuario::findOrFail(self::MARIA)->roles()->where('nombre_rol', 'docente')->exists());
    }

    public function test_un_docente_no_aprueba_dos_veces_la_misma_solicitud(): void
    {
        $servicio = app(ValidarRegistroDocenteService::class);
        $servicio->aprobar(self::MARIA, self::ROBERTO);

        $this->expectExceptionObject(new InvalidArgumentException(ValidarRegistroDocenteService::MENSAJE_YA_APROBO));
        $servicio->aprobar(self::MARIA, self::ROBERTO);
    }

    public function test_desaprobar_exige_un_motivo(): void
    {
        $this->comoDocente(self::ROBERTO)
            ->call('desaprobar', self::MARIA, '   ')
            ->assertReturned(ValidarRegistroDocenteService::MENSAJE_MOTIVO_REQUERIDO);

        $this->assertSame(EstadoCuenta::Pendiente->value, $this->estado(self::MARIA));
        $this->assertSame(0, DB::table('validacion_registro')->count());
    }

    public function test_desaprobar_rechaza_la_cuenta_y_no_suma_verificaciones(): void
    {
        $this->comoDocente(self::ROBERTO)
            ->call('desaprobar', self::MARIA, 'No es docente de la facultad')
            ->assertReturned('')
            ->assertSee('Rechazaste el registro de Maria Fernanda Lopez.')
            ->assertDontSee('maria.lopez@umss.edu');

        $this->assertSame(EstadoCuenta::Rechazada->value, $this->estado(self::MARIA));
        $this->assertSame(0, DB::table('validacion_registro')->where('decision', 'aprobado')->count());
        $this->assertSame('No es docente de la facultad', DB::table('validacion_registro')->value('motivo'));
    }

    public function test_una_solicitud_resuelta_no_se_vuelve_a_validar(): void
    {
        $servicio = app(ValidarRegistroDocenteService::class);
        $servicio->desaprobar(self::MARIA, self::ROBERTO, 'Datos falsos');

        $this->expectExceptionObject(new InvalidArgumentException(ValidarRegistroDocenteService::MENSAJE_RESUELTA));
        $servicio->aprobar(self::MARIA, self::PATRICIA);
    }

    public function test_solo_un_docente_habilitado_valida_registros(): void
    {
        try {
            app(ValidarRegistroDocenteService::class)->aprobar(self::MARIA, self::AUXILIAR);
            $this->fail('Un auxiliar no deberia poder validar.');
        } catch (InvalidArgumentException $error) {
            $this->assertSame(ValidarRegistroDocenteService::MENSAJE_SIN_PERMISO, $error->getMessage());
        }

        $this->actingAs(Usuario::findOrFail(self::AUXILIAR))->get(route('docentes'))->assertForbidden();
    }

    public function test_una_cuenta_pendiente_no_puede_ingresar(): void
    {
        $this->postJson(route('api.auth.login'), ['nombreUsuario' => 'mlopez', 'contrasena' => self::CONTRASENA])
            ->assertForbidden()
            ->assertJson(['mensaje' => EstadoCuenta::mensajeAcceso(EstadoCuenta::Pendiente)]);

        $this->assertGuest();
    }

    public function test_una_cuenta_rechazada_no_puede_ingresar(): void
    {
        app(ValidarRegistroDocenteService::class)->desaprobar(self::MARIA, self::ROBERTO, 'Datos falsos');

        $this->postJson(route('api.auth.login'), ['nombreUsuario' => 'mlopez', 'contrasena' => self::CONTRASENA])
            ->assertForbidden()
            ->assertJson(['mensaje' => 'Tu solicitud de registro fue rechazada.']);

        $this->assertGuest();
    }

    public function test_una_cuenta_habilitada_por_dos_docentes_ingresa(): void
    {
        $servicio = app(ValidarRegistroDocenteService::class);
        $servicio->aprobar(self::MARIA, self::ROBERTO);
        $servicio->aprobar(self::MARIA, self::PATRICIA);

        $this->postJson(route('api.auth.login'), ['nombreUsuario' => 'mlopez', 'contrasena' => self::CONTRASENA])
            ->assertOk();

        $this->assertAuthenticatedAs(Usuario::findOrFail(self::MARIA));
    }

    public function test_el_login_por_codigo_sis_tambien_bloquea_la_cuenta_pendiente(): void
    {
        $this->from(route('login'))
            ->post(route('login.store'), ['cod_sis' => '202301310', 'password' => self::CONTRASENA])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['cod_sis' => EstadoCuenta::mensajeAcceso(EstadoCuenta::Pendiente)]);

        $this->assertGuest();
    }

    private function comoDocente(int $idDocente): Testable
    {
        $this->actingAs(Usuario::findOrFail($idDocente));

        return Livewire::test(ValidarRegistros::class);
    }

    private function estado(int $idUsuario): string
    {
        return (string) DB::table('usuario')->where('id_usuario', $idUsuario)->value('estado_cuenta');
    }

    private function crearEsquema(): void
    {
        Schema::create('usuario', function (Blueprint $tabla): void {
            $tabla->integer('id_usuario')->primary();
            $tabla->string('cod_sis');
            $tabla->string('password');
            $tabla->string('nombre_usuario');
            $tabla->string('apellido');
            $tabla->string('correo')->nullable();
            $tabla->string('nombre')->nullable();
            $tabla->timestamp('fecha_creacion')->nullable();
        });
        Schema::create('rol', function (Blueprint $tabla): void {
            $tabla->integer('id_rol')->primary();
            $tabla->string('nombre_rol');
        });
        Schema::create('rol_usuario', function (Blueprint $tabla): void {
            $tabla->integer('id_usuario');
            $tabla->integer('id_rol');
        });
    }

    private function crearDatos(): void
    {
        DB::table('rol')->insert([
            ['id_rol' => 1, 'nombre_rol' => 'docente'],
            ['id_rol' => 2, 'nombre_rol' => 'auxiliar'],
        ]);

        $clave = Hash::make(self::CONTRASENA);
        $usuario = fn (int $id, string $sis, string $usuario, string $nombre, string $apellido, string $fecha) => [
            'id_usuario' => $id, 'cod_sis' => $sis, 'password' => $clave, 'nombre_usuario' => $usuario,
            'nombre' => $nombre, 'apellido' => $apellido, 'correo' => $usuario.'@umss.edu', 'fecha_creacion' => $fecha,
        ];

        DB::table('usuario')->insert([
            $usuario(self::ROBERTO, 'DOC001', 'Roberto', 'Roberto', 'Silva', '2026-09-01 08:00:00'),
            $usuario(self::PATRICIA, 'DOC002', 'Patricia', 'Patricia', 'Rojas', '2026-09-01 08:00:00'),
            $usuario(3, 'DOC003', 'Fernando', 'Fernando', 'Vargas', '2026-09-01 08:00:00'),
            $usuario(self::AUXILIAR, 'AUX001', 'Diego', 'Diego', 'Mamani', '2026-09-01 08:00:00'),
            // Del registro: sin rol hasta que dos docentes las aprueben.
            ['correo' => 'maria.lopez@umss.edu'] + $usuario(self::MARIA, '202301310', 'mlopez', 'Maria Fernanda', 'Lopez', '2026-11-01 09:14:00'),
            $usuario(self::CARLOS, '202301311', 'crojas', 'Carlos', 'Rojas', '2026-11-02 10:02:00'),
        ]);

        DB::table('rol_usuario')->insert([
            ['id_usuario' => self::ROBERTO, 'id_rol' => 1],
            ['id_usuario' => self::PATRICIA, 'id_rol' => 1],
            ['id_usuario' => 3, 'id_rol' => 1],
            ['id_usuario' => self::AUXILIAR, 'id_rol' => 2],
        ]);
    }
}

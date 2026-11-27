<?php

/**
 * @file    LoginUsuarioTest.php
 *
 * @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
 *
 * @created 2026-11-27
 *
 * @description
 * Inicio de sesión por nombre de usuario (docentes y auxiliares), con la
 * alternativa por `cod_sis`, y el rechazo de credenciales inválidas. Cubre la
 * ruta real de la interfaz (`api.auth.login`) y el formulario web (`login.store`).
 *
 * @changelog
 * - 2026-11-27  [Diego Tejerina]  feat: login por nombre de usuario con fallback por cod_sis.
 *
 * @see  App\Services\Auth\AutenticarUsuarioService
 */

namespace Tests\Feature\Auth;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginUsuarioTest extends TestCase
{
    use DatabaseTransactions;

    private const CONTRASENA = 'secreto123';

    protected function setUp(): void
    {
        parent::setUp();

        $clave = Hash::make(self::CONTRASENA);

        DB::table('usuario')->insert([
            $this->fila(901, 'DOC901', 'roberto.silva', 'docente901@umss.edu', $clave),
            $this->fila(902, 'EXT-902', 'aux.diego.mendoza', 'aux902@ejemplo.com', $clave),
        ]);

        DB::table('rol_usuario')->insert([
            ['id_usuario' => 901, 'id_rol' => 1],
            ['id_usuario' => 902, 'id_rol' => 2],
        ]);
    }

    public function test_un_docente_ingresa_con_su_usuario(): void
    {
        $this->postJson(route('api.auth.login'), ['nombreUsuario' => 'roberto.silva', 'contrasena' => self::CONTRASENA])
            ->assertOk();

        $this->assertAuthenticatedAs(Usuario::findOrFail(901));
    }

    public function test_un_auxiliar_ingresa_con_su_usuario(): void
    {
        $this->postJson(route('api.auth.login'), ['nombreUsuario' => 'aux.diego.mendoza', 'contrasena' => self::CONTRASENA])
            ->assertOk();

        $this->assertAuthenticatedAs(Usuario::findOrFail(902));
    }

    public function test_el_usuario_no_distingue_mayusculas_ni_espacios(): void
    {
        $this->postJson(route('api.auth.login'), ['nombreUsuario' => '  Roberto.Silva ', 'contrasena' => self::CONTRASENA])
            ->assertOk();

        $this->assertAuthenticatedAs(Usuario::findOrFail(901));
    }

    public function test_tambien_ingresa_por_cod_sis_como_alternativa(): void
    {
        $this->postJson(route('api.auth.login'), ['nombreUsuario' => 'DOC901', 'contrasena' => self::CONTRASENA])
            ->assertOk();

        $this->assertAuthenticatedAs(Usuario::findOrFail(901));
    }

    public function test_credenciales_incorrectas_no_ingresan(): void
    {
        $this->postJson(route('api.auth.login'), ['nombreUsuario' => 'roberto.silva', 'contrasena' => 'clave-mala'])
            ->assertUnauthorized()
            ->assertJson(['mensaje' => 'Credenciales inválidas']);

        $this->assertGuest();
    }

    public function test_el_formulario_web_ingresa_por_usuario(): void
    {
        $this->from(route('login'))
            ->post(route('login.store'), ['nombreUsuario' => 'roberto.silva', 'password' => self::CONTRASENA])
            ->assertRedirect();

        $this->assertAuthenticatedAs(Usuario::findOrFail(901));
    }

    /**
     * @return array<string, mixed>
     */
    private function fila(int $id, string $codSis, string $nombreUsuario, string $correo, string $clave): array
    {
        return [
            'id_usuario' => $id,
            'cod_sis' => $codSis,
            'correo' => $correo,
            'password' => $clave,
            'nombre_usuario' => $nombreUsuario,
            'nombre' => 'Nombre',
            'apellido' => 'Apellido',
            'fecha_creacion' => '2026-09-01 08:00:00',
            'estado_cuenta' => 'habilitada',
        ];
    }
}

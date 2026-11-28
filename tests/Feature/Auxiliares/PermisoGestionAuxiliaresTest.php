<?php

/**
 * @file    PermisoGestionAuxiliaresTest.php
 *
 * @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
 *
 * @created 2026-11-27
 *
 * @description
 * Acceso a "Crear auxiliar" y "Asignar auxiliar": entran los docentes y los
 * auxiliares con permiso de coordinador; un auxiliar sin permiso recibe 403,
 * también por URL.
 *
 * @changelog
 * - 2026-11-27  [Diego Tejerina]  feat: creación inicial (permiso de coordinador).
 *
 * @see  App\Http\Middleware\EnsurePuedeGestionarAuxiliares
 */

namespace Tests\Feature\Auxiliares;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PermisoGestionAuxiliaresTest extends TestCase
{
    use DatabaseTransactions;

    private const DOCENTE = 801;

    private const COORDINADOR = 802;

    private const AUXILIAR = 803;

    protected function setUp(): void
    {
        parent::setUp();

        $clave = Hash::make('secreto123');

        DB::table('usuario')->insert([
            $this->fila(self::DOCENTE, 'DOC801', 'docente801@umss.edu', $clave, false),
            $this->fila(self::COORDINADOR, 'AUX802', 'coord802@ejemplo.com', $clave, true),
            $this->fila(self::AUXILIAR, 'AUX803', 'aux803@ejemplo.com', $clave, false),
        ]);

        DB::table('rol_usuario')->insert([
            ['id_usuario' => self::DOCENTE, 'id_rol' => 1],
            ['id_usuario' => self::COORDINADOR, 'id_rol' => 2],
            ['id_usuario' => self::AUXILIAR, 'id_rol' => 2],
        ]);
    }

    public function test_un_docente_entra_a_crear_y_asignar(): void
    {
        $this->actingAs(Usuario::findOrFail(self::DOCENTE));

        $this->get(route('auxiliares.crear'))->assertOk();
        $this->get(route('auxiliares.asignar'))->assertOk();
    }

    public function test_un_coordinador_entra_a_crear_y_asignar(): void
    {
        $this->actingAs(Usuario::findOrFail(self::COORDINADOR));

        $this->get(route('auxiliares.crear'))->assertOk();
        $this->get(route('auxiliares.asignar'))->assertOk();
    }

    public function test_un_auxiliar_sin_permiso_recibe_403_en_crear(): void
    {
        $this->actingAs(Usuario::findOrFail(self::AUXILIAR))
            ->get(route('auxiliares.crear'))
            ->assertForbidden();
    }

    public function test_un_auxiliar_sin_permiso_recibe_403_en_asignar(): void
    {
        $this->actingAs(Usuario::findOrFail(self::AUXILIAR))
            ->get(route('auxiliares.asignar'))
            ->assertForbidden();
    }

    /**
     * @return array<string, mixed>
     */
    private function fila(int $id, string $codSis, string $correo, string $clave, bool $coordinador): array
    {
        return [
            'id_usuario' => $id,
            'cod_sis' => $codSis,
            'correo' => $correo,
            'password' => $clave,
            'nombre_usuario' => 'Usuario'.$id,
            'nombre' => 'Usuario'.$id,
            'apellido' => 'Prueba',
            'fecha_creacion' => '2026-09-01 08:00:00',
            'estado_cuenta' => 'habilitada',
            'puede_crear_auxiliares' => $coordinador,
        ];
    }
}

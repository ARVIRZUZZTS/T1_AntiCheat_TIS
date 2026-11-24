<?php

/**
 * @file    NavegacionArchivarRestaurarCursoTest.php
 *
 * @author  OchoaCesar <cesareduardonick@gmail.com>
 *
 * @created 2026-11-22
 *
 * @updated 2026-11-23
 *
 * @description
 * Prueba de la navegación a la subopción «Archivar curso» del
 * grupo Cursos del sidebar, incluido el estado activo de la página única con
 * dos pestañas.
 *
 * @changelog
 * - 2026-11-23  [Valery D. Ortuno P]  test: la subopción pasa a llamarse
 *   «Archivar curso».
 */

declare(strict_types=1);

namespace Tests\Feature\Cursos;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class NavegacionArchivarRestaurarCursoTest extends TestCase
{
    use DatabaseTransactions;

    public function test_sidebar_muestra_archivar_restaurar_como_item_activo(): void
    {
        $rol = DB::table('rol')->where('nombre_rol', 'docente')->value('id_rol');
        $this->assertNotNull($rol);

        DB::table('usuario')->insert([
            'id_usuario' => 997301,
            'cod_sis' => 'DOC997301',
            'password' => 'x',
            'nombre_usuario' => 'Docente',
            'apellido' => 'Sidebar',
        ]);
        DB::table('rol_usuario')->insert(['id_usuario' => 997301, 'id_rol' => $rol]);

        $html = $this->actingAs(Usuario::findOrFail(997301))
            ->get(route('cursos.archivar-restaurar'))
            ->assertOk()
            ->getContent();

        $this->assertIsString($html);
        $this->assertMatchesRegularExpression(
            '/<a[^>]+href="'.preg_quote(route('cursos.archivar-restaurar'), '/').'"[^>]*aria-current="page"\s*>\s*<span[^>]*>Archivar curso<\/span>/',
            $html,
        );
    }
}

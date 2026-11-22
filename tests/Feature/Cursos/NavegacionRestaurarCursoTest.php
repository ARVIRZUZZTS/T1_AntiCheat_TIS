<?php

/**
 * @file    NavegacionRestaurarCursoTest.php
 *
 * @author  Equipo T1
 *
 * @created 2026-11-21
 *
 * @description
 * Prueba de la navegación a «Restaurar curso» desde el sidebar del grupo
 * Cursos, incluido el estado activo de la subsección. Refs #326.
 */

declare(strict_types=1);

namespace Tests\Feature\Cursos;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class NavegacionRestaurarCursoTest extends TestCase
{
    use DatabaseTransactions;

    public function test_sidebar_muestra_restaurar_curso_como_subseccion_activa(): void
    {
        $rol = DB::table('rol')->where('nombre_rol', 'docente')->value('id_rol');
        $this->assertNotNull($rol);

        DB::table('usuario')->insert([
            'id_usuario' => 998201,
            'cod_sis' => 'DOC998201',
            'password' => 'x',
            'nombre_usuario' => 'Docente',
            'apellido' => 'Sidebar',
        ]);
        DB::table('rol_usuario')->insert(['id_usuario' => 998201, 'id_rol' => $rol]);

        $html = $this->actingAs(Usuario::findOrFail(998201))
            ->get(route('cursos.restaurar'))
            ->assertOk()
            ->getContent();

        $this->assertIsString($html);
        $this->assertMatchesRegularExpression(
            '/<a[^>]+href="'.preg_quote(route('cursos.restaurar'), '/').'"[^>]*aria-current="page"\s*>\s*<span[^>]*>Restaurar curso<\/span>/',
            $html,
        );
    }
}

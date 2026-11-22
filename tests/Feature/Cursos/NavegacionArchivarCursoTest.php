<?php

/**
 * @file    NavegacionArchivarCursoTest.php
 *
 * @author  Equipo T1
 *
 * @created 2026-11-21
 *
 * @description
 * Pruebas de la navegación a «Archivar curso» desde el sidebar del grupo
 * Cursos, incluido el estado activo de la subsección.
 */

declare(strict_types=1);

namespace Tests\Feature\Cursos;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class NavegacionArchivarCursoTest extends TestCase
{
    use DatabaseTransactions;

    public function test_sidebar_muestra_archivar_curso_como_subseccion_activa(): void
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
            ->get(route('cursos.archivar'))
            ->assertOk()
            ->getContent();

        $this->assertIsString($html);
        $this->assertMatchesRegularExpression(
            '/<a[^>]+href="'.preg_quote(route('cursos.archivar'), '/').'"[^>]*aria-current="page"\s*>\s*<span[^>]*>Archivar curso<\/span>/',
            $html,
        );
    }
}

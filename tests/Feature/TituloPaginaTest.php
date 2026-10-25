<?php

/**
 * @file    TituloPaginaTest.php
 * @author  Valery D. Ortuno P. <valerydariana98@gmail.com>
 * @created 2026-09-25
 * @updated 2026-09-25
 * @updated 2026-10-25
 *
 * @description
 * El layout base recibe el título de dos maneras: como sección en las vistas
 * Blade y como dato en los componentes Livewire de página completa. Estas
 * pruebas blindan las dos formas.
 *
 * @changelog
 * - 2026-09-25  [Valery D. Ortuno P]  test: creación inicial de las pruebas.
 * - 2026-10-25  [Valery D. Ortuno P]  test: el componente Livewire de prueba pasa
 *   a ser la página de estudiantes del curso; /registrar-incidencia se retiró.
 */

namespace Tests\Feature;

use Tests\TestCase;

class TituloPaginaTest extends TestCase
{
    /**
     * Verifica que una vista Blade mantenga su título por sección.
     */
    public function test_una_vista_blade_muestra_su_titulo_por_seccion(): void
    {
        $this->actingAs(\App\Models\Usuario::query()->firstOrFail());
        $contenido = $this->get('/monitoreo')->assertOk()->getContent();

        $this->assertStringContainsString('<title>Monitor en vivo</title>', $contenido);
        $this->assertStringContainsString('>Monitor en vivo</h1>', $contenido);
    }

    /**
     * Verifica que un componente Livewire muestre el título que entrega.
     */
    public function test_un_componente_livewire_muestra_el_titulo_entregado(): void
    {
        // Página suelta de estudiantes del curso: es un componente Livewire de
        // página completa y solo la abre el docente dueño del curso.
        $curso = \App\Models\Curso::query()->orderBy('id_curso')->firstOrFail();
        $this->actingAs(\App\Models\Usuario::query()->findOrFail($curso->sis_doc));
        $contenido = $this->get(route('cursos.estudiantes.estado', $curso->id_curso))->assertOk()->getContent();

        $this->assertStringContainsString('<title>Estudiantes del curso — Anticheat TIS</title>', $contenido);
        $this->assertStringContainsString('>Estudiantes del curso</h1>', $contenido);
    }
}

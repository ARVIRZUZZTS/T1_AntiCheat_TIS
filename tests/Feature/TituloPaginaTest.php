<?php

/**
 * @file    TituloPaginaTest.php
 * @author  Valery D. Ortuno P. <valerydariana98@gmail.com>
 * @created 2026-09-25
 * @updated 2026-09-25
 *
 * @description
 * El layout base recibe el título de dos maneras: como sección en las vistas
 * Blade y como dato en los componentes Livewire de página completa. Estas
 * pruebas blindan las dos formas.
 *
 * @changelog
 * - 2026-09-25  [Valery D. Ortuno P]  test: creación inicial de las pruebas.
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
        $contenido = $this->get('/monitoreo')->assertOk()->getContent();

        $this->assertStringContainsString('<title>Monitor en vivo</title>', $contenido);
        $this->assertStringContainsString('>Monitor en vivo</h1>', $contenido);
    }

    /**
     * Verifica que un componente Livewire muestre el título que entrega.
     */
    public function test_un_componente_livewire_muestra_el_titulo_entregado(): void
    {
        $contenido = $this->get('/registrar-incidencia')->assertOk()->getContent();

        $this->assertStringContainsString('<title>Registrar incidencia</title>', $contenido);
        $this->assertStringContainsString('>Registrar incidencia</h1>', $contenido);
    }
}

<?php

/**
 * @file    RutasPrincipalesTest.php
 *
 * @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
 *
 * @created 2026-09-28
 *
 * @updated 2026-09-28
 *
 * @description
 * Prueba de humo (smoke test) de las rutas principales de navegación. Se
 * agregó tras un bug real: un merge (`76cc77a`) borró en silencio la ruta
 * `materias.detalle` sin dejar marcadores de conflicto ni romper la
 * compilación — nadie lo notó hasta probarlo a mano. Este test existe para
 * que una pérdida de ruta como esa falle en la próxima corrida de tests,
 * no en producción.
 *
 * @see  routes/web.php
 *
 * @changelog
 * - 2026-09-28  [T1]  test: creación inicial (fix de la ruta materias.detalle).
 */

namespace Tests\Feature;

use Tests\TestCase;

class RutasPrincipalesTest extends TestCase
{
    public function test_la_lista_de_materias_carga_sin_error(): void
    {
        $this->get(route('materias'))->assertOk();
    }

    /**
     * Regresión puntual: route('materias.detalle', ...) debe existir y
     * resolver, porque pages/materias.blade.php genera un link con ella
     * para cada materia listada.
     */
    public function test_el_detalle_de_una_materia_carga_sin_error(): void
    {
        $this->get(route('materias.detalle', 'MAT-101'))->assertOk();
    }

    public function test_el_link_de_la_lista_apunta_al_detalle_correcto(): void
    {
        $this->get(route('materias'))
            ->assertSee(route('materias.detalle', 'MAT-101'), false);
    }
}

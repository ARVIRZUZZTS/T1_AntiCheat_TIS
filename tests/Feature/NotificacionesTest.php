<?php

/**
 * @file    NotificacionesTest.php
 *
 * @created 2026-10-10
 *
 * @description
 * Pruebas de humo de la pantalla de notificaciones (HU 12 · T3/T4): la ruta
 * carga, lista tarjetas de "Posible Tramposo" con su acción "Ver detalle" y el
 * destino de esa acción resuelve. Los datos son mockeados, por eso no toca la
 * base de datos.
 *
 * @changelog
 * - 2026-10-10  test: creación inicial.
 */

namespace Tests\Feature;

use Tests\TestCase;

class NotificacionesTest extends TestCase
{
    public function test_la_pantalla_de_notificaciones_carga_sin_error(): void
    {
        $this->get(route('notificaciones'))
            ->assertOk()
            ->assertSee('Notificaciones')
            ->assertSee('Posible Tramposo')
            ->assertSee('Ver detalle')
            ->assertSee('Ivan Soto Peredo');
    }

    public function test_el_detalle_del_registro_resuelve(): void
    {
        $this->get(route('incidencias.detalle', 1))->assertOk();
    }
}

<?php

/**
 * @file    NotificacionesTest.php
 *
 * @created 2026-10-10
 *
 * @description
 * Pruebas de humo de la pantalla de notificaciones (HU 12 · T1/T3/T4): la ruta
 * carga, incluye la plantilla de tarjeta de "Posible Tramposo" con su acción
 * "Ver detalle", el estado vacío y el destino del detalle resuelve. La pantalla
 * consume el endpoint real, así que el contenido se pinta por JavaScript y aquí
 * solo se comprueba el esqueleto servido.
 *
 * @changelog
 * - 2026-10-10  test: creación inicial.
 * - 2026-10-11  [T1]  test: adaptar a la pantalla conectada al endpoint real
 *   (ya no usa datos mockeados).
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
            ->assertSee('No tienes notificaciones');
    }

    public function test_el_detalle_del_registro_resuelve(): void
    {
        $this->get(route('incidencias.detalle', 1))->assertOk();
    }
}

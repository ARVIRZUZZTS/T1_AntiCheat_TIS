<?php

/**
 * @file    NotificacionesTest.php
 *
 * @created 2026-10-10
 *
 * @description
 * Pruebas de humo de la pantalla de notificaciones (HU 12 · T1/T3/T4 y
 * HU 6 · T3): la ruta carga, incluye las plantillas de tarjeta de "Posible
 * Tramposo" (con su acción "Ver detalle") y de "Estudiante ingresado a central
 * de riesgo", el estado vacío y el destino del detalle resuelve. La pantalla
 * consume el endpoint real, así que el contenido se pinta por JavaScript y aquí
 * se comprueba el esqueleto servido y el render de cada tipo de tarjeta.
 *
 * @changelog
 * - 2026-10-10  test: creación inicial.
 * - 2026-10-11  [T1]  test: adaptar a la pantalla conectada al endpoint real
 *   (ya no usa datos mockeados).
 * - 2026-10-11  [T1]  test: cubrir el segundo tipo de tarjeta "Estudiante
 *   ingresado a central de riesgo" (título, texto, tiempo y sin acción).
 * - 2026-10-11  [David E. Chavez T.]  test: HU 6 · T4 — la pantalla apunta al
 *   endpoint real, declara la plantilla de cada `tipo` de aviso y la tarjeta
 *   central acepta la descripción que manda el backend.
 */

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
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
            ->assertSee('Estudiante ingresado a central de riesgo')
            ->assertSee('No tienes notificaciones');
    }

    public function test_el_detalle_del_registro_resuelve(): void
    {
        $this->get(route('incidencias.detalle', 1))->assertOk();
    }

    public function test_la_pantalla_apunta_al_endpoint_real_y_declara_las_dos_plantillas(): void
    {
        // El script de la página elige la plantilla según el `tipo` del aviso,
        // así que deben existir las dos y el contenedor debe llevar la ruta
        // del endpoint real como destino del fetch.
        $respuesta = $this->get(route('notificaciones'))->assertOk();

        $respuesta->assertSee('data-endpoint="'.route('api.notificaciones.index').'"', false);
        $respuesta->assertSee('id="notificacion-template-tramposo"', false);
        $respuesta->assertSee('id="notificacion-template-central"', false);
    }

    public function test_la_tarjeta_de_central_de_riesgo_muestra_titulo_texto_y_tiempo_sin_accion(): void
    {
        $html = Blade::render(
            '<x-ui.notificacion-card tipo="central_riesgo" estudiante="Ivan Soto Peredo" materia="Algebra 1" hace="Hace 10 minutos" />'
        );

        $this->assertStringContainsString('Estudiante ingresado a central de riesgo', $html);
        $this->assertStringContainsString('El estudiante Ivan Soto Peredo fue ingresado a la central de riesgo en la materia de Algebra 1', $html);
        $this->assertStringContainsString('Hace 10 minutos', $html);
        $this->assertStringContainsString('js-cerrar-notificacion', $html);
        $this->assertStringNotContainsString('Ver detalle', $html);
    }

    public function test_la_tarjeta_central_acepta_la_descripcion_que_manda_el_backend(): void
    {
        // El endpoint ya manda la descripción armada (con punto final): la
        // tarjeta debe pintarla tal cual y no el texto de respaldo.
        $html = Blade::render(
            '<x-ui.notificacion-card tipo="central_riesgo" estudiante="Juan Quispe" materia="Matemática I" hace="Hace 3 minutos" descripcion="El estudiante Juan Quispe fue ingresado a la central de riesgo en la materia de Matemática I." />'
        );

        $this->assertStringContainsString('El estudiante Juan Quispe fue ingresado a la central de riesgo en la materia de Matemática I.', $html);
        $this->assertStringNotContainsString('Ver detalle', $html);
    }

    public function test_la_tarjeta_de_posible_tramposo_conserva_su_accion_y_estilo(): void
    {
        $html = Blade::render(
            '<x-ui.notificacion-card tipo="posible_tramposo" auxiliar="Aux Uno" estudiante="Juan Quispe" examen="PP" materia="Matemática I" hace="Hace 20 minutos" />'
        );

        $this->assertStringContainsString('Posible Tramposo', $html);
        $this->assertStringContainsString('Ver detalle', $html);
        $this->assertStringContainsString('El auxiliar Aux Uno registró al estudiante Juan Quispe', $html);
        $this->assertStringContainsString('Hace 20 minutos', $html);
    }
}

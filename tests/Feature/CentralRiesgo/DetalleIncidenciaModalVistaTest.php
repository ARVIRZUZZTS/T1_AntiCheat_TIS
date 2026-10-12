<?php

/**
 * @file    DetalleIncidenciaModalVistaTest.php
 *
 * @author  OchoaCesar <cesareduardonick@gmail.com>
 *
 * @created 2026-10-11
 *
 * @updated 2026-10-11
 *
 * @description Pruebas de fuente del modal de detalle de incidencia de la
 * central de riesgos (`x-ui.modal-detalle-incidencia`): que la tarjeta de
 * notificación lo abra con el evento window, que el detalle consulte el
 * endpoint con sus estados, que los submodales de confirmar y rechazar cumplan
 * textos, botones y mensajes de toast del criterio, que cancelar cierre solo
 * el submodal y que el layout exponga el token CSRF para los POST.
 *
 * @changelog
 * - 2026-10-11  [OchoaCesar]  test: creación inicial (fuente, sin Supabase).
 *
 * @see App\Http\Controllers\Api\DetalleIncidenciaController
 * @see App\Http\Controllers\Api\ResolverIncidenciaController
 * @see resources\views\components\ui\modal-detalle-incidencia.blade.php
 * @see resources\views\components\ui\notificacion-card.blade.php
 * @see resources\views\pages\notificaciones.blade.php
 */

namespace Tests\Feature\CentralRiesgo;

use Tests\TestCase;

class DetalleIncidenciaModalVistaTest extends TestCase
{
    /** @throws \Throwable Si alguno de los archivos no se puede leer. */
    private function fuente(string $ruta): string
    {
        $contenido = file_get_contents(resource_path($ruta));

        $this->assertIsString($contenido);

        return $contenido;
    }

    /** @throws \Throwable Si el modal no expone las tres rutas de la API. */
    public function test_las_rutas_de_la_api_estan_registradas(): void
    {
        $this->assertSame('/api/incidencias/__ID__', route('api.incidencias.detalle', ['idIncidencia' => '__ID__'], false));
        $this->assertSame('/api/incidencias/__ID__/confirmar', route('api.incidencias.confirmar', ['idIncidencia' => '__ID__'], false));
        $this->assertSame('/api/incidencias/__ID__/rechazar', route('api.incidencias.rechazar', ['idIncidencia' => '__ID__'], false));
    }

    /** @throws \Throwable Si la tarjeta no abre el modal o la página no lo incluye. */
    public function test_la_tarjeta_abre_el_modal_del_detalle(): void
    {
        $tarjeta = $this->fuente('views/components/ui/notificacion-card.blade.php');
        $modal = $this->fuente('views/components/ui/modal-detalle-incidencia.blade.php');
        $pagina = $this->fuente('views/pages/notificaciones.blade.php');

        $this->assertStringContainsString("new CustomEvent('abrir-detalle-incidencia'", $tarjeta);
        $this->assertStringContainsString('data-incidencia="{{ $id }}"', $tarjeta);
        $this->assertStringContainsString('parseInt(this.dataset.incidencia, 10)', $tarjeta);
        $this->assertStringNotContainsString('@click.prevent="$dispatch', $tarjeta);
        $this->assertStringContainsString('@abrir-detalle-incidencia.window="abrir($event)"', $modal);
        $this->assertStringContainsString('<x-ui.modal-detalle-incidencia', $pagina);
    }

    /** @throws \Throwable Si el detalle no consulta el endpoint con sus estados. */
    public function test_el_detalle_consulta_el_endpoint_con_estados(): void
    {
        $modal = $this->fuente('views/components/ui/modal-detalle-incidencia.blade.php');

        $this->assertStringContainsString("route('api.incidencias.detalle'", $modal);
        $this->assertStringContainsString("estado === 'cargando'", $modal);
        $this->assertStringContainsString("estado === 'error'", $modal);
        $this->assertStringContainsString("estado === 'listo'", $modal);
        $this->assertStringContainsString('cuerpo.datos', $modal);
        $this->assertStringContainsString("detalle.estado !== 'Confirmado'", $modal);
    }

    /** @throws \Throwable Si el submodal de confirmación no cumple el criterio. */
    public function test_el_submodal_de_confirmacion_cumple_los_criterios(): void
    {
        $modal = $this->fuente('views/components/ui/modal-detalle-incidencia.blade.php');

        $this->assertStringContainsString(
            '¿Está seguro que quiere ingresar este estudiante a la central de riesgos?',
            $modal
        );
        $this->assertStringContainsString('Confirmar Incidencia', $modal);
        $this->assertStringContainsString('Cancelar', $modal);
        $this->assertStringContainsString("route('api.incidencias.confirmar'", $modal);
        $this->assertStringContainsString('Se ha añadido estudiante a la central de riesgos', $modal);
    }

    /** @throws \Throwable Si el submodal de rechazo no cumple el criterio. */
    public function test_el_submodal_de_rechazo_cumple_los_criterios(): void
    {
        $modal = $this->fuente('views/components/ui/modal-detalle-incidencia.blade.php');

        $this->assertStringContainsString('¿Está seguro que quiere rechazar esta incidencia?', $modal);
        $this->assertStringContainsString('Rechazar', $modal);
        $this->assertStringContainsString('Cancelar', $modal);
        $this->assertStringContainsString("route('api.incidencias.rechazar'", $modal);
        $this->assertStringContainsString('Se ha rechazado la incidencia', $modal);
    }

    /** @throws \Throwable Si cancelar o Escape cerraran el detalle completo. */
    public function test_cancelar_cerrar_solo_el_submodal(): void
    {
        $modal = $this->fuente('views/components/ui/modal-detalle-incidencia.blade.php');

        $this->assertGreaterThanOrEqual(2, substr_count($modal, '@click.self="sub = null"'));
        $this->assertGreaterThanOrEqual(2, substr_count($modal, '@keydown.escape.window="sub = null"'));
        $this->assertStringContainsString('@click.self="if (!sub) show = false"', $modal);
        $this->assertStringContainsString('@keydown.escape.window="if (!sub) show = false"', $modal);
        $this->assertStringNotContainsString('@click.self="show = false"', $modal);
    }

    /** @throws \Throwable Si el POST no enviara el token CSRF. */
    public function test_las_peticiones_post_envian_el_token_csrf(): void
    {
        $modal = $this->fuente('views/components/ui/modal-detalle-incidencia.blade.php');

        $this->assertStringContainsString("method: 'POST'", $modal);
        $this->assertStringContainsString("'X-CSRF-TOKEN'", $modal);
        $this->assertStringContainsString('meta[name=csrf-token]', $modal);
    }

    /** @throws \Throwable Si el layout no expone la meta con la que se lee el token. */
    public function test_el_layout_expone_el_token_csrf(): void
    {
        $layout = $this->fuente('views/layouts/app.blade.php');

        $this->assertStringContainsString('<meta name="csrf-token"', $layout);
        $this->assertStringContainsString('csrf_token()', $layout);
    }
}

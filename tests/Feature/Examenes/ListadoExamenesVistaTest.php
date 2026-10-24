<?php

/**
 * @file    ListadoExamenesVistaTest.php
 *
 * @author  OchoaCesar <cesareduardonick@gmail.com>
 *
 * @created 2026-10-09
 *
 * @updated 2026-10-10
 * @updated 2026-10-24
 *
 * @description
 * Pruebas de la vista del listado de exámenes de un curso: verifican sobre
 * la fuente de las vistas y de las rutas que el parcial consulta con fetch el
 * endpoint /api/cursos/{idCurso}/examenes, que la página de exámenes del
 * curso lo incrusta, que la tarjeta sigue un diseño horizontal (ícono, badge
 * invertido, grid con labels en mayúsculas y valores mono, fecha en
 * dd/mm/yyyy) con los campos del criterio (tipo, estado, fecha, horario,
 * ambientes e ingresados) y que las rutas no resuelven los exámenes
 * server-side.
 *
 * Son aserciones de fuente, al estilo de BuscadorEstudiantesMateriaTest:
 * el comportamiento de la consulta en sí lo cubre ListarExamenesCursoTest.
 *
 * @see  resources/views/partials/examenes-curso.blade.php
 * @see  resources/views/pages/examenes-curso.blade.php
 * @see  routes/web.php
 * @see  App\Http\Controllers\Api\CursoExamenController
 *
 * @changelog
 * - 2026-10-09  [OchoaCesar]  test: creación inicial.
 * - 2026-10-10  [OchoaCesar]  test: cubrir el diseño de las tarjetas (labels con
 *   dos puntos, badges invertidos, fechaDe, mono) y que sigue sin haber
 *   "Ver Detalles" ni inscritos en la vista.
 * - 2026-10-24  [Valery D. Ortuno P]  test: el parcial pasa a
 *   examenes-curso.blade.php y lo incrusta la página de exámenes del curso
 *   (ya no una pestaña x-if del detalle). El header ya no tiene "Crear
 *   examen" sino Invitar docente / Dividir examen, y los textos van sin
 *   acentos.
 */

namespace Tests\Feature\Examenes;

use Tests\TestCase;

class ListadoExamenesVistaTest extends TestCase
{
    /**
     * Devuelve el fuente de una vista o archivo del proyecto.
     */
    private function fuente(string $ruta): string
    {
        $contenido = file_get_contents($ruta);

        $this->assertIsString($contenido);

        return $contenido;
    }

    private function fuenteParcial(): string
    {
        return $this->fuente(resource_path('views/partials/examenes-curso.blade.php'));
    }

    public function test_el_parcial_consulta_el_endpoint_de_examenes(): void
    {
        $parcial = $this->fuenteParcial();

        $this->assertStringContainsString('fetch(', $parcial);
        $this->assertStringContainsString('api.cursos.examenes.listar', $parcial);
    }

    public function test_el_parcial_renderiza_la_lista_con_alpine_y_no_con_blade(): void
    {
        $parcial = $this->fuenteParcial();

        $this->assertStringContainsString('<template x-for="examen in examenes"', $parcial);
        $this->assertStringNotContainsString('@forelse', $parcial);
        $this->assertStringNotContainsString('$examenes', $parcial);
    }

    public function test_el_parcial_muestra_los_campos_del_criterio(): void
    {
        $parcial = $this->fuenteParcial();

        // Estado debajo del tipo, con las dos etiquetas del contrato del endpoint.
        $this->assertStringContainsString("examen.estado === 'Programado'", $parcial);
        $this->assertStringContainsString("examen.estado === 'Finalizado'", $parcial);

        // Ítem: fecha, horario, ambientes e ingresados, con dos puntos.
        $this->assertStringContainsString('>Fecha:<', $parcial);
        $this->assertStringContainsString('>Horario:<', $parcial);
        $this->assertStringContainsString('>Ambientes:<', $parcial);
        $this->assertStringContainsString('>Ingresos:<', $parcial);
        $this->assertStringContainsString('examen.ingresados', $parcial);
        $this->assertStringContainsString('ambientesDe(examen)', $parcial);

        // Solo ingresados: sin el total de inscritos.
        $this->assertStringNotContainsString('examen.inscritos', $parcial);

        // Campos que el criterio pidió quitar.
        $this->assertStringNotContainsString('>Duración<', $parcial);
        $this->assertStringNotContainsString('>Inscritos<', $parcial);

        // La vista no indica cuántos exámenes se consultaron.
        $this->assertStringNotContainsString('x-text="examenes.length', $parcial);
    }

    public function test_las_tarjetas_tienen_el_diseno_horizontal(): void
    {
        $parcial = $this->fuenteParcial();

        // Header: título grande y acciones sobre los exámenes. El alta es una
        // página propia, así que el parcial ya no tiene "Crear examen".
        $this->assertStringContainsString('text-2xl font-bold text-heading', $parcial);
        $this->assertStringNotContainsString('Ver examenes', $parcial);
        $this->assertStringContainsString('Invitar docente', $parcial);
        $this->assertStringContainsString('Dividir examen', $parcial);
        $this->assertStringNotContainsString('Crear examen', $parcial);

        // Tarjeta horizontal con ícono y badges invertidos respecto al diseño anterior.
        $this->assertStringContainsString('lg:w-72', $parcial);
        $this->assertStringContainsString('type="gray" class="mt-1" x-show="examen.estado === \'Programado\'"', $parcial);
        $this->assertStringContainsString('type="brand" class="mt-1 gap-1" x-show="examen.estado === \'Finalizado\'"', $parcial);

        // Grid de datos con valores mono y de ingresos en negrita.
        $this->assertStringContainsString('grid flex-1 grid-cols-2 gap-4 sm:grid-cols-4', $parcial);
        $this->assertStringContainsString('font-mono text-body', $parcial);
        $this->assertStringContainsString('font-bold text-heading', $parcial);
        $this->assertStringContainsString('fechaDe(examen)', $parcial);

        // Sin botón de acción: no existe la pantalla de detalle del examen.
        $this->assertStringNotContainsString('>Ver Detalles<', $parcial);
    }

    public function test_el_parcial_tiene_los_estados_de_carga_error_y_vacio(): void
    {
        $parcial = $this->fuenteParcial();

        $this->assertStringContainsString("estado === 'cargando'", $parcial);
        $this->assertStringContainsString("estado === 'error'", $parcial);
        $this->assertStringContainsString("estado === 'listo'", $parcial);
        $this->assertStringContainsString('Reintentar', $parcial);
        $this->assertStringContainsString('no tiene examenes registrados', $parcial);
    }

    public function test_la_pagina_de_examenes_del_curso_monta_el_parcial(): void
    {
        $pagina = $this->fuente(resource_path('views/pages/examenes-curso.blade.php'));

        $this->assertStringContainsString(
            "@include('partials.examenes-curso', ['curso' => \$curso])",
            $pagina
        );
        $this->assertStringNotContainsString("'examenes' => \$examenes", $pagina);
    }

    public function test_el_detalle_del_curso_ya_no_tiene_pestana_examenes(): void
    {
        $pagina = $this->fuente(resource_path('views/pages/curso-detalle.blade.php'));

        $this->assertStringNotContainsString("tab === 'examenes'", $pagina);
        $this->assertStringNotContainsString('partials.examenes-curso', $pagina);
    }

    public function test_la_ruta_deja_de_resolver_los_examenes_server_side(): void
    {
        $rutas = $this->fuente(base_path('routes/web.php'));

        $this->assertStringNotContainsString('ListarExamenesCursoService', $rutas);
        $this->assertStringNotContainsString("'examenes' =>", $rutas);
    }
}

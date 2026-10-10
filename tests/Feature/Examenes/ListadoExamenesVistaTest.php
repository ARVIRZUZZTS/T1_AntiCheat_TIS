<?php

/**
 * @file    ListadoExamenesVistaTest.php
 *
 * @author  T1 - Tech One SRL
 *
 * @created 2026-10-09
 *
 * @updated 2026-10-09
 *
 * @description
 * Pruebas de la vista del listado de exámenes de la pestaña Exámenes:
 * verifican sobre la fuente de las vistas y de las rutas que el parcial
 * consulta con fetch el endpoint /api/cursos/{idCurso}/examenes, que la
 * pestaña vive en un template x-if, que el ítem muestra los campos del
 * criterio (tipo, estado, fecha, horario, ambientes e ingresados) y que la
 * ruta materias.detalle ya no resuelve los exámenes server-side.
 *
 * Son aserciones de fuente, al estilo de BuscadorEstudiantesMateriaTest:
 * el comportamiento de la consulta en sí lo cubre ListarExamenesCursoTest.
 *
 * @see  resources/views/partials/materia-examenes.blade.php
 * @see  resources/views/pages/materia-estudiantes.blade.php
 * @see  routes/web.php
 * @see  App\Http\Controllers\Api\CursoExamenController
 *
 * @changelog
 * - 2026-10-09  [T1]  test: creación inicial.
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
        return $this->fuente(resource_path('views/partials/materia-examenes.blade.php'));
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

        // Ítem: fecha, horario, ambientes e ingresados.
        $this->assertStringContainsString('>Fecha<', $parcial);
        $this->assertStringContainsString('>Horario<', $parcial);
        $this->assertStringContainsString('>Ambientes<', $parcial);
        $this->assertStringContainsString('>Ingresos<', $parcial);
        $this->assertStringContainsString('examen.ingresados', $parcial);
        $this->assertStringContainsString('ambientesDe(examen)', $parcial);

        // Campos que el criterio pidió quitar.
        $this->assertStringNotContainsString('>Duración<', $parcial);
        $this->assertStringNotContainsString('>Inscritos<', $parcial);

        // La vista no indica cuántos exámenes se consultaron.
        $this->assertStringNotContainsString('x-text="examenes.length', $parcial);
    }

    public function test_el_parcial_tiene_los_estados_de_carga_error_y_vacio(): void
    {
        $parcial = $this->fuenteParcial();

        $this->assertStringContainsString("estado === 'cargando'", $parcial);
        $this->assertStringContainsString("estado === 'error'", $parcial);
        $this->assertStringContainsString("estado === 'listo'", $parcial);
        $this->assertStringContainsString('Reintentar', $parcial);
        $this->assertStringContainsString('todavía no tiene exámenes', $parcial);
    }

    public function test_la_pestana_es_un_template_x_if_que_monta_el_parcial(): void
    {
        $pagina = $this->fuente(resource_path('views/pages/materia-estudiantes.blade.php'));

        $this->assertStringContainsString("<template x-if=\"tab === 'examenes'\">", $pagina);
        $this->assertStringNotContainsString("x-show=\"tab === 'examenes'\"", $pagina);
        $this->assertStringContainsString(
            "@include('partials.materia-examenes', ['curso' => \$curso])",
            $pagina
        );
        $this->assertStringNotContainsString("'examenes' => \$examenes", $pagina);
    }

    public function test_la_ruta_deja_de_resolver_los_examenes_server_side(): void
    {
        $rutas = $this->fuente(base_path('routes/web.php'));

        $this->assertStringNotContainsString('ListarExamenesCursoService', $rutas);
        $this->assertStringNotContainsString("'examenes' =>", $rutas);
    }
}

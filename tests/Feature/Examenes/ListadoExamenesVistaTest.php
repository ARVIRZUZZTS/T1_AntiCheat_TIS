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
 * @updated 2026-10-25
 * @updated 2026-11-04
 * @updated 2026-11-05
 * @updated 2026-11-14
 *
 * @description
 * Pruebas de la vista del listado de exámenes: verifican sobre la fuente de las
 * vistas y de las rutas que el parcial consulta con fetch el endpoint global
 * /api/examenes o el endpoint /api/cursos/{idCurso}/examenes, que la página
 * única de Exámenes lo incrusta, que la tarjeta sigue un diseño horizontal
 * (ícono, badge invertido, grid con labels en mayúsculas y valores mono, fecha
 * en dd/mm/yyyy) con los campos del criterio (tipo, estado, fecha, horario,
 * ambientes e ingresados), que las acciones viven en cuatro pestañas y que las
 * rutas no resuelven los exámenes server-side.
 *
 * Son aserciones de fuente, al estilo de BuscadorEstudiantesMateriaTest:
 * el comportamiento de la consulta en sí lo cubre ListarExamenesCursoTest y
 * ListarExamenesDocenteTest.
 *
 * @see  resources/views/partials/examenes-curso.blade.php
 * @see  resources/views/pages/examenes.blade.php
 * @see  resources/views/pages/examenes-curso.blade.php
 * @see  routes/web.php
 * @see  App\Http\Controllers\Api\CursoExamenController
 * @see  App\Http\Controllers\Api\ExamenDocenteController
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
 * - 2026-10-25  [Alex Candia]  test: las acciones pasan a cuatro pestañas y la
 *   página pasa catálogos y permiso de alta al parcial.
 * - 2026-10-25  [Alex Candia]  test: la página única de Exámenes monta el
 *   parcial y el selector de materia oculta las pestañas sin selección.
 * - 2026-11-04  [Alex Candia]  test: los selectores de Invitar y Dividir solo
 *   listan exámenes con fecha y hora futuras, sin filtrar el listado completo.
 * - 2026-11-05  [Alex Candia]  test: Editar Examen solo ofrece exámenes propios,
 *   consulta su detalle y precarga el formulario sin enviar cambios.
 * - 2026-11-14  [Alex Candia]  test: la página única ya no tiene selector de
 *   materia, usa el endpoint global, muestra el curso en las etiquetas y el
 *   formulario de alta permite elegir el curso.
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

        $this->assertStringContainsString('<template x-for="examen in examenes" :key="examen.id">', $parcial);
        $this->assertStringContainsString('<template x-for="examen in examenesDisponibles"', $parcial);
        $this->assertStringNotContainsString('@forelse', $parcial);
        $this->assertStringNotContainsString('$examenes', $parcial);
    }

    public function test_el_parcial_muestra_los_campos_del_criterio(): void
    {
        $parcial = $this->fuenteParcial();

        // Estado debajo del tipo, con las dos etiquetas del contrato del endpoint.
        $this->assertStringContainsString("examen.estado === 'Programado'", $parcial);
        $this->assertStringContainsString("examen.estado === 'En curso'", $parcial);
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

        // Las cuatro pestañas de gestión viven en la misma página.
        $this->assertStringContainsString('aria-label="Opciones de examenes"', $parcial);
        $this->assertStringNotContainsString('Ver examenes', $parcial);
        $this->assertStringContainsString('Ver Lista de Examenes', $parcial);
        $this->assertStringContainsString('Crear Examen', $parcial);
        $this->assertStringContainsString('Editar Examen', $parcial);
        $this->assertStringContainsString('Invitar Docente a Examen', $parcial);
        $this->assertStringContainsString('Dividir Examen', $parcial);

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
        $this->assertStringContainsString('No hay examenes registrados.', $parcial);
    }

    public function test_los_selectores_solo_listan_examenes_con_fecha_y_hora_futuras(): void
    {
        $parcial = $this->fuenteParcial();

        $this->assertStringContainsString("ahora: @js(now()->format('Y-m-d H:i'))", $parcial);
        $this->assertStringContainsString('get examenesDisponibles()', $parcial);
        $this->assertStringContainsString("const inicio = examen.fecha + ' ' + (examen.hora_inicio || '00:00');", $parcial);
        $this->assertStringContainsString('return inicio > this.ahora;', $parcial);
        $this->assertStringContainsString('x-for="examen in examenesDisponibles"', $parcial);
        $this->assertStringContainsString('No hay examenes futuros para mostrar.', $parcial);
        $this->assertStringContainsString('<template x-for="examen in examenes" :key="examen.id">', $parcial);
        $this->assertStringContainsString("tab === 'editar'", $parcial);
        $this->assertStringContainsString('id="examen-editar"', $parcial);

        // Las etiquetas de los selectores muestran tipo y curso, sin fecha.
        $this->assertStringContainsString("return [examen.tipo || 'Sin tipo', examen.curso || '']", $parcial);
        $this->assertStringNotContainsString('this.fechaDe(examen), this.horario(examen)', $parcial);
    }

    public function test_editar_carga_el_formulario_sin_enviar_cambios(): void
    {
        $parcial = $this->fuenteParcial();
        $formulario = $this->fuente(resource_path('views/components/ui/form-crear-examen.blade.php'));

        $this->assertStringContainsString('get examenesEditables()', $parcial);
        $this->assertStringContainsString('examenesDisponibles.filter((examen) => Boolean(examen.es_propio))', $parcial);
        $this->assertStringContainsString('api.examenes.edicion.mostrar', $parcial);
        $this->assertStringContainsString('modo="editar"', $parcial);
        $this->assertStringContainsString('x-for="examen in examenesEditables"', $parcial);

        $this->assertStringContainsString('x-on:editar-examen-datos.window="cargarEdicion($event.detail)"', $formulario);
        $this->assertStringContainsString('x-on:submit.prevent', $formulario);
        $this->assertStringContainsString("type=\"{{ \$esEdicion ? 'button' : 'submit' }}\"", $formulario);
        $this->assertStringNotContainsString('examenes.update', $parcial);
    }

    public function test_la_pagina_de_examenes_monta_el_parcial(): void
    {
        $pagina = $this->fuente(resource_path('views/pages/examenes.blade.php'));

        $this->assertStringContainsString("@include('partials.examenes-curso', [", $pagina);
        $this->assertStringContainsString("'cursos' => \$cursos", $pagina);
        $this->assertStringContainsString("'cursoInicial' => \$cursoInicial", $pagina);
        $this->assertStringNotContainsString('>Ver examenes<', $pagina);
        $this->assertStringNotContainsString('>Crear examen<', $pagina);
        $this->assertStringNotContainsString("'examenes' => \$examenes", $pagina);
    }

    public function test_el_parcial_ya_no_tiene_selector_de_materia(): void
    {
        $parcial = $this->fuenteParcial();

        $this->assertStringNotContainsString('label="Materia"', $parcial);
        $this->assertStringNotContainsString('x-model="cursoSeleccionado"', $parcial);
        $this->assertStringNotContainsString('x-show="hayCurso"', $parcial);
        $this->assertStringNotContainsString('Selecciona una materia', $parcial);
        $this->assertStringContainsString("route('api.examenes.listar')", $parcial);
    }

    public function test_la_pagina_unica_muestra_todos_los_examenes_del_docente(): void
    {
        $parcial = $this->fuenteParcial();
        $pagina = $this->fuente(resource_path('views/pages/examenes.blade.php'));

        $this->assertStringContainsString("route('api.cursos.examenes.listar', ['idCurso' => '__CURSO__'])", $parcial);
        $this->assertStringContainsString('$listadoGlobal = $curso === null;', $parcial);
        $this->assertStringContainsString("'puedeCrearExamen' => \$cursos->isNotEmpty()", $pagina);
        $this->assertStringNotContainsString('@if ($cursos->isEmpty())', $pagina);
    }

    public function test_el_alta_permite_elegir_el_curso_en_la_pagina_unica(): void
    {
        $formulario = $this->fuente(resource_path('views/components/ui/form-crear-examen.blade.php'));

        $this->assertStringContainsString("'cursos' => [],", $formulario);
        $this->assertStringContainsString('$tieneSelectorCurso = ! $esEdicion && $opcionesCursos !== [];', $formulario);
        $this->assertStringContainsString('label="Curso"', $formulario);
        $this->assertStringContainsString('name="curso_id"', $formulario);
        $this->assertStringContainsString('@change="cambiarCurso()"', $formulario);
        $this->assertStringContainsString('this.nombreCurso = this.cursos[this.cursoId] ?? \'\';', $formulario);
        $this->assertStringContainsString("old('curso_id', \$cursoId)", $formulario);
        $this->assertStringContainsString(":cursos=\"\$listadoGlobal ? \$opcionesCursos : []\"", $this->fuenteParcial());
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

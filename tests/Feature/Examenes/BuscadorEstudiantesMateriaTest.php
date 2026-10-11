<?php

/**
 * @file    BuscadorEstudiantesMateriaTest.php
 *
 * @author  Alisson D. Alvarado <alvaradoalissondalet@gmail.com>
 *
 * @created 2026-09-26
 *
 * @updated 2026-09-26
 *
 * @description
 * Pruebas del render del buscador de la pestaña Estudiantes de
 * `pages/materia-estudiantes`. La vista ya no trae filas mock: delega la tabla,
 * el buscador y los filtros al componente Livewire `EstudiantesCurso`, que a su
 * vez usa el parcial compartido y toma sus parametros del Service. Se verifica
 * que ese contrato se siga manteniendo y que la pagina no vuelva a renderizar
 * datos mock ni tags de componente sin compilar.
 *
 * @see  resources/views/pages/materia-estudiantes.blade.php
 * @see  resources/views/livewire/examenes/estudiantes-curso.blade.php
 * @see  resources/views/partials/busqueda-estudiante.blade.php
 * @see  resources/views/components/ui/table.blade.php
 * @see  resources/views/components/ui/search-input.blade.php
 * @see  App\Livewire\Examenes\EstudiantesCurso
 * @see  App\Services\Examen\BusquedaEstudianteService
 *
 * @changelog
 * - 2026-09-26  [Alisson D. Alvarado]  test: creación inicial.
 * - 2026-09-26  [Alisson D. Alvarado]  test: el estado del buscador pasa al
 *   parcial compartido; se cubre el aviso de sin resultados y que sus
 *   parámetros vengan de BusquedaEstudianteService.
 * - 2026-09-28  [T1]  test: el buscador y la tabla pasan al componente
 *   Livewire; las aserciones que cubrian el markup mock de la pagina (#28) se
 *   repuntan al componente y la pagina se cubre sin datos mock.
 */

namespace Tests\Feature\Examenes;

use App\Models\Curso;
use App\Services\Examen\BusquedaEstudianteService;
use App\Services\Examen\ListarEstudiantesCursoConEstadoService;
use Illuminate\Support\Js;
use Illuminate\View\ComponentSlot;
use Tests\TestCase;

class BuscadorEstudiantesMateriaTest extends TestCase
{
    /**
     * Renderiza la vista de la materia con los mismos datos que le pasa la
     * ruta `materias.detalle`.
     */
    private function renderPagina(): string
    {
        $curso = Curso::query()->firstOrFail();

        return view('pages.materia-estudiantes', [
            'curso' => $curso,
            'conteos' => app(ListarEstudiantesCursoConEstadoService::class)
                ->conteosPorEstado($curso->id_curso),
        ])->render();
    }

    /**
     * Devuelve el fuente de la vista del componente, que es donde vive hoy el
     * buscador.
     */
    private function fuenteComponente(): string
    {
        $vista = file_get_contents(resource_path('views/livewire/examenes/estudiantes-curso.blade.php'));

        $this->assertIsString($vista);

        return $vista;
    }

    /**
     * Renderiza el parcial con el estado Alpine del buscador.
     */
    private function renderParcial(): string
    {
        return view('partials.busqueda-estudiante')->render();
    }

    /**
     * Renderiza `x-ui.table` con las filas dadas.
     *
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function renderTabla(array $rows): string
    {
        return view('components.ui.table', [
            'slot' => new ComponentSlot(''),
            'headers' => ['Estudiante', 'Código SIS'],
            'rows' => $rows,
        ])->render();
    }

    public function test_el_buscador_delega_el_sanitizeo_en_alpine(): void
    {
        $html = $this->fuenteComponente();

        $this->assertStringContainsString(
            '@input="$event.target.value = sanitizarBusqueda($event.target.value)"',
            $html
        );
        $this->assertStringContainsString('x-bind:maxlength="maximoBusqueda()"', $html);
    }

    /**
     * La vista no puede decidir la regla: la toma del Service. Se compara contra
     * el mismo `Js::from` que usa el parcial para no fijar a mano los escapes
     * unicode de las expresiones regulares.
     */
    public function test_el_parcial_toma_los_parametros_del_service(): void
    {
        $html = $this->renderParcial();

        $this->assertStringContainsString(Js::from(BusquedaEstudianteService::DESCARTE_NOMBRE), $html);
        $this->assertStringContainsString(Js::from(BusquedaEstudianteService::DESCARTE_COD_SIS), $html);
        $this->assertStringContainsString('? '.BusquedaEstudianteService::LARGO_COD_SIS, $html);
        $this->assertStringContainsString('.slice(0, '.BusquedaEstudianteService::LARGO_COD_SIS.')', $html);
    }

    public function test_el_modo_se_decide_por_el_primer_caracter(): void
    {
        $html = $this->renderParcial();

        $this->assertStringContainsString('modoDe(valor) {', $html);
        $this->assertStringContainsString('/^[0-9]/.test(valor)', $html);
    }

    public function test_cada_filtrado_usa_el_campo_correspondiente_del_estudiante(): void
    {
        $html = $this->renderParcial();

        $this->assertStringContainsString(
            'return (modo === this.modoBusqueda ? sis : nombre).toLowerCase().includes(termino);',
            $html
        );
    }

    /**
     * Las filas ya no las arma la vista con datos mock: la pagina solo monta el
     * componente Livewire, que las pide al Service. Este test lo fija para que
     * el mock no vuelva a colarse en la pagina.
     */
    public function test_la_pagina_delega_las_filas_al_componente_livewire(): void
    {
        $html = $this->renderPagina();

        $this->assertStringContainsString('wire:snapshot=', $html);

        foreach (['Ana López', 'Bruno Díaz', 'Carla Ruiz', 'Diego Soto', 'Ernesto Vera', 'Fátima Quispe'] as $nombre) {
            $this->assertStringNotContainsString(
                $nombre,
                $html,
                'La pagina no debe renderizar estudiantes mock ('.$nombre.').'
            );
        }
    }

    /**
     * El aviso de sin resultados cuenta sobre lo que devuelve el componente,
     * asi que tiene que seguir living en su vista.
     */
    public function test_la_vista_avisa_cuando_la_busqueda_no_tiene_coincidencias(): void
    {
        $html = $this->fuenteComponente();

        $this->assertStringContainsString('title="Sin resultados"', $html);
        $this->assertStringContainsString('No se encontraron resultados para la búsqueda', $html);
    }

    /**
     * La vista Livewire comparte el mismo parcial: si el buscador de la pagina
     * mock se-saneara distinto, esta asercion lo va a avisar.
     */
    public function test_la_vista_livewire_usa_el_mismo_parcial(): void
    {
        $vista = file_get_contents(resource_path('views/livewire/examenes/estudiantes-curso.blade.php'));

        $this->assertIsString($vista);
        $this->assertStringContainsString(
            'x-data="@include(\'partials.busqueda-estudiante\')"',
            $vista
        );
        $this->assertStringContainsString(
            '@input="$event.target.value = sanitizarBusqueda($event.target.value)"',
            $vista
        );
    }

    public function test_la_tabla_no_deja_tags_de_componente_sin_compilar(): void
    {
        $html = $this->renderPagina();

        $this->assertStringNotContainsString('<x-ui.', $html);
        $this->assertStringNotContainsString('@component(', $html);
    }

    public function test_la_tabla_oculta_la_fila_cuando_la_expresion_es_falsa(): void
    {
        $html = $this->renderTabla([
            ['__xShow' => 'coincideEstudiante("Ana López", "202201013")', 'Ana López', '202201013'],
        ]);

        $this->assertStringContainsString(
            'x-show="coincideEstudiante(&quot;Ana López&quot;, &quot;202201013&quot;)"',
            $html
        );
    }

    public function test_la_tabla_ignora_la_clave_de_visibilidad_entre_las_celdas(): void
    {
        $html = $this->renderTabla([
            ['__xShow' => 'coincideEstudiante("Ana López", "202201013")', 'Ana López', '202201013'],
        ]);

        // Solo las dos celdas reales: '__xShow' no debe caer en el bucle.
        $this->assertSame(2, substr_count($html, '<td'));
        $this->assertStringNotContainsString('__xShow', $html);
    }

    public function test_la_tabla_no_agrega_x_show_a_las_filas_sin_la_clave(): void
    {
        $html = $this->renderTabla([
            ['__rowClass' => 'bg-status-en-revision-bg', 'Ana López', '202201013'],
        ]);

        $this->assertStringNotContainsString('x-show', $html);
        $this->assertStringNotContainsString('__rowClass', $html);
    }
}

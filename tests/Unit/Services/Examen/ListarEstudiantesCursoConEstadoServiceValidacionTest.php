<?php

/**
 * @file    ListarEstudiantesCursoConEstadoServiceValidacionTest.php
 *
 * @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
 *
 * @created 2026-09-25
 *
 * @updated 2026-09-26
 *
 * @description
 * Pruebas unitarias de la validación de búsqueda del servicio de listado. Ya
 * no es lógica propia: desde el refactor del 2026-09-26 el criterio vive en
 * BusquedaEstudianteService y el servicio lo recibe por inyección, así que acá
 * se cubre ese contrato (que se pueda inyectar y que la regla no esté
 * duplicada).
 *
 * @see  App\Services\Examen\ListarEstudiantesCursoConEstadoService
 * @see  App\Services\Examen\BusquedaEstudianteService
 *
 * @changelog
 * - 2026-09-25  [T1]  test: creación inicial.
 * - 2026-09-26  [Alisson D. Alvarado]  test: la validación de la búsqueda se
 *   delega a BusquedaEstudianteService; se cubre la inyección del criterio y
 *   que el servicio ya no duplique la regla.
 */

namespace Tests\Unit\Services\Examen;

use App\Services\Examen\BusquedaEstudianteService;
use App\Services\Examen\ListarEstudiantesCursoConEstadoService;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

class ListarEstudiantesCursoConEstadoServiceValidacionTest extends TestCase
{
    private BusquedaEstudianteService $busqueda;

    private ListarEstudiantesCursoConEstadoService $servicio;

    protected function setUp(): void
    {
        parent::setUp();

        $this->busqueda = new BusquedaEstudianteService;
        $this->servicio = new ListarEstudiantesCursoConEstadoService($this->busqueda);
    }

    public function test_acepta_el_criterio_de_busqueda_por_inyeccion(): void
    {
        $this->assertInstanceOf(ListarEstudiantesCursoConEstadoService::class, $this->servicio);
    }

    public function test_el_servicio_usa_el_criterio_inyectado(): void
    {
        $propiedad = new ReflectionProperty(ListarEstudiantesCursoConEstadoService::class, 'busqueda');

        $this->assertSame($this->busqueda, $propiedad->getValue($this->servicio));
    }

    public function test_el_servicio_no_duplica_la_validacion_de_busqueda(): void
    {
        $this->assertFalse(method_exists($this->servicio, 'validarBusqueda'));
    }
}

<?php

/**
 * @file    ListarEstudiantesCursoConEstadoServiceValidacionTest.php
 *
 * @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
 *
 * @created 2026-09-25
 *
 * @updated 2026-09-25
 *
 * @description
 * Pruebas unitarias de validarBusqueda(): es la unica logica del servicio
 * que no depende de la base de datos, por eso va como test unitario puro
 * (sin bootstrap de Laravel) en vez de en tests/Feature.
 *
 * @see  App\Services\Examen\ListarEstudiantesCursoConEstadoService
 *
 * @changelog
 * - 2026-09-25  [T1]  test: creación inicial.
 */

namespace Tests\Unit\Services\Examen;

use App\Services\Examen\ListarEstudiantesCursoConEstadoService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ListarEstudiantesCursoConEstadoServiceValidacionTest extends TestCase
{
    private ListarEstudiantesCursoConEstadoService $servicio;

    protected function setUp(): void
    {
        parent::setUp();

        $this->servicio = new ListarEstudiantesCursoConEstadoService;
    }

    public function test_acepta_busqueda_alfanumerica_valida(): void
    {
        $this->servicio->validarBusqueda('Juan');
        $this->servicio->validarBusqueda('202100542');
        $this->servicio->validarBusqueda('Ñoño');

        $this->addToAssertionCount(3);
    }

    public function test_acepta_busqueda_vacia_o_nula(): void
    {
        $this->servicio->validarBusqueda(null);
        $this->servicio->validarBusqueda('');

        $this->addToAssertionCount(2);
    }

    public function test_rechaza_caracteres_no_permitidos(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->servicio->validarBusqueda('juan@perez.com');
    }

    public function test_acepta_busqueda_de_nombre_completo_con_espacio(): void
    {
        $this->servicio->validarBusqueda('Diego Camacho');

        $this->addToAssertionCount(1);
    }
}

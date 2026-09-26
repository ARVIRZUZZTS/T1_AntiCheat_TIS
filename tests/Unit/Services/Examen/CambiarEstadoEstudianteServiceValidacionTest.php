<?php

/**
 * @file    CambiarEstadoEstudianteServiceValidacionTest.php
 *
 * @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
 *
 * @created 2026-09-26
 *
 * @updated 2026-09-26
 *
 * @description
 * Pruebas unitarias de validarMotivo(): es la unica logica del servicio que
 * no depende de la base de datos (issue #27, criterio 1).
 *
 * @see  App\Services\Examen\CambiarEstadoEstudianteService
 *
 * @changelog
 * - 2026-09-26  [T1]  test: creación inicial.
 */

namespace Tests\Unit\Services\Examen;

use App\Services\Examen\CambiarEstadoEstudianteService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class CambiarEstadoEstudianteServiceValidacionTest extends TestCase
{
    private CambiarEstadoEstudianteService $servicio;

    protected function setUp(): void
    {
        parent::setUp();

        $this->servicio = new CambiarEstadoEstudianteService;
    }

    public function test_rechaza_motivo_vacio(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->servicio->validarMotivo('');
    }

    public function test_rechaza_motivo_nulo(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->servicio->validarMotivo(null);
    }

    public function test_rechaza_motivo_de_solo_espacios(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->servicio->validarMotivo('   ');
    }

    public function test_rechaza_motivo_mayor_a_150_caracteres(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->servicio->validarMotivo(str_repeat('a', 151));
    }

    public function test_acepta_motivo_de_exactamente_150_caracteres(): void
    {
        $this->servicio->validarMotivo(str_repeat('a', 150));

        $this->addToAssertionCount(1);
    }

    public function test_rechaza_caracteres_no_permitidos(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->servicio->validarMotivo('No entregó tareas.');
    }

    public function test_acepta_motivo_valido(): void
    {
        $this->servicio->validarMotivo('No entrego las tareas del laboratorio 4');

        $this->addToAssertionCount(1);
    }
}

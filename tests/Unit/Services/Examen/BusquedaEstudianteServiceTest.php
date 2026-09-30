<?php

/**
 * @file    BusquedaEstudianteServiceTest.php
 *
 * @author  Alisson D. Alvarado <alvaradoalissondalet@gmail.com>
 *
 * @created 2026-09-26
 *
 * @updated 2026-09-26
 *
 * @description
 * Pruebas unitarias de BusquedaEstudianteService. modo(), sanear() y
 * validar() son logica pura sin base de datos, asi que van como test unitario
 * sin bootstrap de Laravel; aplicar() se cubre en
 * tests/Feature/Examenes/ListarEstudiantesCursoFiltroTest.php.
 *
 * @see  App\Services\Examen\BusquedaEstudianteService
 *
 * @changelog
 * - 2026-09-26  [Alisson D. Alvarado]  test: creación inicial.
 */

namespace Tests\Unit\Services\Examen;

use App\Services\Examen\BusquedaEstudianteService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class BusquedaEstudianteServiceTest extends TestCase
{
    private BusquedaEstudianteService $servicio;

    protected function setUp(): void
    {
        parent::setUp();

        $this->servicio = new BusquedaEstudianteService;
    }

    public function test_el_primer_caracter_decide_el_modo(): void
    {
        $this->assertSame(BusquedaEstudianteService::MODO_SIS, $this->servicio->modo('202100542'));
        $this->assertSame(BusquedaEstudianteService::MODO_NOMBRE, $this->servicio->modo('Juan'));
        $this->assertSame(BusquedaEstudianteService::MODO_NOMBRE, $this->servicio->modo('Ñoño'));
        $this->assertSame(BusquedaEstudianteService::MODO_NOMBRE, $this->servicio->modo(' Diego'));
        $this->assertSame('', $this->servicio->modo(''));
    }

    public function test_valida_y_devuelve_el_modo(): void
    {
        $this->assertSame(BusquedaEstudianteService::MODO_SIS, $this->servicio->validar('202100542'));
        $this->assertSame(BusquedaEstudianteService::MODO_NOMBRE, $this->servicio->validar('Diego Camacho'));
        $this->assertSame('', $this->servicio->validar(null));
        $this->assertSame('', $this->servicio->validar(''));
    }

    public function test_rechaza_un_codigo_sis_mas_largo_que_nueve_digitos(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->servicio->validar('2021005421');
    }

    public function test_rechaza_letras_en_termino_que_declara_modo_sis(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->servicio->validar('2021abc');
    }

    public function test_rechaza_simbolos_en_la_busqueda_por_nombre(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->servicio->validar('juan@perez.com');
    }

    public function test_sanea_descartando_lo_que_no_corresponde_al_modo(): void
    {
        $this->assertSame('202100542', $this->servicio->sanear('2021a00542'));
        $this->assertSame('Diego Camacho', $this->servicio->sanear('Diego 9Camacho!'));
        $this->assertSame('202100542', $this->servicio->sanear('2021005421234'));
        // Las tildes y la enye si son parte del nombre: no se descartan.
        $this->assertSame('Ángel Ñoño', $this->servicio->sanear('Ángel Ñoño7'));
    }
}

<?php

/**
 * @file    GeneradorContrasenaAuxiliarServiceTest.php
 *
 * @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
 *
 * @created 2026-11-27
 *
 * @description
 * Regla de la contraseña del auxiliar: prefijo AUX-, iniciales + documento +
 * iniciales con documento, y 10 aleatorios sin documento.
 *
 * @changelog
 * - 2026-11-27  [Diego Tejerina]  feat: creación inicial.
 */

namespace Tests\Unit\Services\Auxiliar;

use App\Services\Auxiliar\GeneradorContrasenaAuxiliarService;
use PHPUnit\Framework\TestCase;

class GeneradorContrasenaAuxiliarServiceTest extends TestCase
{
    private GeneradorContrasenaAuxiliarService $generador;

    protected function setUp(): void
    {
        parent::setUp();
        $this->generador = new GeneradorContrasenaAuxiliarService;
    }

    public function test_dos_palabras_por_campo(): void
    {
        $this->assertSame('AUX-MJ9479386CF', $this->generador->generar('Manual Jorge', 'Camacho Flores', '9479386'));
    }

    public function test_una_sola_palabra_repite_la_inicial(): void
    {
        $this->assertSame('AUX-JJ9479386MM', $this->generador->generar('Juan', 'Mamani', '9479386'));
    }

    public function test_normaliza_tildes_y_enie(): void
    {
        $this->assertSame('AUX-AN123456AN', $this->generador->generar('Ángel Núñez', 'Áñez Ñato', '123456'));
    }

    public function test_ignora_apostrofos_y_guiones_en_la_inicial(): void
    {
        $this->assertSame('AUX-DD55555SS', $this->generador->generar("D'Angelo", 'Saavedra-Lima', '55555'));
    }

    public function test_el_carnet_sirve_como_documento(): void
    {
        $this->assertSame('AUX-LP1234567890RM', $this->generador->generar('Luis Pedro', 'Rojas Mota', '1234567890'));
    }

    public function test_sin_documento_usa_prefijo_mas_diez_aleatorios(): void
    {
        $clave = $this->generador->generar('Ana', 'Perez', null);

        $this->assertStringStartsWith('AUX-', $clave);
        $this->assertSame(14, strlen($clave));
        $this->assertMatchesRegularExpression('/^AUX-[A-Za-z0-9]{10}$/', $clave);
    }

    public function test_sin_documento_cada_llamada_es_distinta(): void
    {
        $this->assertNotSame(
            $this->generador->generar('Ana', 'Perez', ''),
            $this->generador->generar('Ana', 'Perez', ''),
        );
    }
}

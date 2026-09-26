<?php

/**
 * @file    RegistrarIncidenciaTest.php
 * @author  Valery D. Ortuno P. <valerydariana98@gmail.com>
 * @created 2026-09-25
 * @updated 2026-09-25
 *
 * @description
 * Pruebas del formulario de registro de incidencia: renderizado de la página,
 * contador de la descripción, obligatoriedad de los campos y derivación del
 * estado a partir del rol del registrador.
 *
 * @changelog
 * - 2026-09-25  [Valery D. Ortuno P]  test: creación inicial de las pruebas.
 */

namespace Tests\Feature;

use App\Livewire\Monitoreo\RegistrarIncidencia;
use App\Models\Rol;
use Livewire\Livewire;
use Tests\TestCase;

class RegistrarIncidenciaTest extends TestCase
{
    /**
     * Verifica que la página muestre los datos precargados, el estado derivado del
     * rol y los campos del formulario.
     */
    public function test_la_pagina_muestra_los_datos_precargados_y_el_estado(): void
    {
        $this->get('/registrar-incidencia')
            ->assertOk()
            ->assertSee('Ana López')
            ->assertSee('202201013')
            ->assertSee('Cálculo Diferencial')
            ->assertSee('Confirmado')
            ->assertSee('Máximo 300 caracteres.')
            ->assertSee('0 / 300');
    }

    /**
     * Verifica que el contador de la descripción Accompañe lo escrito.
     */
    public function test_el_contador_de_descripcion_crece_al_escribir(): void
    {
        Livewire::test(RegistrarIncidencia::class)
            ->set('descripcion', 'El estudiante miró el celular')
            ->assertSee('29 / 300');
    }

    /**
     * Verifica que el motivo y la descripción se exijan al registrar.
     */
    public function test_exige_motivo_y_descripcion_al_registrar(): void
    {
        Livewire::test(RegistrarIncidencia::class)
            ->call('registrar')
            ->assertHasErrors(['tipoIncidencia', 'descripcion'])
            ->assertSee('aria-invalid="true"', escape: false)
            ->assertDontSee('error=""', escape: false);
    }

    /**
     * Verifica que la descripción se rechace al superar el límite de caracteres.
     */
    public function test_rechaza_una_descripcion_mas_larga_al_limite(): void
    {
        Livewire::test(RegistrarIncidencia::class)
            ->set('tipoIncidencia', 'uso_de_dispositivo')
            ->set('descripcion', str_repeat('a', RegistrarIncidencia::DESCRIPCION_MAXIMO + 1))
            ->call('registrar')
            ->assertHasErrors('descripcion');
    }

    /**
     * Verifica que el registro avance con los campos obligatorios completos.
     */
    public function test_acepta_el_registro_con_los_campos_obligatorios(): void
    {
        Livewire::test(RegistrarIncidencia::class)
            ->set('tipoIncidencia', 'uso_de_dispositivo')
            ->set('descripcion', 'El estudiante usaba un segundo dispositivo.')
            ->call('registrar')
            ->assertHasNoErrors();
    }

    /**
     * Verifica que la etiqueta del estado se derive del rol del registrador: un
     * docente confirma la incidencia y un auxiliar la deja en revisión.
     */
    public function test_el_estado_depende_del_rol_del_registrador(): void
    {
        Livewire::test(RegistrarIncidencia::class)
            ->set('rol', Rol::NOMBRE_DOCENTE)
            ->assertSee('Confirmado')
            ->assertSet('tipoInfraccion', \App\Enums\TipoInfraccion::Tramposo);

        Livewire::test(RegistrarIncidencia::class)
            ->set('rol', Rol::NOMBRE_AUXILIAR)
            ->assertSee('En revisión')
            ->assertSet('tipoInfraccion', \App\Enums\TipoInfraccion::Sospechoso);
    }
}

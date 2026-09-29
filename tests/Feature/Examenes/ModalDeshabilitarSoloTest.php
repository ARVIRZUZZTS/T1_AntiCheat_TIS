<?php

/**
 * @file    ModalDeshabilitarSoloTest.php
 *
 * @author  Alisson D. Alvarado <alvaradoalissondalet@gmail.com>
 *
 * @created 2026-09-26
 *
 * @updated 2026-09-26
 *
 * @description
 * Pruebas del render del componente UI `modal-deshabilitar` (#26). Se prueba
 * el HTML que produce el componente en los dos contextos en los que se usa,
 * sin tocar la base de datos: la vista con Alpine (materia-estudiantes) y el
 * componente Livewire (estudiantes-curso). Se cubre el motivo obligatorio con
 * maximo de 150 caracteres, el boton rojo de confirmar, el azul de cancelar y
 * el cierre sin cambios.
 *
 * @see  resources/views/components/ui/modal-deshabilitar.blade.php
 * @see  resources/views/pages/materia-estudiantes.blade.php
 * @see  resources/views/livewire/examenes/estudiantes-curso.blade.php
 *
 * @changelog
 * - 2026-09-26  [Alisson D. Alvarado]  test: creación inicial.
 */

namespace Tests\Feature\Examenes;

use Illuminate\View\ComponentSlot;
use Tests\TestCase;

class ModalDeshabilitarSoloTest extends TestCase
{
    /**
     * Renderiza `x-ui.modal-deshabilitar` con las props dadas.
     *
     * @param  array<string, mixed>  $props
     */
    private function renderModal(array $props = []): string
    {
        return view('components.ui.modal-deshabilitar', array_merge([
            'slot' => new ComponentSlot(''),
        ], $props))->render();
    }

    public function test_no_renderiza_cuando_esta_cerrado(): void
    {
        $html = $this->renderModal();

        $this->assertStringNotContainsString('Deshabilitar estudiante', $html);
        $this->assertStringNotContainsString('<x-ui.', $html);
    }

    public function test_modo_livewire_enlaza_motivo_confirmacion_y_cierre(): void
    {
        $html = $this->renderModal([
            'show' => true,
            'nombre' => 'Ana Lopez',
            'sis' => '202201013',
            'motivo' => '',
            'error' => '',
            'wireModel' => 'motivoInhabilitacion',
            'wireConfirm' => 'confirmarInhabilitar',
            'wireClose' => 'cerrarModal',
        ]);

        $this->assertStringContainsString('wire:model.defer="motivoInhabilitacion"', $html);
        $this->assertStringContainsString('wire:click="confirmarInhabilitar"', $html);
        $this->assertStringContainsString('wire:click="cerrarModal"', $html);
        $this->assertStringContainsString('Estudiante: Ana Lopez (SIS: 202201013)', $html);
    }

    public function test_modo_alpine_usa_el_scope_padre_y_no_de_directivas_livewire(): void
    {
        $html = $this->renderModal(['show' => true]);

        $this->assertStringContainsString('x-model="motivo"', $html);
        $this->assertStringContainsString('@click="confirmarDeshabilitar()"', $html);
        $this->assertStringContainsString('@click="cerrarModalDeshabilitar()"', $html);
        $this->assertStringContainsString('x-text="nombre"', $html);
        $this->assertStringContainsString('x-text="sis"', $html);
        $this->assertStringContainsString('x-text="errorMotivo"', $html);
        $this->assertStringNotContainsString('wire:model', $html);
        $this->assertStringNotContainsString('wire:click', $html);
    }

    public function test_el_motivo_acepta_maximo_150_caracteres(): void
    {
        $html = $this->renderModal([
            'show' => true,
            'wireModel' => 'motivoInhabilitacion',
            'wireConfirm' => 'confirmarInhabilitar',
            'wireClose' => 'cerrarModal',
        ]);

        $this->assertStringContainsString('maxlength="150"', $html);
        $this->assertStringContainsString('Máximo 150 caracteres.', $html);
    }

    public function test_confirmar_es_rojo_y_cancelar_es_azul(): void
    {
        $html = $this->renderModal([
            'show' => true,
            'wireModel' => 'motivoInhabilitacion',
            'wireConfirm' => 'confirmarInhabilitar',
            'wireClose' => 'cerrarModal',
        ]);

        $this->assertMatchesRegularExpression(
            '/bg-danger\b[^>]*>\s*Deshabilitar\s*</',
            $html,
            'El botón de confirmar debe usar el token de peligro (#D32027).'
        );
        $this->assertMatchesRegularExpression(
            '/bg-brand\b[^>]*>\s*Cancelar\s*</',
            $html,
            'El botón de cancelar debe usar el token de marca (#1B3A73).'
        );
    }

    public function test_muestra_el_error_que_entra_por_prop(): void
    {
        $html = $this->renderModal([
            'show' => true,
            'error' => 'El motivo es obligatorio.',
            'wireModel' => 'motivoInhabilitacion',
            'wireConfirm' => 'confirmarInhabilitar',
            'wireClose' => 'cerrarModal',
        ]);

        $this->assertStringContainsString('El motivo es obligatorio.', $html);
        $this->assertStringContainsString('text-fg-danger', $html);
    }

    public function test_no_deja_tags_de_componente_sin_compilar(): void
    {
        $html = $this->renderModal([
            'show' => true,
            'wireModel' => 'motivoInhabilitacion',
            'wireConfirm' => 'confirmarInhabilitar',
            'wireClose' => 'cerrarModal',
        ]);

        $this->assertStringNotContainsString('<x-ui.', $html);
        $this->assertStringNotContainsString('@component(', $html);
    }
}

<?php

/**
 * @file    ModalHabilitarSoloTest.php
 *
 * @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
 *
 * @created 2026-09-26
 *
 * @updated 2026-09-26
 *
 * @description
 * Pruebas del render del componente UI `modal-habilitar` (#25). Mismo
 * enfoque que ModalDeshabilitarSoloTest (#26): se prueba el HTML que
 * produce el componente en los dos contextos en los que se usa, sin tocar
 * la base de datos: la vista con Alpine (materia-estudiantes) y el
 * componente Livewire (estudiantes-curso). Se cubre el cierre con clic
 * fuera del modal y con Esc (criterio 3 de #25), y que ambos botones usen
 * el mismo azul (a diferencia de #26, que usa rojo para confirmar).
 *
 * @see  resources/views/components/ui/modal-habilitar.blade.php
 * @see  resources/views/pages/materia-estudiantes.blade.php
 * @see  resources/views/livewire/examenes/estudiantes-curso.blade.php
 *
 * @changelog
 * - 2026-09-26  [Diego Tejerina]  test: creación inicial.
 */

namespace Tests\Feature\Examenes;

use Illuminate\View\ComponentSlot;
use Tests\TestCase;

class ModalHabilitarSoloTest extends TestCase
{
    /**
     * Renderiza `x-ui.modal-habilitar` con las props dadas.
     *
     * @param  array<string, mixed>  $props
     */
    private function renderModal(array $props = []): string
    {
        return view('components.ui.modal-habilitar', array_merge([
            'slot' => new ComponentSlot(''),
        ], $props))->render();
    }

    public function test_no_renderiza_cuando_esta_cerrado(): void
    {
        $html = $this->renderModal();

        $this->assertStringNotContainsString('Habilitar Estudiante', $html);
        $this->assertStringNotContainsString('<x-ui.', $html);
    }

    public function test_modo_livewire_enlaza_confirmacion_y_cierre(): void
    {
        $html = $this->renderModal([
            'show' => true,
            'nombre' => 'Ana Lopez',
            'sis' => '202201013',
            'error' => '',
            'wireConfirm' => 'confirmarHabilitar',
            'wireClose' => 'cerrarModal',
        ]);

        $this->assertStringContainsString('wire:click="confirmarHabilitar"', $html);
        $this->assertStringContainsString('wire:click="cerrarModal"', $html);
        $this->assertStringContainsString('Estudiante: Ana Lopez (SIS: 202201013)', $html);
    }

    public function test_modo_alpine_usa_el_scope_padre_y_no_de_directivas_livewire(): void
    {
        $html = $this->renderModal(['show' => true]);

        $this->assertStringContainsString('@click="confirmarHabilitar()"', $html);
        $this->assertStringContainsString('@click="cerrarModalHabilitar()"', $html);
        $this->assertStringContainsString('x-text="nombreHabilitar"', $html);
        $this->assertStringContainsString('x-text="sisHabilitar"', $html);
        $this->assertStringContainsString('x-text="errorHabilitar"', $html);
        $this->assertStringNotContainsString('wire:model', $html);
        $this->assertStringNotContainsString('wire:click', $html);
    }

    public function test_no_tiene_campo_de_motivo(): void
    {
        $html = $this->renderModal([
            'show' => true,
            'wireConfirm' => 'confirmarHabilitar',
            'wireClose' => 'cerrarModal',
        ]);

        $this->assertStringNotContainsString('<textarea', $html);
        $this->assertStringNotContainsString('motivo', $html);
    }

    public function test_confirmar_y_cancelar_usan_el_mismo_azul(): void
    {
        $html = $this->renderModal([
            'show' => true,
            'wireConfirm' => 'confirmarHabilitar',
            'wireClose' => 'cerrarModal',
        ]);

        $this->assertMatchesRegularExpression(
            '/bg-brand\b[^>]*>.*?Habilitar.*?</s',
            $html,
            'El botón de confirmar debe usar el token de marca (#1B3A73), igual que Cancelar.'
        );
        $this->assertMatchesRegularExpression(
            '/bg-brand\b[^>]*>\s*Cancelar\s*</',
            $html,
            'El botón de cancelar debe usar el token de marca (#1B3A73).'
        );
        $this->assertStringNotContainsString('bg-danger', $html);
    }

    /**
     * Criterio 3 de #25: cierra con clic fuera del modal y con Esc, no solo
     * con la X y con Cancelar.
     */
    public function test_cierra_con_clic_afuera_y_con_escape(): void
    {
        $html = $this->renderModal([
            'show' => true,
            'wireConfirm' => 'confirmarHabilitar',
            'wireClose' => 'cerrarModal',
        ]);

        $this->assertStringContainsString('wire:click.self="cerrarModal"', $html);
        $this->assertStringContainsString('wire:keydown.escape.window="cerrarModal"', $html);
    }

    public function test_muestra_el_error_que_entra_por_prop(): void
    {
        $html = $this->renderModal([
            'show' => true,
            'error' => 'El curso no tiene un examen actual',
            'wireConfirm' => 'confirmarHabilitar',
            'wireClose' => 'cerrarModal',
        ]);

        $this->assertStringContainsString('El curso no tiene un examen actual', $html);
        $this->assertStringContainsString('text-fg-danger', $html);
    }

    public function test_no_deja_tags_de_componente_sin_compilar(): void
    {
        $html = $this->renderModal([
            'show' => true,
            'wireConfirm' => 'confirmarHabilitar',
            'wireClose' => 'cerrarModal',
        ]);

        $this->assertStringNotContainsString('<x-ui.', $html);
        $this->assertStringNotContainsString('@component(', $html);
    }
}

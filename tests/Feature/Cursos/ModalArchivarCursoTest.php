<?php

/**
 * @file    ModalArchivarCursoTest.php
 *
 * @author  Equipo T1
 *
 * @created 2026-11-21
 *
 * @description
 * Pruebas del componente `x-ui.modal-archivar-curso`: texto, cierre sin
 * cambios y accesibilidad básica.
 */

declare(strict_types=1);

namespace Tests\Feature\Cursos;

use Tests\TestCase;

class ModalArchivarCursoTest extends TestCase
{
    /** @param array<string, mixed> $props */
    private function renderModal(array $props = []): string
    {
        return view('components.ui.modal-archivar-curso', array_merge([
            'abierto' => true,
            'nombre' => 'Curso En Curso',
            'archivando' => false,
            'error' => '',
        ], $props))->render();
    }

    public function test_no_renderiza_cuando_esta_cerrado(): void
    {
        $html = $this->renderModal(['abierto' => false]);

        $this->assertStringNotContainsString('Archivar curso', $html);
        $this->assertStringNotContainsString('<x-ui.', $html);
    }

    public function test_muestra_titulo_nombre_y_botones(): void
    {
        $html = $this->renderModal();

        $this->assertStringContainsString('Archivar curso', $html);
        $this->assertStringContainsString('Curso En Curso', $html);
        $this->assertStringContainsString('wire:click="archivar"', $html);
        $this->assertStringNotContainsString('Archivando…', $html);
        $this->assertSame(1, substr_count($html, 'Cancelar'));
    }

    public function test_cierra_sin_cambios_con_cancelar_x_escape_y_clic_fuera(): void
    {
        $html = $this->renderModal();

        $this->assertStringContainsString('wire:click.self="cerrarModal"', $html);
        $this->assertStringContainsString('wire:keydown.escape.window="cerrarModal"', $html);
        $this->assertSame(2, substr_count($html, 'wire:click="cerrarModal"'));
        $this->assertStringNotContainsString('<form', $html);
    }

    public function test_muestra_error_y_deshabilita_botones_mientras_archiva(): void
    {
        $html = $this->renderModal([
            'archivando' => true,
            'error' => 'No se pudo archivar el curso.',
        ]);

        $this->assertStringContainsString('Archivando…', $html);
        $this->assertStringContainsString('No se pudo archivar el curso.', $html);
        $this->assertStringContainsString('role="alert"', $html);
        $this->assertSame(3, substr_count($html, 'wire:loading.attr="disabled"'));
    }
}

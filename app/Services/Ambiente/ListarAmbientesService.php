<?php

/**
 * @file    ListarAmbientesService.php
 *
 * @author  Alex Candia <alex.leonar.candia@gmail.com>
 *
 * @created 2026-10-05
 *
 * @updated 2026-10-05
 *
 * @description
 * Servicio que publica el índice de ambientes que el modal de alta de examen usa
 * para el buscador. Es de solo lectura.
 *
 * Devuelve, además del id y el nombre, el campo `termino`: el nombre ya en
 * minúsculas, normalizado una sola vez al construir el índice. Así el navegador
 * no tiene que componer cadenas ni aplicar toLowerCase en cada tecla, que es el
 * mismo criterio que usa el índice de
 * {@see \App\Livewire\Monitoreo\BuscadorRegistro} para los estudiantes.
 *
 * El filtrado NO se hace en PHP a propósito: con el catálogo ya resuelto en la
 * página, filtrar en el cliente responde al instante, mientras que un viaje al
 * servidor por tecla es lo que volvió lento aquel buscador.
 *
 * @see  \App\Livewire\Monitoreo\BuscadorRegistro
 * @see  resources/views/components/ui/modal-crear-examen.blade.php
 *
 * @changelog
 * - 2026-10-05  [Alex Candia]  feat: creación inicial del servicio.
 */

namespace App\Services\Ambiente;

use App\Models\Ambiente;

class ListarAmbientesService
{
    /**
     * Ambientes del catálogo, ordenados por nombre, con su término de búsqueda.
     *
     * @return list<array{id: int, nombre: string, termino: string}>
     */
    public function ejecutar(): array
    {
        return Ambiente::query()
            ->orderBy('nombre_ambiente')
            ->get(['id_ambiente', 'nombre_ambiente'])
            ->map(fn (Ambiente $ambiente): array => [
                'id' => $ambiente->id_ambiente,
                'nombre' => $ambiente->nombre_ambiente,
                'termino' => mb_strtolower(trim($ambiente->nombre_ambiente)),
            ])
            ->values()
            ->all();
    }
}

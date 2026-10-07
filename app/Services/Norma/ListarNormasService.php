<?php

/**
 * @file    ListarNormasService.php
 *
 * @author  Alex Candia <alex.leonar.candia@gmail.com>
 *
 * @created 2026-10-05
 *
 * @updated 2026-10-05
 *
 * @description
 * Servicio que publica el catálogo de normas que el modal de alta de examen
 * muestra como casillas de verificación. Es de solo lectura.
 *
 * Va al lado de {@see \App\Services\Material\ListarMaterialesService} y por el
 * mismo motivo: el catálogo se muestra completo y sin buscador, así que no
 * necesita un `termino` normalizado para que el navegador compare.
 *
 * @see  App\Services\Material\ListarMaterialesService
 * @see  resources/views/components/ui/modal-crear-examen.blade.php
 *
 * @changelog
 * - 2026-10-05  [Alex Candia]  feat: creación inicial del servicio.
 */

namespace App\Services\Norma;

use App\Models\Norma;

class ListarNormasService
{
    /**
     * Normas del catálogo, ordenadas por detalle.
     *
     * @return list<array{id: int, detalle: string}>
     */
    public function ejecutar(): array
    {
        return Norma::query()
            ->orderBy('detalle_norma')
            ->get(['id_norma', 'detalle_norma'])
            ->map(fn (Norma $norma): array => [
                'id' => $norma->id_norma,
                'detalle' => $norma->detalle_norma,
            ])
            ->values()
            ->all();
    }
}

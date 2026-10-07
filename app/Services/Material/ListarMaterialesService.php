<?php

/**
 * @file    ListarMaterialesService.php
 *
 * @author  Alex Candia <alex.leonar.candia@gmail.com>
 *
 * @created 2026-10-05
 *
 * @updated 2026-10-05
 *
 * @description
 * Servicio que publica el catálogo de materiales que el modal de alta de examen
 * muestra como casillas de verificación. Es de solo lectura.
 *
 * A diferencia del índice de ambientes, acá no hace falta un campo `termino`
 * normalizado: el catálogo se muestra completo y sin buscador, así que el
 * navegador nunca tiene que comparar contra un texto.
 *
 * @see  App\Services\Ambiente\ListarAmbientesService
 * @see  resources/views/components/ui/modal-crear-examen.blade.php
 *
 * @changelog
 * - 2026-10-05  [Alex Candia]  feat: creación inicial del servicio.
 */

namespace App\Services\Material;

use App\Models\Material;

class ListarMaterialesService
{
    /**
     * Materiales del catálogo, ordenados por descripción.
     *
     * @return list<array{id: int, descripcion: string}>
     */
    public function ejecutar(): array
    {
        return Material::query()
            ->orderBy('descripcion')
            ->get(['id_material', 'descripcion'])
            ->map(fn (Material $material): array => [
                'id' => $material->id_material,
                'descripcion' => $material->descripcion,
            ])
            ->values()
            ->all();
    }
}

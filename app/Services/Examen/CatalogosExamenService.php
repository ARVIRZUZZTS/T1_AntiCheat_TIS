<?php

/**
 * @file    CatalogosExamenService.php
 *
 * @author  Valery D. Ortuno P <valerydariana98@gmail.com>
 *
 * @created 2026-10-26
 *
 * @description
 * Catálogos del alta de examen (ambientes, materiales y normas) cacheados
 * unos minutos. El formulario los muestra enteros y la validación del alta
 * revisa que lo elegido exista: sin la caché eran tres consultas al abrir el
 * formulario y una más por cada opción marcada al guardar, y cada viaje a la
 * base remota pesa (~380 ms).
 *
 * Se puede cachear porque la aplicación no modifica estos catálogos; si se
 * cargan a mano en la base, aparecen al vencer la caché (CACHE_MINUTOS).
 * Las listas las siguen armando ListarAmbientesService,
 * ListarMaterialesService y ListarNormasService.
 *
 * @changelog
 * - 2026-10-26  [Valery D. Ortuno P]  perf: creación inicial del servicio.
 *
 * @see  App\Http\Controllers\ExamenController::crear()
 * @see  App\Http\Requests\StoreExamenRequest
 */

namespace App\Services\Examen;

use App\Services\Ambiente\ListarAmbientesService;
use App\Services\Material\ListarMaterialesService;
use App\Services\Norma\ListarNormasService;
use Illuminate\Support\Facades\Cache;

class CatalogosExamenService
{
    /** Minutos que se cachea cada catálogo. */
    public const CACHE_MINUTOS = 10;

    /** Prefijo de las claves de caché, una por catálogo. */
    public const CACHE_PREFIJO = 'catalogos.examen.';

    public function __construct(
        private readonly ListarAmbientesService $ambientes,
        private readonly ListarMaterialesService $materiales,
        private readonly ListarNormasService $normas,
    ) {}

    /**
     * @return list<array{id: int, nombre: string, termino: string}>
     */
    public function ambientes(): array
    {
        return $this->recordar('ambientes', fn (): array => $this->ambientes->ejecutar());
    }

    /**
     * @return list<array{id: int, descripcion: string}>
     */
    public function materiales(): array
    {
        return $this->recordar('materiales', fn (): array => $this->materiales->ejecutar());
    }

    /**
     * @return list<array{id: int, detalle: string}>
     */
    public function normas(): array
    {
        return $this->recordar('normas', fn (): array => $this->normas->ejecutar());
    }

    /**
     * Ids válidos de un catálogo, para la regla `in` de la validación.
     *
     * @param  'ambientes'|'materiales'|'normas'  $catalogo
     * @return list<int>
     */
    public function ids(string $catalogo): array
    {
        return array_column($this->{$catalogo}(), 'id');
    }

    private function recordar(string $catalogo, callable $consulta): array
    {
        return Cache::remember(self::CACHE_PREFIJO.$catalogo, now()->addMinutes(self::CACHE_MINUTOS), $consulta);
    }
}

<?php

namespace App\Support\DTO;

readonly class ExamenCursoDTO
{
    /**
     * @param  list<int>  $normasIds
     * @param  list<string>  $normasPersonalizadas
     * @param  list<int>  $materialesIds
     * @param  list<string>  $materialesPersonalizados
     */
    public function __construct(
        public string $nombre,
        public ?string $fecha,
        public array $normasIds,
        public array $normasPersonalizadas,
        public array $materialesIds,
        public array $materialesPersonalizados,
    ) {}
}

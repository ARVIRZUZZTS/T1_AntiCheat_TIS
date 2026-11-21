<?php

namespace App\Support\DTO;

readonly class DetalleCursoDTO
{
    public function __construct(
        public int $idCurso,
        public string $materia,
        public string $grupo,
        public int $gestion,
        public string $gestionNombre,
        public int $anio,
        public string $nombre,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->idCurso,
            'materia' => $this->materia,
            'grupo' => $this->grupo,
            'gestion' => $this->gestion,
            'gestion_nombre' => $this->gestionNombre,
            'anio' => $this->anio,
            'nombre' => $this->nombre,
        ];
    }
}

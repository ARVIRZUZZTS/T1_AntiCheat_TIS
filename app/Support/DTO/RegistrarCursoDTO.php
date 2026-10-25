<?php

namespace App\Support\DTO;

readonly class RegistrarCursoDTO
{
    /** @param list<ExamenCursoDTO> $examenes */
    public function __construct(
        public string $materia,
        public string $grupo,
        public GestionCursoDTO $gestion,
        public int $idAmbiente,
        public array $examenes,
    ) {}

    public function nombreCurso(): string
    {
        return $this->materia.' - Grupo '.$this->grupo;
    }
}

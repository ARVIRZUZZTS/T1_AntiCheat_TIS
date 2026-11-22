<?php

namespace App\Support\DTO;

use App\Support\NombreCurso;

readonly class ActualizarCursoDTO
{
    public function __construct(
        public string $materia,
        public string $grupo,
        public GestionCursoDTO $gestion,
    ) {}

    public function nombreCurso(): string
    {
        return NombreCurso::formatear($this->materia, $this->grupo);
    }
}

<?php

namespace App\Support\DTO;

readonly class ResumenCursoDTO
{
    public function __construct(
        public int $idCurso,
        public string $materia,
        public string $grupo,
        public int $idGestion,
        public string $nombreGestion,
        public int $anio,
        public int $idAmbiente,
        public string $nombreAmbiente,
        public int $cantidadExamenes,
    ) {}

    /**
     * @return array{
     *     id_curso: int,
     *     materia: string,
     *     grupo: string,
     *     gestion: array{id: int, nombre: string, anio: int},
     *     ambiente: array{id: int, nombre: string},
     *     cantidad_examenes: int
     * }
     */
    public function toArray(): array
    {
        return [
            'id_curso' => $this->idCurso,
            'materia' => $this->materia,
            'grupo' => $this->grupo,
            'gestion' => [
                'id' => $this->idGestion,
                'nombre' => $this->nombreGestion,
                'anio' => $this->anio,
            ],
            'ambiente' => [
                'id' => $this->idAmbiente,
                'nombre' => $this->nombreAmbiente,
            ],
            'cantidad_examenes' => $this->cantidadExamenes,
        ];
    }
}

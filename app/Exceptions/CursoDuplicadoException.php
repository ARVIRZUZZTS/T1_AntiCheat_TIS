<?php

namespace App\Exceptions;

use App\Support\DTO\ActualizarCursoDTO;
use RuntimeException;
use Throwable;

class CursoDuplicadoException extends RuntimeException
{
    public const MENSAJE = 'Este curso ya existe en la gestión y el año elegidos.';

    public function __construct(private readonly ActualizarCursoDTO $datos, ?Throwable $previous = null)
    {
        parent::__construct(self::MENSAJE, 0, $previous);
    }

    /** @return array<string, int|string> */
    public function campos(): array
    {
        return [
            'materia' => $this->datos->materia,
            'grupo' => $this->datos->grupo,
            'gestion' => $this->datos->gestion->idTipoGestion,
            'anio' => $this->datos->gestion->anio,
        ];
    }

    /** @return array<string, list<string>> */
    public function errores(): array
    {
        return [
            'materia' => [self::MENSAJE],
            'grupo' => [self::MENSAJE],
            'gestion' => [self::MENSAJE],
            'anio' => [self::MENSAJE],
        ];
    }
}

<?php

namespace App\Exceptions;

use RuntimeException;

class RegistroDuplicadoException extends RuntimeException
{
    /**
     * @param  array<string, string>  $errores
     */
    public function __construct(private readonly array $errores)
    {
        parent::__construct('Registro duplicado.');
    }

    /**
     * @return array<string, string>
     */
    public function errores(): array
    {
        return $this->errores;
    }
}

<?php

/**
 * @file    ResultadoCreacionAuxiliar.php
 *
 * @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
 *
 * @created 2026-11-27
 *
 * @description
 * Resultado del alta de un auxiliar: la cuenta creada y si el correo con las
 * credenciales llegó a enviarse. Si no se pudo enviar, la cuenta igual quedó
 * creada y hay que avisar que se reenvíe desde la lista.
 *
 * @changelog
 * - 2026-11-27  [Diego Tejerina]  feat: creación inicial.
 *
 * @see  App\Services\Auxiliar\CrearAuxiliarService
 */

namespace App\Support\DTO;

use App\Models\Usuario;

final readonly class ResultadoCreacionAuxiliar
{
    /**
     * @param  Usuario  $auxiliar              Auxiliar recién creado.
     * @param  bool     $credencialesEnviadas  true si el correo al auxiliar se envió.
     */
    public function __construct(
        public Usuario $auxiliar,
        public bool $credencialesEnviadas,
    ) {
    }
}

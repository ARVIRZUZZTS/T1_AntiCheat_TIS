<?php

/**
 * @file    Motivo.php
 *
 * @author  Valery D. Ortuno P. <valerydariana98@gmail.com>
 *
 * @created 2026-09-29
 *
 * @updated 2026-10-10
 *
 * @description
 * Enum de dominio con los motivos por los que se puede registrar una incidencia
 * en la central de riesgos (tipo `motivo` de la base de datos).
 *
 * El `value` de cada caso es el texto exacto que guarda PostgreSQL, con
 * espacios y en minúscula; `etiqueta()` devuelve el texto que ve la persona.
 * El motivo `Otro` es genérico: cuando se elige, la descripción del hecho es
 * obligatoria y va en `central_riesgo.detalle_motivo`.
 *
 * @see  App\Livewire\Monitoreo\RegistrarIncidencia
 *
 * @changelog
 * - 2026-09-29  [Valery D. Ortuno P]  feat: creación inicial del enum (#70).
 * - 2026-10-10  [Alex Candia]  fix: los valores pasan a llevar espacios
 *   (`intento de ingreso no autorizado`), que es como los guarda el enum `motivo`
 *   de PostgreSQL. Con guiones bajos el registro de incidencias fallaba al
 *   insertar, porque ningún valor del enum existía en la base.
 */

namespace App\Enums;

enum Motivo: string
{
    case IntentoDeIngresoNoAutorizado = 'intento de ingreso no autorizado';

    case UsoDeDispositivosElectronicos = 'uso de dispositivos electronicos';

    case CopiaOIntercambioDeRespuestas = 'copia o intercambio de respuestas';

    case UsoDeMaterialNoAutorizado = 'uso de material no autorizado';

    case SuplantacionDeIdentidad = 'suplantacion de identidad';

    case Otro = 'otro';

    /**
     * Texto que se muestra en el selector del formulario.
     */
    public function etiqueta(): string
    {
        return match ($this) {
            self::IntentoDeIngresoNoAutorizado => 'Intento de Ingreso a examen no autorizado',
            self::UsoDeDispositivosElectronicos => 'Uso de dispositivos electrónicos no autorizados',
            self::CopiaOIntercambioDeRespuestas => 'Copia o intercambio de respuestas',
            self::UsoDeMaterialNoAutorizado => 'Uso de material no autorizado',
            self::SuplantacionDeIdentidad => 'Suplantación de identidad',
            self::Otro => 'Otro',
        };
    }
}

<?php

/**
 * @file    Motivo.php
 *
 * @author  Valery D. Ortuno P. <valerydariana98@gmail.com>
 *
 * @created 2026-09-29
 *
 * @updated 2026-09-29
 *
 * @description
 * Enum de dominio con los motivos por los que se puede registrar una incidencia
 * en la central de riesgos (tipo `motivo` de la base de datos).
 *
 * El `value` de cada caso es el código estable que viaja en el formulario y se
 * guarda en `central_riesgo.motivo`; `etiqueta()` devuelve el texto que ve la
 * persona. El motivo `Otro` es genérico: cuando se elige, la descripción del
 * hecho es obligatoria y va en `central_riesgo.detalle_motivo`.
 *
 * @see  App\Livewire\Monitoreo\RegistrarIncidencia
 *
 * @changelog
 * - 2026-09-29  [Valery D. Ortuno P]  feat: creación inicial del enum (#70).
 */

namespace App\Enums;

enum Motivo: string
{
    case IntentoDeIngresoNoAutorizado = 'intento_de_ingreso_no_autorizado';

    case UsoDeDispositivosElectronicos = 'uso_de_dispositivos_electronicos';

    case CopiaOIntercambioDeRespuestas = 'copia_o_intercambio_de_respuestas';

    case UsoDeMaterialNoAutorizado = 'uso_de_material_no_autorizado';

    case SuplantacionDeIdentidad = 'suplantacion_de_identidad';

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

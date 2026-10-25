<?php

/**
 * @file    Motivo.php
 *
 * @author  Valery D. Ortuno P. <valerydariana98@gmail.com>
 *
 * @created 2026-09-29
 *
 * @updated 2026-10-10
 * @updated 2026-10-25
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
 * Los motivos que se ofrecen dependen de dónde se registra la incidencia en el
 * monitor en vivo: `paraIngreso()` al registrar el ingreso y `paraMonitor()`
 * durante el examen. Es la única fuente de esas dos listas: la usan los modales
 * y la validación del servidor.
 *
 * @see  App\Services\CentralRiesgo\RegistrarIncidenciaService
 *
 * @changelog
 * - 2026-09-29  [Valery D. Ortuno P]  feat: creación inicial del enum (#70).
 * - 2026-10-10  [Alex Candia]  fix: los valores pasan a llevar espacios
 *   (`intento de ingreso no autorizado`), que es como los guarda el enum `motivo`
 *   de PostgreSQL. Con guiones bajos el registro de incidencias fallaba al
 *   insertar, porque ningún valor del enum existía en la base.
 * - 2026-10-25  [Valery D. Ortuno P]  feat: motivo `EnExamenSinRegistroDeIngreso`,
 *   listas paraIngreso() y paraMonitor() (antes repetidas en el modal), y
 *   etiquetas sin acentos; "Intento de ingreso a examen no autorizado" pasa a
 *   "Intento de ingreso sin autorizacion" (HU 5).
 */

namespace App\Enums;

enum Motivo: string
{
    case IntentoDeIngresoNoAutorizado = 'intento de ingreso no autorizado';

    case IntentoDeIngresoDuplicado = 'intento de ingreso duplicado';

    case AulaEquivocada = 'aula equivocada';

    case IntentoDeIngresoMaterialNoAutorizado = 'intento de ingreso de material no autorizado';

    case UsoDeDispositivosElectronicos = 'uso de dispositivos electronicos';

    case CopiaOIntercambioDeRespuestas = 'copia o intercambio de respuestas';

    case UsoDeMaterialNoAutorizado = 'uso de material no autorizado';

    case SuplantacionDeIdentidad = 'suplantacion de identidad';

    case EnExamenSinRegistroDeIngreso = 'en examen sin registro de ingreso';

    case Otro = 'otro';

    /**
     * Texto que se muestra en los formularios y listas.
     */
    public function etiqueta(): string
    {
        return match ($this) {
            self::IntentoDeIngresoNoAutorizado => 'Intento de ingreso sin autorizacion',
            self::IntentoDeIngresoDuplicado => 'Intento de ingreso duplicado',
            self::AulaEquivocada => 'Aula equivocada',
            self::IntentoDeIngresoMaterialNoAutorizado => 'Intento de ingreso con material no autorizado',
            self::UsoDeDispositivosElectronicos => 'Uso de dispositivos electronicos no autorizados',
            self::CopiaOIntercambioDeRespuestas => 'Copia o intercambio de respuestas',
            self::UsoDeMaterialNoAutorizado => 'Uso de material no autorizado',
            self::SuplantacionDeIdentidad => 'Suplantacion de identidad',
            self::EnExamenSinRegistroDeIngreso => 'En examen sin registro de ingreso',
            self::Otro => 'Otro',
        };
    }

    /**
     * Motivos que se ofrecen al registrar el ingreso al examen.
     *
     * @return list<self>
     */
    public static function paraIngreso(): array
    {
        return [
            self::IntentoDeIngresoNoAutorizado,
            self::IntentoDeIngresoDuplicado,
            self::AulaEquivocada,
            self::IntentoDeIngresoMaterialNoAutorizado,
            self::SuplantacionDeIdentidad,
            self::Otro,
        ];
    }

    /**
     * Motivos que se ofrecen durante el examen (vista Monitor).
     *
     * @return list<self>
     */
    public static function paraMonitor(): array
    {
        return [
            self::UsoDeDispositivosElectronicos,
            self::CopiaOIntercambioDeRespuestas,
            self::UsoDeMaterialNoAutorizado,
            self::SuplantacionDeIdentidad,
            self::EnExamenSinRegistroDeIngreso,
            self::Otro,
        ];
    }
}

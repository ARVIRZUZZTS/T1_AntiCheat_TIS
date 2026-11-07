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
 * @updated 2026-11-07
 *
 * @description
 * Enum de dominio con los motivos por los que se puede registrar una incidencia
 * en la central de riesgos (tipo `motivo` de la base de datos).
 *
 * El `value` de cada caso es el texto exacto que guarda PostgreSQL, con
 * espacios y en minúscula; `etiqueta()` devuelve el texto que ve la persona.
 * Una incidencia puede tener varios motivos (`central_riesgo_motivo`). El
 * motivo `Otro` es genérico: cuando está entre los elegidos, la descripción
 * del hecho es obligatoria y va en `central_riesgo.detalle_motivo`.
 * `lista()` y `etiquetas()` ordenan y muestran varios motivos juntos
 * ("Motivo 1 · Motivo 2").
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
 * - 2026-11-07  [Valery D. Ortuno P]  feat: lista() y etiquetas() para las
 *   incidencias con varios motivos.
 */

namespace App\Enums;

enum Motivo: string
{
    /** Separador al mostrar varios motivos: "Motivo 1 · Motivo 2". */
    public const SEPARADOR = ' · ';

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

    /**
     * Motivos sin repetir y en el orden del enum, que es el de los
     * formularios. Acepta casos, sus valores o el texto separado por comas
     * que arma MotivosIncidencia::seleccionar(); lo que no es un motivo se
     * descarta.
     *
     * @param  iterable<self|string>|string|null  $motivos
     * @return list<self>
     */
    public static function lista(iterable|string|null $motivos): array
    {
        if ($motivos === null) {
            return [];
        }

        if (is_string($motivos)) {
            $motivos = explode(',', $motivos);
        }

        $elegidos = [];

        foreach ($motivos as $motivo) {
            $caso = $motivo instanceof self ? $motivo : self::tryFrom(trim((string) $motivo));

            if ($caso !== null) {
                $elegidos[$caso->value] = true;
            }
        }

        return array_values(array_filter(
            self::cases(),
            fn (self $caso): bool => isset($elegidos[$caso->value]),
        ));
    }

    /**
     * Etiquetas de varios motivos en una línea: "Motivo 1 · Motivo 2".
     *
     * @param  iterable<self|string>|string|null  $motivos  Lo mismo que acepta lista().
     */
    public static function etiquetas(iterable|string|null $motivos): string
    {
        return implode(self::SEPARADOR, array_map(
            fn (self $motivo): string => $motivo->etiqueta(),
            self::lista($motivos),
        ));
    }
}

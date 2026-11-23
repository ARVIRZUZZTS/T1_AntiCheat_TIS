<?php

/**
 * @file    PlanificacionRespaldo.php
 *
 * @author  T1 - Tech One SRL
 *
 * @created 2026-11-22
 *
 * @updated 2026-11-22
 *
 * @description
 * Regla de calendario del respaldo automático quincenal (HU 33). El Scheduler
 * dispara el comando todos los martes a las 08:20 en `America/La_Paz`; esta
 * clase decide, de esos martes, cuáles corresponden al respaldo: a partir del
 * martes 24/11/2026 y uno sí, uno no (semanas pares desde el inicio).
 *
 * Así: corre el 24/11/2026, se salta el 01/12/2026, corre el 08/12/2026, etc.
 *
 * Es lógica pura —sin base de datos ni reloj propio— para poder probar la
 * elegibilidad de cualquier fecha sin depender del calendario real; el "ahora"
 * siempre llega como parámetro desde el Scheduler o desde las pruebas.
 *
 * @see  App\Services\Backup\RespaldarBaseDatosService
 *
 * @changelog
 * - 2026-11-22  [T1]  feat: regla quincenal del respaldo automático (#HU33).
 */

namespace App\Services\Backup;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

class PlanificacionRespaldo
{
    /** Zona horaria en la que se evalúa el calendario del respaldo. */
    public const ZONA = 'America/La_Paz';

    /** Hora del día (24h) en que corre el respaldo los martes elegibles. */
    public const HORA = '08:20';

    /** Día de la semana del respaldo (2 = martes, según Carbon). */
    public const DIA_SEMANA = CarbonInterface::TUESDAY;

    /** Primer martes en que corre el respaldo; desde aquí se cuentan las semanas. */
    public const FECHA_INICIO = '2026-11-24';

    private const DIAS_POR_SEMANA = 7;

    private const SEGUNDOS_POR_DIA = 86400;

    /**
     * Indica si un martes dado corresponde a un respaldo quincenal.
     *
     * Es elegible cuando la fecha es igual o posterior al inicio y el número de
     * semanas completas transcurridas desde el inicio es par (0, 2, 4, ...).
     * Se compara por día (sin la hora) para que el resultado no dependa del
     * minuto exacto en que se evalúe.
     *
     * @param  CarbonInterface  $momento  Momento a evaluar (normalmente "ahora").
     *
     * @return bool  True si ese día toca respaldo.
     *
     * @author T1 - Tech One SRL
     *
     * @since  2026-11-22
     */
    public function esElegible(CarbonInterface $momento): bool
    {
        $inicio = CarbonImmutable::parse(self::FECHA_INICIO, self::ZONA)->startOfDay();
        $dia = CarbonImmutable::parse($momento->format('Y-m-d'), self::ZONA)->startOfDay();

        if ($dia->lessThan($inicio)) {
            return false;
        }

        $dias = intdiv($dia->getTimestamp() - $inicio->getTimestamp(), self::SEGUNDOS_POR_DIA);
        $semanas = intdiv($dias, self::DIAS_POR_SEMANA);

        return $semanas % 2 === 0;
    }
}

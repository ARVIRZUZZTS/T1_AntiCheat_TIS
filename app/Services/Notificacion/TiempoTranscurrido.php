<?php

/**
 * @file    TiempoTranscurrido.php
 *
 * @author  T1 - Tech One SRL
 *
 * @created 2026-10-11
 *
 * @description
 * Formatea cuánto tiempo pasó desde una fecha en español legible ("Hace 20
 * minutos", "Hace 2 horas"). Es lógica pura —sin base de datos—, compartida por
 * el listado de notificaciones (HU 12 · T1).
 *
 * Se usa Carbon con locale `es` y sintaxis relativa a "ahora" para que el
 * resultado sea "hace X"; la primera letra se sube a mayúscula para el texto
 * que ve la persona. El "ahora" es inyectable para poder probar sin depender
 * del reloj.
 *
 * @changelog
 * - 2026-10-11  [T1]  feat: helper de tiempo transcurrido (#HU12).
 */

namespace App\Services\Notificacion;

use Carbon\CarbonInterface;

class TiempoTranscurrido
{
    /**
     * Texto legible del tiempo transcurrido entre una fecha y "ahora".
     *
     * @param  CarbonInterface  $fecha  Momento del que se mide el tiempo.
     * @param  ?CarbonInterface  $ahora  Referencia; por defecto, el momento actual.
     * @return string Texto en español, con la inicial en mayúscula.
     */
    public function enEspanol(CarbonInterface $fecha, ?CarbonInterface $ahora = null): string
    {
        $referencia = $ahora ?? now();

        $texto = $fecha
            ->locale('es')
            ->diffForHumans($referencia, [
                'parts' => 1,
                'syntax' => CarbonInterface::DIFF_RELATIVE_TO_NOW,
            ]);

        return ucfirst($texto);
    }
}

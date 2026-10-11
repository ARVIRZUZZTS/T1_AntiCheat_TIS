<?php

/**
 * @file    TiempoTranscurridoTest.php
 *
 * @author  T1 - Tech One SRL
 *
 * @created 2026-10-11
 *
 * @description Pruebas del formato de tiempo transcurrido en español (HU 12 · T1).

 *
 * @changelog
 * - 2026-10-11  [T1]  test: tiempos legibles.
 */

namespace Tests\Unit\Services\Notificacion;

use App\Services\Notificacion\TiempoTranscurrido;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class TiempoTranscurridoTest extends TestCase
{
    private TiempoTranscurrido $tiempo;

    private Carbon $ahora;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tiempo = new TiempoTranscurrido;
        $this->ahora = Carbon::parse('2026-10-11 12:00:00');
    }

    public function test_formatea_minutos_horas_y_dias_en_espanol(): void
    {
        $this->assertSame('Hace 20 minutos', $this->tiempo->enEspanol($this->ahora->copy()->subMinutes(20), $this->ahora));
        $this->assertSame('Hace 1 minuto', $this->tiempo->enEspanol($this->ahora->copy()->subMinute(), $this->ahora));
        $this->assertSame('Hace 2 horas', $this->tiempo->enEspanol($this->ahora->copy()->subHours(2), $this->ahora));
        $this->assertSame('Hace 1 día', $this->tiempo->enEspanol($this->ahora->copy()->subDay(), $this->ahora));
    }
}

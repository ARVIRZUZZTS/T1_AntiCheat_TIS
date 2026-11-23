<?php

use App\Services\Backup\PlanificacionRespaldo;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

/*
|--------------------------------------------------------------------------
| Respaldo automático quincenal de la base de datos (HU 33)
|--------------------------------------------------------------------------
|
| Corre los martes a las 08:20 en `America/La_Paz`. El filtro `when()` deja
| pasar solo los martes quincenales a partir del 24/11/2026 (semanas pares
| desde esa fecha): corre el 24/11, se salta el 01/12, corre el 08/12, etc.
|
*/

Schedule::command('db:respaldar-automatico')
    ->weeklyOn(PlanificacionRespaldo::DIA_SEMANA, PlanificacionRespaldo::HORA)
    ->timezone(PlanificacionRespaldo::ZONA)
    ->when(fn (): bool => (new PlanificacionRespaldo)->esElegible(CarbonImmutable::now(PlanificacionRespaldo::ZONA)));

<?php

/**
 * @file    RespaldarBaseDatosCommand.php
 *
 * @author  T1 - Tech One SRL
 *
 * @created 2026-11-22
 *
 * @updated 2026-11-22
 *
 * @description
 * Comando Artisan del respaldo automático de la base de datos (HU 33). El
 * Scheduler lo dispara los martes elegibles a las 08:20 (`America/La_Paz`).
 *
 * Orquesta, no decide: delega el dump y la retención en
 * `RespaldarBaseDatosService` y el aviso en `NotificacionService`.
 *
 *   - Éxito: registra fecha, hora de finalización y ruta en el log, y notifica
 *     a todos los docentes (solo lectura, sin acciones).
 *   - Fallo: registra el error con `Log::error` y NO envía ninguna notificación;
 *     el servicio ya limpió cualquier archivo incompleto.
 *
 * @see  App\Services\Backup\RespaldarBaseDatosService
 * @see  App\Services\Notificacion\NotificacionService
 *
 * @changelog
 * - 2026-11-22  [T1]  feat: comando de respaldo automático quincenal (#HU33).
 */

namespace App\Console\Commands;

use App\Services\Backup\PlanificacionRespaldo;
use App\Services\Backup\RespaldarBaseDatosService;
use App\Services\Notificacion\NotificacionService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class RespaldarBaseDatosCommand extends Command
{
    /** @var string */
    protected $signature = 'db:respaldar-automatico';

    /** @var string */
    protected $description = 'Genera un respaldo comprimido de la base de datos y avisa a los docentes.';

    /**
     * Ejecuta el respaldo y, si tiene éxito, notifica a los docentes.
     *
     * @param  RespaldarBaseDatosService  $respaldo  Genera el dump y rota los antiguos.
     * @param  NotificacionService  $notificaciones  Crea los avisos de los docentes.
     *
     * @return int  Command::SUCCESS si respaldó, Command::FAILURE si falló.
     *
     * @author T1 - Tech One SRL
     *
     * @since  2026-11-22
     */
    public function handle(RespaldarBaseDatosService $respaldo, NotificacionService $notificaciones): int
    {
        $momento = CarbonImmutable::now(PlanificacionRespaldo::ZONA);

        try {
            $ruta = $respaldo->ejecutar($momento);
        } catch (Throwable $error) {
            Log::error('Respaldo automático de la base de datos fallido.', [
                'momento' => $momento->toDateTimeString(),
                'error' => $error->getMessage(),
            ]);

            $this->error('El respaldo automático falló. Revisa el log del sistema.');

            return self::FAILURE;
        }

        $avisos = $notificaciones->notificarRespaldoBaseDatos($momento);

        Log::info('Respaldo automático de la base de datos completado.', [
            'momento' => $momento->toDateTimeString(),
            'archivo' => $ruta,
            'docentes_notificados' => count($avisos),
        ]);

        $this->info("Respaldo generado en {$ruta}.");

        return self::SUCCESS;
    }
}

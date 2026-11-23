<?php

/**
 * @file    backup.php
 *
 * @author  T1 - Tech One SRL
 *
 * @created 2026-11-22
 *
 * @updated 2026-11-22
 *
 * @description
 * Configuración de los respaldos automáticos de la base de datos (HU 33).
 * Centraliza dónde se guardan los dumps comprimidos y cuántos se conservan,
 * para no hardcodear esos valores en el servicio ni en el comando. Todo es
 * sobreescribible por entorno con variables `.env`, de modo que las pruebas
 * puedan apuntar el directorio a una carpeta temporal sin tocar Supabase.
 *
 * @changelog
 * - 2026-11-22  [T1]  feat: configuración de respaldos quincenales (#HU33).
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Directorio de respaldos
    |--------------------------------------------------------------------------
    |
    | Carpeta donde se escriben los archivos `.sql.gz`. Por defecto vive bajo
    | `storage/app/backups`, que está ignorado por git para no versionar dumps.
    |
    */

    'directorio' => env('BACKUP_DIRECTORIO', storage_path('app/backups')),

    /*
    |--------------------------------------------------------------------------
    | Máximo de respaldos conservados
    |--------------------------------------------------------------------------
    |
    | Política de retención: tras cada respaldo exitoso se conservan como mucho
    | estos archivos (los más recientes) y se eliminan los más antiguos.
    |
    */

    'maximo' => (int) env('BACKUP_MAXIMO', 3),

];

<?php

/**
 * @file    DatabaseSeeder.php
 *
 * @author  T1 - Tech One SRL
 *
 * @created 2026-09-26
 *
 * @updated 2026-09-28
 *
 * @description
 * Seed de la base de desarrollo.
 *
 * Antes esto era el seeder por defecto de Laravel y creaba un `User` de
 * prueba en la tabla `users`, que en este proyecto no se usa: la tabla de
 * personas es `usuario` y la de autenticación también (ver `config/auth.php`).
 * Ahora siembra los mismos usuarios de prueba que trae
 * `database/sql/002_seed_data.sql`, para que `php artisan db:seed` y el
 * `.sql` dejen de ser dos fuentes de verdad distintas.
 *
 * Es idempotente por `cod_sis`: se puede correr las veces que quieras.
 *
 * @changelog
 * - 2026-09-26  [T1]  feat: seeder por defecto de Laravel.
 * - 2026-09-28  [T1]  fix: sembrar `usuario` en vez de la tabla `users`, que
 *   el proyecto no usa, para que el modelo `User` quede huérfano.
 */

namespace Database\Seeders;

use App\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Usuarios de prueba. El login es por `cod_sis`.
     *
     * @var array<int, array{0: int, 1: string, 2: string, 3: string, 4: string}>
     */
    private array $usuarios = [
        [1, 'DOC001', 'pass123', 'Roberto', 'Silva'],
        [2, 'DOC002', 'pass456', 'Patricia', 'Rojas'],
        [3, 'AUX001', 'pass789', 'Diego', 'Mendoza'],
        [4, 'AUX002', 'pass321', 'Sofia', 'Castro'],
        [5, 'DOC003', 'pass654', 'Fernando', 'Vargas'],
    ];

    public function run(): void
    {
        foreach ($this->usuarios as [$id, $codSis, $clave, $nombre, $apellido]) {
            Usuario::query()->updateOrCreate(
                ['cod_sis' => $codSis],
                [
                    'id_usuario' => $id,
                    'password' => Hash::make($clave),
                    'nombre_usuario' => $nombre,
                    'apellido' => $apellido,
                ]
            );
        }
    }
}

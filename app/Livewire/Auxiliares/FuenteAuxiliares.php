<?php

namespace App\Livewire\Auxiliares;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class FuenteAuxiliares
{
    public const USAR_DATOS_SIMULADOS = false;

    public const DATOS_SIMULADOS = [
        [
            'id' => 12,
            'nombre' => 'Ana',
            'apellido' => 'Pérez',
            'codigoSis' => '202012345',
            'curso' => 'Programación I',
            'estado' => 'ACTIVO',
        ],
        [
            'id' => 13,
            'nombre' => 'Luis',
            'apellido' => 'Gómez',
            'codigoSis' => '202012346',
            'curso' => 'Base de Datos',
            'estado' => 'BAJA',
        ],
        [
            'id' => 14,
            'nombre' => 'Carla',
            'apellido' => 'Rojas',
            'codigoSis' => '202012347',
            'curso' => 'Programación I',
            'estado' => 'ACTIVO',
        ],
    ];

    public function usarDatosSimulados(): bool
    {
        return self::USAR_DATOS_SIMULADOS;
    }

    /**
     * @return list<array{id: int, nombre: string, apellido: string, codigoSis: string, curso: string, estado: string}>
     */
    public function obtener(): array
    {
        if ($this->usarDatosSimulados()) {
            return self::DATOS_SIMULADOS;
        }

        $respuesta = Http::acceptJson()
            ->withHeaders(['Cookie' => request()->header('Cookie', '')])
            ->get(url('/api/docentes/me/auxiliares'));

        if ($respuesta->status() === 401) {
            throw new RuntimeException('Sin sesión.', 401);
        }

        if ($respuesta->status() === 403) {
            throw new RuntimeException('Rol no autorizado.', 403);
        }

        if ($respuesta->failed()) {
            throw new RuntimeException('No se pudo consultar la lista de auxiliares.', $respuesta->status());
        }

        $datos = $respuesta->json();

        if (! is_array($datos)) {
            throw new RuntimeException('Respuesta inválida de la lista de auxiliares.', 500);
        }

        return array_values($datos);
    }
}

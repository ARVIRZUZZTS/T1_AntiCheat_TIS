<?php

/**
 * @file    ListarSolicitudesRegistroService.php
 *
 * @author  Valery D. Ortuno P <valerydariana98@gmail.com>
 *
 * @created 2026-11-08
 *
 * @description
 * Solicitudes de registro de docentes pendientes de validación (HU 26): una
 * por cada cuenta `pendiente`, de la más antigua a la más reciente, con sus
 * datos, los docentes que ya la aprobaron y si el docente que mira ya la
 * aprobó.
 *
 * Es una sola consulta (las aprobaciones entran por join y se agrupan acá):
 * cada viaje a la base remota pesa. Una solicitud pendiente no tiene
 * desaprobaciones, porque con una sola queda rechazada.
 *
 * contarPendientes() es el número del sidebar: se cachea porque el sidebar se
 * dibuja en cada página, y se olvida cuando se registra o se valida una cuenta.
 *
 * @changelog
 * - 2026-11-08  [Valery D. Ortuno P]  feat: creación inicial (HU 26).
 *
 * @see  App\Livewire\Docentes\ValidarRegistros
 * @see  App\Services\Docente\ValidarRegistroDocenteService
 */

namespace App\Services\Docente;

use App\Enums\EstadoCuenta;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ListarSolicitudesRegistroService
{
    /** Clave de caché del número de solicitudes pendientes. */
    public const CLAVE_PENDIENTES = 'docentes.solicitudes_pendientes';

    /** Segundos que dura el número cacheado si nadie lo olvida antes. */
    private const TTL_PENDIENTES = 300;

    /**
     * @param  int  $idDocente  Docente que mira la lista.
     * @return Collection<int, array{id: int, nombre: string, correo: string, sis: string, fecha: string, aprobaciones: list<string>, ya_aprobo: bool}>
     */
    public function ejecutar(int $idDocente): Collection
    {
        $filas = DB::table('usuario as u')
            ->leftJoin('validacion_registro as v', function ($join): void {
                $join->on('v.id_solicitante', '=', 'u.id_usuario')->where('v.decision', 'aprobado');
            })
            ->leftJoin('usuario as d', 'd.id_usuario', '=', 'v.id_validador')
            ->where('u.estado_cuenta', EstadoCuenta::Pendiente->value)
            ->orderBy('u.fecha_creacion')
            ->orderBy('u.id_usuario')
            ->orderBy('v.fecha')
            ->get([
                'u.id_usuario', 'u.nombre', 'u.apellido', 'u.nombre_usuario', 'u.correo', 'u.cod_sis', 'u.fecha_creacion',
                'v.id_validador', 'd.nombre as validador_nombre', 'd.apellido as validador_apellido', 'd.nombre_usuario as validador_usuario',
            ]);

        return $filas->groupBy('id_usuario')->map(function (Collection $grupo) use ($idDocente): array {
            $u = $grupo->first();
            $aprobaciones = $grupo->whereNotNull('id_validador');

            return [
                'id' => (int) $u->id_usuario,
                'nombre' => $this->nombreCompleto($u->nombre ?: $u->nombre_usuario, $u->apellido),
                'correo' => (string) $u->correo,
                'sis' => (string) $u->cod_sis,
                'fecha' => $u->fecha_creacion ? Carbon::parse($u->fecha_creacion)->format('d/m/Y H:i') : '-',
                'aprobaciones' => $aprobaciones
                    ->map(fn (object $v): string => $this->nombreCompleto($v->validador_nombre ?: $v->validador_usuario, $v->validador_apellido))
                    ->values()->all(),
                'ya_aprobo' => $aprobaciones->contains(fn (object $v): bool => (int) $v->id_validador === $idDocente),
            ];
        })->values();
    }

    /**
     * Número de solicitudes pendientes (cacheado). Si no se puede contar
     * (base sin migrar) devuelve 0: es un adorno del sidebar y no debe
     * romper la página.
     */
    public static function contarPendientes(): int
    {
        try {
            return (int) Cache::remember(
                self::CLAVE_PENDIENTES,
                self::TTL_PENDIENTES,
                fn (): int => DB::table('usuario')->where('estado_cuenta', EstadoCuenta::Pendiente->value)->count(),
            );
        } catch (QueryException $error) {
            report($error);

            return 0;
        }
    }

    /** Olvida el número cacheado: se llama al registrar o validar una cuenta. */
    public static function olvidarPendientes(): void
    {
        Cache::forget(self::CLAVE_PENDIENTES);
    }

    private function nombreCompleto(?string $nombre, ?string $apellido): string
    {
        return trim(trim((string) $nombre).' '.trim((string) $apellido));
    }
}

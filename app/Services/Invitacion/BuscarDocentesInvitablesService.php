<?php

namespace App\Services\Invitacion;

use App\Enums\InvitacionEstado;
use App\Models\Examen;
use App\Models\Rol;
use App\Models\Usuario;
use App\Services\Examen\BusquedaEstudianteService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class BuscarDocentesInvitablesService
{
    public const BUSQUEDA_MINIMA = 2;

    public const BUSQUEDA_MAXIMA = 254;

    public const PATRON_CORREO_PARCIAL = '/^[A-Za-z0-9._%+@-]+$/';

    /**
     * @return Collection<int, array{id: int, sis: string, nombre: string, correo: ?string}>
     */
    public function ejecutar(Usuario $solicitante, Examen $examen, string $busqueda, int $limite = 10): Collection
    {
        Gate::forUser($solicitante)->authorize('invitarDocente', $examen);

        $termino = trim($busqueda);
        $this->validarBusqueda($termino);

        $operador = DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';
        $patron = '%'.$termino.'%';
        $estadosActivos = [InvitacionEstado::Pendiente->value, InvitacionEstado::Aceptada->value];

        return Usuario::query()
            ->select(['usuario.id_usuario', 'usuario.cod_sis', 'usuario.nombre_usuario', 'usuario.apellido', 'usuario.correo'])
            ->where('usuario.id_usuario', '!=', $solicitante->id_usuario)
            ->whereHas('roles', fn ($consulta) => $consulta->where('nombre_rol', Rol::NOMBRE_DOCENTE))
            ->whereDoesntHave('invitacionesRecibidas', function ($consulta) use ($examen, $estadosActivos): void {
                $consulta->where('id_examen', $examen->id_examen)
                    ->whereIn('estado', $estadosActivos);
            })
            ->where(function ($consulta) use ($operador, $patron): void {
                $consulta->where('usuario.nombre_usuario', $operador, $patron)
                    ->orWhere('usuario.apellido', $operador, $patron)
                    ->orWhere('usuario.cod_sis', $operador, $patron)
                    ->orWhereRaw(
                        "usuario.nombre_usuario || ' ' || usuario.apellido {$operador} ?",
                        [$patron]
                    )
                    ->orWhere('usuario.correo', $operador, $patron);
            })
            ->orderBy('usuario.nombre_usuario')
            ->orderBy('usuario.apellido')
            ->limit(max(1, min($limite, 20)))
            ->get()
            ->map(fn (Usuario $docente): array => [
                'id' => (int) $docente->id_usuario,
                'sis' => $docente->cod_sis,
                'nombre' => trim($docente->nombre_usuario.' '.$docente->apellido),
                'correo' => $docente->correo,
            ]);
    }

    /** @throws ValidationException */
    public function validarBusqueda(string $busqueda): void
    {
        $largo = mb_strlen($busqueda);

        if ($largo < self::BUSQUEDA_MINIMA) {
            throw ValidationException::withMessages([
                'busqueda' => 'La búsqueda debe tener al menos '.self::BUSQUEDA_MINIMA.' caracteres.',
            ]);
        }

        if ($largo > self::BUSQUEDA_MAXIMA) {
            throw ValidationException::withMessages([
                'busqueda' => 'La búsqueda no debe superar los '.self::BUSQUEDA_MAXIMA.' caracteres.',
            ]);
        }

        $esNombre = preg_match(BusquedaEstudianteService::PATRON_NOMBRE, $busqueda) === 1;
        $esCorreo = preg_match(self::PATRON_CORREO_PARCIAL, $busqueda) === 1;

        if (! $esNombre && ! $esCorreo) {
            throw ValidationException::withMessages([
                'busqueda' => 'La búsqueda contiene caracteres no permitidos.',
            ]);
        }
    }
}

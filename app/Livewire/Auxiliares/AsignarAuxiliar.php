<?php

/**
 * @file    AsignarAuxiliar.php
 *
 * @author  Valery D. Ortuno P <valerydariana98@gmail.com>
 *
 * @created 2026-11-22
 *
 * @description
 * Pagina "Asignar auxiliar" (HU 24): la lista de todos los auxiliares con
 * sus asignaciones dentro de los cursos del docente y el boton "Asignar".
 * El modal asigna a un curso (ve Ingreso y Monitor en todos sus examenes) o a
 * un examen futuro, eligiendo que paginas ve (Ingreso, Monitor o las dos).
 * Cada asignacion se puede quitar.
 *
 * Los metodos que llama el modal devuelven el error a mostrar ('' si salio
 * bien) para que el modal quede abierto con el mensaje cuando algo falla.
 *
 * @changelog
 * - 2026-11-22  [Valery D. Ortuno P]  feat: creacion inicial (HU 24).
 *
 * @see  App\Services\Auxiliar\AsignarAuxiliarService
 * @see  App\Services\Auxiliar\ListarAsignacionesAuxiliaresService
 */

namespace App\Livewire\Auxiliares;

use App\Models\Usuario;
use App\Services\Auxiliar\AsignarAuxiliarService;
use App\Services\Auxiliar\ListarAsignacionesAuxiliaresService;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Component;

class AsignarAuxiliar extends Component
{
    public string $mensaje = '';

    /** Auxiliar que llega preseleccionado desde "Asignar ahora" (o null). */
    public ?int $auxiliarPreseleccionado = null;

    public function mount(?int $auxiliar = null): void
    {
        $this->auxiliarPreseleccionado = $auxiliar !== null ? (int) $auxiliar : null;
    }

    /**
     * @return array{auxiliares: list<array<string, mixed>>, cursos: list<array{id: int, nombre: string}>, examenes: list<array{id: int, etiqueta: string}>}
     */
    #[Computed]
    public function datos(): array
    {
        return app(ListarAsignacionesAuxiliaresService::class)
            ->ejecutar($this->idDocenteObjetivo(), $this->idCoordinador());
    }

    public function asignarCurso(int $idAuxiliar, int $idCurso): string
    {
        return $this->ejecutar(function (AsignarAuxiliarService $servicio) use ($idAuxiliar, $idCurso): string {
            $curso = $servicio->asignarCurso($this->idDocenteObjetivo(), $idAuxiliar, $idCurso, $this->idCoordinador());

            return 'Se asigno a '.$this->nombreAuxiliar($idAuxiliar).' al curso '.$curso.'. Vera Ingreso y Monitor en todos sus examenes.';
        });
    }

    public function asignarExamen(int $idAuxiliar, int $idExamen, bool $verIngreso, bool $verMonitor): string
    {
        return $this->ejecutar(function (AsignarAuxiliarService $servicio) use ($idAuxiliar, $idExamen, $verIngreso, $verMonitor): string {
            $resultado = $servicio->asignarExamen($this->idDocenteObjetivo(), $idAuxiliar, $idExamen, $verIngreso, $verMonitor, $this->idCoordinador());
            $paginas = $verIngreso && $verMonitor ? 'Ingreso y Monitor' : ($verIngreso ? 'solo Ingreso' : 'solo Monitor');

            return ($resultado['actualizada'] ? 'Se actualizo la asignacion de ' : 'Se asigno a ')
                .$this->nombreAuxiliar($idAuxiliar).' al examen. Vera '.$paginas.'.';
        });
    }

    public function quitarCurso(int $idAuxiliar, int $idCurso): string
    {
        return $this->ejecutar(function (AsignarAuxiliarService $servicio) use ($idAuxiliar, $idCurso): string {
            $servicio->quitarCurso($this->idDocenteObjetivo(), $idAuxiliar, $idCurso, $this->idCoordinador());

            return 'Se quito la asignacion de '.$this->nombreAuxiliar($idAuxiliar).' al curso.';
        });
    }

    public function quitarExamen(int $idAuxiliar, int $idExamen): string
    {
        return $this->ejecutar(function (AsignarAuxiliarService $servicio) use ($idAuxiliar, $idExamen): string {
            $servicio->quitarExamen($this->idDocenteObjetivo(), $idAuxiliar, $idExamen, $this->idCoordinador());

            return 'Se quito la asignacion de '.$this->nombreAuxiliar($idAuxiliar).' al examen.';
        });
    }

    public function cerrarMensaje(): void
    {
        $this->mensaje = '';
    }

    public function render(): View
    {
        return view('livewire.auxiliares.asignar-auxiliar')->extends('layouts.app');
    }

    /**
     * Corre la accion: si sale bien deja el aviso y refresca la lista; si no,
     * devuelve el primer error para el modal.
     */
    private function ejecutar(callable $accion): string
    {
        try {
            $this->mensaje = $accion(app(AsignarAuxiliarService::class));
        } catch (ValidationException $error) {
            return (string) collect($error->errors())->flatten()->first();
        }

        unset($this->datos);

        return '';
    }

    private function nombreAuxiliar(int $idAuxiliar): string
    {
        $auxiliar = collect($this->datos['auxiliares'])->firstWhere('id', $idAuxiliar);

        return $auxiliar['nombre'] ?? 'el auxiliar';
    }

    /**
     * Docente cuyos cursos y examenes entran en juego: el propio usuario si es
     * docente, o el docente responsable del coordinador que esta actuando.
     */
    private function idDocenteObjetivo(): int
    {
        $usuario = $this->usuario();

        if ($usuario->esDocente()) {
            return (int) $usuario->id_usuario;
        }

        return (int) ($usuario->id_docente_responsable ?? 0);
    }

    /** ID del coordinador cuando quien actua es un auxiliar con permiso; null si es docente. */
    private function idCoordinador(): ?int
    {
        $usuario = $this->usuario();

        return $usuario->esDocente() ? null : (int) $usuario->id_usuario;
    }

    private function usuario(): Usuario
    {
        $usuario = auth()->user();
        abort_unless($usuario instanceof Usuario, 401);

        return $usuario;
    }
}

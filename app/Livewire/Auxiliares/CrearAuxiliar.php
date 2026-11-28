<?php

/**
 * @file    CrearAuxiliar.php
 *
 * @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
 *
 * @created 2026-11-15
 *
 * @updated 2026-11-27
 *
 * @description
 * Página "Crear auxiliar" (Auxiliares › Crear auxiliar). Pide nombre, apellido,
 * tipo de auxiliar (UMSS con código SIS o externo con carnet opcional), correo
 * y, solo para docentes, el permiso de coordinador. Ya no se elige curso: la
 * asignación se hace después en "Asignar auxiliar".
 *
 * Orquesta: valida en el servidor, muestra el modal de confirmación y, al
 * confirmar, delega el alta y el envío de credenciales a CrearAuxiliarService.
 * Al terminar vuelve a la lista con el aviso de éxito.
 *
 * @changelog
 * - 2026-11-15  [Diego Tejerina]  refactor: el alta pasa de modal a página propia.
 * - 2026-11-27  [Diego Tejerina]  feat: tipo de auxiliar (UMSS/externo), carnet
 *   opcional, correo obligatorio, permiso de coordinador, modal de confirmación
 *   y envío de credenciales por correo. Se quita la elección de curso.
 *
 * @see  App\Services\Auxiliar\CrearAuxiliarService
 * @see  App\Support\Validadores
 */

namespace App\Livewire\Auxiliares;

use App\Models\Usuario;
use App\Services\Auxiliar\CrearAuxiliarService;
use App\Services\Usuario\GeneradorNombreUsuarioService;
use App\Support\DTO\DatosNuevoAuxiliar;
use App\Support\Validadores;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Livewire\Attributes\Computed;
use Livewire\Component;

class CrearAuxiliar extends Component
{
    public string $nombre = '';

    public string $apellido = '';

    public string $tipoAuxiliar = DatosNuevoAuxiliar::TIPO_UMSS;

    public string $codSis = '';

    public string $carnet = '';

    public string $correo = '';

    public bool $puedeCrearAuxiliares = false;

    /** true mientras se muestra el modal de confirmación. */
    public bool $confirmando = false;

    /** Usuario generado que se mostrará en la confirmación (aux.nombre.apellido). */
    public string $usuarioPreview = '';

    /** El permiso de coordinador solo lo otorga un docente. */
    #[Computed]
    public function esDocente(): bool
    {
        return $this->creador()->esDocente();
    }

    /** Al pasar a externo se limpia el código SIS (el carnet aplica a ambos). */
    public function updatedTipoAuxiliar(): void
    {
        if ($this->tipoAuxiliar !== DatosNuevoAuxiliar::TIPO_UMSS) {
            $this->codSis = '';
        }
    }

    /**
     * Valida los datos y, si están bien y no hay duplicados, calcula el usuario
     * generado y abre el modal de confirmación.
     */
    public function revisar(): void
    {
        $this->validate(
            Validadores::reglasAuxiliar($this->tipoAuxiliar),
            Validadores::mensajesAuxiliar(),
        );

        $datos = $this->datos();
        app(CrearAuxiliarService::class)->revisarDuplicados($datos);

        $this->usuarioPreview = app(GeneradorNombreUsuarioService::class)->generar(
            $datos->nombre,
            $datos->apellido,
            GeneradorNombreUsuarioService::PREFIJO_AUXILIAR,
        );

        $this->confirmando = true;
    }

    /** Cierra el modal sin crear y vuelve al formulario. */
    public function cancelarConfirmacion(): void
    {
        $this->confirmando = false;
    }

    /**
     * Crea el auxiliar y vuelve a la lista con el aviso de éxito. Si el correo
     * con las credenciales no se pudo enviar, lo indica para que se reenvíe.
     */
    public function confirmar(): RedirectResponse
    {
        $resultado = app(CrearAuxiliarService::class)->crear($this->datos(), $this->creador());

        return redirect()
            ->route('auxiliares')
            ->with('auxiliarCreado', [
                'id' => $resultado->auxiliar->id_usuario,
                'credencialesEnviadas' => $resultado->credencialesEnviadas,
            ]);
    }

    public function render(): View
    {
        return view('livewire.auxiliares.crear-auxiliar')->extends('layouts.app');
    }

    /** Datos normalizados del formulario. */
    private function datos(): DatosNuevoAuxiliar
    {
        return new DatosNuevoAuxiliar(
            nombre: $this->limpiar($this->nombre),
            apellido: $this->limpiar($this->apellido),
            tipoAuxiliar: $this->tipoAuxiliar,
            codSis: trim($this->codSis),
            carnet: trim($this->carnet),
            correo: trim($this->correo),
            // Un coordinador no puede otorgar el permiso aunque llegue marcado.
            puedeCrearAuxiliares: $this->esDocente() && $this->puedeCrearAuxiliares,
        );
    }

    private function creador(): Usuario
    {
        $usuario = auth()->user();
        abort_unless($usuario instanceof Usuario, 401);

        return $usuario;
    }

    /** Recorta y colapsa espacios internos sin tocar letras, apóstrofos ni guiones. */
    private function limpiar(string $valor): string
    {
        return (string) preg_replace('/\s+/u', ' ', trim($valor));
    }
}

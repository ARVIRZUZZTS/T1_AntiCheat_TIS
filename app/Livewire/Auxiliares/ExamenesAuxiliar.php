<?php

/**
 * @file    ExamenesAuxiliar.php
 *
 * @author  Valery D. Ortuno P <valerydariana98@gmail.com>
 *
 * @created 2026-11-22
 *
 * @description
 * Pagina "Examenes > Ver examenes" del auxiliar (HU 24): solo los examenes a
 * los que esta asignado (todos los de sus cursos activos y los sueltos), con
 * el acceso que tiene en cada uno. Es de solo consulta y solo para
 * auxiliares.
 *
 * @changelog
 * - 2026-11-22  [Valery D. Ortuno P]  feat: creacion inicial (HU 24).
 *
 * @see  App\Services\Auxiliar\ListarExamenesAuxiliarService
 */

namespace App\Livewire\Auxiliares;

use App\Models\Rol;
use App\Services\Auxiliar\ListarExamenesAuxiliarService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ExamenesAuxiliar extends Component
{
    public function mount(): void
    {
        abort_unless(auth()->user()?->roles->contains('nombre_rol', Rol::NOMBRE_AUXILIAR), 403);
    }

    /**
     * @return list<array<string, mixed>>
     */
    #[Computed]
    public function examenes(): array
    {
        return app(ListarExamenesAuxiliarService::class)->ejecutar((int) auth()->id());
    }

    public function render(): View
    {
        return view('livewire.auxiliares.examenes-auxiliar')->extends('layouts.app');
    }
}

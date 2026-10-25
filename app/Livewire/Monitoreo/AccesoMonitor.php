<?php

namespace App\Livewire\Monitoreo;

use App\Models\Examen;
use App\Models\Rol;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use Livewire\Component;

class AccesoMonitor extends Component
{
    #[Url]
    public ?int $simular = null;

    public int $idExamen = 0;

    public function mount(): void
    {
        $this->simular = $this->simular ?? (int) request()->query('simular', 0);
        if ($this->simular === 0 && ! request()->has('simular')) {
            $this->simular = null;
        }

        $ahora = now();
        $consulta = $this->consultaExamenesAccesibles();

        $examen = $consulta
            ->where(function ($query) use ($ahora): void {
                $query->where('fecha', '>', $ahora->toDateString())
                    ->orWhere(function ($subquery) use ($ahora): void {
                        $subquery->where('fecha', $ahora->toDateString())
                            ->where('hora_inicio', '>=', $ahora->format('H:i:s'));
                    });
            })
            ->orderBy('fecha')
            ->orderBy('hora_inicio')
            ->first();

        if ($examen === null) {
            $fallback = $this->consultaExamenesAccesibles();

            $examen = $fallback
                ->orderByDesc('fecha')
                ->orderByDesc('id_examen')
                ->first();
        }

        if ($examen !== null) {
            $this->idExamen = (int) $examen->id_examen;
        }
    }

    public function ahoraSimulada(): Carbon
    {
        return $this->simular === null ? now() : now()->addMinutes($this->simular);
    }

    public function examen()
    {
        if ($this->idExamen <= 0) {
            return null;
        }

        return Examen::query()->with(['cursos', 'ambientes', 'materialesPermitidos', 'tipoExamen'])->find($this->idExamen);
    }

    public function estado(): string
    {
        $examen = $this->examen();
        if ($examen === null) {
            return 'sin_examen';
        }

        $inicio = Carbon::parse($examen->fecha.' '.$examen->hora_inicio);
        $fin = $examen->hora_fin
            ? Carbon::parse($examen->fecha.' '.$examen->hora_fin)
            : $inicio->copy()->addMinutes((int) $examen->duracion);
        $apertura = $inicio->copy()->subMinutes(15);
        $ahora = $this->ahoraSimulada();

        if ($ahora < $apertura) {
            return 'proximo';
        }
        if ($ahora < $inicio) {
            return 'registro_abierto';
        }
        if ($ahora <= $fin) {
            return 'en_curso';
        }

        return 'finalizado';
    }

    public function minutosParaInicio(): int
    {
        $examen = $this->examen();
        if ($examen === null) {
            return 0;
        }

        $inicio = Carbon::parse($examen->fecha.' '.$examen->hora_inicio);

        return (int) $this->ahoraSimulada()->diffInMinutes($inicio, false);
    }

    public function proximosExamenes()
    {
        $consulta = $this->consultaExamenesAccesibles()->with('cursos');

        $examenes = $consulta
            ->where('fecha', '>=', now()->toDateString())
            ->orderBy('fecha')
            ->orderBy('hora_inicio')
            ->limit(5)
            ->get();

        return $examenes->map(function (Examen $examen): array {
            $cursoIds = $examen->cursos->pluck('id_curso');

            $auxiliares = 0;
            if ($cursoIds->isNotEmpty()) {
                $auxiliares = DB::table('auxiliar_curso')
                    ->whereIn('id_curso', $cursoIds)
                    ->distinct()
                    ->count('id_auxiliar');
            }

            return [
                'id' => $examen->id_examen,
                'materias' => $examen->cursos->pluck('nombre_curso')->implode(', '),
                'fecha' => $examen->fecha,
                'hora' => substr((string) $examen->hora_inicio, 0, 5),
                'auxiliares' => $auxiliares,
            ];
        });
    }

    public function auxiliares()
    {
        if (! auth()->check()) {
            return collect();
        }

        $query = DB::table('auxiliar_curso')
            ->join('curso', 'curso.id_curso', '=', 'auxiliar_curso.id_curso')
            ->join('usuario', 'usuario.id_usuario', '=', 'auxiliar_curso.id_auxiliar');

        if (auth()->user()->roles->contains('nombre_rol', Rol::NOMBRE_DOCENTE)) {
            $query->where('curso.sis_doc', auth()->id());
        } else {
            $query->where('auxiliar_curso.id_auxiliar', auth()->id());
        }

        return $query->select('usuario.id_usuario', 'usuario.cod_sis', 'usuario.nombre_usuario', 'usuario.apellido', 'curso.nombre_curso', 'auxiliar_curso.estado')
            ->orderBy('usuario.nombre_usuario')
            ->get();
    }

    public function render()
    {
        return view('livewire.monitoreo.acceso-monitor')->extends('layouts.app');
    }

    private function consultaExamenesAccesibles()
    {
        $consulta = Examen::query();

        if (! auth()->check()) {
            return $consulta;
        }

        if (auth()->user()->roles->contains('nombre_rol', Rol::NOMBRE_DOCENTE)) {
            return $consulta
                ->paraDocente((int) auth()->id())
                ->whereHas('cursos', fn ($cursos) => $cursos
                    ->where('curso.sis_doc', auth()->id()));
        }

        $cursosAsignados = DB::table('auxiliar_curso')
            ->where('id_auxiliar', auth()->id())
            ->pluck('id_curso');

        return $consulta->whereHas(
            'cursos',
            fn ($cursos) => $cursos->whereIn('curso.id_curso', $cursosAsignados)
        );
    }
}

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

    public function mount(): void
    {
        $this->simular = $this->simular ?? (int) request()->query('simular', 0);
        if ($this->simular === 0 && ! request()->has('simular')) {
            $this->simular = null;
        }
    }

    public function ahoraSimulada(): Carbon
    {
        return $this->simular === null ? now() : now()->addMinutes($this->simular);
    }

    /**
     * Próximos exámenes (hasta 5) con los mismos datos que las tarjetas del
     * monitor, para que "Ingreso" mantenga la misma línea de diseño. Cada uno
     * trae su estado (proximo / registro_abierto / en_curso / finalizado),
     * si es hoy y el tiempo que falta, calculados con la hora simulada.
     *
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    public function proximosExamenes()
    {
        $examenes = $this->consultaExamenesAccesibles()
            ->with(['cursos', 'ambientes', 'tipoExamen'])
            ->where('fecha', '>=', now()->toDateString())
            ->orderBy('fecha')
            ->orderBy('hora_inicio')
            ->limit(5)
            ->get();

        $ahora = $this->ahoraSimulada();
        $hoy = $ahora->toDateString();

        return $examenes->map(function (Examen $examen) use ($ahora, $hoy): array {
            $inicio = Carbon::parse($examen->fecha.' '.$examen->hora_inicio);
            $fin = $examen->hora_fin
                ? Carbon::parse($examen->fecha.' '.$examen->hora_fin)
                : $inicio->copy()->addMinutes((int) $examen->duracion);
            $apertura = $inicio->copy()->subMinutes(15);

            if ($ahora < $apertura) {
                $estado = 'proximo';
            } elseif ($ahora < $inicio) {
                $estado = 'registro_abierto';
            } elseif ($ahora <= $fin) {
                $estado = 'en_curso';
            } else {
                $estado = 'finalizado';
            }

            return [
                'id' => $examen->id_examen,
                'materias' => $examen->cursos->pluck('nombre_curso')->implode(', '),
                'tipo' => $examen->tipoExamen->nombre_tipo_examen ?? 'Sin tipo',
                'fecha' => $examen->fecha,
                'hora_inicio' => substr((string) $examen->hora_inicio, 0, 5),
                'hora_fin' => $examen->hora_fin ? substr((string) $examen->hora_fin, 0, 5) : null,
                'ambientes' => $examen->ambientes->pluck('nombre_ambiente')->implode(', '),
                'estado' => $estado,
                'es_hoy' => $examen->fecha === $hoy,
                'tiempo_restante' => $this->tiempoRestante((int) $ahora->diffInMinutes($inicio, false)),
            ];
        });
    }

    /**
     * Texto legible del tiempo que falta para el inicio (misma forma que el
     * monitor): "En X min", "En X h Y min", "En X días...", o "En curso".
     */
    private function tiempoRestante(int $minutos): string
    {
        if ($minutos <= 0) {
            return 'En curso';
        }
        if ($minutos < 60) {
            return 'En '.$minutos.' min';
        }
        if ($minutos < 1440) {
            return 'En '.intdiv($minutos, 60).' h '.($minutos % 60).' min';
        }
        if ($minutos < 10080) {
            return 'En '.intdiv($minutos, 1440).' días '.intdiv($minutos % 1440, 60).' h';
        }

        return '+7 días';
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

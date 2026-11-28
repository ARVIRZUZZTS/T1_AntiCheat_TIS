<?php

namespace Tests\Feature\Monitoreo;

use App\Livewire\Monitoreo\AccesoMonitor;
use App\Livewire\Monitoreo\MonitorEnVivo;
use App\Models\Curso;
use App\Models\Examen;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

class IngresoMonitorRegresionTest extends TestCase
{
    use DatabaseTransactions;

    private int $idExamen;

    private int $creador;

    protected function setUp(): void
    {
        parent::setUp();

        $examen = Examen::query()
            ->orderByDesc('fecha')
            ->orderByDesc('id_examen')
            ->first();

        if ($examen === null) {
            $this->markTestSkipped('No hay examen registrado.');
        }

        $this->idExamen = (int) $examen->id_examen;
    }

    public function test_mount_no_rompe_con_curso_archivado(): void
    {
        $examen = Examen::query()->findOrFail($this->idExamen);
        $creador = (int) $examen->creador;
        auth()->login(\App\Models\Usuario::query()->findOrFail($creador));

        $curso = $examen->cursos()->first();

        if ($curso !== null) {
            $curso->estado = \App\Enums\CursoEstado::Archivado->value;
            $curso->save();
        }

        $componente = Livewire::test(MonitorEnVivo::class);

        $this->assertSame(0, $componente->instance()->idExamen);
    }

    public function test_mount_selecciona_examen_vigente(): void
    {
        $componente = Livewire::test(MonitorEnVivo::class);

        $this->assertSame($this->idExamen, $componente->instance()->idExamen);
    }
}

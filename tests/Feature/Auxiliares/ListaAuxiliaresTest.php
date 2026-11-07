<?php

namespace Tests\Feature\Auxiliares;

use App\Livewire\Auxiliares\FuenteAuxiliares;
use App\Livewire\Auxiliares\ListaAuxiliares;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;
use Throwable;

class ListaAuxiliaresTest extends TestCase
{
    private FuenteAuxiliaresFalsa $fuente;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fuente = new FuenteAuxiliaresFalsa;
        $this->app->instance(FuenteAuxiliares::class, $this->fuente);
    }

    public function test_muestra_indicador_de_carga_antes_de_pedir_los_datos(): void
    {
        Livewire::test(ListaAuxiliares::class)
            ->assertSet('cargando', true)
            ->assertSee('Cargando auxiliares...')
            ->assertDontSee('No tienes auxiliares registrados');
    }

    public function test_lista_muestra_nombre_completo_codigo_sis_curso_y_estado(): void
    {
        $this->fuente->datos = $this->datos();

        Livewire::test(ListaAuxiliares::class)
            ->call('cargar')
            ->assertSet('cargando', false)
            ->assertSee('Nombre completo')
            ->assertSee('Código SIS')
            ->assertSee('Curso')
            ->assertSee('Estado')
            ->assertSee('Ana Pérez')
            ->assertSee('202012345')
            ->assertSee('Programación I')
            ->assertSee('Activo');
    }

    public function test_estado_activo_y_baja_se_muestran_con_su_texto(): void
    {
        $this->fuente->datos = $this->datos();

        Livewire::test(ListaAuxiliares::class)
            ->call('cargar')
            ->assertSee('Activo')
            ->assertSee('Baja')
            ->assertDontSee('ACTIVO')
            ->assertDontSee('BAJA');
    }

    public function test_auxiliares_de_varios_cursos_aparecen_todos(): void
    {
        $this->fuente->datos = $this->datos();

        Livewire::test(ListaAuxiliares::class)
            ->call('cargar')
            ->assertSee('Ana Pérez')
            ->assertSee('Luis Gómez')
            ->assertSee('Carla Rojas')
            ->assertSee('Programación I')
            ->assertSee('Base de Datos');
    }

    public function test_lista_vacia_muestra_el_mensaje_exacto(): void
    {
        $this->fuente->datos = [];

        Livewire::test(ListaAuxiliares::class)
            ->call('cargar')
            ->assertSee('No tienes auxiliares registrados')
            ->assertDontSee('Nombre completo');
    }

    public function test_error_de_carga_muestra_mensaje_y_reintentar_vuelve_a_pedir(): void
    {
        $this->fuente->error = new RuntimeException('Fallo de red.', 500);

        $componente = Livewire::test(ListaAuxiliares::class)
            ->call('cargar')
            ->assertSee('No se pudieron cargar los auxiliares.')
            ->assertSee('Reintentar');

        $this->assertSame(1, $this->fuente->llamadas);

        $this->fuente->error = null;
        $this->fuente->datos = $this->datos();

        $componente
            ->call('cargar')
            ->assertSee('Ana Pérez')
            ->assertDontSee('Reintentar');

        $this->assertSame(2, $this->fuente->llamadas);
    }

    public function test_sin_botones_de_editar_eliminar_ni_agregar(): void
    {
        $this->fuente->datos = $this->datos();

        Livewire::test(ListaAuxiliares::class)
            ->call('cargar')
            ->assertDontSee('Editar')
            ->assertDontSee('Eliminar')
            ->assertDontSee('Agregar')
            ->assertDontSee('Reintentar');
    }

    public function test_respeta_el_orden_de_la_respuesta(): void
    {
        $this->fuente->datos = $this->datos();

        Livewire::test(ListaAuxiliares::class)
            ->call('cargar')
            ->assertSeeHtmlInOrder(['Ana Pérez', 'Luis Gómez', 'Carla Rojas']);
    }

    public function test_401_redirige_al_inicio_de_sesion(): void
    {
        $this->fuente->error = new RuntimeException('Sin sesión.', 401);

        Livewire::test(ListaAuxiliares::class)
            ->call('cargar')
            ->assertRedirect(route('login'));
    }

    public function test_la_ruta_exige_sesion_iniciada(): void
    {
        $this->get(route('docente.auxiliares'))
            ->assertRedirect(route('login'));
    }

    /**
     * @return list<array{id: int, nombre: string, apellido: string, codigoSis: string, curso: string, estado: string}>
     */
    private function datos(): array
    {
        return [
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
    }
}

class FuenteAuxiliaresFalsa extends FuenteAuxiliares
{
    /**
     * @var list<array{id: int, nombre: string, apellido: string, codigoSis: string, curso: string, estado: string}>
     */
    public array $datos = [];

    public ?Throwable $error = null;

    public int $llamadas = 0;

    public function usarDatosSimulados(): bool
    {
        return true;
    }

    /**
     * @return list<array{id: int, nombre: string, apellido: string, codigoSis: string, curso: string, estado: string}>
     */
    public function obtener(): array
    {
        $this->llamadas++;

        if ($this->error !== null) {
            throw $this->error;
        }

        return $this->datos;
    }
}

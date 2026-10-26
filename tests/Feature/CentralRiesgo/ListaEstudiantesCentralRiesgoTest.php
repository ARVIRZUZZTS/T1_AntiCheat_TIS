<?php

/**
 * @file    ListaEstudiantesCentralRiesgoTest.php
 *
 * @author  Valery D. Ortuno P. <valerydariana98@gmail.com>
 *
 * @created 2026-10-10
 *
 * @updated 2026-10-10
 * @updated 2026-10-24
 * @updated 2026-10-25
 * @updated 2026-10-26
 *
 * @description
 * Pruebas de la lista de estudiantes de la central de riesgos (HU 10), sobre el
 * componente Livewire ListaEstudiantes: orden por defecto y A-Z, alcance,
 * búsqueda, mensajes de lista vacía, paginación de 8 registros y detalle de la
 * incidencia.
 *
 * Usan SQLite en memoria con un esquema mínimo, igual que DetalleIncidenciaTest,
 * así que no leen ni escriben en la base de desarrollo.
 *
 * @see App\Livewire\CentralRiesgo\ListaEstudiantes
 * @see App\Services\CentralRiesgo\ListarIncidenciasTramposoService
 * @see App\Services\CentralRiesgo\ConsultarDetalleIncidenciaService
 *
 * @changelog
 * - 2026-10-10  [Valery D. Ortuno P]  test: creación inicial.
 * - 2026-10-11  [Valery D. Ortuno P]  test: el alcance por defecto pasa a ser
 *   "Toda la institución"; "Mis materias" se elige explícitamente.
 * - 2026-10-11  [Valery D. Ortuno P]  test: el detalle es un modal (x y
 *   "Aceptar"), la lista sigue visible detrás, el orden A-Z también ordena
 *   "Mis materias" y ordenar no cambia la cantidad de estudiantes.
 * - 2026-10-11  [Valery D. Ortuno P]  test: la lista muestra el nombre como
 *   "Nombre Apellido" (el orden A-Z sigue siendo por apellido).
 * - 2026-10-11  [Valery D. Ortuno P]  test: una incidencia `tramposo` pero
 *   `Pendiente` no aparece en la central (solo las confirmadas).
 * - 2026-10-11  [Valery D. Ortuno P]  test: una escritura por Eloquent invalida
 *   la caché de la lista (la invalidación por versión no deja datos viejos).
 * - 2026-10-24  [Valery D. Ortuno P]  test: las pruebas inician sesión como el
 *   docente (ya no hay usuario por defecto); cada incidencia trae su curso
 *   registrado, un tramposo se lista aunque `estado_incidencia` diga
 *   Pendiente, sin curso se lista "Sin materia", el detalle muestra materia y
 *   grupo, y los textos esperados van sin acentos.
 * - 2026-10-25  [Valery D. Ortuno P]  test: etiqueta "No verificado" para los
 *   estudiantes dados de alta desde una incidencia; el esquema de prueba suma
 *   `estudiante.verificado` y las etiquetas de motivo van sin acentos (HU 5).
 * - 2026-10-26  [Valery D. Ortuno P]  test: sin `estado_incidencia`,
 *   eliminada de la base; se quita la prueba de tramposo con estado
 *   Pendiente, que ya no puede darse.
 */

namespace Tests\Feature\CentralRiesgo;

use App\Livewire\CentralRiesgo\ListaEstudiantes;
use App\Models\CentralRiesgo;
use App\Models\Usuario;
use App\Services\CentralRiesgo\ListarIncidenciasTramposoService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\Constraints\SeeInOrder;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class ListaEstudiantesCentralRiesgoTest extends TestCase
{
    private const DOCENTE_A = 1;

    private const DOCENTE_B = 2;

    private const AUXILIAR = 3;

    private const CURSO_A = 1;

    private const CURSO_B = 2;

    private const EXAMEN_A = 1;

    private const EXAMEN_B = 2;

    private const EXAMEN_COMPARTIDO = 3;

    private const LUIS = '900000002';

    private const MARIA = '900000003';

    private const ANA = '900000001';

    private const CARLA = '900000005';

    private const JAIME = '900000007';

    private const PEDRO_SOSPECHOSO = '900000004';

    /** De la incidencia más reciente a la más antigua. */
    private const POR_FECHA = [self::LUIS, self::MARIA, self::ANA, self::CARLA, self::JAIME];

    /** Primer apellido, segundo apellido y nombre. */
    private const ALFABETICO = [self::LUIS, self::MARIA, self::JAIME, self::ANA, self::CARLA];

    private int $ultimoIdRegistro = 0;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.connections.central_pruebas' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
        ]]);
        DB::setDefaultConnection('central_pruebas');
        $this->crearEsquema();
        $this->crearDatos();

        // "Mis materias" es siempre del usuario de la sesión: el componente ya
        // no toma un usuario por defecto ni de la URL.
        $this->actingAs(Usuario::query()->findOrFail(self::DOCENTE_A));
    }

    protected function tearDown(): void
    {
        DB::purge('central_pruebas');
        parent::tearDown();
    }

    public function test_por_defecto_ordena_de_la_incidencia_mas_reciente_a_la_mas_antigua(): void
    {
        $this->assertSame(self::POR_FECHA, $this->sisListados($this->institucion()));
    }

    public function test_solo_lista_estudiantes_sancionados(): void
    {
        $this->assertNotContains(self::PEDRO_SOSPECHOSO, $this->sisListados($this->institucion()));
    }

    /** Sin curso registrado la incidencia se lista igual, sin materia. */
    public function test_una_incidencia_sin_curso_registrado_se_lista_sin_materia(): void
    {
        $this->crearEstudiante('900000041', 'Nadia', 'Cordero Vargas');
        $this->crearIncidencia('900000041', self::EXAMEN_A, 'tramposo', '2026-10-05 09:00:00', self::DOCENTE_A, null);

        $this->institucion()->assertSee('Sin materia');
    }

    /** Un estudiante dado de alta desde una incidencia se marca como no verificado. */
    public function test_un_estudiante_no_verificado_lleva_su_etiqueta(): void
    {
        $this->crearEstudiante('900000042', 'Nadia', 'Cordero Vargas');
        DB::table('estudiante')->where('sis_estudiante', '900000042')->update(['verificado' => false]);
        $this->crearIncidencia('900000042', self::EXAMEN_A, 'tramposo', '2026-10-05 09:00:00');

        $this->institucion()->assertSee('No verificado');
        $this->assertStringNotContainsString('No verificado', Livewire::test(ListaEstudiantes::class)->set('busqueda', self::LUIS)->html());
    }

    public function test_la_lista_muestra_estudiante_codigo_sis_materia_motivo_y_ver_detalle(): void
    {
        $componente = $this->institucion();

        $this->assertTextoEnOrden($componente, ['Estudiante', 'Codigo SIS', 'Materia', 'Motivo']);
        $componente
            ->assertSee('Luis Alvarez Gomez')
            ->assertSee(self::LUIS)
            ->assertSee('Calculo I')
            ->assertSee('Uso de dispositivos electronicos no autorizados')
            ->assertSee('Ver detalle');
    }

    public function test_mis_materias_muestra_solo_los_sancionados_en_materias_del_usuario(): void
    {
        $componente = Livewire::test(ListaEstudiantes::class)
            ->call('cambiarAlcance', ListarIncidenciasTramposoService::ALCANCE_MIS_MATERIAS);

        $this->assertSame([self::LUIS, self::ANA, self::CARLA, self::JAIME], $this->sisListados($componente));
    }

    public function test_toda_la_institucion_es_el_alcance_por_defecto(): void
    {
        $componente = Livewire::test(ListaEstudiantes::class);

        $componente->assertSet('alcance', ListarIncidenciasTramposoService::ALCANCE_INSTITUCION);
        $this->assertSame(self::POR_FECHA, $this->sisListados($componente));
    }

    public function test_un_alcance_desconocido_no_cambia_la_lista(): void
    {
        Livewire::test(ListaEstudiantes::class)
            ->call('cambiarAlcance', 'otro')
            ->assertSet('alcance', ListarIncidenciasTramposoService::ALCANCE_INSTITUCION);
    }

    public function test_ordenar_az_ordena_por_primer_apellido_segundo_apellido_y_nombre(): void
    {
        $componente = $this->institucion()->set('ordenAlfabetico', true);

        $this->assertSame(self::ALFABETICO, $this->sisListados($componente));
    }

    public function test_desactivar_ordenar_az_restablece_el_orden_por_fecha(): void
    {
        $componente = $this->institucion()
            ->set('ordenAlfabetico', true)
            ->set('ordenAlfabetico', false);

        $this->assertSame(self::POR_FECHA, $this->sisListados($componente));
    }

    public function test_el_orden_az_no_separa_los_apellidos_con_tilde(): void
    {
        $this->crearEstudiante('900000020', 'Óscar', 'Álvarez Gómez');
        $this->crearIncidencia('900000020', self::EXAMEN_A, 'tramposo', '2026-09-01 08:00:00');

        $componente = $this->institucion()->set('ordenAlfabetico', true);

        // "Álvarez Gómez" empata con "Alvarez Gomez" y se define por el nombre.
        $this->assertSame([self::LUIS, '900000020'], array_slice($this->sisListados($componente), 0, 2));
    }

    public function test_la_busqueda_filtra_por_nombre_sin_distinguir_mayusculas(): void
    {
        $componente = $this->institucion()->set('busqueda', 'zapata');

        $this->assertSame([self::ANA, self::CARLA], $this->sisListados($componente));
    }

    public function test_la_busqueda_filtra_por_nombre_y_apellido_juntos(): void
    {
        $componente = $this->institucion()->set('busqueda', 'Carla Zap');

        $this->assertSame([self::CARLA], $this->sisListados($componente));
    }

    public function test_la_busqueda_filtra_por_codigo_sis(): void
    {
        $componente = $this->institucion()->set('busqueda', '90000000');

        $this->assertSame(self::POR_FECHA, $this->sisListados($componente));
        $this->assertSame([self::MARIA], $this->sisListados($componente->set('busqueda', self::MARIA)));
    }

    public function test_la_busqueda_respeta_el_alcance_y_el_orden_elegidos(): void
    {
        $componente = Livewire::test(ListaEstudiantes::class)
            ->call('cambiarAlcance', ListarIncidenciasTramposoService::ALCANCE_MIS_MATERIAS)
            ->set('ordenAlfabetico', true)
            ->set('busqueda', 'a');

        // María también tiene "a", pero su materia no es del usuario.
        $this->assertSame([self::LUIS, self::JAIME, self::ANA, self::CARLA], $this->sisListados($componente));
    }

    public function test_un_caracter_que_el_buscador_no_admite_se_descarta_sin_romper_la_lista(): void
    {
        $componente = $this->institucion();

        $this->assertSame([self::LUIS], $this->sisListados($componente->set('busqueda', '9000a00002')));
        $this->assertSame([self::ANA], $this->sisListados($componente->set('busqueda', 'Ana#1')));
    }

    public function test_sin_coincidencias_muestra_su_mensaje(): void
    {
        $this->institucion()
            ->set('busqueda', 'Zzzz')
            ->assertSee('No se encontro ninguna coincidencia')
            ->assertDontSee('No hay estudiantes registrados');
    }

    public function test_sin_estudiantes_en_la_central_muestra_su_mensaje(): void
    {
        DB::table('central_riesgo')->delete();

        $this->institucion()
            ->assertSee('No hay estudiantes registrados')
            ->assertDontSee('No se encontro ninguna coincidencia');
    }

    public function test_con_ocho_registros_o_menos_no_hay_paginacion(): void
    {
        $this->crearSancionados(3);

        $componente = $this->institucion();

        $this->assertCount(8, $this->sisListados($componente));
        $componente->assertDontSee('irPagina', false);
    }

    public function test_muestra_un_maximo_de_ocho_registros_por_pagina(): void
    {
        $this->crearSancionados(6);

        $componente = $this->institucion();

        $this->assertCount(8, $this->sisListados($componente));
        $this->assertSame(2, $componente->instance()->estudiantes->lastPage());
        $componente->assertSee('irPagina(2)', false);

        $this->assertCount(3, $this->sisListados($componente->call('irPagina', 2)));
    }

    public function test_buscar_actualiza_las_paginas_y_vuelve_a_la_primera(): void
    {
        $this->crearSancionados(6);

        $componente = $this->institucion()
            ->call('irPagina', 2)
            ->set('busqueda', 'Zapata');

        $componente->assertSet('pagina', 1);
        $this->assertSame(1, $componente->instance()->estudiantes->lastPage());
        $this->assertSame([self::ANA, self::CARLA], $this->sisListados($componente));
    }

    public function test_cambiar_el_alcance_o_el_orden_vuelve_a_la_primera_pagina(): void
    {
        $this->crearSancionados(6);

        $this->institucion()
            ->call('irPagina', 2)
            ->set('ordenAlfabetico', true)
            ->assertSet('pagina', 1)
            ->call('irPagina', 2)
            ->call('cambiarAlcance', ListarIncidenciasTramposoService::ALCANCE_MIS_MATERIAS)
            ->assertSet('pagina', 1);
    }

    public function test_ver_detalle_muestra_los_datos_de_la_incidencia(): void
    {
        $componente = $this->institucion()
            ->assertDontSee('Detalles de la incidencia')
            ->call('verDetalle', $this->idDeIncidencia(self::LUIS));

        $this->assertTextoEnOrden($componente, [
            'Detalles de la incidencia',
            'Luis Alvarez Gomez',
            'Codigo SIS:',
            self::LUIS,
            'Motivo',
            'Uso de dispositivos electronicos no autorizados',
            'Materia',
            'Calculo I',
            'Grupo',
            '2',
            'Docente que reporto',
            'Roberto Silva',
            'Fecha y hora',
            '03/10/2026 10:00',
            'Estado',
            'Confirmado',
            'Descripcion del hecho',
            'Motivo de prueba '.self::LUIS,
        ]);
    }

    public function test_el_detalle_indica_cuando_reporto_un_auxiliar(): void
    {
        $this->crearEstudiante('900000030', 'Elena', 'Quiroga Paz');
        $this->crearIncidencia('900000030', self::EXAMEN_A, 'tramposo', '2026-10-05 09:00:00', self::AUXILIAR);

        $this->institucion()
            ->call('verDetalle', $this->idDeIncidencia('900000030'))
            ->assertSee('Auxiliar que reporto')
            ->assertSee('Diego Mendoza')
            ->assertDontSee('Docente que reporto');
    }

    public function test_cerrar_el_detalle_vuelve_a_la_lista_con_el_orden_el_filtro_y_la_pagina(): void
    {
        $this->crearSancionados(6);

        $componente = $this->institucion()
            ->set('ordenAlfabetico', true)
            ->call('irPagina', 2);
        $antes = $this->sisListados($componente);

        $componente
            ->call('verDetalle', $this->idDeIncidencia(self::ANA))
            ->assertSee('Detalles de la incidencia')
            ->call('cerrarDetalle')
            ->assertDontSee('Detalles de la incidencia')
            ->assertSet('alcance', ListarIncidenciasTramposoService::ALCANCE_INSTITUCION)
            ->assertSet('ordenAlfabetico', true)
            ->assertSet('pagina', 2);

        $this->assertSame($antes, $this->sisListados($componente));
    }

    public function test_el_detalle_de_una_incidencia_que_ya_no_existe_no_rompe_la_pantalla(): void
    {
        $this->institucion()
            ->call('verDetalle', 999)
            ->assertOk()
            ->assertDontSee('Detalles de la incidencia');
    }

    public function test_ordenar_az_tambien_ordena_mis_materias(): void
    {
        $componente = Livewire::test(ListaEstudiantes::class)
            ->call('cambiarAlcance', ListarIncidenciasTramposoService::ALCANCE_MIS_MATERIAS)
            ->set('ordenAlfabetico', true);

        // Mis materias: Alvarez (Luis), Paredes (Jaime), Zapata (Ana, Carla).
        $this->assertSame([self::LUIS, self::JAIME, self::ANA, self::CARLA], $this->sisListados($componente));
    }

    public function test_ordenar_no_cambia_la_cantidad_de_estudiantes(): void
    {
        $this->crearSancionados(6);

        $componente = $this->institucion();
        $antes = $componente->instance()->estudiantes->total();

        $this->assertSame(11, $antes);
        $this->assertSame($antes, $componente->set('ordenAlfabetico', true)->instance()->estudiantes->total());
        $this->assertSame($antes, $componente->set('ordenAlfabetico', false)->instance()->estudiantes->total());
    }

    public function test_el_detalle_se_muestra_como_modal_con_la_x_y_aceptar(): void
    {
        $componente = $this->institucion()
            ->call('verDetalle', $this->idDeIncidencia(self::LUIS));

        $componente
            ->assertSee('role="dialog"', false)
            ->assertSee('aria-modal="true"', false)
            ->assertSee('aria-label="Cerrar detalles de la incidencia"', false)
            ->assertSee('Aceptar');

        // Ya no parte la pantalla en dos: no queda el panel lateral.
        $componente->assertDontSee('lg:w-[380px]', false);
    }

    public function test_la_lista_sigue_visible_detras_del_modal(): void
    {
        $componente = $this->institucion()
            ->call('verDetalle', $this->idDeIncidencia(self::LUIS));

        $componente
            ->assertSee('Mostrando')
            ->assertSee('Maria Bernales Soto');
    }

    public function test_la_pantalla_carga_desde_el_boton_central_de_riesgos_del_sidebar(): void
    {
        $this->actingAs(Usuario::query()->firstOrFail());
        $this->withoutVite()
            ->get(route('central-riesgo'))
            ->assertOk()
            ->assertSeeInOrder(['Central de riesgos', 'Toda la institucion', 'Mis materias', 'Ordenar A-Z'])
            ->assertSee('href="'.route('central-riesgo').'"', false)
            ->assertSee('Luis Alvarez Gomez');
    }

    public function test_una_escritura_invalida_la_cache_de_la_lista(): void
    {
        $componente = $this->institucion();
        $antes = $this->sisListados($componente);

        // Alta por Eloquent: dispara `saved`, sube la versión de la caché y la
        // lista se vuelve a consultar en vez de servir el resultado viejo.
        $this->crearEstudiante('900000050', 'Zoe', 'Lopez Mena');
        CentralRiesgo::create([
            'id_registro' => ++$this->ultimoIdRegistro,
            'id_examen' => self::EXAMEN_A,
            'sis_estudiante' => '900000050',
            'id_registrador' => self::DOCENTE_A,
            'motivo' => 'uso de dispositivos electronicos',
            'detalle_motivo' => 'Prueba de invalidación de la caché',
            'fecha_registro' => '2026-10-06 08:00:00',
            'tipo_infraccion' => 'tramposo',
        ]);

        $despues = $this->sisListados($componente->call('$refresh'));

        $this->assertNotSame($antes, $despues);
        $this->assertContains('900000050', $despues);
    }

    /** Componente con el alcance "Toda la institución". */
    private function institucion(): Testable
    {
        return Livewire::test(ListaEstudiantes::class)
            ->call('cambiarAlcance', ListarIncidenciasTramposoService::ALCANCE_INSTITUCION);
    }

    /**
     * @return list<string> Códigos SIS de la página visible, en el orden mostrado.
     */
    private function sisListados(Testable $componente): array
    {
        return collect($componente->instance()->estudiantes->items())->pluck('sis')->all();
    }

    /**
     * `assertSeeInOrder` de Livewire compara contra la respuesta JSON, donde las
     * tildes viajan escapadas; acá se compara contra el HTML del componente.
     *
     * @param  list<string>  $textos  Textos esperados, en el orden en que deben aparecer.
     */
    private function assertTextoEnOrden(Testable $componente, array $textos): void
    {
        $this->assertThat($textos, new SeeInOrder($componente->html()));
    }

    private function idDeIncidencia(string $sis): int
    {
        return (int) DB::table('central_riesgo')->where('sis_estudiante', $sis)->value('id_registro');
    }

    /** Agrega sancionados más antiguos que los de base, para llenar páginas. */
    private function crearSancionados(int $cantidad): void
    {
        for ($i = 1; $i <= $cantidad; $i++) {
            $sis = (string) (900000100 + $i);
            $this->crearEstudiante($sis, 'Extra', 'Relleno Numero'.chr(64 + $i));
            $this->crearIncidencia($sis, self::EXAMEN_A, 'tramposo', sprintf('2026-08-%02d 08:00:00', 20 - $i));
        }
    }

    private function crearEstudiante(string $sis, string $nombre, string $apellido): void
    {
        DB::table('estudiante')->insert([
            'sis_estudiante' => $sis,
            'nombre_estudiante' => $nombre,
            'apellido_estudiante' => $apellido,
            'carrera' => 'Sistemas',
        ]);
    }

    /**
     * @param  int|null|false  $curso  Curso registrado; false toma el curso del
     *         examen (el examen compartido queda en el curso del usuario).
     */
    private function crearIncidencia(
        string $sis,
        int $idExamen,
        string $tipo,
        string $fecha,
        int $registrador = self::DOCENTE_A,
        int|null|false $curso = false,
    ): void {
        $cursoPorExamen = [
            self::EXAMEN_A => self::CURSO_A,
            self::EXAMEN_B => self::CURSO_B,
            self::EXAMEN_COMPARTIDO => self::CURSO_A,
        ];

        DB::table('central_riesgo')->insert([
            'id_registro' => ++$this->ultimoIdRegistro,
            'id_examen' => $idExamen,
            'id_curso' => $curso === false ? $cursoPorExamen[$idExamen] : $curso,
            'sis_estudiante' => $sis,
            'id_registrador' => $registrador,
            'motivo' => 'uso de dispositivos electronicos',
            'detalle_motivo' => 'Motivo de prueba '.$sis,
            'fecha_registro' => $fecha,
            'tipo_infraccion' => $tipo,
        ]);
    }

    private function crearEsquema(): void
    {
        Schema::create('usuario', function (Blueprint $tabla): void {
            $tabla->integer('id_usuario')->primary();
            $tabla->string('cod_sis');
            $tabla->string('password');
            $tabla->string('nombre_usuario');
            $tabla->string('apellido');
        });
        Schema::create('rol', function (Blueprint $tabla): void {
            $tabla->integer('id_rol')->primary();
            $tabla->string('nombre_rol');
        });
        Schema::create('rol_usuario', function (Blueprint $tabla): void {
            $tabla->integer('id_usuario');
            $tabla->integer('id_rol');
        });
        Schema::create('estudiante', function (Blueprint $tabla): void {
            $tabla->string('sis_estudiante')->primary();
            $tabla->string('nombre_estudiante');
            $tabla->string('apellido_estudiante');
            $tabla->string('carrera')->nullable();
            $tabla->boolean('verificado')->default(true);
        });
        Schema::create('curso', function (Blueprint $tabla): void {
            $tabla->integer('id_curso')->primary();
            $tabla->string('nombre_curso');
            $tabla->integer('sis_doc');
        });
        Schema::create('estudiante_curso', function (Blueprint $tabla): void {
            $tabla->string('sis_estudiante');
            $tabla->integer('id_curso');
        });
        Schema::create('examen', function (Blueprint $tabla): void {
            $tabla->integer('id_examen')->primary();
        });
        Schema::create('examen_curso', function (Blueprint $tabla): void {
            $tabla->integer('id_examen');
            $tabla->integer('id_curso');
        });
        Schema::create('central_riesgo', function (Blueprint $tabla): void {
            $tabla->integer('id_registro')->primary();
            $tabla->integer('id_examen');
            $tabla->integer('id_curso')->nullable();
            $tabla->string('sis_estudiante');
            $tabla->integer('id_registrador');
            $tabla->string('motivo');
            $tabla->string('detalle_motivo')->nullable();
            $tabla->dateTime('fecha_registro');
            $tabla->string('tipo_infraccion');
            $tabla->foreign('sis_estudiante')->references('sis_estudiante')->on('estudiante');
        });
    }

    private function crearDatos(): void
    {
        DB::table('usuario')->insert([
            ['id_usuario' => self::DOCENTE_A, 'cod_sis' => 'DOC1', 'password' => 'x', 'nombre_usuario' => 'Roberto', 'apellido' => 'Silva'],
            ['id_usuario' => self::DOCENTE_B, 'cod_sis' => 'DOC2', 'password' => 'x', 'nombre_usuario' => 'Patricia', 'apellido' => 'Rojas'],
            ['id_usuario' => self::AUXILIAR, 'cod_sis' => 'AUX1', 'password' => 'x', 'nombre_usuario' => 'Diego', 'apellido' => 'Mendoza'],
        ]);
        DB::table('rol')->insert([
            ['id_rol' => 1, 'nombre_rol' => 'docente'], ['id_rol' => 2, 'nombre_rol' => 'auxiliar'],
        ]);
        DB::table('rol_usuario')->insert([
            ['id_usuario' => self::DOCENTE_A, 'id_rol' => 1],
            ['id_usuario' => self::DOCENTE_B, 'id_rol' => 1],
            ['id_usuario' => self::AUXILIAR, 'id_rol' => 2],
        ]);
        DB::table('curso')->insert([
            // Formato de Crear curso: la lista muestra "Calculo I" y el detalle
            // suma el grupo "2".
            ['id_curso' => self::CURSO_A, 'nombre_curso' => 'Calculo I - Grupo 2', 'sis_doc' => self::DOCENTE_A],
            ['id_curso' => self::CURSO_B, 'nombre_curso' => 'Fisica I', 'sis_doc' => self::DOCENTE_B],
        ]);
        DB::table('examen')->insert([
            ['id_examen' => self::EXAMEN_A], ['id_examen' => self::EXAMEN_B], ['id_examen' => self::EXAMEN_COMPARTIDO],
        ]);
        DB::table('examen_curso')->insert([
            ['id_examen' => self::EXAMEN_A, 'id_curso' => self::CURSO_A],
            ['id_examen' => self::EXAMEN_B, 'id_curso' => self::CURSO_B],
            ['id_examen' => self::EXAMEN_COMPARTIDO, 'id_curso' => self::CURSO_A],
            ['id_examen' => self::EXAMEN_COMPARTIDO, 'id_curso' => self::CURSO_B],
        ]);

        $this->crearEstudiante(self::ANA, 'Ana', 'Zapata Ruiz');
        $this->crearIncidencia(self::ANA, self::EXAMEN_A, 'tramposo', '2026-10-01 08:30:00');

        $this->crearEstudiante(self::LUIS, 'Luis', 'Alvarez Gomez');
        $this->crearIncidencia(self::LUIS, self::EXAMEN_A, 'tramposo', '2026-10-03 10:00:00');

        // María: sancionada en una materia de otro docente.
        $this->crearEstudiante(self::MARIA, 'Maria', 'Bernales Soto');
        $this->crearIncidencia(self::MARIA, self::EXAMEN_B, 'tramposo', '2026-10-02 09:15:00');

        // Pedro: sospechoso, todavía sin sanción; no pertenece a la lista.
        $this->crearEstudiante(self::PEDRO_SOSPECHOSO, 'Pedro', 'Acosta Lima');
        $this->crearIncidencia(self::PEDRO_SOSPECHOSO, self::EXAMEN_A, 'sospechoso', '2026-10-04 11:00:00');

        // Carla: mismos apellidos que Ana, para el desempate por nombre.
        $this->crearEstudiante(self::CARLA, 'Carla', 'Zapata Ruiz');
        $this->crearIncidencia(self::CARLA, self::EXAMEN_A, 'tramposo', '2026-09-30 12:00:00');

        // Jaime: examen compartido, registrado en la materia del usuario.
        $this->crearEstudiante(self::JAIME, 'Jaime', 'Paredes Rojas');
        $this->crearIncidencia(self::JAIME, self::EXAMEN_COMPARTIDO, 'tramposo', '2026-09-28 09:00:00');
    }
}

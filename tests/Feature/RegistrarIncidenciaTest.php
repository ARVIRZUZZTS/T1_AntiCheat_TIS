<?php

/**
 * @file    RegistrarIncidenciaTest.php
 * @author  Valery D. Ortuno P. <valerydariana98@gmail.com>
 * @created 2026-09-25
 * @updated 2026-09-26
 *
 * @description
 * Pruebas del formulario de registro de incidencias: precarga desde el monitor,
 * búsqueda del estudiante en la base de datos, motivos, obligatoriedad de la
 * descripción y estado derivado del rol de quien registra.
 *
 * El esquema de estas tablas no tiene migraciones Eloquent (se crea vía
 * docker/postgres/init/001_create_schema.sql), por eso se usa
 * DatabaseTransactions en vez de RefreshDatabase: cada test crea sus propios
 * datos con IDs dedicados (rango 900000+) y se revierten al terminar, sin
 * tocar los datos reales de desarrollo. Requiere el contenedor de Docker
 * levantado (ver INSTALACION_DOCKER.md).
 *
 * @see  App\Livewire\Monitoreo\RegistrarIncidencia
 *
 * @changelog
 * - 2026-09-25  [Valery D. Ortuno P]  feat: creación inicial de las pruebas.
 * - 2026-09-26  [Valery D. Ortuno P]  feat: pruebas de la precarga por URL, del
 *   buscador de estudiantes y de la obligatoriedad condicional de la descripción;
 *   cambio de RefreshDatabase a DatabaseTransactions para no borrar el esquema.
 * - 2026-09-26  [Amiddala]  fix: la etiqueta del auxiliar pasa de "En revisión" a
 *   "Sospechoso", para calzar literal con el criterio de aceptación de la #68.
 */

namespace Tests\Feature;

use App\Enums\TipoInfraccion;
use App\Livewire\Monitoreo\RegistrarIncidencia;
use App\Models\Curso;
use App\Models\Estudiante;
use App\Models\Rol;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class RegistrarIncidenciaTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Curso de prueba, en el rango reservado para los tests.
     *
     * @var int
     */
    private const ID_CURSO = 900001;

    /**
     * Docente de prueba, requerido por la clave foránea de `curso.sis_doc`.
     *
     * @var int
     */
    private const ID_DOCENTE = 900001;

    /**
     * Nombre del curso de prueba, para no chocar con los datos de desarrollo.
     *
     * @var string
     */
    private const NOMBRE_CURSO = 'Curso de Prueba';

    /**
     * Código SIS del estudiante de prueba, en el rango reservado.
     *
     * @var string
     */
    private const SIS_ESTUDIANTE = '90000001';

    /**
     * Crea el docente, el curso y el estudiante que necesitan el buscador y el
     * selector de materia.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usuario')->insert([
            'id_usuario' => self::ID_DOCENTE,
            'cod_sis' => 'TESTDOC1',
            'contraseña' => 'x',
            'nombre_usuario' => 'Docente',
            'apellido' => 'De Prueba',
        ]);

        Curso::query()->create([
            'id_curso' => self::ID_CURSO,
            'nombre_curso' => self::NOMBRE_CURSO,
            'sis_doc' => self::ID_DOCENTE,
            'fecha_creacion' => now()->toDateString(),
            'estado' => 'EnCurso',
        ]);

        Estudiante::query()->create([
            'sis_estudiante' => self::SIS_ESTUDIANTE,
            'nombre_estudiante' => 'Juan',
            'apellido_estudiante' => 'Perez',
            'carrera' => 'Ingenieria de Sistemas',
        ]);
    }

    /**
     * Verifica que al llegar desde el monitor se precarguen el estudiante y el
     * estado que corresponde al rol recibido, y que el formulario se muestre con
     * sus dos tarjetas.
     */
    public function test_desde_el_monitor_se_precargan_el_estudiante_y_el_estado(): void
    {
        $this->get('/registrar-incidencia?origen=monitoreo&nombre=Ana%20L%C3%B3pez&sis=202201013&rol=docente')
            ->assertOk()
            ->assertSee('Datos del estudiante')
            ->assertSee('Detalles de la incidencia')
            ->assertSee('Ana López')
            ->assertSee('202201013')
            ->assertSee('Confirmado');
    }

    /**
     * Verifica que sin el contexto del monitor los datos del estudiante queden
     * vacíos para completarlos con el buscador.
     */
    public function test_sin_contexto_de_monitor_el_formulario_se_abre_vacio(): void
    {
        $this->get('/registrar-incidencia')
            ->assertOk()
            ->assertSee('name="nombreEstudiante"', escape: false)
            ->assertSee('value=""', escape: false);

        Livewire::test(RegistrarIncidencia::class)
            ->assertSet('nombreEstudiante', '')
            ->assertSet('codigoSis', '')
            ->assertSet('materia', '');
    }

    /**
     * Verifica que la etiqueta y el tipo de infraccion del estado sigan al rol:
     * el docente confirma y el auxiliar deja el caso como sospechoso.
     */
    public function test_el_estado_depende_del_rol_del_registrador(): void
    {
        Livewire::test(RegistrarIncidencia::class)
            ->set('rol', Rol::NOMBRE_DOCENTE)
            ->assertSee('Confirmado')
            ->assertSet('tipoInfraccion', TipoInfraccion::Tramposo);

        Livewire::test(RegistrarIncidencia::class)
            ->set('rol', Rol::NOMBRE_AUXILIAR)
            ->assertSee('Sospechoso')
            ->assertSet('tipoInfraccion', TipoInfraccion::Sospechoso);
    }

    /**
     * Verifica que el buscador encuentre al estudiante por nombre, por apellido
     * y por codigo SIS.
     */
    public function test_el_buscador_encuentra_por_nombre_apellido_y_codigo_sis(): void
    {
        $componente = Livewire::test(RegistrarIncidencia::class)
            ->set('busqueda', 'Jua')
            ->assertSee('Juan Perez')
            ->set('busqueda', 'Perez')
            ->assertSee('Juan Perez')
            ->set('busqueda', self::SIS_ESTUDIANTE)
            ->assertSee('Juan Perez');

        $this->assertCount(1, $componente->instance()->resultadosBusqueda());
    }

    /**
     * Verifica que no se ofrezcan resultados con menos de dos caracteres escritos.
     */
    public function test_el_buscador_no_pregunta_con_un_solo_caracter(): void
    {
        $componente = Livewire::test(RegistrarIncidencia::class)->set('busqueda', 'J');

        $this->assertCount(0, $componente->instance()->resultadosBusqueda());
    }

    /**
     * Verifica que elegir un resultado del buscador reemplace los datos del
     * estudiante y limpie el texto de busqueda.
     */
    public function test_elegir_un_resultado_reemplaza_al_estudiante(): void
    {
        Livewire::test(RegistrarIncidencia::class)
            ->set('busqueda', 'Juan')
            ->call('seleccionarEstudiante', self::SIS_ESTUDIANTE)
            ->assertSet('codigoSis', self::SIS_ESTUDIANTE)
            ->assertSet('nombreEstudiante', 'Juan Perez')
            ->assertSet('busqueda', '');
    }

    /**
     * Verifica que elegir un resultado que no existe en la base de datos no rompa
     * el formulario ni cambie el estudiante ya elegido.
     */
    public function test_elegir_un_resultado_inexistente_no_cambia_al_estudiante(): void
    {
        Livewire::test(RegistrarIncidencia::class)
            ->set('codigoSis', self::SIS_ESTUDIANTE)
            ->call('seleccionarEstudiante', '99999999')
            ->assertSet('codigoSis', self::SIS_ESTUDIANTE);
    }

    /**
     * Verifica que el selector de materia ofrezca los cursos de la base de datos.
     */
    public function test_el_selector_de_materia_trae_los_cursos_de_la_base_de_datos(): void
    {
        $materias = Livewire::test(RegistrarIncidencia::class)->instance()->materias();

        $this->assertArrayHasKey(self::NOMBRE_CURSO, $materias->toArray());
    }

    /**
     * Verifica que se exijan el estudiante, la materia y el motivo al registrar.
     */
    public function test_exige_estudiante_materia_y_motivo_al_registrar(): void
    {
        Livewire::test(RegistrarIncidencia::class)
            ->call('registrar')
            ->assertHasErrors(['codigoSis', 'materia', 'tipoIncidencia'])
            ->assertSee('aria-invalid="true"', escape: false);
    }

    /**
     * Verifica que la descripcion sea opcional con un motivo del catalogo y
     * obligatoria cuando se elige "Otro".
     */
    public function test_la_descripcion_solo_es_obligatoria_con_el_motivo_otro(): void
    {
        Livewire::test(RegistrarIncidencia::class)
            ->set('codigoSis', self::SIS_ESTUDIANTE)
            ->set('materia', self::NOMBRE_CURSO)
            ->set('tipoIncidencia', 'uso_de_dispositivo')
            ->call('registrar')
            ->assertHasNoErrors();

        Livewire::test(RegistrarIncidencia::class)
            ->set('codigoSis', self::SIS_ESTUDIANTE)
            ->set('materia', self::NOMBRE_CURSO)
            ->set('tipoIncidencia', RegistrarIncidencia::MOTIVO_OTRO)
            ->call('registrar')
            ->assertHasErrors('descripcion');
    }

    /**
     * Verifica que el asterisco de la descripcion aparezca solo cuando el motivo
     * obliga a rellenarla, que es el que no encaja en los demás.
     */
    public function test_la_etiqueta_de_descripcion_cambia_segun_el_motivo(): void
    {
        Livewire::test(RegistrarIncidencia::class)
            ->set('tipoIncidencia', 'uso_de_dispositivo')
            ->assertSee('Descripción del hecho</label>', escape: false)
            ->set('tipoIncidencia', RegistrarIncidencia::MOTIVO_OTRO)
            ->assertSee('Descripción del hecho *</label>', escape: false);
    }

    /**
     * Verifica que se rechace una descripcion mas larga que el limite.
     */
    public function test_rechaza_una_descripcion_mas_larga_al_limite(): void
    {
        Livewire::test(RegistrarIncidencia::class)
            ->set('codigoSis', self::SIS_ESTUDIANTE)
            ->set('materia', self::NOMBRE_CURSO)
            ->set('tipoIncidencia', 'uso_de_dispositivo')
            ->set('descripcion', str_repeat('a', RegistrarIncidencia::DESCRIPCION_MAXIMO + 1))
            ->call('registrar')
            ->assertHasErrors('descripcion');
    }

    /**
     * Verifica que el contador de la descripcion acompanie lo escrito.
     */
    public function test_el_contador_de_descripcion_crece_al_escribir(): void
    {
        $escrito = 'El estudiante miro el celular';

        Livewire::test(RegistrarIncidencia::class)
            ->set('descripcion', $escrito)
            ->assertSee(mb_strlen($escrito).' / 300', escape: false);
    }

    /**
     * Verifica que cancelar desde el monitor regrese al monitor en vivo.
     */
    public function test_cancelar_desde_el_monitor_regresa_al_monitor(): void
    {
        Livewire::test(RegistrarIncidencia::class)
            ->set('origen', RegistrarIncidencia::ORIGEN_MONITOREO)
            ->call('cancelar')
            ->assertRedirect(route('monitoreo'));
    }

    /**
     * Verifica que se ofrezcan los cuatro motivos acordados por el equipo mas
     * la opcion de escribir otro motivo.
     */
    public function test_los_motivos_son_los_acordados_mas_otro(): void
    {
        Livewire::test(RegistrarIncidencia::class)
            ->assertSee('Uso de dispositivo electrónico')
            ->assertSee('Ingreso a examen no autorizado')
            ->assertSee('Copia o ayuda externa')
            ->assertSee('Suplantación de identidad')
            ->assertSee('Otro');
    }
}
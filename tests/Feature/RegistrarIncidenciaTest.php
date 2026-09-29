<?php

/**
 * @file    RegistrarIncidenciaTest.php
 * @author  Valery D. Ortuno P. <valerydariana98@gmail.com>
 * @created 2026-09-25
 * @updated 2026-09-28
 *
 * @description
 * Pruebas del formulario de registro de incidencias: precarga desde el monitor,
 * entrada en blanco desde la central de riesgo, búsqueda del estudiante en la
 * base de datos, motivos, obligatoriedad de la descripción y estado derivado
 * del rol de quien registra.
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
 * - 2026-09-28  [Candy]  feat: pruebas de la entrada desde la central de riesgo,
 *   que abre el formulario en blanco, y del enlace de vuelta y el cancelar
 *   según la pantalla de origen.
 * - 2026-09-28  [Valery D. Ortuno P]  fix: ajuste a nombre y apellido separados,
 *   motivos acordados por el equipo, materia de texto libre con solo letras,
 *   números y espacios, y etiquetas sin acentos (#66).
 * - 2026-09-28  [Valery D. Ortuno P]  fix: pruebas de los campos editables del
 *   estudiante: nombre y apellido solo con letras y código SIS de 9 dígitos
 *   (#66).
 * - 2026-09-28  [Valery D. Ortuno P]  fix: prueba de que los mensajes de
 *   obligatoriedad se muestren en español (#66).
 * - 2026-09-28  [Candy]  feat: pruebas del modal de confirmación, del resumen
 *   que muestra, del estado según el rol y de que aceptar devuelva a la
 *   pantalla de origen sin dejar registrar dos veces.
 * - 2026-09-28  [Candy]  feat: pruebas de la persistencia en `central_riesgo`
 *   (estudiante, registrador, materia, estado, motivo, descripción y fecha con
 *   hora), del reporte a un estudiante sin ingreso previo y del id que asigna la
 *   secuencia.
 * - 2026-09-29  [Candy]  feat: pruebas del modal con el esquema que quedó tras el
 *   merge: `sis_estudiante`, `id_examen` y el enum `motivo`; la materia del
 *   examen del monitor, el rechazo de un SIS que no está en la base y el resumen
 *   con el número del registro (#70).
 * - 2026-09-28  [Valery D. Ortuno P]  feat: pruebas del aviso de codigo SIS
 *   duplicado escrito a mano, con la excepcion del estudiante precargado del
 *   monitor y del elegido con la lupa. La prueba de la semilla ahora precarga
 *   tambien `sisPrecargado`, como hace mount() con lo que llega por la URL.
 * - 2026-09-29  [Valery D. Ortuno P]  fix: pruebas de que el modal quede dentro
 *   de la raiz del componente, de que no aparezca como raiz extra ni con el modal
 *   abierto ni cerrado, y de que el resumen no muestre quien registro (#70).
 */

namespace Tests\Feature;

use App\Enums\Motivo;
use App\Enums\TipoInfraccion;
use App\Livewire\Monitoreo\RegistrarIncidencia;
use App\Models\CentralRiesgo;
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
     * Materia típica de un examen, usada para validar el campo de texto libre.
     *
     * @var string
     */
    private const MATERIA_EJEMPLO = 'Calculo I';

    /**
     * Nombre y apellido válidos para el formulario.
     *
     * @var string
     */
    private const NOMBRE = 'Ana';

    /**
     * Apellido válido, con tilde, para comprobar que las reglas aceptan
     * caracteres acentuados.
     *
     * @var string
     */
    private const APELLIDO = 'López';

    /**
     * Código SIS válido para registrar: nueve dígitos, como exige la regla
     * `digits:9`, y presente en la base de datos.
     *
     * Lo segundo es obligatorio desde que `central_riesgo.sis_estudiante` tiene
     * llave foránea contra `estudiante`: registrar a alguien que no está en la
     * base ya no es posible (#70). `setUp` lo crea.
     *
     * @var string
     */
    private const SIS_VALIDO = '900000012';

    /**
     * Código SIS del estudiante que usa el buscador, en el rango reservado.
     *
     * @var string
     */
    private const SIS_ESTUDIANTE = '90000099';

    /**
     * Examen que usa el monitor en vivo, del que se deriva la materia al
     * guardar. En la semilla es el examen 3, del curso `Programacion I`.
     *
     * @var int
     */
    private const EXAMEN_DEL_MONITOR = 3;

    /**
     * Nombre del curso del examen del monitor, que es la materia que termina
     * mostrando el modal.
     *
     * @var string
     */
    private const MATERIA_DEL_EXAMEN = 'Programacion I';

    /**
     * Crea los estudiantes que necesitan las pruebas: el del buscador y el que
     * se puede registrar en la central de riesgos.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        Estudiante::query()->create([
            'sis_estudiante' => self::SIS_ESTUDIANTE,
            'nombre_estudiante' => 'Juan',
            'apellido_estudiante' => 'Perez',
            'carrera' => 'Ingenieria de Sistemas',
        ]);

        // El nombre y el apellido coinciden con `NOMBRE` y `APELLIDO` para que el
        // resumen del modal, que los lee de la base, muestre lo mismo que se
        // escribió en el formulario.
        Estudiante::query()->create([
            'sis_estudiante' => self::SIS_VALIDO,
            'nombre_estudiante' => self::NOMBRE,
            'apellido_estudiante' => self::APELLIDO,
            'carrera' => 'Ingenieria de Sistemas',
        ]);
    }

    /**
     * Verifica que al llegar desde el monitor se precarguen el estudiante y el
     * estado que corresponde al rol recibido, y que el formulario se muestre con
     * sus dos tarjetas. Los datos del estudiante llegan cargados pero editables,
     * por si el mismo no esta en la base de datos; la materia que viene del
     * monitor, en cambio, es de solo lectura.
     */
    public function test_desde_el_monitor_se_precargan_el_estudiante_y_el_estado(): void
    {
        $respuesta = $this->get('/registrar-incidencia?origen=monitoreo&nombre=Ana%20L%C3%B3pez&sis=202201013&materia=Programacion%20I&rol=docente')
            ->assertOk()
            ->assertSee('Datos del estudiante')
            ->assertSee('Detalles de la incidencia')
            ->assertSee('value="Ana"', escape: false)
            ->assertSee('value="López"', escape: false)
            ->assertSee('value="202201013"', escape: false)
            ->assertSee('value="Programacion I"', escape: false)
            ->assertSee('Confirmado');

        $cuerpo = $respuesta->getContent();

        foreach (['nombreEstudiante', 'apellidoEstudiante', 'codigoSis'] as $campo) {
            $this->assertMatchesRegularExpression(
                '/<input(?![^>]*readonly)[^>]*name="'.$campo.'"/',
                $cuerpo,
                'El campo '.$campo.' debe llegar editable para poder corregirlo a mano.'
            );
        }

        $this->assertMatchesRegularExpression(
            '/<input[^>]*name="materia"[^>]*readonly/',
            $cuerpo,
            'La materia que llega del monitor es un dato de solo lectura.'
        );
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
            ->assertSet('apellidoEstudiante', '')
            ->assertSet('codigoSis', '')
            ->assertSet('materia', '');
    }

    /**
     * Verifica que los mensajes de campo obligatorio esten en el idioma de la
     * interfaz: la aplicacion corre en ingles, asi que sin mensajes propios
     * Laravel responderia "The nombre field is required".
     */
    public function test_los_mensajes_de_obligatoriedad_estan_en_espanol(): void
    {
        Livewire::test(RegistrarIncidencia::class)
            ->call('registrar')
            ->assertSee('Ingrese el nombre del estudiante.')
            ->assertSee('Ingrese el apellido del estudiante.')
            ->assertSee('Ingrese el código SIS del estudiante.')
            ->assertSee('Ingrese la materia del examen.')
            ->assertSee('Seleccione el motivo de la incidencia.')
            ->assertDontSee('field is required');
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
            ->assertSee('En revision')
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
            ->assertSet('nombreEstudiante', 'Juan')
            ->assertSet('apellidoEstudiante', 'Perez')
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
     * Verifica que la materia admita solo letras, números y espacios, porque no
     * hay un catálogo de materias en la base: llega precargada del monitor o se
     * escribe a mano cuando el formulario se abre sin contexto.
     */
    public function test_la_materia_solo_admite_letras_numeros_y_espacios(): void
    {
        Livewire::test(RegistrarIncidencia::class)
            ->set('nombreEstudiante', self::NOMBRE)
            ->set('apellidoEstudiante', self::APELLIDO)
            ->set('codigoSis', self::SIS_VALIDO)
            ->set('sisPrecargado', self::SIS_VALIDO)
            ->set('tipoIncidencia', 'copia_o_intercambio_de_respuestas')
            ->set('materia', 'Fisica 1!')
            ->call('registrar')
            ->assertHasErrors('materia')
            ->assertSee('La materia solo puede contener letras, números y espacios.', escape: false);

        Livewire::test(RegistrarIncidencia::class)
            ->set('nombreEstudiante', self::NOMBRE)
            ->set('apellidoEstudiante', self::APELLIDO)
            ->set('codigoSis', self::SIS_VALIDO)
            ->set('sisPrecargado', self::SIS_VALIDO)
            ->set('tipoIncidencia', RegistrarIncidencia::MOTIVO_OTRO)
            ->set('materia', self::MATERIA_EJEMPLO)
            ->set('descripcion', 'Descripcion de prueba')
            ->call('registrar')
            ->assertHasNoErrors();
    }

    /**
     * Verifica que el nombre y el apellido solo admitan letras, porque se
     * escriben a mano cuando el estudiante no esta en la base de datos.
     */
    public function test_nombre_y_apellido_solo_admiten_letras(): void
    {
        Livewire::test(RegistrarIncidencia::class)
            ->set('nombreEstudiante', 'Ana2')
            ->set('apellidoEstudiante', self::APELLIDO)
            ->set('codigoSis', self::SIS_VALIDO)
            ->set('sisPrecargado', self::SIS_VALIDO)
            ->set('tipoIncidencia', 'copia_o_intercambio_de_respuestas')
            ->set('materia', self::MATERIA_EJEMPLO)
            ->call('registrar')
            ->assertHasErrors('nombreEstudiante')
            ->assertSee('El nombre solo puede contener letras.', escape: false);

        Livewire::test(RegistrarIncidencia::class)
            ->set('nombreEstudiante', self::NOMBRE)
            ->set('apellidoEstudiante', 'Lopez1')
            ->set('codigoSis', self::SIS_VALIDO)
            ->set('sisPrecargado', self::SIS_VALIDO)
            ->set('tipoIncidencia', 'copia_o_intercambio_de_respuestas')
            ->set('materia', self::MATERIA_EJEMPLO)
            ->call('registrar')
            ->assertHasErrors('apellidoEstudiante')
            ->assertSee('El apellido solo puede contener letras.', escape: false);
    }

    /**
     * Verifica que el codigo SIS admita solo 9 numeros, sin letras ni signos.
     */
    public function test_el_codigo_sis_solo_admite_nueve_numeros(): void
    {
        Livewire::test(RegistrarIncidencia::class)
            ->set('nombreEstudiante', self::NOMBRE)
            ->set('apellidoEstudiante', self::APELLIDO)
            ->set('codigoSis', '20220101')
            ->set('tipoIncidencia', 'copia_o_intercambio_de_respuestas')
            ->set('materia', self::MATERIA_EJEMPLO)
            ->call('registrar')
            ->assertHasErrors('codigoSis')
            ->assertSee('El código SIS debe tener 9 números.', escape: false);

        Livewire::test(RegistrarIncidencia::class)
            ->set('nombreEstudiante', self::NOMBRE)
            ->set('apellidoEstudiante', self::APELLIDO)
            ->set('codigoSis', '2022A013')
            ->set('tipoIncidencia', 'copia_o_intercambio_de_respuestas')
            ->set('materia', self::MATERIA_EJEMPLO)
            ->call('registrar')
            ->assertHasErrors('codigoSis');

        Livewire::test(RegistrarIncidencia::class)
            ->set('nombreEstudiante', self::NOMBRE)
            ->set('apellidoEstudiante', self::APELLIDO)
            ->set('codigoSis', self::SIS_VALIDO)
            ->set('sisPrecargado', self::SIS_VALIDO)
            ->set('tipoIncidencia', 'copia_o_intercambio_de_respuestas')
            ->set('materia', self::MATERIA_EJEMPLO)
            ->call('registrar')
            ->assertHasNoErrors();
    }

    /**
     * Verifica que se exijan el nombre, el apellido, el codigo, la materia y el
     * motivo al registrar.
     */
    public function test_exige_estudiante_materia_y_motivo_al_registrar(): void
    {
        Livewire::test(RegistrarIncidencia::class)
            ->call('registrar')
            ->assertHasErrors([
                'nombreEstudiante',
                'apellidoEstudiante',
                'codigoSis',
                'materia',
                'tipoIncidencia',
            ])
            ->assertSee('aria-invalid="true"', escape: false);
    }

    /**
     * Verifica que la descripcion sea opcional con un motivo del catalogo y
     * obligatoria cuando se elige "Otro".
     */
    public function test_la_descripcion_solo_es_obligatoria_con_el_motivo_otro(): void
    {
        Livewire::test(RegistrarIncidencia::class)
            ->set('nombreEstudiante', self::NOMBRE)
            ->set('apellidoEstudiante', self::APELLIDO)
            ->set('codigoSis', self::SIS_VALIDO)
            ->set('sisPrecargado', self::SIS_VALIDO)
            ->set('materia', self::MATERIA_EJEMPLO)
            ->set('tipoIncidencia', 'copia_o_intercambio_de_respuestas')
            ->call('registrar')
            ->assertHasNoErrors();

        Livewire::test(RegistrarIncidencia::class)
            ->set('nombreEstudiante', self::NOMBRE)
            ->set('apellidoEstudiante', self::APELLIDO)
            ->set('codigoSis', self::SIS_VALIDO)
            ->set('sisPrecargado', self::SIS_VALIDO)
            ->set('materia', self::MATERIA_EJEMPLO)
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
            ->set('tipoIncidencia', 'copia_o_intercambio_de_respuestas')
            ->assertSee('Descripcion del hecho</label>', escape: false)
            ->set('tipoIncidencia', RegistrarIncidencia::MOTIVO_OTRO)
            ->assertSee('Descripcion del hecho *</label>', escape: false);
    }

    /**
     * Verifica que se rechace una descripcion mas larga que el limite.
     */
    public function test_rechaza_una_descripcion_mas_larga_al_limite(): void
    {
        Livewire::test(RegistrarIncidencia::class)
            ->set('nombreEstudiante', self::NOMBRE)
            ->set('apellidoEstudiante', self::APELLIDO)
            ->set('codigoSis', self::SIS_VALIDO)
            ->set('sisPrecargado', self::SIS_VALIDO)
            ->set('materia', self::MATERIA_EJEMPLO)
            ->set('tipoIncidencia', 'copia_o_intercambio_de_respuestas')
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
     * Verifica que la central de riesgo ofrezca el enlace al formulario de
     * registro de incidencia y que lo abra sin datos precargados.
     */
    public function test_desde_la_central_de_riesgo_el_formulario_se_abre_vacio(): void
    {
        $this->get('/central-riesgo')
            ->assertOk()
            ->assertSee(route('registrar-incidencia', ['origen' => 'central-riesgo']), escape: false);

        $this->get('/registrar-incidencia?origen=central-riesgo')
            ->assertOk()
            ->assertSee('Volver a la central de riesgo')
            ->assertDontSee('Ana López');

        Livewire::withQueryParams(['origen' => 'central-riesgo'])
            ->test(RegistrarIncidencia::class)
            ->assertSet('origen', RegistrarIncidencia::ORIGEN_CENTRAL_RIESGO)
            ->assertSet('nombreEstudiante', '')
            ->assertSet('codigoSis', '')
            ->assertSet('materia', '');
    }

    /**
     * Verifica que el estudiante y la materia que viajan en la URL se ignoren
     * cuando el origen no es el monitor: desde la central de riesgo el
     * estudiante se busca a mano.
     */
    public function test_la_central_de_riesgo_no_precarga_el_estudiante_que_viaje_en_la_url(): void
    {
        Livewire::withQueryParams([
            'origen' => 'central-riesgo',
            'nombre' => 'Ana López',
            'sis' => '202201013',
            'materia' => 'Curso de Prueba',
        ])->test(RegistrarIncidencia::class)
            ->assertSet('nombreEstudiante', '')
            ->assertSet('codigoSis', '')
            ->assertSet('materia', '');
    }

    /**
     * Verifica que el enlace de vuelta y el cancelar devuelvan a la central de
     * riesgo cuando el formulario se abrió desde ahí.
     */
    public function test_cancelar_desde_la_central_de_riesgo_regresa_a_la_central(): void
    {
        Livewire::test(RegistrarIncidencia::class)
            ->set('origen', RegistrarIncidencia::ORIGEN_CENTRAL_RIESGO)
            ->assertSee(route('central-riesgo'), escape: false)
            ->call('cancelar')
            ->assertRedirect(route('central-riesgo'));
    }

    /**
     * Verifica que sin origen conocido el enlace de vuelta apunte al monitor en
     * vivo, que es la pantalla desde la que se entra al formulario hoy.
     */
    public function test_sin_origen_el_enlace_de_vuelta_apunta_al_monitor(): void
    {
        Livewire::test(RegistrarIncidencia::class)
            ->assertSee('Volver al monitor en vivo')
            ->assertSee(route('monitoreo'), escape: false);
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
     * Verifica que se ofrezcan los motivos acordados por el equipo mas la
     * opcion de escribir otro motivo, y que la etiqueta del campo lo invite a
     * elegir uno.
     */
    public function test_los_motivos_son_los_acordados_mas_otro(): void
    {
        Livewire::test(RegistrarIncidencia::class)
            ->assertSee('Seleccione el motivo de la incidencia')
            ->assertSee('Intento de Ingreso a examen no autorizado')
            ->assertSee('Uso de dispositivos electrónicos no autorizados')
            ->assertSee('Copia o intercambio de respuestas')
            ->assertSee('Uso de material no autorizado')
            ->assertSee('Suplantación de identidad')
            ->assertSee('Otro');
    }

    /**
     * Verifica que los estudiantes que trae la semilla de `002_seed_data.sql`
     * sirvan para probar el formulario: codigo SIS de 9 digitos y nombre y
     * apellido que el componente acepta. Si alguien cambia la semilla y rompe
     * una de las dos cosas, este test lo avisa en vez de dejarlo para
     * descubrirlo a mano en el navegador.
     */
    public function test_los_estudiantes_de_la_semilla_son_validos_para_el_formulario(): void
    {
        $estudiantes = Estudiante::query()
            ->where('sis_estudiante', 'not like', '9%')
            ->orderBy('sis_estudiante')
            ->get();

        $this->assertGreaterThan(0, $estudiantes->count(), 'La semilla debe traer estudiantes de prueba.');

        foreach ($estudiantes as $estudiante) {
            /* El monitor precarga el codigo SIS por la URL, y mount() guarda
               ese mismo valor en `sisPrecargado` para distinguirlo de lo que se
               escribe a mano; hay que setear los dos, como hace el monitor. */
            $componente = Livewire::test(RegistrarIncidencia::class)
                ->set('origen', RegistrarIncidencia::ORIGEN_MONITOREO)
                ->set('nombreEstudiante', $estudiante->nombre_estudiante)
                ->set('apellidoEstudiante', $estudiante->apellido_estudiante)
                ->set('codigoSis', $estudiante->sis_estudiante)
                ->set('sisPrecargado', $estudiante->sis_estudiante)
                ->set('materia', self::MATERIA_EJEMPLO)
                ->set('tipoIncidencia', Motivo::IntentoDeIngresoNoAutorizado->value)
                ->call('registrar');

            $errores = $componente->errors()->toArray();

            $this->assertArrayNotHasKey(
                'codigoSis',
                $errores,
                'El SIS '.$estudiante->sis_estudiante.' no cumple la regla de 9 digitos.',
            );

            foreach (['nombreEstudiante', 'apellidoEstudiante'] as $campo) {
                $this->assertArrayNotHasKey(
                    $campo,
                    $errores,
                    'El valor de '.$campo.' del estudiante '.$estudiante->sis_estudiante
                        .' es rechazado por el formulario: '.$estudiante->nombre_estudiante
                        .' '.$estudiante->apellido_estudiante,
                );
            }
        }
    }

    /**
     * Verifica que al registrar se abra el modal de confirmación con el mensaje
     * de éxito y el resumen de la incidencia.
     */
    public function test_registrar_abre_el_modal_de_confirmacion_con_el_resumen(): void
    {
        Livewire::test(RegistrarIncidencia::class)
            ->set('idExamen', self::EXAMEN_DEL_MONITOR)
            ->set('nombreEstudiante', self::NOMBRE)
            ->set('apellidoEstudiante', self::APELLIDO)
            ->set('codigoSis', self::SIS_VALIDO)
            ->set('sisPrecargado', self::SIS_VALIDO)
            ->set('materia', self::MATERIA_EJEMPLO)
            ->set('tipoIncidencia', 'copia_o_intercambio_de_respuestas')
            ->call('registrar')
            ->assertHasNoErrors()
            ->assertSet('confirmacionVisible', true)
            ->assertSee('Estudiante agregado a la central de riesgos con éxito')
            ->assertSee('Ana López')
            ->assertSee(self::SIS_VALIDO)
            ->assertSee('Copia o intercambio de respuestas')
            ->assertSee('Aceptar');
    }

    /**
     * Verifica que el modal no aparezca antes de registrar, para que el resumen no
     * se confunda con el formulario.
     */
    public function test_el_modal_de_confirmacion_no_aparece_antes_de_registrar(): void
    {
        Livewire::test(RegistrarIncidencia::class)
            ->assertSet('confirmacionVisible', false)
            ->assertDontSee('Estudiante agregado a la central de riesgos con éxito');
    }

    /**
     * Verifica que el resumen no muestre quién registró.
     *
     * Todavía no hay login, así que lo que se guarda es siempre el usuario por
     * defecto: mostrarlo daría un nombre que no es el de quien está frente a la
     * pantalla (#70).
     */
    public function test_el_resumen_no_muestra_registrado_por(): void
    {
        $componente = Livewire::test(RegistrarIncidencia::class)
            ->set('origen', RegistrarIncidencia::ORIGEN_MONITOREO)
            ->set('idExamen', self::EXAMEN_DEL_MONITOR)
            ->set('nombreEstudiante', self::NOMBRE)
            ->set('apellidoEstudiante', self::APELLIDO)
            ->set('codigoSis', self::SIS_VALIDO)
            ->set('sisPrecargado', self::SIS_VALIDO)
            ->set('materia', self::MATERIA_EJEMPLO)
            ->set('tipoIncidencia', 'uso_de_dispositivos_electronicos')
            ->call('registrar')
            ->assertHasNoErrors();

        $componente
            ->assertDontSee('Registrado por')
            ->assertDontSee('registrador');
        $this->assertArrayNotHasKey('registrador', $componente->get('resumen'));
    }

    /**
     * Verifica que el botón Aceptar cierre el modal y devuelva al monitor en
     * vivo, la pantalla desde la que se abrió el formulario.
     */
    public function test_aceptar_cierra_el_modal_y_regresa_al_monitor(): void
    {
        Livewire::test(RegistrarIncidencia::class)
            ->set('origen', RegistrarIncidencia::ORIGEN_MONITOREO)
            ->set('idExamen', self::EXAMEN_DEL_MONITOR)
            ->set('nombreEstudiante', self::NOMBRE)
            ->set('apellidoEstudiante', self::APELLIDO)
            ->set('codigoSis', self::SIS_VALIDO)
            ->set('sisPrecargado', self::SIS_VALIDO)
            ->set('materia', self::MATERIA_EJEMPLO)
            ->set('tipoIncidencia', 'uso_de_dispositivos_electronicos')
            ->call('registrar')
            ->call('aceptarRegistro')
            ->assertRedirect(route('monitoreo'));
    }

    /**
     * Verifica que aceptar desde la entrada de la central de riesgo devuelva a la
     * central, no al monitor.
     */
    public function test_aceptar_desde_la_central_de_riesgo_regresa_a_la_central(): void
    {
        Livewire::test(RegistrarIncidencia::class)
            ->set('origen', RegistrarIncidencia::ORIGEN_CENTRAL_RIESGO)
            ->set('nombreEstudiante', self::NOMBRE)
            ->set('apellidoEstudiante', self::APELLIDO)
            ->set('codigoSis', self::SIS_VALIDO)
            ->set('sisPrecargado', self::SIS_VALIDO)
            ->set('materia', self::MATERIA_EJEMPLO)
            ->set('tipoIncidencia', 'suplantacion_de_identidad')
            ->call('registrar')
            ->call('aceptarRegistro')
            ->assertRedirect(route('central-riesgo'));
    }

    /**
     * Verifica que el resumen guarde el estado derivado del rol: un docente
     * confirma y un auxiliar deja el caso en revisión.
     */
    public function test_el_resumen_muestra_el_estado_segun_el_rol(): void
    {
        Livewire::test(RegistrarIncidencia::class)
            ->set('rol', Rol::NOMBRE_AUXILIAR)
            ->set('idExamen', self::EXAMEN_DEL_MONITOR)
            ->set('nombreEstudiante', self::NOMBRE)
            ->set('apellidoEstudiante', self::APELLIDO)
            ->set('codigoSis', self::SIS_VALIDO)
            ->set('sisPrecargado', self::SIS_VALIDO)
            ->set('materia', self::MATERIA_EJEMPLO)
            ->set('tipoIncidencia', 'uso_de_material_no_autorizado')
            ->call('registrar')
            ->assertSee('En revision');
    }

    /**
     * Verifica que un segundo envío no vuelva a registrar mientras el modal está
     * abierto, para no duplicar la incidencia con un doble clic.
     */
    public function test_no_se_registra_de_nuevo_con_el_modal_abierto(): void
    {
        $componente = Livewire::test(RegistrarIncidencia::class)
            ->set('idExamen', self::EXAMEN_DEL_MONITOR)
            ->set('nombreEstudiante', self::NOMBRE)
            ->set('apellidoEstudiante', self::APELLIDO)
            ->set('codigoSis', self::SIS_VALIDO)
            ->set('sisPrecargado', self::SIS_VALIDO)
            ->set('materia', self::MATERIA_EJEMPLO)
            ->set('tipoIncidencia', 'intento_de_ingreso_no_autorizado')
            ->call('registrar')
            ->assertSet('confirmacionVisible', true);

        $resumen = $componente->get('resumen');

        // Se vacían los campos y se vuelve a enviar: el registro ya está hecho,
        // así que el resumen no debe cambiar.
        $componente
            ->set('nombreEstudiante', '')
            ->set('materia', '')
            ->call('registrar')
            ->assertSet('resumen', $resumen);
    }

    /**
     * Verifica que registrar guarde de verdad la incidencia en la central de
     * riesgos, con el estudiante, el examen, el usuario registrador, el motivo,
     * el detalle, el tipo de infracción y la fecha con hora.
     */
    public function test_registrar_guarda_la_incidencia_en_la_central_de_riesgos(): void
    {
        $antes = CentralRiesgo::query()->count();

        Livewire::test(RegistrarIncidencia::class)
            ->set('idExamen', self::EXAMEN_DEL_MONITOR)
            ->set('usuario', 3)
            ->set('nombreEstudiante', self::NOMBRE)
            ->set('apellidoEstudiante', self::APELLIDO)
            ->set('codigoSis', self::SIS_VALIDO)
            ->set('sisPrecargado', self::SIS_VALIDO)
            ->set('materia', self::MATERIA_EJEMPLO)
            ->set('tipoIncidencia', 'uso_de_dispositivos_electronicos')
            ->set('descripcion', 'Se le vio el celular debajo del banco')
            ->call('registrar')
            ->assertHasNoErrors()
            ->assertSet('confirmacionVisible', true);

        $this->assertSame($antes + 1, CentralRiesgo::query()->count(), 'Debe guardarse una fila nueva.');

        $registro = CentralRiesgo::query()->orderByDesc('id_registro')->firstOrFail();

        $this->assertSame(3, $registro->id_registrador, 'Debe guardarse el usuario que reporta.');
        $this->assertSame(self::SIS_VALIDO, $registro->sis_estudiante);
        $this->assertSame(self::EXAMEN_DEL_MONITOR, $registro->id_examen);
        $this->assertSame(Motivo::UsoDeDispositivosElectronicos, $registro->motivo);
        $this->assertSame('Se le vio el celular debajo del banco', $registro->detalle_motivo);
        $this->assertSame(TipoInfraccion::Tramposo, $registro->tipo_infraccion);
        $this->assertNotNull($registro->fecha_registro, 'Debe guardarse la fecha del registro.');

        // La fecha se guarda con hora, no solo el día.
        $this->assertNotSame(
            $registro->fecha_registro->format('d/m/Y H:i'),
            $registro->fecha_registro->format('d/m/Y'),
            'La fecha guardada debe conservar la hora.'
        );
    }

    /**
     * Verifica que el motivo se guarde en el enum `motivo` y no como texto en
     * `detalle_motivo`, que quedó para el detalle del motivo "Otro".
     */
    public function test_el_motivo_se_guarda_en_la_columna_motivo(): void
    {
        Livewire::test(RegistrarIncidencia::class)
            ->set('idExamen', self::EXAMEN_DEL_MONITOR)
            ->set('nombreEstudiante', self::NOMBRE)
            ->set('apellidoEstudiante', self::APELLIDO)
            ->set('codigoSis', self::SIS_VALIDO)
            ->set('sisPrecargado', self::SIS_VALIDO)
            ->set('materia', self::MATERIA_EJEMPLO)
            ->set('tipoIncidencia', 'intento_de_ingreso_no_autorizado')
            ->call('registrar')
            ->assertHasNoErrors();

        $registro = CentralRiesgo::query()->orderByDesc('id_registro')->firstOrFail();

        $this->assertSame(Motivo::IntentoDeIngresoNoAutorizado, $registro->motivo);
        $this->assertNull($registro->detalle_motivo, 'Sin descripción no hay detalle que guardar.');
    }

    /**
     * Verifica que la materia mostrada en el modal sea la del examen registrado
     * y no la escrita en el formulario, porque la base la deriva de `id_examen`.
     */
    public function test_el_modal_muestra_la_materia_del_examen_registrado(): void
    {
        $componente = Livewire::test(RegistrarIncidencia::class)
            ->set('idExamen', self::EXAMEN_DEL_MONITOR)
            ->set('nombreEstudiante', self::NOMBRE)
            ->set('apellidoEstudiante', self::APELLIDO)
            ->set('codigoSis', self::SIS_VALIDO)
            ->set('sisPrecargado', self::SIS_VALIDO)
            ->set('materia', self::MATERIA_EJEMPLO)
            ->set('tipoIncidencia', 'copia_o_intercambio_de_respuestas')
            ->call('registrar')
            ->assertHasNoErrors();

        $this->assertSame(self::MATERIA_DEL_EXAMEN, $componente->get('resumen')['materia']);
        $componente->assertSee(self::MATERIA_DEL_EXAMEN);
    }

    /**
     * Verifica que un código SIS escrito a mano que no está en la base se dé de
     * alta al guardar la incidencia, en vez de rechazarse: reportar a un alumno
     * que todavía no está cargado es un caso legítimo (#70).
     */
    public function test_un_codigo_sis_nuevo_se_da_de_alta_al_guardar(): void
    {
        Livewire::test(RegistrarIncidencia::class)
            ->set('idExamen', self::EXAMEN_DEL_MONITOR)
            ->set('nombreEstudiante', self::NOMBRE)
            ->set('apellidoEstudiante', self::APELLIDO)
            ->set('codigoSis', '111111111')
            ->set('materia', self::MATERIA_EJEMPLO)
            ->set('tipoIncidencia', 'copia_o_intercambio_de_respuestas')
            ->call('registrar')
            ->assertHasNoErrors()
            ->assertSet('confirmacionVisible', true);

        $this->assertDatabaseHas('estudiante', [
            'sis_estudiante' => '111111111',
            'nombre_estudiante' => self::NOMBRE,
            'apellido_estudiante' => self::APELLIDO,
        ]);

        $this->assertDatabaseHas('central_riesgo', ['sis_estudiante' => '111111111']);
    }

    /**
     * Verifica que el examen que llega del monitor por la URL sea el que queda
     * guardado, sin importar la materia escrita en el formulario.
     */
    public function test_el_examen_del_monitor_llega_por_la_url(): void
    {
        Livewire::withQueryParams([
            'origen' => 'monitoreo',
            'examen' => self::EXAMEN_DEL_MONITOR,
        ])->test(RegistrarIncidencia::class)
            ->assertSet('idExamen', self::EXAMEN_DEL_MONITOR);
    }

    /**
     * Verifica que sin examen en la URL se use el último examen del estudiante,
     * que es el de donde viene la sospecha cuando se reporta desde la central de
     * riesgos.
     */
    public function test_sin_examen_en_la_url_se_usa_el_ultimo_del_estudiante(): void
    {
        // El estudiante de `setUp` no tiene asistencia, así que se le crea una del
        // examen 2, que en la semilla es del curso `Fisica I`.
        DB::table('registro_asistencia')->insert([
            'id_ingreso' => 900001,
            'hora_ingreso' => '09:00',
            'id_examen' => 2,
            'id_estudiante' => self::SIS_VALIDO,
            'id_registrador' => 3,
        ]);

        Livewire::test(RegistrarIncidencia::class)
            ->set('origen', RegistrarIncidencia::ORIGEN_CENTRAL_RIESGO)
            ->set('nombreEstudiante', self::NOMBRE)
            ->set('apellidoEstudiante', self::APELLIDO)
            ->set('codigoSis', self::SIS_VALIDO)
            ->set('sisPrecargado', self::SIS_VALIDO)
            ->set('materia', self::MATERIA_EJEMPLO)
            ->set('tipoIncidencia', 'uso_de_material_no_autorizado')
            ->call('registrar')
            ->assertHasNoErrors();

        $registro = CentralRiesgo::query()->orderByDesc('id_registro')->firstOrFail();

        $this->assertSame(2, $registro->id_examen);
        $this->assertSame('Fisica I', $registro->materia());
    }

    /**
     * Verifica que el estado guardado siga al rol: un auxiliar deja el caso como
     * sospechoso y un docente lo confirma.
     */
    public function test_el_estado_guardado_depende_del_rol(): void
    {
        Livewire::test(RegistrarIncidencia::class)
            ->set('rol', Rol::NOMBRE_AUXILIAR)
            ->set('idExamen', self::EXAMEN_DEL_MONITOR)
            ->set('nombreEstudiante', self::NOMBRE)
            ->set('apellidoEstudiante', self::APELLIDO)
            ->set('codigoSis', self::SIS_VALIDO)
            ->set('sisPrecargado', self::SIS_VALIDO)
            ->set('materia', self::MATERIA_EJEMPLO)
            ->set('tipoIncidencia', 'suplantacion_de_identidad')
            ->call('registrar')
            ->assertHasNoErrors();

        $this->assertSame(
            TipoInfraccion::Sospechoso,
            CentralRiesgo::query()->orderByDesc('id_registro')->firstOrFail()->tipo_infraccion
        );
    }

    /**
     * Verifica que un estudiante sin ingreso previo también se pueda reportar
     * desde la central de riesgos, que es el caso para el que `id_ingreso` quedó
     * nullable.
     */
    public function test_se_puede_registrar_a_un_estudiante_sin_ingreso_previo(): void
    {
        Livewire::test(RegistrarIncidencia::class)
            ->set('origen', RegistrarIncidencia::ORIGEN_CENTRAL_RIESGO)
            ->set('usuario', 1)
            ->set('nombreEstudiante', self::NOMBRE)
            ->set('apellidoEstudiante', self::APELLIDO)
            ->set('codigoSis', self::SIS_VALIDO)
            ->set('sisPrecargado', self::SIS_VALIDO)
            ->set('materia', self::MATERIA_EJEMPLO)
            ->set('tipoIncidencia', 'uso_de_material_no_autorizado')
            ->call('registrar')
            ->assertHasNoErrors()
            ->assertSet('confirmacionVisible', true);

        $registro = CentralRiesgo::query()->orderByDesc('id_registro')->firstOrFail();

        $this->assertNull($registro->id_ingreso, 'El estudiante no tiene ingreso, la columna queda en null.');
        $this->assertSame(self::SIS_VALIDO, $registro->sis_estudiante);
    }

    /**
     * Verifica que el id del registro lo asigne la secuencia de la base y no la
     * aplicación, para que dos altas simultáneas no choquen.
     *
     * No se comprueba que los ids sean correlativos porque en Postgres las
     * secuencias no se revierten con la transacción del test: los números se
     * gastan aunque la fila termine borrada. Lo que importa es que sean distintos
     * y crecientes, y que ninguna alta choque por clave primaria.
     */
    public function test_el_id_del_registro_lo_asigna_la_secuencia(): void
    {
        foreach ([1, 2] as $indice) {
            Livewire::test(RegistrarIncidencia::class)
                ->set('idExamen', self::EXAMEN_DEL_MONITOR)
                ->set('usuario', 1)
                ->set('nombreEstudiante', self::NOMBRE)
                ->set('apellidoEstudiante', self::APELLIDO)
                ->set('codigoSis', self::SIS_VALIDO)
            ->set('sisPrecargado', self::SIS_VALIDO)
                ->set('materia', self::MATERIA_EJEMPLO)
                ->set('tipoIncidencia', 'intento_de_ingreso_no_autorizado')
                ->call('registrar')
                ->assertHasNoErrors();
        }

        $ids = CentralRiesgo::query()
            ->where('motivo', '=', Motivo::IntentoDeIngresoNoAutorizado->value)
            ->orderByDesc('id_registro')
            ->limit(2)
            ->pluck('id_registro')
            ->all();

        $this->assertCount(2, $ids, 'Deben quedar dos registros de este test.');
        $this->assertNotSame($ids[0], $ids[1], 'La secuencia debe entregar ids distintos.');
        $this->assertGreaterThan($ids[1], $ids[0], 'El id más reciente debe ser el mayor.');
    }

    /**
     * Verifica que se rechace un registrador que no existe en la tabla `usuario`,
     * porque la columna es NOT NULL y aun así no debe romper.
     */
    public function test_rechaza_un_usuario_registrador_inexistente(): void
    {
        Livewire::test(RegistrarIncidencia::class)
            ->set('idExamen', self::EXAMEN_DEL_MONITOR)
            ->set('usuario', 999999)
            ->set('nombreEstudiante', self::NOMBRE)
            ->set('apellidoEstudiante', self::APELLIDO)
            ->set('codigoSis', self::SIS_VALIDO)
            ->set('sisPrecargado', self::SIS_VALIDO)
            ->set('materia', self::MATERIA_EJEMPLO)
            ->set('tipoIncidencia', 'suplantacion_de_identidad')
            ->call('registrar')
            ->assertHasErrors('usuario')
            ->assertSet('confirmacionVisible', false);
    }

    /**
     * Verifica que el modelo se quede con el id que le asignó la secuencia, para
     * que el modal pueda mostrar el número del registro.
     *
     * Con `incrementing` en `false` la fila se guardaba, pero el id llegaba vacío
     * y el modal mostraba "#" sin número.
     */
    public function test_el_resumen_muestra_el_numero_del_registro_guardado(): void
    {
        $componente = Livewire::test(RegistrarIncidencia::class)
            ->set('idExamen', self::EXAMEN_DEL_MONITOR)
            ->set('usuario', 1)
            ->set('nombreEstudiante', self::NOMBRE)
            ->set('apellidoEstudiante', self::APELLIDO)
            ->set('codigoSis', self::SIS_VALIDO)
            ->set('sisPrecargado', self::SIS_VALIDO)
            ->set('materia', self::MATERIA_EJEMPLO)
            ->set('tipoIncidencia', 'uso_de_dispositivos_electronicos')
            ->call('registrar')
            ->assertHasNoErrors();

        $codigo = (string) CentralRiesgo::query()->max('id_registro');

        $this->assertNotSame('', $codigo, 'La base debe haber asignado un id.');
        $this->assertSame($codigo, $componente->get('resumen')['codigo']);
        $componente->assertSee('N° de registro');
        $componente->assertSee($codigo);
    }

    /**
     * Verifica que el modal muestre el detalle escrito cuando el motivo es "Otro",
     * el único que obliga a describirlo, y lo esconda cuando no se escribió nada.
     */
    public function test_el_modal_muestra_el_detalle_cuando_se_escribio(): void
    {
        $componente = Livewire::test(RegistrarIncidencia::class)
            ->set('idExamen', self::EXAMEN_DEL_MONITOR)
            ->set('nombreEstudiante', self::NOMBRE)
            ->set('apellidoEstudiante', self::APELLIDO)
            ->set('codigoSis', self::SIS_VALIDO)
            ->set('sisPrecargado', self::SIS_VALIDO)
            ->set('materia', self::MATERIA_EJEMPLO)
            ->set('tipoIncidencia', RegistrarIncidencia::MOTIVO_OTRO)
            ->set('descripcion', 'Miraba hacia la puerta')
            ->call('registrar')
            ->assertHasNoErrors()
            ->assertSee('Descripción')
            ->assertSee('Miraba hacia la puerta');

        $this->assertSame(
            'Miraba hacia la puerta',
            CentralRiesgo::query()->orderByDesc('id_registro')->firstOrFail()->detalle_motivo
        );
    }

    /**
     * Verifica que la semilla mezcle los cuatro patrones de nombre que tiene que
     * separar el monitor: nombre con un apellido, dos nombres con un apellido,
     * un nombre con dos apellidos y dos nombres con dos apellidos.
     */
    public function test_la_semilla_mezcla_los_cuatro_patrones_de_nombre(): void
    {
        $estudiantes = Estudiante::query()
            ->where('sis_estudiante', 'not like', '9%')
            ->get();

        $patrones = [
            'nombre + 1 apellido' => $estudiantes->contains(
                fn (Estudiante $e): bool => $this->contarPalabras($e->nombre_estudiante) === 1
                    && $this->contarPalabras($e->apellido_estudiante) === 1,
            ),
            '2 nombres + 1 apellido' => $estudiantes->contains(
                fn (Estudiante $e): bool => $this->contarPalabras($e->nombre_estudiante) === 2
                    && $this->contarPalabras($e->apellido_estudiante) === 1,
            ),
            'nombre + 2 apellidos' => $estudiantes->contains(
                fn (Estudiante $e): bool => $this->contarPalabras($e->nombre_estudiante) === 1
                    && $this->contarPalabras($e->apellido_estudiante) === 2,
            ),
            '2 nombres + 2 apellidos' => $estudiantes->contains(
                fn (Estudiante $e): bool => $this->contarPalabras($e->nombre_estudiante) === 2
                    && $this->contarPalabras($e->apellido_estudiante) === 2,
            ),
        ];

        foreach ($patrones as $patron => $existe) {
            $this->assertTrue($existe, 'La semilla no tiene ningun estudiante con el patron: '.$patron);
        }
    }

    /**
     * Verifica que el modal de confirmación quede dentro del elemento raíz del
     * componente y no sea un hermano suyo.
     *
     * Esto no es cosmético: Livewire 4 solo morfea el primer elemento del HTML
     * que devuelve el componente. Con el modal como hermano de la raíz, el
     * markup se renderiza bien (y por eso los demás tests de este archivo
     * pasaban) pero se descarta al pintar el update, así que la confirmación
     * nunca se ve. Además, con más de una raíz,
     * `SupportMultipleRootElementDetection` lanza
     * `MultipleRootElementsDetectedException` al montar con el modal abierto.
     *
     * El caso cerrado importa tanto como el abierto: la raíz no puede cambiar de
     * cantidad entre renders, porque de eso depende el morph.
     */
    public function test_el_modal_queda_dentro_de_la_raiz_del_componente(): void
    {
        $cerrado = Livewire::test(RegistrarIncidencia::class)->html();

        $this->assertCount(
            1,
            $this->raicesDelComponente($cerrado),
            'El componente no debe tener más de una raíz con el modal cerrado.'
        );

        $abierto = Livewire::test(RegistrarIncidencia::class)
            ->set('idExamen', self::EXAMEN_DEL_MONITOR)
            ->set('nombreEstudiante', self::NOMBRE)
            ->set('apellidoEstudiante', self::APELLIDO)
            ->set('codigoSis', '777777777')
            ->set('materia', self::MATERIA_EJEMPLO)
            ->set('tipoIncidencia', Motivo::CopiaOIntercambioDeRespuestas->value)
            ->call('registrar')
            ->assertSet('confirmacionVisible', true)
            ->html();

        $this->assertCount(
            1,
            $this->raicesDelComponente($abierto),
            'El modal no debe convertirse en una raíz extra al abrirse.'
        );

        // Y tiene que estar dentro de la raíz, no pegado después.
        $this->assertStringContainsString('titulo-confirmacion', $abierto);
        $this->assertLessThan(
            strrpos($abierto, '</div>'),
            strpos($abierto, 'titulo-confirmacion'),
            'El modal se está renderizando después del cierre de la raíz.'
        );
    }

    /**
     * Elementos de nivel superior del HTML que devuelve el componente, que es lo
     * que Livewire cuenta como raíces al morfear.
     *
     * @return list<string>  Etiqueta de cada raíz, para que el fallo las muestre.
     */
    private function raicesDelComponente(string $html): array
    {
        // Se quitan script y style porque las versiones de libxml los parsean
        // de forma distinta y contaría nodos que el navegador no crea; es lo
        // mismo que hace `SupportMultipleRootElementDetection` de Livewire.
        $html = preg_replace('/<script\b[^>]*>.*?<\/script>/si', '', $html) ?? $html;
        $html = preg_replace('/<style\b[^>]*>.*?<\/style>/si', '', $html) ?? $html;

        $dom = new \DOMDocument();
        @$dom->loadHTML($html, LIBXML_NOERROR);

        $body = $dom->getElementsByTagName('body')->item(0);

        if ($body === null) {
            return [];
        }

        $raices = [];
        foreach ($body->childNodes as $hijo) {
            if ($hijo->nodeType === XML_ELEMENT_NODE) {
                $raices[] = $hijo->nodeName.'.'.($hijo->getAttribute('class') ?: '(sin clase)');
            }
        }

        return $raices;
    }

    /**
     * Verifica que se avise cuando el codigo SIS escrito a mano corresponde a un
     * estudiante que ya estaba ingresado.
     */
    public function test_avisa_si_el_codigo_sis_escrito_a_ya_esta_ingresado(): void
    {
        $ingresado = $this->estudianteDeLaSemilla();

        Livewire::test(RegistrarIncidencia::class)
            ->set('nombreEstudiante', $ingresado->nombre_estudiante)
            ->set('apellidoEstudiante', $ingresado->apellido_estudiante)
            ->set('codigoSis', $ingresado->sis_estudiante)
            ->set('materia', self::MATERIA_EJEMPLO)
            ->set('tipoIncidencia', Motivo::CopiaOIntercambioDeRespuestas->value)
            ->call('registrar')
            ->assertHasErrors('codigoSis')
            ->assertSee('Ese código SIS ya está registrado', escape: false);
    }

    /**
     * Verifica que no se avise cuando el codigo SIS escrito a mano todavia no
     * esta en la base: es el caso legitimo de reportar a un alumno nuevo.
     */
    public function test_no_avisa_si_el_codigo_sis_escrito_a_no_esta_ingresado(): void
    {
        Livewire::test(RegistrarIncidencia::class)
            ->set('nombreEstudiante', self::NOMBRE)
            ->set('apellidoEstudiante', self::APELLIDO)
            ->set('codigoSis', '202299887')
            ->set('materia', self::MATERIA_EJEMPLO)
            ->set('tipoIncidencia', Motivo::CopiaOIntercambioDeRespuestas->value)
            ->call('registrar')
            ->assertHasNoErrors();
    }

    /**
     * Verifica que el estudiante que llega precargado desde el monitor no se
     * bloquee: ese SIS ya esta en la base, pero no lo escribio la persona, asi
     * que el aviso de duplicado no aplica.
     */
    public function test_no_avisa_con_el_estudiante_precargado_del_monitor(): void
    {
        $ingresado = $this->estudianteDeLaSemilla();

        Livewire::withQueryParams([
            'origen' => RegistrarIncidencia::ORIGEN_MONITOREO,
            'nombre' => $ingresado->nombre_estudiante.' '.$ingresado->apellido_estudiante,
            'sis' => $ingresado->sis_estudiante,
            'materia' => self::MATERIA_EJEMPLO,
            'rol' => Rol::NOMBRE_DOCENTE,
        ])
            ->test(RegistrarIncidencia::class)
            ->set('tipoIncidencia', Motivo::CopiaOIntercambioDeRespuestas->value)
            ->call('registrar')
            ->assertHasNoErrors();
    }

    /**
     * Verifica lo mismo para el estudiante elegido con la lupa, que es la via
     * que el propio aviso le indica al usuario para resolver el duplicado.
     */
    public function test_no_avisa_con_el_estudiante_elegido_con_la_lupa(): void
    {
        $ingresado = $this->estudianteDeLaSemilla();

        Livewire::test(RegistrarIncidencia::class)
            ->set('busqueda', $ingresado->nombre_estudiante)
            ->call('buscarEstudiantes')
            ->call('seleccionarEstudiante', $ingresado->sis_estudiante)
            ->set('materia', self::MATERIA_EJEMPLO)
            ->set('tipoIncidencia', Motivo::CopiaOIntercambioDeRespuestas->value)
            ->call('registrar')
            ->assertHasNoErrors();
    }

    /**
     * Un estudiante de la semilla, excluyendo el rango 900000+ de los tests.
     */
    private function estudianteDeLaSemilla(): Estudiante
    {
        $estudiante = Estudiante::query()
            ->where('sis_estudiante', 'not like', '9%')
            ->orderBy('sis_estudiante')
            ->first();

        $this->assertNotNull($estudiante, 'La semilla debe traer estudiantes de prueba.');

        return $estudiante;
    }

    /**
     * Cuenta las palabras de un nombre, ignorando los espacios sobrantes.
     */
    private function contarPalabras(string $texto): int
    {
        $limpio = trim(preg_replace('/\s+/u', ' ', $texto) ?? '');

        return $limpio === '' ? 0 : count(explode(' ', $limpio));
    }
}

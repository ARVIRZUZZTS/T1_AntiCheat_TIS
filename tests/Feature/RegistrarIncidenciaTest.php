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
 */

namespace Tests\Feature;

use App\Enums\Motivo;
use App\Enums\TipoInfraccion;
use App\Livewire\Monitoreo\RegistrarIncidencia;
use App\Models\CentralRiesgo;
use App\Models\Estudiante;
use App\Models\Rol;
use Illuminate\Foundation\Testing\DatabaseTransactions;
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
     * Código SIS válido: exactamente 9 dígitos, como exige la regla `digits:9`.
     *
     * @var string
     */
    private const SIS_VALIDO = '202201013';

    /**
     * Código SIS del estudiante de prueba, en el rango reservado.
     *
     * @var string
     */
    private const SIS_ESTUDIANTE = '90000001';

    /**
     * Código SIS de un estudiante en la base de datos que además cumple la
     * validación del formulario, que pide nueve dígitos.
     *
     * @var string
     */
    private const SIS_EN_BASE = '900000012';

    /**
     * Crea un estudiante con un código SIS válido para el formulario, distinto
     * al de `setUp`, que no cumple la regla de nueve dígitos.
     *
     * @return void
     */
    private function crearEstudianteEnBase(): void
    {
        Estudiante::query()->create([
            'sis_estudiante' => self::SIS_EN_BASE,
            'nombre_estudiante' => 'Carla',
            'apellido_estudiante' => 'Ruiz',
            'carrera' => 'Ingenieria de Sistemas',
        ]);
    }

    /**
     * Crea el estudiante que necesita el buscador.
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
            ->set('tipoIncidencia', 'copia_o_intercambio_de_respuestas')
            ->set('materia', 'Fisica 1!')
            ->call('registrar')
            ->assertHasErrors('materia')
            ->assertSee('La materia solo puede contener letras, números y espacios.', escape: false);

        Livewire::test(RegistrarIncidencia::class)
            ->set('nombreEstudiante', self::NOMBRE)
            ->set('apellidoEstudiante', self::APELLIDO)
            ->set('codigoSis', self::SIS_VALIDO)
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
            ->set('tipoIncidencia', 'copia_o_intercambio_de_respuestas')
            ->set('materia', self::MATERIA_EJEMPLO)
            ->call('registrar')
            ->assertHasErrors('nombreEstudiante')
            ->assertSee('El nombre solo puede contener letras.', escape: false);

        Livewire::test(RegistrarIncidencia::class)
            ->set('nombreEstudiante', self::NOMBRE)
            ->set('apellidoEstudiante', 'Lopez1')
            ->set('codigoSis', self::SIS_VALIDO)
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
            ->set('materia', self::MATERIA_EJEMPLO)
            ->set('tipoIncidencia', 'copia_o_intercambio_de_respuestas')
            ->call('registrar')
            ->assertHasNoErrors();

        Livewire::test(RegistrarIncidencia::class)
            ->set('nombreEstudiante', self::NOMBRE)
            ->set('apellidoEstudiante', self::APELLIDO)
            ->set('codigoSis', self::SIS_VALIDO)
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
            $componente = Livewire::test(RegistrarIncidencia::class)
                ->set('origen', RegistrarIncidencia::ORIGEN_MONITOREO)
                ->set('nombreEstudiante', $estudiante->nombre_estudiante)
                ->set('apellidoEstudiante', $estudiante->apellido_estudiante)
                ->set('codigoSis', $estudiante->sis_estudiante)
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
     * Cuenta las palabras de un nombre, ignorando los espacios sobrantes.
     */
    private function contarPalabras(string $texto): int
    {
        $limpio = trim(preg_replace('/\s+/u', ' ', $texto) ?? '');

        return $limpio === '' ? 0 : count(explode(' ', $limpio));
    }
}

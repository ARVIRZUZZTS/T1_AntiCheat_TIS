<?php

/**
 * @file    RegistrarIncidencia.php
 * @author  Valery D. Ortuno P. <valerydariana98@gmail.com>
 * @created 2026-09-25
 * @updated 2026-09-28
 *
 * @description
 * Componente de página con el formulario de registro de una incidencia en la
 * central de riesgos. Precarga el estudiante y la materia que llegan desde el
 * monitor en vivo, permite reemplazarlos con el buscador de estudiantes de la
 * base de datos, escribir el estudiante a mano cuando no está en la base y
 * deriva el estado del registro del rol de quien lo realiza. Desde la central de
 * riesgo se abre sin datos precargados y el estudiante se busca a mano.
 *
 * @changelog
 * - 2026-09-25  [Valery D. Ortuno P]  feat: creación inicial del formulario desktop.
 * - 2026-09-26  [Valery D. Ortuno P]  feat: buscador de estudiantes contra la base
 *   de datos, motivos acordados por el equipo, estado a partir del rol recibido
 *   por la URL y una sola vista responsive de escritorio y móvil.
 * - 2026-09-26  [Amiddala]  fix: mount() recibía un parámetro `Rol $rol =
 *   Rol::DOCENTE` que ya no compila, porque `Rol` pasó de ser un enum a un
 *   modelo Eloquent sin ese caso/constante. Se quita el parámetro y la
 *   asignación duplicada; el rol se sigue leyendo únicamente de la URL (#68).
 * - 2026-09-28  [Candy]  feat: segunda entrada al formulario, la central de
 *   riesgo, que abre el formulario en blanco para que la persona busque al
 *   estudiante; el enlace de vuelta y el cancelar siguen a la pantalla de
 *   origen.
 * - 2026-09-28  [Valery D. Ortuno P]  fix: nombre y apellido por separado,
 *   motivos actualizados a los acordados por el equipo, materia precargada del
 *   monitor como solo lectura o texto libre con solo letras, números y espacios
 *   (#66).
 * - 2026-09-28  [Valery D. Ortuno P]  fix: nombre, apellido y código SIS pasan a
 *   ser editables para poder registrar a un estudiante que no esté en la base,
 *   con validación de solo letras para el nombre y el apellido y de 9 dígitos
 *   para el código SIS (#66).
 * - 2026-09-28  [Valery D. Ortuno P]  feat: aviso de código SIS duplicado cuando
 *   el campo se escribe a mano, comparando contra el valor precargado desde el
 *   monitor o elegido con la lupa para no bloquear esos dos caminos.
 * - 2026-09-28  [Valery D. Ortuno P]  fix: mensajes de validación propios para
 *   cada campo, porque la aplicación está en inglés y Laravel respondía "The
 *   nombre field is required" (#66).
 * - 2026-09-28  [Candy]  feat: al registrar se guarda la incidencia en
 *   `central_riesgo` con el estudiante, el usuario registrador, la materia, el
 *   estado, el motivo, la descripción y la fecha con hora, y se abre un modal de
 *   confirmación con el resumen de lo guardado, cuyo botón Aceptar termina el
 *   proceso y devuelve a la pantalla de origen (#70).
 * - 2026-09-29  [Candy]  feat: el registro se alinea con el esquema de
 *   `central_riesgo` que quedó tras el merge: se guarda `sis_estudiante`,
 *   `id_examen`, el motivo del enum `Motivo` y el detalle en `detalle_motivo`.
 *   La materia ya no es columna, sale del examen, y el modal muestra la del
 *   examen registrado. El código SIS tiene que existir en la base, porque la
 *   columna tiene llave foránea (#70).
 */
namespace App\Livewire\Monitoreo;

use App\Enums\Motivo;
use App\Enums\TipoInfraccion;
use App\Models\CentralRiesgo;
use App\Models\Estudiante;
use App\Models\Examen;
use App\Models\Rol;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Component;
 
/**
 * Formulario de registro de incidencia contra un estudiante, invocado desde el
 * monitor del examen en curso.
 *
 * El monitor y el formulario viajan como página completa, así que el contexto
 * del registro (estudiante, materia y rol de quien reporta) llega por la URL en
 * lugar de por la sesión.
 *
 * @package  App\Livewire\Monitoreo
 * @author   Valery D. Ortuno P. <valerydariana98@gmail.com>
 * @since    2026-09-25
 *
 * @see  \App\Enums\TipoInfraccion
 * @see  \App\Models\Estudiante
 * @see  \App\Models\Rol
 */
class RegistrarIncidencia extends Component
{
    /**
     * Caracteres admitidos en la descripción del hecho.
     *
     * @var int
     */
    public const DESCRIPCION_MAXIMO = 300;

    /**
     * Caracteres admitidos en el nombre y el apellido del estudiante, el mismo
     * ancho que sus columnas en la tabla `estudiante`.
     *
     * @var int
     */
    public const NOMBRE_MAXIMO = 50;

    /**
     * Caracteres admitidos en la materia escrita a mano.
     *
     * @var int
     */
    public const MATERIA_MAXIMO = 100;
 
    /**
     * Caracteres mínimos antes de consultar estudiantes en la base de datos.
     *
     * @var int
     */
    public const BUSQUEDA_MINIMO = 2;

    /**
     * Estudiantes mostrados como máximo en el desplegable de resultados.
     *
     * @var int
     */
    public const BUSQUEDA_LIMITE = 8;

    /**
     * Motivo que obliga a describir el hecho, porque no encaja en los demás.
     *
     * Es el valor del caso `Motivo::Otro`, que se escribe literal para no
     * arrastrar el enum a los tests.
     *
     * @var string
     */
    public const MOTIVO_OTRO = 'otro';

    /**
     * Pantalla desde la que se abrió el formulario.
     *
     * @var string
     */
    public const ORIGEN_MONITOREO = 'monitoreo';

    /**
     * Pantalla desde la que se abrió el formulario sin datos precargados: la
     * central de riesgo, donde el estudiante se busca a mano.
     *
     * @var string
     */
    public const ORIGEN_CENTRAL_RIESGO = 'central-riesgo';

    /**
     * Registrador usado cuando la pantalla de origen no envía ninguno.
     *
     * Es el primer usuario de `docker/postgres/init/002_seed_data.sql`. Existe
     * solo para que el registro se pueda guardar sin login; en cuanto la
     * aplicación tenga autenticación (#69) deja de usarse.
     *
     * @var int
     */
    public const USUARIO_POR_DEFECTO = 1;

    /**
     * Pantalla desde la que se abrió el formulario.
     *
     * @var string
     */
    public string $origen = '';

    /**
     * Rol con el que se registra la incidencia, recibido desde la pantalla que
     * abrió el formulario.
     *
     * @var string  Uno de los valores de \App\Models\Rol::NOMBRE_*.
     */
    public string $rol = Rol::NOMBRE_DOCENTE;

    /**
     * Usuario de la tabla `usuario` que queda como registrador de la incidencia.
     *
     * No hay autenticación en la aplicación, así que el id viaja por la URL junto
     * al rol, igual que este. Si no llega ninguno se usa el primer usuario
     * sembrado, para que la columna `id_registrador`, que es NOT NULL, nunca
     * deje la incidences sin poder guardar.
     *
     * @var int
     */
    public int $usuario = self::USUARIO_POR_DEFECTO;

    /**
     * Examen en el que se observó la incidencia, del que se deriva la materia.
     *
     * `central_riesgo.id_examen` es NOT NULL porque la materia ya no se guarda:
     * sale de `id_examen -> examen_curso -> curso.nombre_curso`. El monitor en
     * vivo, que sigue un solo examen, lo manda por la URL; desde la central de
     * riesgo, donde no hay un examen en curso, se resuelve con
     * `resolverExamen()` al validar.
     *
     * @var int
     */
    public int $idExamen = 0;

    /**
     * Texto escrito en el buscador de estudiantes.
     *
     * @var string
     */
    public string $busqueda = '';

    /**
     * Nombre del estudiante sobre el que se registra la incidencia.
     *
     * @var string
     */
    public string $nombreEstudiante = '';

    /**
     * Apellido del estudiante sobre el que se registra la incidencia.
     *
     * @var string
     */
    public string $apellidoEstudiante = '';
 
    /**
     * Código SIS del estudiante sobre el que se registra la incidencia.
     *
     * @var string
     */
    public string $codigoSis = '';

    /**
     * Código SIS con el que se precargó el campo, para distinguir lo que llega de
     * una fuente confiable de lo que la persona escribe a mano.
     *
     * Es público a propósito: si fuera privado, Livewire lo reiniciaría a vacío en
     * cada petición posterior y se perdería la diferencia con el valor original.
     *
     * @var string
     */
    public string $sisPrecargado = '';
 
    /**
     * Materia del examen en el que se observa la anomalía, precargada desde el
     * monitor o escrita a mano cuando no viaja por la URL.
     *
     * @var string
     */
    public string $materia = '';
 
    /**
     * Motivo de la incidencia, con el valor de un caso de `Motivo`.
     *
     * @var string
     */
    public string $tipoIncidencia = '';

    /**
     * Detalle de lo ocurrido, con un máximo de `DESCRIPCION_MAXIMO` caracteres.
     *
     * Se guarda en `detalle_motivo`, que es la columna que la base reserva para
     * el texto libre del motivo.
     *
     * @var string
     */
    public string $descripcion = '';
 
    /**
     * Momento en que se abre el formulario, mostrado como dato de solo lectura.
     *
     * @var string
     */
    public string $fechaHoraRegistro = '';

    /**
     * Si el modal de confirmación ya está abierto, es decir, si la incidencia
     * quedó registrada y solo falta que la persona lo acepte para volver a la
     * pantalla de origen.
     *
     * @var bool
     */
    public bool $confirmacionVisible = false;

    /**
     * Copia de los datos validados en el momento del registro, para que el modal
     * muestre siempre lo que se acaba de guardar y no lo que el formulario
     * tuviera escrito después.
     *
     * @var array<string, string>
     */
    public array $resumen = [];
 
    /**
     * Precarga el estudiante, la materia y la fecha con lo que llega por la URL.
     *
     * El monitor es la única entrada que precarga datos: si el origen no es el
     * monitor —como pasa al entrar desde la central de riesgo— el formulario se
     * abre en blanco para que la persona busque al estudiante y escriba el
     * motivo. El rol también llega por la URL como texto (uno de `Rol::NOMBRE_*`),
     * porque `Rol` es un modelo de la tabla `rol` y no un enum con casos fijos.
     *
     * El id del examen llega siempre por la URL, venga del monitor o no, porque
     * la materia se deriva de él y `central_riesgo.id_examen` no admite null.
     *
     * @author Valery D. Ortuno P. <valerydariana98@gmail.com>
     * @author Amiddala
     * @since  2026-09-25
     */
    public function mount(): void
    {
        $this->fechaHoraRegistro = now()->format('d/m/Y H:i');
        $this->origen = (string) request()->query('origen', '');
        $this->rol = (string) request()->query('rol', Rol::NOMBRE_DOCENTE);
        $this->usuario = (int) request()->query('usuario', self::USUARIO_POR_DEFECTO);
        $this->idExamen = (int) request()->query('examen', 0);

        if ($this->origen !== self::ORIGEN_MONITOREO) {
            return;
        }

        [$nombre, $apellido] = $this->separarNombre((string) request()->query('nombre', ''));
        $this->nombreEstudiante = $nombre;
        $this->apellidoEstudiante = $apellido;
        $this->codigoSis = (string) request()->query('sis', '');
        $this->sisPrecargado = $this->codigoSis;
        $this->materia = (string) request()->query('materia', '');
    }

    /**
    /**
     * Pantalla a la que vuelve el formulario, según desde dónde se abrió.
     *
     * @return string  URL de la pantalla de origen.
     *
     * @author Candy
     * @since  2026-09-28
     */
    #[Computed]
    public function rutaVolver(): string
    {
        return match ($this->origen) {
            self::ORIGEN_CENTRAL_RIESGO => route('central-riesgo'),
            default => route('monitoreo'),
        };
    }

    /**
     * Texto del enlace que regresa a la pantalla desde la que se abrió el
     * formulario.
     *
     * @return string  Etiqueta del enlace de vuelta.
     *
     * @author Candy
     * @since  2026-09-28
     */
    #[Computed]
    public function etiquetaVolver(): string
    {
        return $this->origen === self::ORIGEN_CENTRAL_RIESGO
            ? 'Volver a la central de riesgo'
            : 'Volver al monitor en vivo';
    }

    /**
     * Divide el nombre completo que llega del monitor en nombre y apellido.
     *
     * El monitor envía "Nombres Apellidos" en un solo parámetro y el formulario
     * los muestra por separado; si la persona tiene más de una palabra, el resto
     * se acumula en el apellido.
     *
     * @param  string  $completo  Nombre recibido desde la pantalla de origen.
     * @return array{0: string, 1: string}  Nombre y apellido por separado.
     *
     * @author Valery D. Ortuno P. <valerydariana98@gmail.com>
     * @since  2026-09-28
     */
    private function separarNombre(string $completo): array
    {
        // `explode` siempre devuelve al menos un elemento, así que solo el
        // segundo —el apellido— puede faltar cuando no hay espacio.
        $partes = explode(' ', trim($completo), 2);

        return [trim($partes[0]), trim($partes[1] ?? '')];
    }
    /**
     * Tipo de infracción con el que se persiste el registro, derivado del rol
     * de quien lo realiza: un docente confirma y un auxiliar deja el caso en
     * revisión.
     *
     * @return TipoInfraccion  Valor del enum `tipo_infraccion` de la base de datos.
     *
     * @author Valery D. Ortuno P. <valerydariana98@gmail.com>
     * @since  2026-09-26
     */
    #[Computed]
    public function tipoInfraccion(): TipoInfraccion
    {
        return $this->rol === Rol::NOMBRE_AUXILIAR
            ? TipoInfraccion::Sospechoso
            : TipoInfraccion::Tramposo;
    }

    /**
     * Etiqueta del estado con la que se muestra la incidencia en la pantalla.
     *
     * @return string  "Confirmado" para el docente, "Sospechoso" para el auxiliar,
     *                 tal como pide el criterio de aceptación de la #68.
     *
     * @author Valery D. Ortuno P. <valerydariana98@gmail.com>
     * @author Amiddala
     * @since  2026-09-26
     */
    #[Computed]
    public function etiquetaEstado(): string
    {
        return $this->rol === Rol::NOMBRE_AUXILIAR ? 'En revision' : 'Confirmado';
    }

    /**
     * Estilo de la insignia del estado, tomado de la paleta que ya usa el
     * monitor en vivo.
     *
     * @return string  Clave de los tipos aceptados por `x-ui.badge`.
     *
     * @author Valery D. Ortuno P. <valerydariana98@gmail.com>
     * @since  2026-09-26
     */
    #[Computed]
    public function tipoEstado(): string
    {
        return $this->rol === Rol::NOMBRE_AUXILIAR
            ? 'status-en-revision'
            : 'status-central-riesgos';
    }
 
    /**
     * Los motivos disponibles para el selector de la vista, tomados del enum
     * `Motivo` para que el valor que se guarda y la etiqueta que se ve no se
     * dupliquen en la aplicación.
     *
     * @return array<string, string>  Motivos indexados por valor persistible.
     *
     * @author Valery D. Ortuno P. <valerydariana98@gmail.com>
     * @since  2026-09-25
     */
    #[Computed]
    public function tiposIncidencia(): array
    {
        $motivos = [];

        foreach (Motivo::cases() as $motivo) {
            $motivos[$motivo->value] = $motivo->etiqueta();
        }

        return $motivos;
    }

    /**
     * Si la materia llegó precargada desde el monitor en vivo y, por lo tanto,
     * no se puede modificar en el formulario.
     *
     * La distinción la da la pantalla de origen, no que el campo tenga texto:
     * cuando el formulario se abre sin monitor (central de riesgos, por
     * ejemplo), la materia se escribe a mano.
     *
     * @return bool  Verdadero cuando la materia viene del monitor.
     *
     * @author Valery D. Ortuno P. <valerydariana98@gmail.com>
     * @since  2026-09-28
     */
    #[Computed]
    public function materiaEsSoloLectura(): bool
    {
        return $this->origen === self::ORIGEN_MONITOREO;
    }

    /**
     * Estudiantes que coinciden con lo escrito en el buscador, por nombre,
     * apellido o código SIS.
     *
     * @return Collection<int, Estudiante>  Estudiantes encontrados, hasta el límite.
     *
     * @author Valery D. Ortuno P. <valerydariana98@gmail.com>
     * @since  2026-09-26
     */
    #[Computed]
    public function resultadosBusqueda(): Collection
    {
        $termino = trim($this->busqueda);

        if (mb_strlen($termino) < self::BUSQUEDA_MINIMO) {
            return new Collection();
        }

        $patron = '%'.$termino.'%';

        return Estudiante::query()
            ->where('sis_estudiante', 'ilike', $patron)
            ->orWhere('nombre_estudiante', 'ilike', $patron)
            ->orWhere('apellido_estudiante', 'ilike', $patron)
            ->orderBy('nombre_estudiante')
            ->orderBy('apellido_estudiante')
            ->limit(self::BUSQUEDA_LIMITE)
            ->get();
    }

    /**
     * Caracteres escritos y límite permitido, para el contador de la descripción.
     *
     * @return string  Contador con el formato "usados / permitidos".
     *
     * @author Valery D. Ortuno P. <valerydariana98@gmail.com>
     * @since  2026-09-25
     */
    #[Computed]
    public function contadorDescripcion(): string
    {
        return mb_strlen($this->descripcion).' / '.self::DESCRIPCION_MAXIMO;
    }
 
    /**
     * Si la descripción debe rellenarse, lo que solo ocurre con el motivo
     * "Otro".
     *
     * @return bool  Verdadero cuando el motivo elegido es el que no se lista.
     *
     * @author Valery D. Ortuno P. <valerydariana98@gmail.com>
     * @since  2026-09-26
     */
    #[Computed]
    public function descripcionEsObligatoria(): bool
    {
        return $this->tipoIncidencia === self::MOTIVO_OTRO;
    }

    /**
     * Refresca los resultados del buscador cuando se envía con el teclado.
     *
     * Los resultados se calculan en cada render, así que basta con volver a
     * validar la entrada para que la vista los muestre.
     *
     * @return void
     *
     * @author Valery D. Ortuno P. <valerydariana98@gmail.com>
     * @since  2026-09-26
     */
    public function buscarEstudiantes(): void
    {
        $this->resetErrorBag('busqueda');
    }

    /**
     * Reemplaza el estudiante del registro por el elegido en el buscador.
     *
     * @param  string  $sis  Código SIS del estudiante seleccionado.
     * @return void
     *
     * @author Valery D. Ortuno P. <valerydariana98@gmail.com>
     * @since  2026-09-26
     */
    public function seleccionarEstudiante(string $sis): void
    {
        $estudiante = Estudiante::query()->find($sis);

        if ($estudiante === null) {
            return;
        }

        $this->codigoSis = $estudiante->sis_estudiante;
        $this->sisPrecargado = $estudiante->sis_estudiante;
        $this->nombreEstudiante = $estudiante->nombre_estudiante;
        $this->apellidoEstudiante = $estudiante->apellido_estudiante;
        $this->busqueda = '';
        $this->resetErrorBag('codigoSis');
    }

    /**
     * Reglas de validación del formulario.
     *
     * @return array<string, array<int, mixed>>  Reglas por propiedad.
     *
     * @author Valery D. Ortuno P. <valerydariana98@gmail.com>
     * @author Amiddala
     * @since  2026-09-25
     */
    protected function rules(): array
    {
        return [
            'nombreEstudiante' => [
                'required',
                'string',
                'max:'.self::NOMBRE_MAXIMO,
                'regex:/^[\p{L}\s]+$/u',
            ],
            'apellidoEstudiante' => [
                'required',
                'string',
                'max:'.self::NOMBRE_MAXIMO,
                'regex:/^[\p{L}\s]+$/u',
            ],
            // La columna `sis_estudiante` tiene llave foránea contra
            // `estudiante`, así que el código escrito tiene que existir en la
            // base: registrar a alguien que no está en la tabla ya no es
            // posible (#70).
            'codigoSis' => ['required', 'digits:9', Rule::exists('estudiante', 'sis_estudiante')],
            'materia' => [
                'required',
                'string',
                'max:'.self::MATERIA_MAXIMO,
                'regex:/^[\p{L}\p{N}\s]+$/u',
            ],
            'tipoIncidencia' => ['required', Rule::in(array_keys($this->tiposIncidencia))],
            'usuario' => ['required', 'integer', Rule::exists('usuario', 'id_usuario')],
            'descripcion' => [
                Rule::requiredIf(fn (): bool => $this->descripcionEsObligatoria()),
                'nullable',
                'string',
                'max:'.self::DESCRIPCION_MAXIMO,
            ],
        ];
    }

    /**
     * Reglas del código SIS, con el aviso de duplicado cuando el valor se
     * escribió a mano.
     *
     * El aviso aplica solo a lo que la persona teclea. El estudiante que llega
     * precargado desde el monitor y el que se elige con la lupa ya están
     * ingresados en la base, así que se compara el valor actual contra el
     * precargado y el duplicado se busca únicamente cuando difieren.
     *
     * @return array<int, mixed>  Reglas de validación del código SIS.
     *
     * @author Valery D. Ortuno P. <valerydariana98@gmail.com>
     * @since  2026-09-28
     */
    private function reglasCodigoSis(): array
    {
        $reglas = ['required', 'digits:9'];

        if ($this->codigoSis !== $this->sisPrecargado) {
            $reglas[] = Rule::unique('estudiante', 'sis_estudiante');
        }

        return $reglas;
    }

    /**
     * Mensajes de validación en el idioma de la interfaz.
     *
     * @return array<string, string>  Mensajes por regla.
     *
     * @author Valery D. Ortuno P. <valerydariana98@gmail.com>
     * @since  2026-09-26
     */
    protected function validationAttributes(): array
    {
        return [
            'nombreEstudiante' => 'nombre',
            'apellidoEstudiante' => 'apellido',
            'codigoSis' => 'código SIS',
            'tipoIncidencia' => 'motivo',
        ];
    }
 
    /**
     * Mensajes de validación en el idioma de la interfaz.
     *
     * Todos los campos llevan su propio mensaje: la aplicación corre en inglés,
     * así que sin esto Laravel respondería "The nombre field is required".
     *
     * @return array<string, string>  Mensaje por regla incumplida.
     *
     * @author Amiddala
     * @since  2026-09-25
     */
    protected function messages(): array
    {
        return [
            'nombreEstudiante.required' => 'Ingrese el nombre del estudiante.',
            'nombreEstudiante.regex' => 'El nombre solo puede contener letras.',
            'nombreEstudiante.max' => 'El nombre admite un máximo de '.self::NOMBRE_MAXIMO.' caracteres.',
            'apellidoEstudiante.required' => 'Ingrese el apellido del estudiante.',
            'apellidoEstudiante.regex' => 'El apellido solo puede contener letras.',
            'apellidoEstudiante.max' => 'El apellido admite un máximo de '.self::NOMBRE_MAXIMO.' caracteres.',
            'codigoSis.required' => 'Ingrese el código SIS del estudiante.',
            'codigoSis.digits' => 'El código SIS debe tener 9 números.',
            'codigoSis.exists' => 'El código SIS no corresponde a un estudiante de la base de datos.',
            'materia.required' => 'Ingrese la materia del examen.',
            'materia.regex' => 'La materia solo puede contener letras, números y espacios.',
            'materia.max' => 'La materia admite un máximo de '.self::MATERIA_MAXIMO.' caracteres.',
            'tipoIncidencia.required' => 'Seleccione el motivo de la incidencia.',
            'tipoIncidencia.in' => 'Seleccione un motivo válido de la lista.',
            'usuario.required' => 'No se identificó al usuario que registra la incidencia.',
            'usuario.exists' => 'El usuario que registra la incidencia no existe.',
            'descripcion.required' => 'Describa el hecho observado.',
            'descripcion.max' => 'La descripción admite un máximo de '.self::DESCRIPCION_MAXIMO.' caracteres.',
        ];
    }
 
    /**
     * Registra la incidencia con los datos ingresados en el formulario y abre el
     * modal de confirmación con el resumen de lo guardado.
     *
     * La materia del formulario no se persiste: la base la deriva de
     * `id_examen -> examen_curso -> curso.nombre_curso`. El campo sigue en
     * pantalla porque es el dato que la persona ve y con el que reconoce el
     * examen, pero el resumen del modal muestra la materia real del examen
     * registrado, no la escrita.
     *
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException  Si falta el estudiante,
     *                                                       la materia o el motivo,
     *                                                       si el estudiante no
     *                                                       está en la base, si la
     *                                                       descripción es
     *                                                       obligatoria y está vacía,
     *                                                       o si excede el límite.
     *
     * @author Valery D. Ortuno P. <valerydariana98@gmail.com>
     * @author Amiddala
     * @since  2026-09-25
     */
    public function registrar(): void
    {
        // Con el modal abierto la incidencia ya quedó registrada: un segundo
        // envío (doble clic, Enter repetido) no debe volver a pasar por aquí.
        if ($this->confirmacionVisible) {
            return;
        }

        $this->validate();

        $estudiante = $this->resolverEstudiante();

        $registro = CentralRiesgo::create([
            'sis_estudiante' => (string) $estudiante?->sis_estudiante,
            'id_examen' => $this->resolverExamen($estudiante),
            'id_registrador' => $this->usuario,
            'motivo' => Motivo::from($this->tipoIncidencia),
            // La descripción es opcional con los motivos del catálogo, así que
            // vacía se guarda como null y no como cadena en blanco.
            'detalle_motivo' => $this->descripcion === '' ? null : $this->descripcion,
            'fecha_registro' => now(),
            'tipo_infraccion' => $this->tipoInfraccion,
            'id_ingreso' => $this->ingresoDelEstudiante($estudiante?->sis_estudiante),
        ]);

        $nombreEnBase = $registro->estudiante === null
            ? ''
            : trim($registro->estudiante->nombre_estudiante.' '.$registro->estudiante->apellido_estudiante);

        $nombreRegistrador = $registro->registrador === null
            ? ''
            : trim($registro->registrador->nombre_usuario.' '.$registro->registrador->apellido);

        $this->resumen = [
            'codigo' => (string) $registro->id_registro,
            'estudiante' => $nombreEnBase !== ''
                ? $nombreEnBase
                : trim($this->nombreEstudiante.' '.$this->apellidoEstudiante),
            'codigoSis' => (string) $registro->sis_estudiante,
            'registrador' => $nombreRegistrador,
            'materia' => (string) $registro->materia(),
            'motivo' => $registro->motivo->etiqueta(),
            'estado' => $this->etiquetaEstado,
            'fechaHora' => $registro->fecha_registro->format('d/m/Y H:i'),
            'descripcion' => (string) $registro->detalle_motivo,
        ];

        $this->confirmacionVisible = true;
    }

    /**
     * Estudiante de la base de datos al que pertenece el código SIS escrito, o
     * null cuando no hay ninguno con ese código.
     *
     * @return ?Estudiante
     */
    private function resolverEstudiante(): ?Estudiante
    {
        return Estudiante::query()->find($this->codigoSis);
    }

    /**
     * Examen al que se cuelga la incidencia, que es de donde sale la materia.
     *
     * `central_riesgo.id_examen` es NOT NULL, así que tiene que haber uno. Se
     * busca en este orden:
     *
     * 1. El que manda el monitor en vivo por la URL, que es el examen que está
     *    siguiendo en ese momento.
     * 2. El último examen en el que el estudiante tiene fila de asistencia,
     *    que es el examen del que viene la sospecha cuando la incidencia se
     *    reporta desde la central de riesgos.
     * 3. El primer examen disponible, para que el registro nunca se quede sin
     *    guardar por un dato que la persona no elige.
     *
     * @param  ?Estudiante  $estudiante  Estudiante del registro, si se encontró.
     * @return int  Id de un examen existente.
     */
    private function resolverExamen(?Estudiante $estudiante): int
    {
        $examen = $this->buscarExamen($this->idExamen);

        if ($examen === null && $estudiante !== null) {
            $examen = Examen::query()
                ->whereIn('id_examen', DB::table('registro_asistencia')
                    ->select('id_examen')
                    ->where('id_estudiante', $estudiante->sis_estudiante))
                ->orderByDesc('id_examen')
                ->first();
        }

        $examen ??= Examen::query()->orderBy('id_examen')->first();

        if ($examen === null) {
            // Sin ningún examen en la base no hay registro posible: la materia se
            // deriva del examen y la columna no admite null.
            throw ValidationException::withMessages([
                'materia' => 'No hay ningún examen registrado para asociar la incidencia.',
            ]);
        }

        return (int) $examen->id_examen;
    }

    /**
     * Examen con el id indicado, o null si no existe o no se indicó ninguno.
     *
     * @param  int  $idExamen  Id recibido por la URL.
     * @return ?Examen
     */
    private function buscarExamen(int $idExamen): ?Examen
    {
        if ($idExamen <= 0) {
            return null;
        }

        return Examen::query()->find($idExamen);
    }

    /**
     * Último ingreso del estudiante, que es el que se enlaza al registro de la
     * incidencia.
     *
     * Puede ser null: desde la central de riesgos se reporta a estudiantes que
     * nunca ingressaron, y `id_ingreso` es nullable justamente para eso.
     *
     * @param  ?string  $sis  Código SIS del estudiante, si está en la base.
     * @return ?int  Id del ingreso, o null si no tiene ninguno.
     */
    private function ingresoDelEstudiante(?string $sis): ?int
    {
        if ($sis === null) {
            return null;
        }

        $ingreso = DB::table('registro_asistencia')
            ->where('id_estudiante', $sis)
            ->orderByDesc('hora_ingreso')
            ->value('id_ingreso');

        return $ingreso === null ? null : (int) $ingreso;
    }

    /**
     * Cierra el modal de confirmación y devuelve a la persona a la pantalla desde
     * la que se abrió el formulario, dando por terminado el registro.
     *
     * @return RedirectResponse  Redirección a la pantalla de origen.
     *
     * @author Candy
     * @since  2026-09-28
     */
    public function aceptarRegistro(): RedirectResponse
    {
        $this->confirmacionVisible = false;

        return redirect()->to($this->rutaVolver);
    }
 
    /**
     * Cancela el registro y regresa a la pantalla desde la que se abrió.
     *
     * @return RedirectResponse  Redirección a la pantalla de origen.
     *
     * @author Valery D. Ortuno P. <valerydariana98@gmail.com>
     * @since  2026-09-25
     */
    public function cancelar(): RedirectResponse
    {
        if ($this->origen === self::ORIGEN_MONITOREO) {
            return redirect()->route('monitoreo');
        }

        if ($this->origen === self::ORIGEN_CENTRAL_RIESGO) {
            return redirect()->route('central-riesgo');
        }

        return redirect()->back();
    }
 
    /**
     * Renderiza el formulario dentro del layout base de la aplicación.
     *
     * El título se declara como sección en la vista para que el layout lo muestre
     * tanto en el `<title>` del documento como en el encabezado.
     *
     * @return View  Vista del componente con el layout de la aplicación.
     *
     * @author Valery D. Ortuno P. <valerydariana98@gmail.com>
     * @since  2026-09-25
     */
    public function render(): View
    {
        return view('livewire.monitoreo.registrar-incidencia')
            ->extends('layouts.app');
    }
}
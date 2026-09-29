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
 * deriva el estado del registro del rol de quien lo realiza.
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
 */

namespace App\Livewire\Monitoreo;

use App\Enums\TipoInfraccion;
use App\Models\Estudiante;
use App\Models\Rol;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
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
     * Motivos de incidencia que se ofrecen en el selector, con el valor que se
     * persiste y la etiqueta que ve el usuario.
     *
     * TODO(@valerydariana98, 2026-09-28): al persistir, guardar el valor elegido
     * en `detalle_motivo` de la central de riesgos (#70).
     *
     * @var array<string, string>
     */
    private const TIPOS_INCIDENCIA = [
        'intento_de_ingreso_no_autorizado' => 'Intento de Ingreso a examen no autorizado',
        'uso_de_dispositivos_electronicos' => 'Uso de dispositivos electrónicos no autorizados',
        'copia_o_intercambio_de_respuestas' => 'Copia o intercambio de respuestas',
        'uso_de_material_no_autorizado' => 'Uso de material no autorizado',
        'suplantacion_de_identidad' => 'Suplantación de identidad',
        self::MOTIVO_OTRO => 'Otro',
    ];
 
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
     * Motivo de la incidencia, con un valor de `TIPOS_INCIDENCIA`.
     *
     * @var string
     */
    public string $tipoIncidencia = '';
 
    /**
     * Detalle de lo ocurrido, con un máximo de `DESCRIPCION_MAXIMO` caracteres.
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
     * Precarga el estudiante, la materia y la fecha con lo que llega por la URL.
     *
     * El monitor es la única entrada por ahora: si no viaja el contexto, el
     * formulario se abre en blanco para completarlo a mano. El rol también
     * llega por la URL como texto (uno de `Rol::NOMBRE_*`), porque `Rol` es un
     * modelo de la tabla `rol` y no un enum con casos fijos.
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
        $partes = explode(' ', trim($completo), 2);

        return [trim($partes[0] ?? ''), trim($partes[1] ?? '')];
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
     * Los motivos disponibles para el selector de la vista.
     *
     * @return array<string, string>  Motivos indexados por valor persistible.
     *
     * @author Valery D. Ortuno P. <valerydariana98@gmail.com>
     * @since  2026-09-25
     */
    #[Computed]
    public function tiposIncidencia(): array
    {
        return self::TIPOS_INCIDENCIA;
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
            'codigoSis' => $this->reglasCodigoSis(),
            'materia' => [
                'required',
                'string',
                'max:'.self::MATERIA_MAXIMO,
                'regex:/^[\p{L}\p{N}\s]+$/u',
            ],
            'tipoIncidencia' => ['required', Rule::in(array_keys(self::TIPOS_INCIDENCIA))],
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
            'codigoSis.unique' => 'Este código SIS ya está registrado.',
            'materia.required' => 'Ingrese la materia del examen.',
            'materia.regex' => 'La materia solo puede contener letras, números y espacios.',
            'materia.max' => 'La materia admite un máximo de '.self::MATERIA_MAXIMO.' caracteres.',
            'tipoIncidencia.required' => 'Seleccione el motivo de la incidencia.',
            'tipoIncidencia.in' => 'Seleccione un motivo válido de la lista.',
            'descripcion.required' => 'Describa el hecho observado.',
            'descripcion.max' => 'La descripción admite un máximo de '.self::DESCRIPCION_MAXIMO.' caracteres.',
        ];
    }
 
    /**
     * Registra la incidencia con los datos ingresados en el formulario.
     *
     * Valida los campos obligatorios y calcula el estado del registro según el
     * rol de quien lo realiza; el guardado en la central de riesgos queda a
     * cargo de la tarea #70.
     *
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException  Si falta el estudiante,
     *                                                       la materia o el motivo,
     *                                                       si la descripción es
     *                                                       obligatoria y está vacía,
     *                                                       o si excede el límite.
     *
     * @author Valery D. Ortuno P. <valerydariana98@gmail.com>
     * @author Amiddala
     * @since  2026-09-25
     */
    public function registrar(): void
    {
        $this->validate();
 
        // TODO(@Amiddala, 2026-09-25): persistir los datos validados junto con
        // $this->tipoInfraccion en la central de riesgos y refrescar la
        // lista de incidencias recientes (#70).
    }
 
    /**
     * Cancela el registro y regresa al monitor en vivo.
     *
     * @return RedirectResponse  Redirección al monitor o a la pantalla anterior.
     *
     * @author Valery D. Ortuno P. <valerydariana98@gmail.com>
     * @since  2026-09-25
     */
    public function cancelar(): RedirectResponse
    {
        if ($this->origen === self::ORIGEN_MONITOREO) {
            return redirect()->route('monitoreo');
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
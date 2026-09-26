<?php

/**
 * @file    RegistrarIncidencia.php
 * @author  Valery D. Ortuno P. <valerydariana98@gmail.com>
 * @created 2026-09-25
 * @updated 2026-09-26
 *
 * @description
 * Componente de página con el formulario de registro de una incidencia en la
 * central de riesgos. Precarga el estudiante y la materia que llegan desde el
 * monitor en vivo, permite reemplazarlos con el buscador de estudiantes de la
 * base de datos y deriva el estado del registro del rol de quien lo realiza.
 *
 * @changelog
 * - 2026-09-25  [Valery D. Ortuno P]  feat: creación inicial del formulario desktop.
 * - 2026-09-26  [Valery D. Ortuno P]  feat: buscador de estudiantes contra la base
 *   de datos, motivos acordados por el equipo, estado a partir del rol recibido
 *   por la URL y una sola vista responsive de escritorio y móvil.
 */

namespace App\Livewire\Monitoreo;

use App\Enums\TipoInfraccion;
use App\Models\Curso;
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
     * TODO(@valerydariana98, 2026-09-26): reemplazar el catálogo por los tipos
     * de incidencia registrados en la base de datos (#70). Hoy no existe la
     * tabla que los almacene, solo el campo de texto libre `detalle_motivo`.
     *
     * @var array<string, string>
     */
    private const TIPOS_INCIDENCIA = [
        'uso_de_dispositivo' => 'Uso de dispositivo electrónico',
        'ingreso_no_autorizado' => 'Ingreso a examen no autorizado',
        'copia_o_ayuda_externa' => 'Copia o ayuda externa',
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
     * Código SIS del estudiante sobre el que se registra la incidencia.
     *
     * @var string
     */
    public string $codigoSis = '';

    /**
     * Materia del examen en el que se observa la anomalía.
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
     * formulario se abre en blanco para completarlo a mano.
     *
     * @author Valery D. Ortuno P. <valerydariana98@gmail.com>
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

        $this->nombreEstudiante = (string) request()->query('nombre', '');
        $this->codigoSis = (string) request()->query('sis', '');
        $this->materia = (string) request()->query('materia', '');
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
     * @return string  "Confirmado" para el docente, "En revisión" para el auxiliar.
     *
     * @author Valery D. Ortuno P. <valerydariana98@gmail.com>
     * @since  2026-09-26
     */
    #[Computed]
    public function etiquetaEstado(): string
    {
        return $this->rol === Rol::NOMBRE_AUXILIAR ? 'En revisión' : 'Confirmado';
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
     * Motivos disponibles para el selector de la vista.
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
     * Materias que se ofrecen en el selector, tomadas de la tabla `curso`.
     *
     * TODO(@valerydariana98, 2026-09-26): acotar a los cursos del estudiante
     * elegido y a los que siguen en curso (`curso.estado = 'EnCurso'`) cuando
     * el grupo defina esa regla (#67).
     *
     * @return Collection<int, string>  Materias indexadas por su nombre.
     *
     * @author Valery D. Ortuno P. <valerydariana98@gmail.com>
     * @since  2026-09-26
     */
    #[Computed]
    public function materias(): Collection
    {
        return Curso::query()
            ->orderBy('nombre_curso')
            ->pluck('nombre_curso', 'nombre_curso');
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
        $this->nombreEstudiante = trim($estudiante->nombre_estudiante.' '.$estudiante->apellido_estudiante);
        $this->busqueda = '';
        $this->resetErrorBag('codigoSis');
    }

    /**
     * Reglas de validación del formulario.
     *
     * @return array<string, array<int, mixed>>  Reglas por propiedad.
     *
     * @author Valery D. Ortuno P. <valerydariana98@gmail.com>
     * @since  2026-09-25
     */
    protected function rules(): array
    {
        return [
            'codigoSis' => ['required', 'string'],
            'materia' => ['required', 'string'],
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
            'codigoSis' => 'código SIS',
            'tipoIncidencia' => 'motivo',
        ];
    }

    /**
     * Registra la incidencia con los datos ingresados en el formulario.
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
     * @since  2026-09-25
     */
    public function registrar(): void
    {
        $this->validate();

        // TODO(@valerydariana98, 2026-09-25): persistir el registro en la
        // central de riesgos y refrescar la lista de incidencias recientes (#70).
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

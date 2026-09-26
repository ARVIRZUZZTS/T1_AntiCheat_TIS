<?php

/**
 * @file    RegistrarIncidencia.php
 * @author  Valery D. Ortuno P. <valerydariana98@gmail.com>
 * @created 2026-09-25
 * @updated 2026-09-25
 *
 * @description
 * Componente de página con el formulario de registro de una incidencia en la
 * central de riesgos. Precarga los datos del estudiante y del examen, y calcula
 * el estado del registro a partir del rol de quien lo realiza.
 *
 * @changelog
 * - 2026-09-25  [Valery D. Ortuno P]  feat: creación inicial del formulario desktop.
 */

namespace App\Livewire\Monitoreo;

use App\Enums\TipoInfraccion;
use App\Models\Rol;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Formulario de registro de incidencia contra un estudiante, invocado desde el
 * monitor del examen en curso.
 *
 * @package  App\Livewire\Monitoreo
 * @author   Valery D. Ortuno P. <valerydariana98@gmail.com>
 * @since    2026-09-25
 *
 * @see  \App\Enums\TipoInfraccion
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
     * Motivos de incidencia que se ofrecen en el selector, con el valor que se
     * persiste y la etiqueta que ve el usuario.
     *
     * TODO(@valerydariana98, 2026-09-25): reemplazar el catálogo por los tipos
     * de incidencia registrados en la base de datos (#70).
     *
     * @var array<string, string>
     */
    private const TIPOS_INCIDENCIA = [
        'uso_de_dispositivo' => 'Uso de dispositivo o material no permitido',
        'comunicacion_externa' => 'Comunicación con personas externas al examen',
        'copia_de_material' => 'Copia o fotografía del material del examen',
        'abandono_del_aula' => 'Abandono del aula o del equipo',
        'otro' => 'Otro',
    ];

    /**
     * Rol con el que se registra la incidencia, recibido desde la pantalla que
     * abrió el formulario.
     *
     * @var string  Uno de los valores de \App\Models\Rol::NOMBRE_*.
     */
    public string $rol = Rol::NOMBRE_DOCENTE;

    /**
     * Datos del estudiante sobre el que se registra la incidencia.
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
     * Motivo de la incidencia, con el valor de `TIPOS_INCIDENCIA`.
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
     * Precarga el estudiante, el examen y la fecha del registro.
     *
     * @author Valery D. Ortuno P. <valerydariana98@gmail.com>
     * @since  2026-09-25
     */
    public function mount(): void
    {
        $this->fechaHoraRegistro = now()->format('d/m/Y H:i');

        // TODO(@valerydariana98, 2026-09-25): reemplazar los datos simulados
        // por los del estudiante y el examen recibidos desde el monitor (#67).
        $this->nombreEstudiante = 'Ana López';
        $this->codigoSis = '202201013';
        $this->materia = 'Cálculo Diferencial';
    }

    /**
     * Tipo de infracción con el que se persiste el registro, derivado del rol
     * de quien lo realiza: un docente confirma y un auxiliar deja el caso en
     * revisión.
     *
     * @return TipoInfraccion  Valor del enum `tipo_infraccion` de la base de datos.
     *
     * @author Valery D. Ortuno P. <valerydariana98@gmail.com>
     * @since  2026-09-25
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
     * @since  2026-09-25
     */
    #[Computed]
    public function etiquetaEstado(): string
    {
        return $this->rol === Rol::NOMBRE_AUXILIAR ? 'En revisión' : 'Confirmado';
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
     * Reglas de validación del formulario.
     *
     * @return array<string, array<int, string>>  Reglas por propiedad.
     *
     * @author Valery D. Ortuno P. <valerydariana98@gmail.com>
     * @since  2026-09-25
     */
    protected function rules(): array
    {
        return [
            'tipoIncidencia' => ['required'],
            'descripcion' => ['required', 'string', 'max:'.self::DESCRIPCION_MAXIMO],
        ];
    }

    /**
     * Registra la incidencia con los datos ingresados en el formulario.
     *
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException  Si falta el motivo o
     *                                                       la descripción, o si
     *                                                       la descripción excede
     *                                                       el límite de caracteres.
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
     * Abandona el registro y regresa a la pantalla desde la que se abrió.
     *
     * @return RedirectResponse  Redirección hacia el origen.
     *
     * @author Valery D. Ortuno P. <valerydariana98@gmail.com>
     * @since  2026-09-25
     */
    public function cancelar(): RedirectResponse
    {
        // TODO(@valerydariana98, 2026-09-25): reemplazar por la navegación
        // explícita del monitor hacia el formulario y de regreso al monitor (#69).
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

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

 * - 2026-09-25  [Amiddala]            feat: asignación automática de estado según
 *                                     el rol del registrador. El rol deja de
 *                                     ser un valor fijo y pasa a recibirse como
 *                                     parámetro del componente, de modo que ambos
 *                                     estados (Sospechoso/Confirmado) son alcanzables.
 */

namespace App\Livewire\Monitoreo;
 
use App\Models\EstadoIncidencia;
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
 * @see  EstadoIncidencia
 * @see  Rol
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
     * Rol de la persona que registra la incidencia. De este valor depende el
     * estado con el que ingresa el registro (ver `EstadoIncidencia::desdeRol()`).
     *
     * Se recibe como parámetro del componente (ver `mount()`) en lugar de un
     * valor fijo, para que el estado se calcule automáticamente según quién
     * esté registrando.
     *
     * TODO(@Amiddala, 2026-09-25): una vez exista el rol del usuario
     * autenticado (`Context::user()`), pasar aquí ese valor en lugar de que el
     * componente que abre el modal lo indique explícitamente.
     *
     * @var Rol
     */
    public Rol $rol = Rol::DOCENTE;
 
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
     * Precarga el estudiante, el examen y la fecha del registro, y recibe el rol
     * de quien registra la incidencia.
     *
     * @param  Rol  $rol  Rol de la persona que abre el formulario. Determina el
     *                    estado con el que ingresará el registro.
     *
     * @author Valery D. Ortuno P. <valerydariana98@gmail.com>
     * @author Amiddala
     * @since  2026-09-25
     */
    public function mount(Rol $rol = Rol::DOCENTE): void
    {
        $this->rol = $rol;
        $this->fechaHoraRegistro = now()->format('d/m/Y H:i');
 
        // TODO(@valerydariana98, 2026-09-25): reemplazar los datos simulados
        // por los del estudiante y el examen recibidos desde el monitor (#67).
        $this->nombreEstudiante = 'Ana López';
        $this->codigoSis = '202201013';
        $this->materia = 'Cálculo Diferencial';
    }
 
    /**
     * Estado con el que ingresó la incidencia, derivado del rol de quien registra.
     *
     * @return EstadoIncidencia  Estado derivado del rol del registrador.
     *
     * @author Valery D. Ortuno P. <valerydariana98@gmail.com>
     * @author Amiddala
     * @since  2026-09-25
     */
    #[Computed]
    public function estadoIncidencia(): EstadoIncidencia
    {
        return EstadoIncidencia::desdeRol($this->rol);
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
     * Reglas de validación del formulario. El motivo y la descripción son
     * obligatorios; el motivo, además, debe ser uno de los valores ofrecidos
     * en el selector.
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
            'tipoIncidencia' => ['required', 'string', 'in:'.implode(',', array_keys(self::TIPOS_INCIDENCIA))],
            'descripcion' => ['required', 'string', 'max:'.self::DESCRIPCION_MAXIMO],
        ];
    }
 
    /**
     * Mensajes de validación personalizados para los campos obligatorios.
     *
     * @return array<string, string>  Mensaje por regla incumplida.
     *
     * @author Amiddala
     * @since  2026-09-25
     */
    protected function messages(): array
    {
        return [
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
     * @throws \Illuminate\Validation\ValidationException  Si falta el motivo o
     *                                                       la descripción, o si
     *                                                       la descripción excede
     *                                                       el límite de caracteres.
     *
     * @author Valery D. Ortuno P. <valerydariana98@gmail.com>
     * @author Amiddala
     * @since  2026-09-25
     */
    public function registrar(): void
    {
        $this->validate();
 
        // TODO(@Amiddala, 2026-09-25): persistir los datos validados junto con
        // $this->estadoIncidencia en la central de riesgos y refrescar la
        // lista de incidencias recientes (#70).
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
        // TODO(@Amiddala, 2026-09-25): reemplazar por la navegación
        // explícita del monitor hacia el formulario y de regreso al monitor (#69).
        return redirect()->back();
    }
 
    /**
     * Renderiza el formulario dentro del layout base de la aplicación.
     *
     * @return View  Vista del componente con el layout y el título de la página.
     *
     * @author Valery D. Ortuno P. <valerydariana98@gmail.com>
     * @since  2026-09-25
     */
    public function render(): View
    {
        return view('livewire.monitoreo.registrar-incidencia')
            ->extends('layouts.app')
            ->title('Registrar incidencia');
    }
}
 
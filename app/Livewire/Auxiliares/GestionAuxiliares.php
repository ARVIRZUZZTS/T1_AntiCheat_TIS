<?php

/**
 * @file    GestionAuxiliares.php
 * @author  David E. Chavez T. <virzuzz12345@gmail.com>
 * @created 2026-10-19
 * @updated 2026-11-08
 * @updated 2026-11-09
 *
 * @description
 * Componente Livewire para la gestión de auxiliares de un docente: listado de
 * auxiliares asignados con filtros por estado y por curso, y alta de un nuevo
 * auxiliar mediante un modal.
 *
 * @changelog
 * - 2026-10-19  [ARVIRZUZZTS]  feat: agregar autenticación y gestión de
 *   docentes/auxiliares.
 * - 2026-11-08  [David E. Chavez T.]  refactor: mover la creación de auxiliar
 *   a un modal con inputs verticales.
 * - 2026-11-09  [OchoaCesar]  feat: filtros de estado (Todos/Activo/Baja) y
 *   curso (Todos + cursos del docente) sobre el listado; por defecto ambos
 *   en "todos" muestran todos los auxiliares.
 *
 * @see  App\Models\Curso
 * @see  App\Models\Usuario
 */

namespace App\Livewire\Auxiliares;

use App\Models\Curso;
use App\Models\Rol;
use App\Models\Usuario;
use App\Support\Validadores;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

use Livewire\Component;

/**
 * Gestiona los auxiliares asignados al docente autenticado.
 *
 * @package  App\Livewire\Auxiliares
 * @author   David E. Chavez T. <virzuzz12345@gmail.com>
 * @since    2026-10-19
 */
class GestionAuxiliares extends Component
{
    /**
     * Nombre del auxiliar a registrar.
     *
     * @var string
     */
    public string $nombre = '';

    /**
     * Apellido del auxiliar a registrar.
     *
     * @var string
     */
    public string $apellido = '';

    /**
     * Código SIS del auxiliar a registrar.
     *
     * @var string
     */
    public string $codSis = '';

    /**
     * Contraseña del auxiliar a registrar.
     *
     * @var string
     */
    public string $password = '';

    /**
     * Curso seleccionado para la asignación.
     *
     * @var int|null
     */
    public ?int $idCurso = null;

    /**
     * Mensaje de éxito de la operación.
     *
     * @var string
     */
    public string $mensajeExito = '';

    /**
     * Mensaje de error de la operación.
     *
     * @var string
     */
    public string $mensajeError = '';

    /**
     * Controla la visibilidad del modal de creación.
     *
     * @var bool
     */
    public bool $mostrarModalCrear = false;

    /**
     * Filtro de estado del listado: todos, Activo o Baja.
     *
     * @var string
     */
    public string $filtroEstado = 'todos';

    /**
     * Filtro de curso del listado: todos o el id de un curso del docente.
     *
     * @var string
     */
    public string $filtroCurso = 'todos';

    /**
     * Inicializa el curso seleccionado por defecto.
     *
     * @return void
     *
     * @author David E. Chavez T. <virzuzz12345@gmail.com>
     * @since  2026-10-19
     */
    public function mount(): void
    {
        $this->idCurso = $this->cursos()->first()?->id_curso;
    }

    /**
     * Abre el modal de creación de auxiliar.
     *
     * @return void
     *
     * @author David E. Chavez T. <virzuzz12345@gmail.com>
     * @since  2026-11-08
     */
    public function abrirModalCrear(): void
    {
        $this->resetErrorBag();
        $this->mensajeError = '';
        $this->mostrarModalCrear = true;
    }

    /**
     * Cierra el modal de creación de auxiliar.
     *
     * @return void
     *
     * @author David E. Chavez T. <virzuzz12345@gmail.com>
     * @since  2026-11-08
     */
    public function cerrarModalCrear(): void
    {
        $this->mostrarModalCrear = false;
    }

    /**
     * Cursos del docente autenticado.
     *
     * @return \Illuminate\Support\Collection<int, \App\Models\Curso>
     *
     * @author David E. Chavez T. <virzuzz12345@gmail.com>
     * @since  2026-10-19
     */
    public function cursos()
    {
        return Curso::query()
            ->where('sis_doc', auth()->id())
            ->orderBy('nombre_curso')
            ->get();
    }

    /**
     * Auxiliares asignados a los cursos del docente.
     *
     * @return \Illuminate\Support\Collection<int, \stdClass>
     *
     * @author David E. Chavez T. <virzuzz12345@gmail.com>
     * @since  2026-10-19
     */
    public function auxiliares()
    {
        $consulta = DB::table('auxiliar_curso')
            ->join('curso', 'curso.id_curso', '=', 'auxiliar_curso.id_curso')
            ->join('usuario', 'usuario.id_usuario', '=', 'auxiliar_curso.id_auxiliar')
            ->where('curso.sis_doc', auth()->id())
            ->select('usuario.id_usuario', 'usuario.cod_sis', 'usuario.nombre_usuario', 'usuario.apellido', 'curso.nombre_curso', 'auxiliar_curso.estado');

        if (in_array($this->filtroEstado, ['Activo', 'Baja'], true)) {
            $consulta->where('auxiliar_curso.estado', $this->filtroEstado);
        }

        if ($this->filtroCurso !== 'todos') {
            $consulta->where('auxiliar_curso.id_curso', (int) $this->filtroCurso);
        }

        return $consulta
            ->orderBy('usuario.nombre_usuario')
            ->get();
    }

    /**
     * Registra un nuevo auxiliar y lo asigna al curso seleccionado.
     *
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException  Si los datos no son válidos.
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException  Si no existe el rol auxiliar.
     *
     * @author David E. Chavez T. <virzuzz12345@gmail.com>
     * @since  2026-10-19
     */
    public function crear(): void
    {
        if (trim($this->password) === '') {
            $this->password = '12345678';
        }

        $this->validate(
            Validadores::reglasAuxiliar(),
            Validadores::mensajesAuxiliar(),
        );

        if (Usuario::query()->where('cod_sis', $this->codSis)->exists()) {
            $this->addError('codSis', 'Ya existe un usuario con este código SIS.');
            return;
        }

        $curso = Curso::query()
            ->where('id_curso', $this->idCurso)
            ->where('sis_doc', auth()->id())
            ->first();

        if ($curso === null) {
            $this->mensajeError = 'Curso no válido.';
            return;
        }

        $rolAuxiliar = Rol::query()->where('nombre_rol', Rol::NOMBRE_AUXILIAR)->firstOrFail();

        $idUsuario = ((int) Usuario::query()->max('id_usuario')) + 1;

        $usuario = Usuario::create([
            'id_usuario' => $idUsuario,
            'cod_sis' => $this->codSis,
            'password' => Hash::make($this->password),
            'nombre_usuario' => $this->nombre,
            'apellido' => $this->apellido,
        ]);

        DB::table('rol_usuario')->insert([
            'id_usuario' => $usuario->id_usuario,
            'id_rol' => $rolAuxiliar->id_rol,
        ]);

        DB::table('auxiliar_curso')->insert([
            'id_auxiliar' => $usuario->id_usuario,
            'id_curso' => $curso->id_curso,
            'estado' => 'Activo',
        ]);

        $this->reset(['nombre', 'apellido', 'codSis', 'password']);
        $this->mensajeExito = 'Auxiliar creado y asignado.';
        $this->mensajeError = '';
        $this->mostrarModalCrear = false;
    }

    /**
     * Renderiza la vista de gestión de auxiliares.
     *
     * @return \Illuminate\Contracts\View\View
     *
     * @author David E. Chavez T. <virzuzz12345@gmail.com>
     * @since  2026-10-19
     */
    public function render()
    {
        return view('livewire.auxiliares.gestion-auxiliares', [
            'cursos' => $this->cursos(),
            'auxiliares' => $this->auxiliares(),
        ])->extends('layouts.app');
    }
}

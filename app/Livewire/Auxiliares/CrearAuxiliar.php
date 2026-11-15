<?php

/**
 * @file    CrearAuxiliar.php
 * @author  David E. Chavez T. <virzuzz12345@gmail.com>
 * @created 2026-11-15
 *
 * @description
 * Componente Livewire del alta de un auxiliar como página propia (subopción
 * "Crear auxiliar" del sidebar). La lógica y las validaciones
 * (Validadores::reglasAuxiliar) son las del modal original de GestionAuxiliares
 * (David E. Chavez T.); solo cambia el contenedor (de modal a página). Al
 * guardar, crea el usuario con rol auxiliar, lo asigna al curso elegido
 * (auxiliar_curso) y redirige a la lista con un aviso de éxito.
 *
 * @changelog
 * - 2026-11-08  [David E. Chavez T.]  feat: alta de auxiliar (originalmente el
 *   modal de GestionAuxiliares).
 * - 2026-11-15  [Diego Tejerina]  refactor: se mueve esa alta a una página
 *   propia (mismo código y validaciones), que redirige a la lista al terminar.
 *
 * @see  App\Livewire\Auxiliares\GestionAuxiliares
 * @see  App\Support\Validadores
 */

namespace App\Livewire\Auxiliares;

use App\Models\Curso;
use App\Models\Rol;
use App\Models\Usuario;
use App\Support\Validadores;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class CrearAuxiliar extends Component
{
    public string $nombre = '';

    public string $apellido = '';

    public string $codSis = '';

    public string $password = '';

    public ?int $idCurso = null;

    public string $mensajeError = '';

    public function mount(): void
    {
        $this->idCurso = $this->cursos()->first()?->id_curso;
    }

    /**
     * Cursos del docente autenticado, para asignar el auxiliar.
     *
     * @return Collection<int, Curso>
     */
    public function cursos(): Collection
    {
        return Curso::query()
            ->where('sis_doc', auth()->id())
            ->orderBy('nombre_curso')
            ->get();
    }

    /**
     * Registra el auxiliar y lo asigna al curso elegido. Al terminar vuelve a la
     * lista con el aviso de éxito.
     *
     * @throws \Illuminate\Validation\ValidationException  Si los datos no son válidos.
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException  Si no existe el rol auxiliar.
     */
    public function crear(): ?RedirectResponse
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

            return null;
        }

        $curso = Curso::query()
            ->where('id_curso', $this->idCurso)
            ->where('sis_doc', auth()->id())
            ->first();

        if ($curso === null) {
            $this->mensajeError = 'Curso no válido.';

            return null;
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

        return redirect()
            ->route('auxiliares')
            ->with('mensaje', 'Auxiliar creado y asignado.');
    }

    public function render(): View
    {
        return view('livewire.auxiliares.crear-auxiliar', [
            'cursos' => $this->cursos(),
        ])->extends('layouts.app');
    }
}

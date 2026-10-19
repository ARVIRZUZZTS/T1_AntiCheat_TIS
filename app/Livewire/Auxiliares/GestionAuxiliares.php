<?php

namespace App\Livewire\Auxiliares;

use App\Models\Curso;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Component;

class GestionAuxiliares extends Component
{
    public string $nombre = '';
    public string $apellido = '';
    public string $codSis = '';
    public string $password = '';
    public ?int $idCurso = null;

    public string $mensajeExito = '';
    public string $mensajeError = '';

    public function mount(): void
    {
        $this->idCurso = $this->cursos()->first()?->id_curso;
    }

    public function cursos()
    {
        return Curso::query()
            ->where('sis_doc', auth()->id())
            ->orderBy('nombre_curso')
            ->get();
    }

    public function auxiliares()
    {
        return DB::table('auxiliar_curso')
            ->join('curso', 'curso.id_curso', '=', 'auxiliar_curso.id_curso')
            ->join('usuario', 'usuario.id_usuario', '=', 'auxiliar_curso.id_auxiliar')
            ->where('curso.sis_doc', auth()->id())
            ->select('usuario.id_usuario', 'usuario.cod_sis', 'usuario.nombre_usuario', 'usuario.apellido', 'curso.nombre_curso', 'auxiliar_curso.estado')
            ->orderBy('usuario.nombre_usuario')
            ->get();
    }

    public function crear(): void
    {
        $this->validate([
            'nombre' => ['required', 'string', 'max:50'],
            'apellido' => ['required', 'string', 'max:50'],
            'codSis' => ['required', 'string', 'max:20', Rule::unique('usuario', 'cod_sis')],
            'password' => ['required', 'string', 'min:8'],
            'idCurso' => ['required', 'integer'],
        ]);

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
    }

    public function render()
    {
        return view('livewire.auxiliares.gestion-auxiliares', [
            'cursos' => $this->cursos(),
            'auxiliares' => $this->auxiliares(),
        ])->extends('layouts.app');
    }
}
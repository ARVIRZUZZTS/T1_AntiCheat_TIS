@extends('layouts.app')

@section('title', 'Cargar lista de estudiantes')
@section('subtitle', 'Elige un curso y sube la lista de estudiantes en Excel o CSV.')

@section('content')
    {{-- El componente trae su propio selector de curso; ?curso lo preselecciona. --}}
    @livewire(\App\Livewire\Cursos\CargarCsv::class, ['curso' => $curso])
@endsection

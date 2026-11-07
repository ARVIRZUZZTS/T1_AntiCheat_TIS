@extends('layouts.app')

@section('title', 'Ver auxiliares')
@section('subtitle', 'Auxiliares que registraste')

@section('content')
    @livewire(\App\Livewire\Auxiliares\ListaAuxiliares::class)
@endsection

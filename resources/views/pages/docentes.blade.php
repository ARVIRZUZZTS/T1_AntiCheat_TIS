{{--
    @file    docentes.blade.php
    @author  T1 - Tech One SRL
    @created 2026-10-17
    @updated 2026-10-17
    @description Gestión y verificación de cuentas de docentes (mock de vista).
--}}
@extends('layouts.app')

@section('title', 'Docentes')
@section('subtitle', 'Gestión y verificación de cuentas')

@section('content')
    <div x-data="{
        solicitudes: [
            { id: 1, nombre: 'María Fernanda López', correo: 'maria.lopez@umss.edu', sis: '202301310', fecha: '16 oct 2026, 09:14', estado: 'pendiente', verificaciones: [{ nombre: 'Roberto Silva', sis: 'DOC001' }] },
            { id: 2, nombre: 'Carlos Eduardo Rojas', correo: 'carlos.rojas@umss.edu', sis: '202301311', fecha: '16 oct 2026, 10:02', estado: 'pendiente', verificaciones: [] },
            { id: 3, nombre: 'Ana Paula Méndez', correo: 'ana.mendez@umss.edu', sis: '202301312', fecha: '15 oct 2026, 14:30', estado: 'pendiente', verificaciones: [{ nombre: 'Patricia Rojas', sis: 'DOC002' }] }
        ],
        ultimaAccion: null,
        alerta: null,
        modal: { abierta: false, tipo: '', id: null, nombre: '', sis: '', motivo: '', error: '' },
        pendientes() { return this.solicitudes.filter(s => s.estado === 'pendiente').length; },
        abrirAprobar(s) {
            if (s.verificaciones.some(v => v.sis === 'DOC001')) return;
            this.modal = { abierta: true, tipo: 'aprobar', id: s.id, nombre: s.nombre, sis: s.sis, motivo: '', error: '' };
        },
        abrirRechazar(s) {
            this.modal = { abierta: true, tipo: 'rechazar', id: s.id, nombre: s.nombre, sis: s.sis, motivo: '', error: '' };
        },
        aprobar(id) {
            const s = this.solicitudes.find(x => x.id === id);
            if (!s) return;
            const anterior = { verificaciones: [...s.verificaciones], estado: s.estado };
            if (s.verificaciones.length < 2 && !s.verificaciones.some(v => v.sis === 'DOC001')) {
                s.verificaciones.push({ nombre: 'Roberto Silva', sis: 'DOC001' });
            }
            this.modal.abierta = false;
            if (s.verificaciones.length >= 2) {
                s.estado = 'aprobada';
                this.solicitudes = this.solicitudes.filter(x => x.id !== id);
                this.ultimaAccion = { id, tipo: 'aprobar', anterior, solicitud: { ...s } };
                this.alerta = { tipo: 'success', texto: 'Cuenta de ' + s.nombre + ' aprobada y habilitada' };
            } else {
                this.ultimaAccion = { id, tipo: 'aprobar', anterior, solicitud: null };
            }
        },
        rechazar(id) {
            const s = this.solicitudes.find(x => x.id === id);
            if (!s) return;
            if (!this.modal.motivo.trim()) { this.modal.error = 'El motivo es obligatorio'; return; }
            const anterior = { verificaciones: [...s.verificaciones], estado: s.estado };
            s.estado = 'rechazada';
            this.solicitudes = this.solicitudes.filter(x => x.id !== id);
            this.modal.abierta = false;
            this.ultimaAccion = { id, tipo: 'rechazar', anterior, solicitud: { ...s } };
            this.alerta = { tipo: 'danger', texto: 'Solicitud de ' + s.nombre + ' rechazada' };
        },
        deshacer() {
            if (!this.ultimaAccion) return;
            const a = this.ultimaAccion;
            if (a.solicitud) {
                const existe = this.solicitudes.some(x => x.id === a.id);
                if (!existe) this.solicitudes.push(a.solicitud);
                const s = this.solicitudes.find(x => x.id === a.id);
                if (s) { s.verificaciones = a.anterior.verificaciones; s.estado = a.anterior.estado; }
            }
            this.ultimaAccion = null;
            this.alerta = null;
        }
    }" class="space-y-4">

    <x-ui.tabs :items="[
        ['label' => 'Autorizaciones', 'href' => '#', 'active' => true],
    ]" />

    <div class="flex items-center justify-between">
        <h3 class="text-lg font-semibold text-heading">Solicitudes de registro de docentes</h3>
        <span class="text-sm text-body" x-text="pendientes() + ' pendientes'"></span>
    </div>

    <template x-if="alerta">
        <x-ui.alert x-bind:type="alerta.tipo" class="mb-4">
            <span x-text="alerta.texto"></span>
            <button type="button" class="ms-2 font-semibold" @click="deshacer()">Deshacer</button>
        </x-ui.alert>
    </template>

    <div class="grid gap-4 md:grid-cols-2">
        <template x-for="s in solicitudes" :key="s.id">
            <div class="rounded-base border border-default bg-neutral-primary-soft p-4 shadow-xs">
                <div class="mb-3 flex items-center gap-3">
                    <x-ui.avatar name="" size="sm" x-bind:name="s.nombre" />
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-medium text-heading" x-text="s.nombre"></p>
                        <x-ui.badge type="status-pendiente">Pendiente</x-ui.badge>
                    </div>
                </div>
                <p class="mb-1 text-sm text-body"><span x-text="s.correo"></span></p>
                <p class="mb-1 font-mono text-sm text-heading" x-text="s.sis"></p>
                <p class="mb-3 text-xs text-muted" x-text="s.fecha"></p>

                <div class="mb-3">
                    <p class="text-sm font-medium text-heading" x-text="s.verificaciones.length + ' de 2 verificaciones'"></p>
                    <div class="mt-1 flex flex-wrap gap-1">
                        <template x-if="s.verificaciones.length === 0">
                            <span class="text-xs text-muted">Sin verificaciones aún</span>
                        </template>
                        <template x-for="v in s.verificaciones" :key="v.sis">
                            <span class="rounded-full bg-success-soft px-2 py-0.5 text-xs text-fg-success-strong">
                                ✓ <span x-text="v.nombre + ' — ' + v.sis"></span>
                            </span>
                        </template>
                    </div>
                </div>

                <div class="flex flex-col gap-2 sm:flex-row">
                    <x-ui.button variant="default" type="button" class="w-full justify-center sm:w-auto"
                                 :disabled="false"
                                 x-bind:disabled="s.verificaciones.some(v => v.sis === 'DOC001')"
                                 @click="abrirAprobar(s)">Aprobar</x-ui.button>
                    <x-ui.button variant="danger" type="button" class="w-full justify-center sm:w-auto"
                                 @click="abrirRechazar(s)">Desaprobar</x-ui.button>
                </div>
            </div>
        </template>
    </div>

    @include('components.ui.modal-aprobar-solicitud')
    @include('components.ui.modal-rechazar-solicitud')
</div>
@endsection

{{--
    @file    badge.blade.php
    @author Alisson D. Alvarado <alvaradoalissondalet@gmail.com>
    @created 2026-09-23
    @updated 2026-09-23

    @description
    Insignia pequeña con variantes de color (brand, alternative, gray,
    danger, success, warning) y los seis estados del dominio
    (habilitado, no-habilitado, en-revision, ya-registrado,
    central-riesgos, pendiente).
--}}

@props(['type' => 'brand'])

@php
    $styles = [
        'brand'       => 'bg-brand-softer border-brand-subtle text-fg-brand-strong',
        'alternative' => 'bg-neutral-primary-soft border-default text-heading',
        'gray'        => 'bg-neutral-secondary-medium border-default-medium text-heading',
        'danger'      => 'bg-danger-soft border-danger-subtle text-fg-danger-strong',
        'success'     => 'bg-success-soft border-success-subtle text-fg-success-strong',
        'warning'     => 'bg-warning-soft border-warning-subtle text-fg-warning',

        'status-habilitado'       => 'bg-status-habilitado-bg border-status-habilitado-fg text-status-habilitado-fg',
        'status-no-habilitado'    => 'bg-status-no-habilitado-bg border-status-no-habilitado-fg text-status-no-habilitado-fg',
        'status-en-revision'      => 'bg-status-en-revision-bg border-status-en-revision-fg text-status-en-revision-fg',
        'status-ya-registrado'    => 'bg-status-ya-registrado-bg border-status-ya-registrado-fg text-status-ya-registrado-fg',
        'status-central-riesgos'  => 'bg-status-central-riesgos-bg border-status-central-riesgos-fg text-status-central-riesgos-fg',
        'status-pendiente'        => 'bg-status-pendiente-bg border-status-pendiente-fg text-status-pendiente-fg',
    ];
@endphp

<span {{ $attributes->merge([
    'class' => 'inline-flex items-center px-1.5 py-0.5 text-xs font-medium rounded-full border ' . ($styles[$type] ?? $styles['brand']),
]) }}>
    {{ $slot }}
</span>
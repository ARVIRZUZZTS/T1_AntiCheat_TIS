{{--
    @file    ficha-estudiante.blade.php
    @author  David E. Chavez T. <virzuzz12345@gmail.com>
    @created 2026-10-10
    @updated 2026-10-10
    @updated 2026-10-25

    @description
    Partial compartido con la ficha del estudiante (avatar con iniciales,
    nombre y código SIS) usada por los modales de ingreso. Recibe $nombre y
    $sis como expresiones Alpine (por defecto 'nombre' y 'sis') y $tone para
    el color del avatar: 'brand' (por defecto) o 'riesgo'.

    Opcionales: $avatar (true por defecto) para ocultar el círculo con las
    iniciales, y $fondo ('riesgo' rojo, 'revision' amarillo) para pintar todo
    el recuadro según el estado de la incidencia.

    @changelog
    - 2026-10-10  [David E. Chavez T.]  feat: creación inicial del partial.
    - 2026-10-25  [Valery D. Ortuno P]  feat: opciones $avatar y $fondo para
      los modales de registro de incidencia (HU 5).

    @see  resources/views/components/ui/modal-registro-ingreso.blade.php
--}}

@php
    $nombre = $nombre ?? 'nombre';
    $sis = $sis ?? 'sis';
    $tone = $tone ?? 'brand';
    $avatar = $avatar ?? true;

    $recuadro = match ($fondo ?? null) {
        'riesgo' => 'border-status-central-riesgos-fg bg-status-central-riesgos-bg',
        'revision' => 'border-status-en-revision-fg bg-status-en-revision-bg',
        default => 'border-default bg-neutral-secondary-soft',
    };

    $colorAvatar = $tone === 'riesgo'
        ? 'bg-status-central-riesgos-bg text-fg-danger'
        : 'bg-brand-softer text-fg-brand';
@endphp

<div class="flex items-center gap-3 rounded-base border {{ $recuadro }} p-4">
    @if ($avatar)
    <span
        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-sm font-semibold {{ $colorAvatar }}"
        x-text="({{ $nombre }} || '').trim().split(/\s+/).filter(Boolean).map(function (p) { return p.charAt(0).toUpperCase(); }).slice(0, 2).join('')"
    ></span>
    @endif
    <div class="min-w-0">
        <p class="truncate text-sm font-semibold text-heading" x-text="{{ $nombre }}"></p>
        <p class="truncate text-sm text-muted" x-text="{{ $sis }}"></p>
    </div>
</div>

{{--
    @file    campos-incidencia.blade.php
    @author  Valery D. Ortuno P <valerydariana98@gmail.com>
    @created 2026-10-25

    @description
    Campos comunes de los modales de registro de incidencia del monitor en
    vivo (registro rápido y estudiante no inscrito), para que todos se vean
    igual (HU 5):

     - "Fecha y hora del registro" y "Estado de la incidencia", de solo
       lectura: el estado sale del rol (docente "Confirmado", auxiliar "En
       revision"); la hora la fija el servidor al guardar.
     - "Motivo de la incidencia" (obligatorio): los motivos de la vista,
       ingreso o monitor (Motivo::paraIngreso() / paraMonitor()).
     - "Descripcion del hecho": hasta 300 caracteres, obligatoria con "Otro".

    Lo incluye un x-data que declara `ahora`, `motivo` y `detalle`. Recibe
    `$contexto` ('ingreso' o 'monitor'), `$comoAuxiliar` y `$prefijo` (para
    que los id no se repitan entre modales).

    @changelog
    - 2026-10-25  [Valery D. Ortuno P]  feat: creación inicial del parcial.

    @see  resources/views/components/ui/modal-registrar-tramposo.blade.php
    @see  resources/views/components/ui/modal-registrar-no-inscrito.blade.php
    @see  App\Services\CentralRiesgo\RegistrarIncidenciaService
--}}

@php
    use App\Enums\Motivo;
    use App\Services\CentralRiesgo\RegistrarIncidenciaService;

    $motivos = $contexto === 'ingreso' ? Motivo::paraIngreso() : Motivo::paraMonitor();
    $maximo = RegistrarIncidenciaService::DESCRIPCION_MAXIMO;
@endphp

<dl class="space-y-3 rounded-base border border-default bg-neutral-secondary-soft p-3 text-sm">
    <div>
        <dt class="text-xs text-muted">Fecha y hora del registro</dt>
        <dd class="font-medium text-heading" x-text="ahora"></dd>
    </div>
    <div>
        <dt class="text-xs text-muted">Estado de la incidencia</dt>
        <dd>
            <x-ui.badge :type="$comoAuxiliar ? 'status-en-revision' : 'status-central-riesgos'" class="font-semibold">
                {{ $comoAuxiliar ? 'En revision' : 'Confirmado' }}
            </x-ui.badge>
        </dd>
    </div>
</dl>

<div>
    <p class="mb-2 text-sm font-medium text-heading">Motivo de la incidencia <span class="text-fg-danger-strong" aria-hidden="true">*</span></p>
    <div class="space-y-2">
        @foreach ($motivos as $item)
            <label
                for="{{ $prefijo }}-motivo-{{ $item->name }}"
                class="flex cursor-pointer items-center gap-3 rounded-base p-3 transition"
                :class="motivo === @js($item->value)
                    ? 'border-2 border-brand bg-brand-softer'
                    : 'border border-light hover:bg-neutral-secondary-soft'"
            >
                <input
                    type="radio"
                    id="{{ $prefijo }}-motivo-{{ $item->name }}"
                    name="{{ $prefijo }}_motivo"
                    value="{{ $item->value }}"
                    x-model="motivo"
                    class="h-4 w-4 shrink-0 accent-brand"
                >
                <span class="text-sm text-heading">{{ $item->etiqueta() }}</span>
            </label>
        @endforeach
    </div>
</div>

<div>
    <label for="{{ $prefijo }}-detalle" class="mb-2 block text-sm font-medium text-heading">
        Descripcion del hecho
        <span x-show="motivo === @js(Motivo::Otro->value)" class="text-fg-danger-strong" aria-hidden="true">*</span>
    </label>
    <textarea
        id="{{ $prefijo }}-detalle"
        rows="3"
        maxlength="{{ $maximo }}"
        placeholder="Describa lo observado"
        x-model="detalle"
        class="block w-full rounded-base border border-default-medium bg-neutral-secondary-medium p-3 text-sm text-heading shadow-xs placeholder:text-body focus:border-brand focus:ring-brand"
    ></textarea>
    <p class="mt-1 text-xs text-muted"><span x-text="detalle.length"></span>/{{ $maximo }}</p>
</div>

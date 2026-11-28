{{--
    @file    selector-materia.blade.php
    @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
    @created 2026-11-28

    @description
    Buscador de materia para "Crear curso", con el mismo patrón que el selector
    de ambientes de Crear examen: un input que filtra una lista desplegable
    mientras se escribe (por nombre o código) y muestra el semestre. Las materias
    del docente salen primero. Si no hay coincidencias, ofrece "+ Agregar materia
    '[texto]'", que abre un mini formulario (nombre, código opcional de 7 dígitos,
    semestre opcional); al guardar crea la materia (POST api.materias.crear),
    valida duplicados ("Esta materia ya existe") y la deja seleccionada. El id
    elegido viaja en un input oculto ({{ $name }}); el texto del buscador no se
    envía.

    @see  App\Http\Controllers\Api\MateriaController
    @see  App\Services\Curso\ListarMateriasSelectorService
--}}

@props([
    'name' => 'id_materia',
    'materias' => [],
    'selected' => null,
    'error' => null,
    'label' => 'Materia',
])

<div
    x-data="{
        materias: @js(array_values($materias)),
        idSeleccionada: @js($selected !== null ? (string) $selected : ''),
        texto: '',
        abierto: false,
        csrfToken: document.querySelector('meta[name=csrf-token]')?.getAttribute('content') || '',
        urlCrear: @js(route('api.materias.crear')),
        agregarAbierto: false,
        nuevoNombre: '',
        nuevoCodigo: '',
        errorAgregar: '',
        enviando: false,

        init() {
            const elegida = this.materias.find((m) => String(m.id) === this.idSeleccionada);
            if (elegida) { this.texto = this.display(elegida); }
        },
        normalizar(valor) {
            return (valor || '').toString().toLowerCase()
                .normalize('NFD').replace(/[̀-ͯ]/g, '');
        },
        display(m) {
            let t = m.nombre;
            if (m.codigo) { t += ' (' + m.codigo + ')'; }
            return t;
        },
        filtradas() {
            const q = this.normalizar(this.texto).trim();
            if (q === '' || this.idSeleccionada !== '') { return this.materias; }
            return this.materias.filter((m) =>
                this.normalizar(m.nombre).includes(q) || this.normalizar(m.codigo).includes(q));
        },
        sinCoincidencias() {
            return this.idSeleccionada === '' && this.texto.trim() !== '' && this.filtradas().length === 0;
        },
        alEscribir() {
            this.idSeleccionada = '';
            this.abierto = true;
            this.errorAgregar = '';
        },
        seleccionar(m) {
            this.idSeleccionada = String(m.id);
            this.texto = this.display(m);
            this.abierto = false;
            this.agregarAbierto = false;
        },
        abrirAgregar() {
            this.agregarAbierto = true;
            this.abierto = false;
            this.nuevoNombre = this.texto.trim();
            this.nuevoCodigo = '';
            this.errorAgregar = '';
        },
        async enviarAgregar() {
            if (this.enviando) { return; }
            this.enviando = true;
            this.errorAgregar = '';
            try {
                const respuesta = await fetch(this.urlCrear, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                    },
                    body: JSON.stringify({
                        nombre: this.nuevoNombre,
                        codigo: this.nuevoCodigo === '' ? null : this.nuevoCodigo,
                    }),
                });
                const cuerpo = await respuesta.json();
                if (! respuesta.ok) {
                    const errores = cuerpo.errors || {};
                    this.errorAgregar = (Object.values(errores)[0]?.[0]) || cuerpo.message || 'No se pudo agregar la materia.';
                    return;
                }
                this.materias.push(cuerpo.materia);
                this.seleccionar(cuerpo.materia);
            } catch (e) {
                this.errorAgregar = 'No se pudo agregar la materia. Intentalo de nuevo.';
            } finally {
                this.enviando = false;
            }
        },
    }"
    class="relative"
    @click.outside="abierto = false"
>
    <label for="buscador-materia" class="block mb-2.5 text-sm font-medium text-heading">
        {{ $label }} <span class="text-fg-danger-strong" aria-hidden="true">*</span>
    </label>

    <input type="hidden" name="{{ $name }}" x-bind:value="idSeleccionada" />

    <input
        type="text"
        id="buscador-materia"
        autocomplete="off"
        role="combobox"
        aria-expanded="false"
        x-bind:aria-expanded="abierto"
        placeholder="Busque por nombre o codigo"
        x-model="texto"
        @input="alEscribir()"
        @focus="abierto = true"
        @class([
            'block w-full px-3 py-2.5 text-sm rounded-base border shadow-xs placeholder:text-body',
            'bg-neutral-secondary-medium border-default-medium text-heading focus:ring-brand focus:border-brand' => ! $error,
            'bg-danger-soft border-danger-subtle text-fg-danger-strong focus:ring-danger focus:border-danger' => $error,
        ])
    />

    @if ($error)
        <p class="mt-1 text-sm text-fg-danger-strong">{{ $error }}</p>
    @endif

    {{-- Lista desplegable --}}
    <div x-show="abierto && ! agregarAbierto" x-cloak
         class="absolute z-20 mt-1 w-full max-h-64 overflow-y-auto rounded-base border border-default bg-neutral-primary-soft shadow-sm">
        <ul class="py-1">
            <template x-for="m in filtradas()" :key="m.id">
                <li>
                    <button type="button" @click="seleccionar(m)"
                        class="flex w-full items-center justify-between gap-2 px-3 py-2 text-left text-sm text-heading hover:bg-neutral-secondary-medium">
                        <span>
                            <span x-text="m.nombre" class="font-medium"></span>
                            <span x-show="m.codigo" x-text="'(' + m.codigo + ')'" class="text-body"></span>
                        </span>
                        <span x-show="m.delDocente" class="shrink-0 rounded-full bg-brand-softer px-2 py-0.5 text-xs font-medium text-fg-brand">tuya</span>
                    </button>
                </li>
            </template>

            <li x-show="sinCoincidencias()">
                <button type="button" @click="abrirAgregar()"
                    class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm font-medium text-fg-brand hover:bg-neutral-secondary-medium">
                    <span>+ Agregar materia “<span x-text="texto.trim()"></span>”</span>
                </button>
            </li>
        </ul>
    </div>

    {{-- Mini formulario para agregar una materia nueva --}}
    <div x-show="agregarAbierto" x-cloak
         class="mt-2 rounded-base border border-default bg-neutral-primary-soft p-3 shadow-xs space-y-3">
        <p class="text-sm font-medium text-heading">Agregar materia</p>

        <div>
            <label for="nueva-materia-nombre" class="block mb-1 text-xs font-medium text-body">Nombre *</label>
            <input id="nueva-materia-nombre" type="text" maxlength="120" x-model="nuevoNombre"
                class="block w-full px-3 py-2 text-sm rounded-base border bg-neutral-secondary-medium border-default-medium text-heading focus:ring-brand focus:border-brand" />
        </div>
        <div>
            <label for="nueva-materia-codigo" class="block mb-1 text-xs font-medium text-body">Codigo (opcional, 7 digitos)</label>
            <input id="nueva-materia-codigo" type="text" inputmode="numeric" maxlength="7" x-model="nuevoCodigo"
                @input="nuevoCodigo = nuevoCodigo.replace(/\D/g, '').slice(0, 7)"
                class="block w-full px-3 py-2 text-sm rounded-base border bg-neutral-secondary-medium border-default-medium text-heading focus:ring-brand focus:border-brand" />
        </div>

        <p x-show="errorAgregar" x-text="errorAgregar" x-cloak class="text-sm text-fg-danger-strong"></p>

        <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <x-ui.button type="button" variant="secondary" @click="agregarAbierto = false; abierto = true">Cancelar</x-ui.button>
            <x-ui.button type="button" variant="default" @click="enviarAgregar()" x-bind:disabled="enviando">
                <span x-text="enviando ? 'Agregando…' : 'Agregar y seleccionar'"></span>
            </x-ui.button>
        </div>
    </div>
</div>

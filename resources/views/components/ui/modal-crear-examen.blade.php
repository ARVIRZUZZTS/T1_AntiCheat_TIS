{{--
    @file    modal-crear-examen.blade.php
    @author  Alex Candia <alex.leonar.candia@gmail.com>
    @created 2026-10-05
    @updated 2026-10-10

    @description
    Componente UI Blade reutilizable para el modal de alta de un examen en una
    materia. Muestra el formulario (tipo, fecha, hora de inicio y
    duración) y sigue el mismo patrón dual de Alpine que modal-registro-ingreso:
    se abre y se cierra con los eventos de ventana `abrir-modal-crear-examen` y
    `cerrar-modal-crear-examen`, sin estado en el servidor.

    La fecha se escribe dd/mm/aaaa con los separadores colocados a mano (así el
    formato no depende del navegador) y la duración son solo dígitos en un
    `type="text"`, para que no aparezcan las flechitas del input number, que en
    móvil se comen medio campo. No hay hora de fin: el examen dura lo que diga
    la duración.

    El formulario tiene, además, el selector de ambientes: un **combobox** cuyo
    input es el buscador y cuya lista (el listbox) se abre al enfocarlo y se va
    filtrando con lo que se escribe. Con la búsqueda vacía están todos los
    ambientes del catálogo, que es lo que se necesita cuando son varios. Al
    tocar una opción se añade a la lista de elegidos, y cada elegido lleva una
    papelera para sacarlo.

    El desplegable va en el flujo y no flotante a propósito: el contenedor del
    modal es el que tiene `overflow-y-auto`, así que un listbox flotante
    (`absolute`) quedaría recortado contra el borde del modal.

    El material permitido y las normas tienen dos mitades cada uno: lo que sale
    del catálogo, en casillas de verificación todas desmarcadas, y lo que la
    persona escribe a mano, en un campo de texto con tope de 300 caracteres que
    impone el `maxlength`. Las casillas usan un `x-model` que es un array: cada
    una marcada empuja su id y la que se desmarca lo saca.

    Ninguno de los tres bloques lleva contador: la lista de elegidos y los
    propios campos ya dicen cuántos hay, y el contador ocupaba espacio sin
    sumar.

    El catálogo de ambientes lo arma App\Services\Ambiente\ListarAmbientesService
    y viaja en un `<script type="application/json">`, igual que el índice de
    estudiantes de livewire/monitoreo/buscador-registro.blade.php: se lee con
    `JSON.parse` sobre `textContent` en vez de incrustarse como atributo, para no
    pelearse con el escapado del HTML. El `termino` de cada ambiente ya viene en
    minúsculas desde el servicio, así que cada tecla solo compara. Los catálogos
    de materiales y de normas no pasan por ese script: los pinta Blade con
    `@foreach`, porque no se filtran en el navegador y no hay nada que buscar.

    El guardado ya está conectado: el formulario hace POST a
    `cursos.examenes.store` y `App\Services\Examen\RegistrarExamenService` escribe
    el examen, los ambientes elegidos en `examen_ambiente`, las normas y los
    materiales del catálogo en `examen_norma` y `examen_material_permitido`, y lo
    escrito a mano en `norma_personalizada` y `material_personalizado`.

    @props([
        'materia' => null,
        'tipos' => null,
        'ambientes' => [],
        'materiales' => [],
        'normas' => [],
        'cursoId' => null,
        'abrir' => false,
    ])

    @changelog
    - 2026-10-05  [Alex Candia]  feat: creación inicial del componente UI.
    - 2026-10-05  [Alex Candia]  refactor: la fecha pasa a texto dd/mm/aaaa con
      saneo en Alpine (el input date mostraba el formato del navegador), se quita
      la hora de fin —la duración ya la define— y la duración pasa a texto con
      solo dígitos, sin las flechitas del input number.
    - 2026-10-05  [Alex Candia]  feat: selector de ambientes con buscador
      dinámico, lista con scroll y papelera por cada ambiente elegido.
    - 2026-10-05  [Alex Candia]  refactor: el selector pasa a combobox: el input
      es el buscador y la lista es su listbox, que se abre al enfocarlo y se
      filtra mientras se escribe, con navegación por flechas y Enter para elegir.
    - 2026-10-05  [Alex Candia]  feat: casillas del catálogo de material
      permitido, todas desmarcadas, y campo de normas propias con tope de 300
      caracteres. Sin guardado todavía.
    - 2026-10-05  [Alex Candia]  refactor: se quitan los contadores de ambientes
      elegidos y de caracteres escritos en las normas.
    - 2026-10-05  [Alex Candia]  refactor: se saca también el contador de
      materiales marcados.
    - 2026-10-05  [Alex Candia]  feat: campo de materiales personalizados y
      casillas del catálogo de normas, como las de materiales pero a una columna
      porque cada norma es una regla entera. Sin guardado todavía.
    - 2026-10-10  [Valery D. Ortuno P]  feat: marca con asterisco los campos
      obligatorios (Materia, Tipo de examen, Fecha, Hora de inicio, Duración y
      Ambientes).
    - 2026-10-10  [Valery D. Ortuno P]  feat: la materia se muestra como un campo
      de solo lectura con el nombre, sin flechita; cuando un examen tenga varias
      materias se cambia por un selector.
    - 2026-10-10  [Valery D. Ortuno P]  fix: el buscador de ambientes cierra la
      lista al elegir una opcion, en vez de quedar abierta.
    - 2026-10-10  [Valery D. Ortuno P]  feat: los errores de validacion se
      muestran debajo de cada campo en vez de una lista arriba del formulario.
    - 2026-10-10  [Alex Candia]  refactor: la etiqueta pasa a "Fecha" (ya no
      "Fecha de inicio"), los materiales del catálogo van a una sola columna,
      los errores de materiales, normas y personalizados también se muestran, y
      al reabrir el modal se conserva lo escrito en vez de vaciarlo.
    - 2026-10-10  [Alex Candia]  refactor: los textos de ayuda de materiales y
      normas pasan a "Ingrese materiales/normas personalizadas para el examen".

    @see  resources/views/partials/materia-examenes.blade.php
    @see  App\Enums\TipoExamen
    @see  App\Services\Ambiente\ListarAmbientesService
    @see  App\Services\Material\ListarMaterialesService
    @see  App\Services\Norma\ListarNormasService
--}}

@php
    use App\Enums\TipoExamen;

    $opciones = $tipos ?? TipoExamen::opciones();

    // Old input: si la validación falla, el formulario vuelve a mostrarse con lo
    // que la persona ya había escrito. El estado de Alpine arranca con esos
    // valores porque todos los campos están atados con x-model: si se pusieran en
    // el HTML, Alpine los sobrescribiría con el estado al inicializar.
    $catalogoAmbientes = collect($ambientes)->keyBy('id');

    $ambientesElegidos = collect((array) old('ambientes', []))
        ->map(fn (mixed $id) => $catalogoAmbientes->get((int) $id))
        ->filter()
        ->values()
        ->all();

    $materialesElegidos = array_map('intval', (array) old('materiales', []));
    $normasElegidas = array_map('intval', (array) old('normas', []));
@endphp

<div
    x-data="{
        show: @js($abrir),
        tipo: @js(old('tipo_examen', '')),
        fecha: @js(old('fecha', '')),
        horaInicio: @js(old('hora_inicio', '')),
        duracion: @js(old('duracion', '')),
        busquedaAmbiente: '',
        indiceAmbientes: [],
        ambientesElegidos: @js($ambientesElegidos),
        listaAbierta: false,
        indiceResaltado: -1,
        materialesElegidos: @js($materialesElegidos),
        materialesPersonalizados: @js(old('materiales_personalizados', '')),
        normasElegidas: @js($normasElegidas),
        normasPersonalizadas: @js(old('normas_personalizadas', '')),

        // El catalogo de ambientes llega en el <script> de abajo. `termino` ya
        // viene en minusculas desde el servicio, asi que aca no se normaliza nada.
        init() {
            const ambientes = document.getElementById('indice-ambientes-examen');

            this.indiceAmbientes = ambientes ? JSON.parse(ambientes.textContent) : [];
        },

        // La fecha se escribe dd/mm/aaaa: se queda solo con digitos, coloca los
        // separadores solos y acota el dia a 31 y el mes a 12. El formato no
        // depende del navegador ni de la configuracion regional del dispositivo.
        sanearFecha() {
            const digitos = this.fecha.replace(/\D/g, '').slice(0, 8);

            if (digitos.length <= 2) {
                this.fecha = digitos;

                return;
            }

            const dia = this.acotar(digitos.slice(0, 2), 31);
            const mes = this.acotar(digitos.slice(2, 4), 12);
            const anio = digitos.slice(4);

            this.fecha = anio === '' ? dia + '/' + mes : dia + '/' + mes + '/' + anio;
        },

        // Limita un grupo de dos digitos a un maximo, solo cuando esta completo.
        acotar(grupo, maximo) {
            if (grupo.length < 2) {
                return grupo;
            }

            const numero = Math.min(Math.max(parseInt(grupo, 10) || 1, 1), maximo);

            return String(numero).padStart(2, '0');
        },

        // La duracion son minutos: solo digitos. Al ser type=text no aparecen
        // las flechitas del input number, que en movil ocupan medio campo.
        sanearDuracion() {
            this.duracion = this.duracion.replace(/\D/g, '');
        },

        ambientesVisibles() {
            const termino = this.busquedaAmbiente.trim().toLowerCase();
            const elegidos = this.ambientesElegidos.map((ambiente) => ambiente.id);

            return this.indiceAmbientes.filter((ambiente) =>
                !elegidos.includes(ambiente.id)
                && ambiente.termino.indexOf(termino) !== -1
            );
        },

        agregarAmbiente(id) {
            const ambiente = this.indiceAmbientes.find((item) => item.id === id);

            if (! ambiente || this.ambientesElegidos.some((item) => item.id === id)) {
                return;
            }

            this.ambientesElegidos = this.ambientesElegidos.concat(ambiente);
            this.busquedaAmbiente = '';
            this.indiceResaltado = -1;
            this.listaAbierta = false;
        },

        quitarAmbiente(id) {
            this.ambientesElegidos = this.ambientesElegidos.filter((ambiente) => ambiente.id !== id);
        },

        moverResaltado(paso) {
            const total = this.ambientesVisibles().length;

            if (total === 0) {
                this.indiceResaltado = -1;

                return;
            }

            this.listaAbierta = true;
            this.indiceResaltado = (this.indiceResaltado + paso + total) % total;
        },
        elegirResaltado() {
            const ambiente = this.ambientesVisibles()[this.indiceResaltado];

            if (! ambiente) {
                return;
            }

            this.agregarAmbiente(ambiente.id);
        }
    }"
    x-show="show"
    x-cloak
    tabindex="-1"
    aria-hidden="true"
    @click.self="$dispatch('cerrar-modal-crear-examen')"
    @keydown.escape.window="$dispatch('cerrar-modal-crear-examen')"
    @cerrar-modal-crear-examen.window="show = false"
    @abrir-modal-crear-examen.window="show = true; busquedaAmbiente = ''; listaAbierta = false; indiceResaltado = -1"
    class="overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 flex justify-center items-center w-full md:inset-0 h-full max-h-full bg-overlay-modal/50"
>
    <div class="relative p-4 w-full max-w-md max-h-full">
        <div class="relative bg-neutral-primary-soft border border-default rounded-base shadow-sm p-4 md:p-6">
            <div class="flex items-center justify-between border-b border-default pb-4 md:pb-5">
                <h3 class="text-lg font-medium text-heading">
                    Crear examen
                </h3>
                <button
                    type="button"
                    @click="$dispatch('cerrar-modal-crear-examen')"
                    class="text-body bg-transparent hover:bg-neutral-tertiary hover:text-heading rounded-base text-sm w-9 h-9 ms-auto inline-flex justify-center items-center cursor-pointer"
                >
                    <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6"/></svg>
                    <span class="sr-only">Cerrar modal</span>
                </button>
            </div>

            {{-- Formulario real: va con POST y @csrf, sin wire: ni fetch. El
                 <form> envuelve los campos y el pie de botones. Alpine se encarga
                 de la ergonomía del formulario y del combobox de ambientes, que sí
                 necesita JavaScript: sin él no se pueden elegir ambientes. --}}
            <form method="POST" action="{{ route('cursos.examenes.store', $cursoId) }}">
                @csrf

            <div class="space-y-4 py-4">
                {{-- La materia se muestra como un campo de solo lectura: si el
                     examen es de una sola materia va el nombre y listo, sin la
                     flechita de desplegable. Cuando un examen pueda tener varias
                     materias, aca va un selector con esas materias. --}}
                @if ($materia)
                    <x-ui.input
                        label="Materia"
                        id="materia_examen"
                        name="materia"
                        :value="$materia"
                        readonly
                        required
                        class="cursor-default"
                    />
                @endif

                <x-ui.select
                    label="Tipo de examen"
                    id="tipo_examen"
                    name="tipo_examen"
                    x-model="tipo"
                    :options="$opciones"
                    placeholder="Selecciona un tipo"
                    :error="$errors->first('tipo_examen')"
                    required
                />

                <x-ui.input
                    label="Fecha"
                    id="fecha_examen"
                    name="fecha"
                    type="text"
                    placeholder="dd/mm/aaaa"
                    inputmode="numeric"
                    maxlength="10"
                    x-model="fecha"
                    @input="sanearFecha()"
                    :error="$errors->first('fecha')"
                    required
                />

                <x-ui.input
                    label="Hora de inicio"
                    id="hora_inicio_examen"
                    name="hora_inicio"
                    type="time"
                    x-model="horaInicio"
                    :error="$errors->first('hora_inicio')"
                    required
                />

                <x-ui.input
                    label="Duración (minutos)"
                    id="duracion_examen"
                    name="duracion"
                    type="text"
                    inputmode="numeric"
                    x-model="duracion"
                    @input="sanearDuracion()"
                    :error="$errors->first('duracion')"
                    required
                />
                <div @click.outside="listaAbierta = false">
                    <label for="busqueda_ambiente" class="block mb-2.5 text-sm font-medium text-heading">
                        Ambientes <span class="text-fg-danger-strong" aria-hidden="true">*</span>
                    </label>

                    <input type="text" id="busqueda_ambiente"
                           role="combobox"
                           aria-autocomplete="list"
                           aria-controls="lista-ambientes"
                           :aria-expanded="listaAbierta"
                           autocomplete="off"
                           x-model="busquedaAmbiente"
                           @click="listaAbierta = true"
                           @input="listaAbierta = true; indiceResaltado = -1"
                           @keydown.down.prevent="moverResaltado(1)"
                           @keydown.up.prevent="moverResaltado(-1)"
                           @keydown.enter.prevent="elegirResaltado()"
                           @keydown.escape="listaAbierta = false"
                           placeholder="Buscar ambiente..."
                           @error('ambientes') aria-invalid="true" @enderror
                           class="block w-full p-3 border text-sm rounded-base focus:ring-brand focus:border-brand shadow-xs placeholder:text-body @error('ambientes') border-danger-subtle bg-danger-soft text-fg-danger-strong focus:ring-danger focus:border-danger @else border-default-medium bg-neutral-secondary-medium text-heading @enderror" />

                    <ul id="lista-ambientes" role="listbox" aria-label="Ambientes disponibles"
                        x-show="listaAbierta" x-cloak
                        class="mt-2 max-h-40 overflow-y-auto rounded-base border border-default divide-y divide-default">
                        <template x-for="(ambiente, posicion) in ambientesVisibles()" :key="ambiente.id">
                            <li>
                                <button type="button" role="option"
                                        :aria-selected="posicion === indiceResaltado"
                                        @click="agregarAmbiente(ambiente.id)"
                                        class="flex w-full items-center justify-between gap-2 px-3 py-2.5 text-start text-sm hover:bg-neutral-secondary-medium focus:outline-none focus:ring-2 focus:ring-brand-medium"
                                        :class="posicion === indiceResaltado ? 'bg-neutral-secondary-medium text-heading' : 'text-body'">
                                    <span class="truncate" x-text="ambiente.nombre"></span>
                                    <svg class="w-4 h-4 shrink-0 text-fg-brand" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14m7-7H5"/></svg>
                                </button>
                            </li>
                        </template>

                        <li x-show="ambientesVisibles().length === 0" class="px-3 py-2.5 text-sm text-body">
                            No hay ambientes que coincidan con la búsqueda.
                        </li>
                    </ul>

                    {{-- Elegidos: cada uno con su papelera para sacarlo. --}}
                    <ul class="mt-3 flex flex-col gap-2">
                        <template x-for="ambiente in ambientesElegidos" :key="ambiente.id">
                            <li class="flex items-center justify-between gap-2 px-3 py-2 rounded-base border border-default bg-neutral-secondary-soft">
                                <span class="truncate text-sm text-heading" x-text="ambiente.nombre"></span>
                                <button type="button" @click="quitarAmbiente(ambiente.id)"
                                        class="shrink-0 inline-flex items-center justify-center w-9 h-9 rounded-base text-body hover:text-fg-danger hover:bg-neutral-secondary-medium focus:outline-none focus:ring-2 focus:ring-danger-medium"
                                        :aria-label="'Quitar ' + ambiente.nombre">
                                    <svg class="w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0 1 16.138 21H7.862a2 2 0 0 1-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 0 0-1-1H10a1 1 0 0 0-1 1v3M4 7h16"/></svg>
                                    <span class="sr-only" x-text="'Quitar ' + ambiente.nombre"></span>
                                </button>
                            </li>
                        </template>
                    </ul>

                    {{-- Los ambientes elegidos viajan al servidor como campos
                         ocultos: la lista es de Alpine y no tiene inputs. --}}
                    <template x-for="ambiente in ambientesElegidos" :key="'envio_' + ambiente.id">
                        <input type="hidden" name="ambientes[]" :value="ambiente.id" />
                    </template>

                    @error('ambientes')
                        <p class="mt-1 text-sm text-fg-danger-strong">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Material permitido: casillas del catalogo, todas desmarcadas.
                     El catalogo lo resuelve ListarMaterialesService y se recorre con
                     Blade, no con x-for: los datos ya vienen del servidor y Alpine
                     solo guarda la seleccion. El `x-model` es un array, asi que la
                     casilla marcada empuja su id y la desmarcada lo saca.
                     Ojo: los atributos van SIN `:` a proposito, porque en un tag de
                     componente Blade un `:` delante del nombre hace que Blade
                     evalue el valor como PHP y no como expresion de Alpine. --}}
                <div>
                    <p class="block mb-2.5 text-sm font-medium text-heading" id="materiales_examen">
                        Material permitido
                    </p>

                    <ul class="flex flex-col gap-3"
                        aria-labelledby="materiales_examen">
                        @forelse ($materiales as $material)
                            <li>
                                <x-ui.checkbox
                                    id="material_{{ $material['id'] }}"
                                    name="materiales[]"
                                    label="{{ $material['descripcion'] }}"
                                    value="{{ $material['id'] }}"
                                    x-model="materialesElegidos"
                                    :checked="in_array((int) $material['id'], $materialesElegidos, true)"
                                />
                            </li>
                        @empty
                            <li class="text-sm text-body">Todavía no hay materiales cargados en el catálogo.</li>
                        @endforelse
                    </ul>

                    @error('materiales')
                        <p class="mt-1 text-sm text-fg-danger-strong">{{ $message }}</p>
                    @enderror
                    @error('materiales.*')
                        <p class="mt-1 text-sm text-fg-danger-strong">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Materiales que no estan en el catalogo: texto libre. El
                     maxlength del input es lo que corta a 300. --}}
                <x-ui.input
                    label="Materiales personalizados"
                    id="materiales_personalizados_examen"
                    name="materiales_personalizados"
                    type="text"
                    maxlength="300"
                    placeholder="Ingrese materiales personalizados para el examen"
                    x-model="materialesPersonalizados"
                    :error="$errors->first('materiales_personalizados')"
                />

                {{-- Normas del catalogo: mismas casillas desmarcadas que los
                     materiales, pero cada una es una regla entera y por eso el
                     texto puede ocupar el ancho completo. --}}
                <div>
                    <p class="block mb-2.5 text-sm font-medium text-heading" id="normas_examen">
                        Normas
                    </p>

                    <ul class="flex flex-col gap-3"
                        aria-labelledby="normas_examen">
                        @forelse ($normas as $norma)
                            <li>
                                <x-ui.checkbox
                                    id="norma_{{ $norma['id'] }}"
                                    name="normas[]"
                                    label="{{ $norma['detalle'] }}"
                                    value="{{ $norma['id'] }}"
                                    x-model="normasElegidas"
                                    :checked="in_array((int) $norma['id'], $normasElegidas, true)"
                                />
                            </li>
                        @empty
                            <li class="text-sm text-body">Todavía no hay normas cargadas en el catálogo.</li>
                        @endforelse
                    </ul>

                    @error('normas')
                        <p class="mt-1 text-sm text-fg-danger-strong">{{ $message }}</p>
                    @enderror
                    @error('normas.*')
                        <p class="mt-1 text-sm text-fg-danger-strong">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Normas propias del examen. El maxlength del textarea es lo
                     que corta a 300. --}}
                <x-ui.textarea
                    label="Normas personalizadas"
                    id="normas_personalizadas_examen"
                    name="normas_personalizadas"
                    rows="3"
                    maxlength="300"
                    placeholder="Ingrese normas personalizadas para el examen"
                    x-model="normasPersonalizadas"
                    :error="$errors->first('normas_personalizadas')"
                />
            </div>

            {{-- Mobile: los botones se apilan a lo ancho (stretch del column
                 flex) y el de guardar queda arriba; desde sm: van en fila. --}}
            <div class="border-t border-default pt-4 flex flex-col-reverse gap-[2vh] sm:flex-row">
                <x-ui.button-cancelar class="flex-1" @click="$dispatch('cerrar-modal-crear-examen')">Cancelar</x-ui.button-cancelar>
                <x-ui.button type="submit" variant="default" class="flex-1">Guardar examen</x-ui.button>
            </div>
            </form>
        </div>
    </div>

    {{-- Indice de ambientes para el buscador del modal. Va como application/json
         y se lee con JSON.parse sobre textContent (no como atributo) por el mismo
         motivo que en el buscador de registro: el escapado del HTML no se mete en
         medio. Los materiales no viajan por aqui: los pinta Blade directo. --}}
    <script type="application/json" id="indice-ambientes-examen">@json($ambientes)</script>
</div>

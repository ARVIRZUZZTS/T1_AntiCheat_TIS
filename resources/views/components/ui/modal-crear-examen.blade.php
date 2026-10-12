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
    - 2026-10-10  [Alex Candia]  fix: el estado de error pasa a ser de Alpine.
      Blade escribía los errores en el HTML y viven toda la carga de la página,
      así que al cerrar el modal y volverlo a abrir los campos seguían pintados
      de rojo aunque el error ya era viejo. Ahora el rojo se apaga al cerrar el
      modal y lo escrito se conserva, como ya pasaba con los valores.
    - 2026-10-10  [Alex Candia]  fix: los errores de un item (`materiales.0`)
      también se muestran. El comodín de la directiva de error de Blade no los
      encontraba, porque la clave real trae el índice del item.
    - 2026-10-10  [Alex Candia]  feat: la fecha completa sola el año en curso, la
      hora pasa a texto en formato 24 horas acotado entre 06:45 y 20:15 (la hora
      nunca pasa de 20 y, en el piso y en el tope, los minutos tampoco), y la
      duración solo admite dígitos y tres cifras.
    - 2026-10-10  [Alex Candia]  fix: la fecha acepta el día de un solo dígito.
      Al teclear "9/12" sobraba el dígito y se leía como día 91, que se acotaba a
      31; ahora se arma 09/12.

    @see  resources/views/partials/materia-examenes.blade.php
    @see  App\Enums\TipoExamen
    @see  App\Services\Ambiente\ListarAmbientesService
    @see  App\Services\Material\ListarMaterialesService
    @see  App\Services\Norma\ListarNormasService
--}}

@php
    use App\Enums\TipoExamen;
    use App\Services\Examen\RegistrarExamenService;

    $opciones = $tipos ?? TipoExamen::opciones();

    $catalogoAmbientes = collect($ambientes)->keyBy('id');

    $ambientesElegidos = collect((array) old('ambientes', []))
        ->map(fn (mixed $id) => $catalogoAmbientes->get((int) $id))
        ->filter()
        ->values()
        ->all();

    $materialesElegidos = array_map('intval', (array) old('materiales', []));
    $normasElegidas = array_map('intval', (array) old('normas', []));

    $mensajes = collect($errors->messages())
        ->map(fn (array $lista): string => (string) ($lista[0] ?? ''))
        ->filter()
        ->all();

    $claseError = 'bg-danger-soft! border-danger-subtle! text-fg-danger-strong! focus:ring-danger! focus:border-danger!';

    $horaMinima = RegistrarExamenService::HORA_INICIO_MINIMA;
    $horaMaxima = RegistrarExamenService::HORA_INICIO_MAXIMA;

    // El año lo pone el campo solo, no la persona: es el año en curso.
    $anioActual = (string) now()->year;
@endphp

<div
    x-data="{
        show: @js($abrir),
        tipo: @js(old('tipo_examen', '')),
        fecha: @js(old('fecha', '')),
        horaInicio: @js(old('hora_inicio', '')),
        horaMinima: @js($horaMinima),
        horaMaxima: @js($horaMaxima),
        anioActual: @js($anioActual),
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
        errores: @js($mensajes),
        mostrarErrores: @js($mensajes !== []),

        // El catalogo de ambientes llega en el bloque de script de abajo. `termino` ya
        // viene en minusculas desde el servicio, asi que aca no se normaliza nada.
        init() {
            const ambientes = document.getElementById('indice-ambientes-examen');

            this.indiceAmbientes = ambientes ? JSON.parse(ambientes.textContent) : [];
        },

        // Mensaje de error de un campo, o cadena vacia si no hay. Se mira tambien
        // dentro de los items: `materiales.0` cuenta para `materiales`. La
        // directiva de error de Blade con comodin no las encontraba, porque la
        // clave real trae el indice del item.
        errorDe(campo) {
            if (! this.mostrarErrores) {
                return '';
            }

            if (this.errores[campo]) {
                return this.errores[campo];
            }

            const conIndice = Object.keys(this.errores)
                .find((clave) => clave.startsWith(campo + '.'));

            return conIndice ? this.errores[conIndice] : '';
        },

        // Clases de error del campo, o nada si no hay.
        claseErrorDe(campo) {
            return this.errorDe(campo) ? '{{ $claseError }}' : '';
        },
        sanearFecha() {
            const digitos = this.fecha.replace(/\D/g, '');
            const diaDeDos = parseInt(digitos.slice(0, 2), 10);
            const diaDeUno = parseInt(digitos.slice(0, 1), 10);
            const diaDeUnDigito = digitos.length >= 3
                && (diaDeDos === 0 || diaDeDos > 31)
                && diaDeUno >= 1
                && diaDeUno <= 9;

            const diaCrudo = digitos.slice(0, diaDeUnDigito ? 1 : 2);
            const mesCrudo = digitos.slice(diaDeUnDigito ? 1 : 2, diaDeUnDigito ? 3 : 4);

            if (mesCrudo === '') {
                this.fecha = diaDeUnDigito ? diaCrudo : digitos;

                return;
            }

            const dia = String(Math.min(Math.max(parseInt(diaCrudo, 10) || 1, 1), 31)).padStart(2, '0');
            const mes = this.acotar(mesCrudo, 12);

            this.fecha = dia + '/' + mes + '/' + this.anioActual;
        },

        sanearHora() {
            const digitos = this.horaInicio.replace(/\D/g, '').slice(0, 4);

            if (digitos.length < 2) {
                this.horaInicio = digitos;

                return;
            }

            const [horaMinima, minutoMinimo] = this.horaMinima.split(':');
            const [horaMaxima, minutoMaximo] = this.horaMaxima.split(':');
            // La hora se acota apenas se escriben sus dos digitos, no al
            // tercero: escribir 21 tiene que volverse 20 en el momento.
            const hora = this.acotar(digitos.slice(0, 2), parseInt(horaMaxima, 10), parseInt(horaMinima, 10));

            if (digitos.length === 2) {
                this.horaInicio = hora;

                return;
            }

            let minimo = 1;
            let maximo = 59;

            if (hora === horaMinima) {
                minimo = parseInt(minutoMinimo, 10);
            }

            if (hora === horaMaxima) {
                maximo = parseInt(minutoMaximo, 10);
            }

            this.horaInicio = hora + ':' + this.acotar(digitos.slice(2, 4), maximo, minimo);
        },

        // Limita un grupo de dos digitos al rango [minimo, maximo], y solo
        // cuando ya esta completo: mientras se escribe, el grupo queda como esta.
        acotar(grupo, maximo, minimo = 1) {
            if (grupo.length < 2) {
                return grupo;
            }

            const numero = Math.min(Math.max(parseInt(grupo, 10) || minimo, minimo), maximo);

            return String(numero).padStart(2, '0');
        },

        sanearDuracion(campo) {
            const digitos = this.duracion.replace(/\D/g, '');

            this.duracion = digitos;

            if (campo && campo.value !== digitos) {
                campo.value = digitos;
            }
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
    @cerrar-modal-crear-examen.window="show = false; mostrarErrores = false"
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

            <form method="POST" action="{{ route('cursos.examenes.store', $cursoId) }}">
                @csrf

            <div class="space-y-4 py-4">
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
                    x-bind:class="claseErrorDe('tipo_examen')"
                    x-bind:aria-invalid="errorDe('tipo_examen') ? 'true' : null"
                    required
                />
                <p x-show="errorDe('tipo_examen')" x-text="errorDe('tipo_examen')"
                   class="mt-1 text-sm text-fg-danger-strong"></p>

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
                    x-bind:class="claseErrorDe('fecha')"
                    x-bind:aria-invalid="errorDe('fecha') ? 'true' : null"
                    required
                />
                <p x-show="errorDe('fecha')" x-text="errorDe('fecha')"
                   class="mt-1 text-sm text-fg-danger-strong"></p>

                <x-ui.input
                    label="Hora de inicio"
                    id="hora_inicio_examen"
                    name="hora_inicio"
                    type="text"
                    inputmode="numeric"
                    maxlength="5"
                    placeholder="06:45"
                    x-model="horaInicio"
                    @input="sanearHora()"
                    x-bind:class="claseErrorDe('hora_inicio')"
                    x-bind:aria-invalid="errorDe('hora_inicio') ? 'true' : null"
                    required
                />
                <p x-show="errorDe('hora_inicio')" x-text="errorDe('hora_inicio')"
                   class="mt-1 text-sm text-fg-danger-strong"></p>

                <x-ui.input
                    label="Duración (minutos)"
                    id="duracion_examen"
                    name="duracion"
                    type="text"
                    inputmode="numeric"
                    pattern="[0-9]*"
                    maxlength="3"
                    x-model="duracion"
                    @input="sanearDuracion($event.target)"
                    x-bind:class="claseErrorDe('duracion')"
                    x-bind:aria-invalid="errorDe('duracion') ? 'true' : null"
                    required
                />
                <p x-show="errorDe('duracion')" x-text="errorDe('duracion')"
                   class="mt-1 text-sm text-fg-danger-strong"></p>
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
                           x-bind:class="claseErrorDe('ambientes')"
                           x-bind:aria-invalid="errorDe('ambientes') ? 'true' : null"
                           class="block w-full p-3 border border-default-medium bg-neutral-secondary-medium text-heading text-sm rounded-base focus:ring-brand focus:border-brand shadow-xs placeholder:text-body" />

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

                    <p x-show="errorDe('ambientes')" x-text="errorDe('ambientes')"
                       class="mt-1 text-sm text-fg-danger-strong"></p>
                </div>

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

                    <p x-show="errorDe('materiales')" x-text="errorDe('materiales')"
                       class="mt-1 text-sm text-fg-danger-strong"></p>
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
                    x-bind:class="claseErrorDe('materiales_personalizados')"
                    x-bind:aria-invalid="errorDe('materiales_personalizados') ? 'true' : null"
                />
                <p x-show="errorDe('materiales_personalizados')" x-text="errorDe('materiales_personalizados')"
                   class="mt-1 text-sm text-fg-danger-strong"></p>

     
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

                    <p x-show="errorDe('normas')" x-text="errorDe('normas')"
                       class="mt-1 text-sm text-fg-danger-strong"></p>
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
                    x-bind:class="claseErrorDe('normas_personalizadas')"
                    x-bind:aria-invalid="errorDe('normas_personalizadas') ? 'true' : null"
                />
                <p x-show="errorDe('normas_personalizadas')" x-text="errorDe('normas_personalizadas')"
                   class="mt-1 text-sm text-fg-danger-strong"></p>
            </div>
            <div class="border-t border-default pt-4 flex flex-col-reverse gap-[2vh] sm:flex-row">
                <x-ui.button-cancelar class="flex-1" @click="$dispatch('cerrar-modal-crear-examen')">Cancelar</x-ui.button-cancelar>
                <x-ui.button type="submit" variant="default" class="flex-1">Guardar examen</x-ui.button>
            </div>
            </form>
        </div>
    </div>

    <script type="application/json" id="indice-ambientes-examen">@json($ambientes)</script>
</div>

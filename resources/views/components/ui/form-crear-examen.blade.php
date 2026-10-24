{{--
    @file    form-crear-examen.blade.php
    @author  Alex Candia <alex.leonar.candia@gmail.com>
    @created 2026-10-05
    @updated 2026-10-10
    @updated 2026-10-12
    @updated 2026-10-24

    @description
    Componente UI Blade reutilizable con el formulario de alta de un examen en
    un curso (tipo, fecha, hora de inicio, duración, ambientes, materiales y
    normas). Lo incrusta la página pages/examen-crear; ya no es un modal, así
    que no se abre ni se cierra: "Cancelar" vuelve a `cancelarUrl`.

    La fecha se escribe dd/mm/aaaa con los separadores colocados a mano (así el
    formato no depende del navegador); las barras se ponen solas y el año en
    curso se completa al terminar el mes, aunque se puede cambiar. La duración son solo dígitos en un
    `type="text"`, para que no aparezcan las flechitas del input number, que en
    móvil se comen medio campo. No hay hora de fin: el examen dura lo que diga
    la duración.

    El formulario tiene, además, el selector de ambientes: un **combobox** cuyo
    input es el buscador y cuya lista (el listbox) se abre al enfocarlo y se va
    filtrando con lo que se escribe. Con la búsqueda vacía están todos los
    ambientes del catálogo, que es lo que se necesita cuando son varios. Al
    tocar una opción se añade a la lista de elegidos, y cada elegido lleva una
    papelera para sacarlo.

    El desplegable va en el flujo y no flotante a propósito: así la lista
    empuja el resto del formulario hacia abajo en vez de taparlo.

    El material permitido y las normas tienen dos mitades cada uno: lo que sale
    del catálogo, en casillas de verificación todas desmarcadas, y lo que la
    persona escribe a mano, en un textarea (un ítem por renglón) con tope de
    300 caracteres que impone el `maxlength`. Las casillas usan un `x-model` que es un array: cada
    una marcada empuja su id y la que se desmarca lo saca.

    Ninguno de los tres bloques lleva contador: la lista de elegidos y los
    propios campos ya dicen cuántos hay, y el contador ocupaba espacio sin
    sumar.

    El catálogo de ambientes lo arma App\Services\Ambiente\ListarAmbientesService
    y viaja en un `<script type="application/json">`, igual que el índice de
    estudiantes de livewire/monitoreo/registrar-incidencia.blade.php: se lee con
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
        'cancelarUrl' => null,
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
    - 2026-10-12  [T1]  docs: el ejemplo del índice JSON apunta a
      registrar-incidencia.blade.php (el índice de estudiantes ya no vive en
      buscador-registro.blade.php).
    - 2026-10-24  [Valery D. Ortuno P]  refactor: el componente pasa de
      modal-crear-examen.blade.php a form-crear-examen.blade.php y deja de ser
      un modal: se quitan el fondo, la X y los eventos de abrir/cerrar, para que
      la página pages/examen-crear lo muestre como formulario propio. "Cancelar"
      vuelve a `cancelarUrl` (prop nueva, reemplaza a `abrir`). Los campos, sus
      validaciones y el envío no cambian.
    - 2026-10-24  [Valery D. Ortuno P]  feat: la fecha se lee por tramos
      dia/mes/año: el año en curso se completa al terminar el mes y ahora se
      puede cambiar, borrar ya no repone barras, y el dia o mes de un digito se
      completa con cero al salir del campo. Placeholders: dd/mm/aaaa, 00:00 en
      la hora, 00 en la duración y "Buscar ambiente" sin puntos suspensivos.
    - 2026-10-24  [Valery D. Ortuno P]  fix: "Cancelar" vuelve a la página
      anterior; la URL iba con la directiva js dentro del atributo del
      componente, que Blade no compila, y el clic daba error.
    - 2026-10-24  [Valery D. Ortuno P]  fix: la hora de inicio acepta minutos
      00 (08:00 quedaba en 08:01, porque el piso de los minutos era 1).
    - 2026-10-24  [Valery D. Ortuno P]  feat: "Materiales personalizados" pasa a
      textarea de 3 renglones, igual que "Normas personalizadas"; la duración
      corta a 3 digitos también al pegar, y los textos visibles van sin acentos.

    @see  resources/views/pages/examen-crear.blade.php
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

    // El año en curso lo completa el campo solo al terminar el mes; la
    // persona puede cambiarlo si el examen es de otro año.
    $anioActual = (string) now()->year;

    $urlCancelar = $cancelarUrl ?? url()->previous();
@endphp

<div
    x-data="{
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
        // La fecha se lee por tramos (dia/mes/año). Las barras se ponen solas
        // al completar el dia y el mes, y el año en curso se escribe solo al
        // terminar el mes; despues se puede cambiar. Al borrar no se reponen
        // barras ni año, para que la tecla de borrar no quede trabada.
        sanearFecha(evento) {
            const borrando = String(evento?.inputType ?? '').startsWith('delete');
            const partes = this.fecha.split('/');
            let dia;
            let mes;
            let anio;

            if (partes.length >= 3) {
                dia = partes[0].replace(/\D/g, '');
                mes = partes[1].replace(/\D/g, '');
                anio = partes.slice(2).join('').replace(/\D/g, '').slice(0, 4);
            } else {
                // Todavia no hay segunda barra: se lee como ddmm corrido.
                const digitos = this.fecha.replace(/\D/g, '');
                dia = digitos.slice(0, 2);
                mes = digitos.slice(2, 4);
                anio = digitos.slice(4, 8);
            }

            // Lo que sobra del dia pasa al mes (se siguio escribiendo en el tramo).
            if (dia.length > 2) {
                mes = dia.slice(2) + mes;
                dia = dia.slice(0, 2);
            }

            // Al teclear '9' y luego '1' no se quiere el dia 91: es 09 y el
            // mes empieza con 1. Lo mismo un primer digito que ya no admite
            // otro (4 a 9 en el dia, 2 a 9 en el mes).
            if (dia.length === 2 && dia[0] !== '0' && parseInt(dia, 10) > 31) {
                mes = dia.slice(1) + mes;
                dia = '0' + dia[0];
            }

            if (dia.length === 1 && parseInt(dia, 10) > 3) {
                dia = '0' + dia;
            }

            mes = mes.slice(0, 2);

            if (mes.length === 1 && parseInt(mes, 10) > 1) {
                mes = '0' + mes;
            }

            dia = this.acotar(dia, 31);
            mes = this.acotar(mes, 12);

            // El año en curso entra solo cuando se acaba de completar el mes
            // (aun no habia segunda barra); si la persona borra el año para
            // escribir otro, no se le vuelve a poner.
            if (! borrando && mes.length === 2 && anio === '' && partes.length < 3) {
                anio = this.anioActual;
            }

            let texto = dia;

            if (mes !== '' || (dia.length === 2 && ! borrando) || partes.length >= 2) {
                texto += '/' + mes;
            }

            if (anio !== '' || (mes.length === 2 && ! borrando) || partes.length >= 3) {
                texto += '/' + anio;
            }

            this.fecha = texto;
        },

        // Al salir del campo, un dia o un mes de un solo digito se completa
        // con cero (1/3 -> 01/03), que es el formato que valida el servidor,
        // y si quedo sin año se pone el año en curso.
        completarFecha() {
            if (this.fecha.replace(/\D/g, '') === '') {
                this.fecha = '';

                return;
            }

            const [dia = '', mes = '', anio = ''] = this.fecha.split('/');
            const conCero = (tramo) => tramo.length === 1 && tramo !== '0' ? '0' + tramo : tramo;

            // Sin mes no hay nada que completar: se deja como esta y la
            // validacion avisa que la fecha no es valida.
            if (mes === '') {
                return;
            }

            this.fecha = conCero(dia) + '/' + conCero(mes) + '/' + (anio !== '' ? anio : this.anioActual);
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

            // Los minutos van de 00 a 59: con piso 1 era imposible poner una
            // hora en punto (08:00 quedaba en 08:01).
            let minimo = 0;
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
            // Tres digitos como tope de escritura (tambien al pegar); el maximo
            // de 300 minutos lo avisa la validacion al guardar.
            const digitos = this.duracion.replace(/\D/g, '').slice(0, 3);

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
    class="w-full"
>
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
            x-model="fecha"
            @input="sanearFecha($event)"
            @blur="completarFecha()"
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
            placeholder="00:00"
            x-model="horaInicio"
            @input="sanearHora()"
            x-bind:class="claseErrorDe('hora_inicio')"
            x-bind:aria-invalid="errorDe('hora_inicio') ? 'true' : null"
            required
        />
        <p x-show="errorDe('hora_inicio')" x-text="errorDe('hora_inicio')"
           class="mt-1 text-sm text-fg-danger-strong"></p>

        <x-ui.input
            label="Duracion (minutos)"
            id="duracion_examen"
            name="duracion"
            type="text"
            inputmode="numeric"
            pattern="[0-9]*"
            maxlength="3"
            placeholder="00"
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
                   placeholder="Buscar ambiente"
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
                    No hay ambientes que coincidan con la busqueda.
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
                    <li class="text-sm text-body">Todavia no hay materiales cargados en el catalogo.</li>
                @endforelse
            </ul>

            <p x-show="errorDe('materiales')" x-text="errorDe('materiales')"
               class="mt-1 text-sm text-fg-danger-strong"></p>
        </div>

        {{-- Materiales que no estan en el catalogo: texto libre, igual que las
             normas personalizadas (un material por renglon). El maxlength del
             textarea es lo que corta a 300. --}}
        <x-ui.textarea
            label="Materiales personalizados"
            id="materiales_personalizados_examen"
            name="materiales_personalizados"
            rows="3"
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
                    <li class="text-sm text-body">Todavia no hay normas cargadas en el catalogo.</li>
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
        {{-- {{ }} y no la directiva js: dentro del atributo de un componente
             Blade las directivas no se compilan y llegaban tal cual al
             navegador, así que el clic fallaba y no volvía. --}}
        <x-ui.button-cancelar class="flex-1" @click="window.location.href = {{ \Illuminate\Support\Js::from($urlCancelar) }}">Cancelar</x-ui.button-cancelar>
        <x-ui.button type="submit" variant="default" class="flex-1">Guardar examen</x-ui.button>
    </div>
    </form>

    <script type="application/json" id="indice-ambientes-examen">@json($ambientes)</script>
</div>

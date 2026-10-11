{{--
    @file    buscador-registro.blade.php
    @author  Alex Candia <alex.leonar.candia@gmail.com>
    @created 2026-10-01
    @updated 2026-10-01

    @description
    Vista del buscador de registro: un único campo de búsqueda que filtra en el
    navegador los estudiantes del examen en curso, ya resueltos por
    GenerarReporteAsistenciaService. Cada coincidencia muestra solo el código SIS
    y el nombre, y su enlace abre el formulario de incidencia existente con el
    estudiante precargado.

    El filtrado es JavaScript plano, sin Alpine ni wire:model. Es deliberado.
    El filtrado en el servidor obligaba a un viaje completo por tecla (petición +
    render + diff del DOM) y el buscador respondía con cientos de milisegundos,
    cuando la respuesta tiene que ser inmediata. La variante con Alpine tampoco
    era válida aquí: este proyecto solo carga el bundle de Livewire, que no
    expone un binding de `x-model` para el input que emite `x-ui.search-input`, así
    que el texto nunca llegaba al estado y las coincidencias no se pintaban.

    Los `<script>` van DENTRO del div raíz porque un componente Livewire exige un
    único elemento raíz: al dejarlos como hermanos, el componente no renderizaba
    y el script no llegaba a ejecutarse, con lo que no aparecía ningún resultado.

    El índice viaja en un `<script type="application/json">` y se lee con
    `JSON.parse` sobre `textContent`: no tiene que pelearse con el escapado del
    HTML, a diferencia de incrustarlo como atributo. Las filas se arman con
    `createElement` y `textContent`, así que ningún dato del estudiante pasa por
    `innerHTML`.

    El título lo pone `layouts.app` desde la sección `title`; como el componente
    delega el layout con `->extends('layouts.app')`, se declara aquí y no con el
    atributo `#[Title]`, que solo aplica cuando Livewire administra el layout.

    @see  \App\Livewire\Monitoreo\BuscadorRegistro
    @see  \App\Livewire\Monitoreo\RegistrarIncidencia

    @changelog
    - 2026-10-01  [Alex Candia]  feat: creación inicial de la vista.
    - 2026-10-01  [Alex Candia]  fix: se declara `@section('title')`, porque el
      atributo `#[Title]` del componente no llegaba al layout y el encabezado
      seguía mostrando el nombre por defecto de la aplicación.
    - 2026-10-01  [Alex Candia]  perf: el filtrado deja de ir al servidor con
      `wire:model.live`, que hacía un viaje completo por tecla, y se resuelve en
      el navegador sobre el índice ya calculado.
    - 2026-10-01  [Alex Candia]  fix: se quita el `<form>` que envolvía a
      `x-ui.search-input`. Ese componente ya emite su propio `<form>` y anidarlos
      es HTML inválido: el parser descartaba el externo y Enter recargaba la
      página dejando el buscador en blanco.
    - 2026-10-01  [Alex Candia]  fix: el filtrado pasa de Alpine a JavaScript plano,
      porque `x-model` no enlazaba el input en este proyecto.
    - 2026-10-01  [Alex Candia]  fix: los `<script>` se mueven dentro del div raíz.
      Como hermanos del elemento raíz violaban el requisito de un único elemento
      raíz de Livewire, el componente no renderizaba y el script nunca llegaba a
      ejecutarse.
--}}

@section('title', 'Registro ingreso')

@php
    // El enlace al formulario se arma una sola vez aquí y el script solo lo
    // reutiliza por coincidencia, en vez de recomponer la URL en cada fila.
    $urlIncidencia = route('buscador-incidencia', [
        'origen' => \App\Livewire\Monitoreo\RegistrarIncidencia::ORIGEN_MONITOREO,
        'materia' => '',
        'examen' => $examenId,
        'rol' => \App\Models\Rol::NOMBRE_DOCENTE,
        'usuario' => auth()->id() ?? \App\Livewire\Monitoreo\RegistrarIncidencia::USUARIO_POR_DEFECTO,
    ]);
@endphp

<div class="flex flex-col gap-6 px-[2vh] mt-[2vh]">
    {{-- Enlace de vuelta con el mismo patrón que usa registrar-incidencia: `nav`
         y clases cortas en vez de un botón completo, porque aquí no hay una
         acción principal a la que competir. --}}
    <nav aria-label="Ruta de navegación">
        <a href="{{ route('monitoreo') }}"
           class="inline-flex items-center gap-1.5 text-sm font-medium text-fg-brand hover:underline">
            <svg class="w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 19-7-7 7-7"/></svg>
            Volver al monitor en vivo
        </a>
    </nav>

    <section class="bg-neutral-primary-soft border border-default rounded-base shadow-xs p-6 sm:p-8">
        <div class="w-full sm:max-w-md">
            <x-ui.search-input
                name="busqueda"
                id="busqueda"
                placeholder="Buscar por codigo SIS o nombre..."
                :show-button="false"
            />
        </div>

        <p class="mt-2 text-xs text-body">
            Los resultados aparecen automaticamente mientras escribe.
        </p>
    </section>

    <section id="buscador-resultados"
             class="bg-neutral-primary-soft border border-default rounded-base shadow-xs p-6 sm:p-8"
             hidden>
        <ul id="buscador-lista" class="flex flex-col"></ul>
    </section>

    {{-- Prototipo de la fila. Se declara en Blade para que el botón sea el
         `x-ui.button` del design system y el JS solo clone, rellene y fije el
         enlace: las clases del diseño viven en un solo sitio y no se duplican
         dentro del script. El texto entra por `textContent`, nunca por
         `innerHTML`, así que ningún dato del estudiante se interpreta como HTML. --}}
    <template id="plantilla-fila">
        <li class="flex flex-wrap items-center justify-between gap-3 border-b border-default py-4">
            <div>
                <p class="text-sm font-semibold text-heading"></p>
                <p class="text-sm text-body"></p>
            </div>

            <x-ui.button href="#" size="sm">Registrar incidencia</x-ui.button>
        </li>
    </template>

    {{-- Prototipo del aviso sin coincidencias, por la misma razón que la fila. --}}
    <template id="plantilla-vacio">
        <li class="py-4 text-center text-sm text-body"></li>
    </template>

    <script type="application/json" id="buscador-indice">@json($indice)</script>

    <script>
        (function () {
            'use strict';

            // El índice se lee del propio DOM con JSON.parse para no depender de
            // que Blade escape un JSON dentro de un atributo HTML.
            var indice = JSON.parse(document.getElementById('buscador-indice').textContent);
            var urlBase = @json($urlIncidencia);
            var minimo = {{ \App\Livewire\Monitoreo\BuscadorRegistro::BUSQUEDA_MINIMO }};
            var limite = {{ \App\Livewire\Monitoreo\BuscadorRegistro::BUSQUEDA_LIMITE }};

            var input = document.getElementById('busqueda');
            var resultados = document.getElementById('buscador-resultados');
            var lista = document.getElementById('buscador-lista');

            // El texto de búsqueda ya viene en minúsculas en el campo `termino`,
            // normalizado una sola vez al construir el índice; así cada tecla solo
            // compara, sin componer cadenas ni aplicar toLowerCase por estudiante.
            function coincidencias(termino) {
                var encontradas = [];

                for (var i = 0; i < indice.length; i++) {
                    if (indice[i].termino.indexOf(termino) !== -1) {
                        encontradas.push(indice[i]);

                        if (encontradas.length >= limite) {
                            break;
                        }
                    }
                }

                return encontradas;
            }

            // La fila se clona del prototipo declarado en Blade, de modo que el
            // botón es el `x-ui.button` del design system y aquí no hay ni una
            // clase de estilo duplicada.
            function fila(estudiante) {
                var item = document.getElementById('plantilla-fila').content
                    .firstElementChild
                    .cloneNode(true);

                var textos = item.querySelectorAll('p');
                textos[0].textContent = estudiante.sis;
                textos[1].textContent = estudiante.nombre;

                // El formulario de incidencia ya existe y lee el estudiante de la
                // URL, así que la fila es un enlace normal y no necesita ida y vuelta.
                item.querySelector('a').href = urlBase
                    + '&sis=' + encodeURIComponent(estudiante.sis)
                    + '&nombre=' + encodeURIComponent(estudiante.nombre);

                return item;
            }

            // El aviso de "sin coincidencias" también se clona desde Blade, por el mismo
            // motivo que la fila: ninguna clase de estilo vive dentro del script.
            function mensaje(texto) {
                var item = document.getElementById('plantilla-vacio').content
                    .firstElementChild
                    .cloneNode(true);

                item.textContent = texto;

                return item;
            }

            function buscar() {
                var escrito = input.value.trim();
                var termino = escrito.toLowerCase();
                lista.innerHTML = '';

                if (termino.length < minimo) {
                    resultados.hidden = true;

                    return;
                }

                var encontradas = coincidencias(termino);

                resultados.hidden = false;

                if (encontradas.length === 0) {
                    lista.appendChild(mensaje('No se encontraron coincidencias para "' + escrito + '"'));

                    return;
                }

                for (var i = 0; i < encontradas.length; i++) {
                    lista.appendChild(fila(encontradas[i]));
                }
            }

            input.addEventListener('input', buscar);

            // El form que emite x-ui.search-input recargaría la página al pulsar
            // Enter y dejaría el buscador vacío; se cancela el envío.
            input.addEventListener('keydown', function (evento) {
                if (evento.key === 'Enter') {
                    evento.preventDefault();
                }
            });
        })();
    </script>
</div>
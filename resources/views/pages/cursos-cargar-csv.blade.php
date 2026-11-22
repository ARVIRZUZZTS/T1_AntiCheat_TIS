@extends('layouts.app')

@section('title', 'Cargar lista de estudiantes')

@section('content')
    @if (! $curso)
        <div class="px-4 py-4 sm:px-6 sm:py-6 lg:px-8">
            <div class="mx-auto max-w-2xl rounded-base border border-default bg-neutral-primary-soft p-6 shadow-xs">
                <h2 class="text-lg font-semibold text-heading mb-4">Selecciona un curso</h2>

                @if ($cursos->isEmpty())
                    <p class="text-sm text-body">No tienes cursos registrados.</p>
                @else
                    <form method="GET" action="{{ route('cursos.cargar-csv') }}" class="space-y-4"
                          x-data="{
                              q: '',
                              open: false,
                              selected: '',
                              activeIndex: -1,
                              cursos: {{ $cursos->map(fn ($c) => ['id' => (int) $c->id_curso, 'name' => $c->nombre_curso])->values()->toJson() }},
                              get filtrados() {
                                  if (this.q === '') return this.cursos;
                                  const t = this.q.toLowerCase();
                                  return this.cursos.filter(c => c.name.toLowerCase().includes(t));
                              },
                              elegir(c) {
                                  this.selected = c.id;
                                  this.q = c.name;
                              },
                              onKeydown(e) {
                                  const items = this.filtrados;
                                  if (e.key === 'ArrowDown') {
                                      e.preventDefault();
                                      this.open = true;
                                      this.activeIndex = Math.min(this.activeIndex + 1, items.length - 1);
                                  } else if (e.key === 'ArrowUp') {
                                      e.preventDefault();
                                      this.activeIndex = Math.max(this.activeIndex - 1, 0);
                                  } else if (e.key === 'Enter') {
                                      if (this.activeIndex >= 0 && this.activeIndex < items.length) {
                                          e.preventDefault();
                                          this.elegir(items[this.activeIndex]);
                                      }
                                  } else if (e.key === 'Escape') {
                                      this.open = false;
                                  }
                              },
                              onBlur() {
                                  setTimeout(() => { this.open = false; }, 120);
                              },
                          }"
                          @keydown="onKeydown($event)">
                        <div class="relative">
                            <label class="block text-sm font-medium text-body mb-1" for="buscar-curso">Buscar curso</label>
                            <input id="buscar-curso" type="text" x-model="q"
                                   @focus="open = true"
                                   @input="open = true; selected = ''; activeIndex = -1"
                                   @blur="onBlur()"
                                   placeholder="Escribe para filtrar..."
                                   autocomplete="off"
                                   class="block w-full rounded-base border border-default bg-neutral-primary-soft px-3 py-2 text-sm focus:ring-2 focus:ring-brand-medium">

                            <input type="hidden" name="curso" :value="selected">

                            <ul x-show="open" x-cloak
                                @click.outside="open = false"
                                class="absolute z-50 mt-1 w-full max-h-[30vh] overflow-y-auto rounded-base border border-default bg-neutral-primary-soft shadow-lg">
                                <template x-for="(c, i) in filtrados" :key="c.id">
                                    <li @mousedown.prevent="elegir(c)"
                                        :class="[
                                            selected === c.id ? 'bg-brand-softer font-medium' : '',
                                            activeIndex === i ? 'bg-brand-active' : '',
                                        ]"
                                        class="cursor-pointer px-3 py-2 text-sm hover:bg-brand-active">
                                        <span x-html="c.name.replace(new RegExp('(' + q.replace(/[.*+?^${}()|[\\]\\\\]/g, '\\\\$&') + ')', 'gi'), '<mark class=\'bg-brand-softer text-fg-brand\'>$1</mark>')"></span>
                                    </li>
                                </template>
                                <li x-show="filtrados.length === 0" class="px-3 py-2 text-sm text-body">
                                    Sin resultados
                                </li>
                            </ul>
                        </div>

                        <x-ui.button type="submit" ::disabled="selected === ''">
                            Continuar
                        </x-ui.button>
                    </form>
                @endif
            </div>
        </div>
    @else
        @livewire(\App\Livewire\Cursos\CargarCsv::class, ['curso' => $curso])
    @endif
@endsection
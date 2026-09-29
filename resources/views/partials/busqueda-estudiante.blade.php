{{--
    @file    busqueda-estudiante.blade.php
    @author  Alisson D. Alvarado <alvaradoalissondalet@gmail.com>
    @created 2026-09-26
    @updated 2026-09-26

    @description
    Cuerpo del `x-data` de Alpine con el estado del buscador de estudiantes. Es
    un parcial y no un componente porque se incluye DENTRO del `x-data` que ya
    tiene cada vista (el de las pestañas y el modal en la pagina mock, el de la
    raiz en el componente Livewire), y asi el resto del markup de la pagina
    puede leer el estado sin que cada vista repita la regla.

    El navegador no puede llamar a PHP, asi que la regla vive en
    App\Services\Examen\BusquedaEstudianteService; de ese servicio salen por
    interpolacion todos los parametros (largo del SIS, techos y expresiones de
    descarte), para que no exista una segunda definicion de la regla.

    Uso en una vista Blade:
        <div x-data="{
            @include('partials.busqueda-estudiante')
            tab: 'estudiantes',
        }">

    Uso en una vista de componente Livewire:
        <div x-data="@include('partials.busqueda-estudiante')">

    @changelog
    - 2026-09-26  [Alisson D. Alvarado]  feat: creación inicial del parcial.

    @see  App\Services\Examen\BusquedaEstudianteService
    @see  resources/views/pages/materia-estudiantes.blade.php
    @see  resources/views/livewire/examenes/estudiantes-curso.blade.php
--}}

@php
    use App\Services\Examen\BusquedaEstudianteService;
@endphp
busqueda: '',
modoBusqueda: '{{ BusquedaEstudianteService::MODO_SIS }}',
// El primer caracter del termino declara el modo y, con el, el unico tipo de
// dato que se acepta: cifra => codigo SIS, letra => nombre del estudiante.
modoDe(valor) {
    if (valor === '') {
        return '';
    }

    return /^[0-9]/.test(valor) ? this.modoBusqueda : '{{ BusquedaEstudianteService::MODO_NOMBRE }}';
},
// En modo nombre el largo no lo acota el navegador: se deja el atributo
// ausente para poder escribir nombres compuestos completos.
maximoBusqueda() {
    return this.modoDe(this.busqueda) === this.modoBusqueda
        ? {{ BusquedaEstudianteService::LARGO_COD_SIS }}
        : false;
},
// Se aplica en cada tecla: el valor del input se reescribe con lo que el modo
// activo permite y se guarda en `busqueda`, que es la fuente para filtrar.
sanitizarBusqueda(valor) {
    if (this.modoDe(valor) === '{{ BusquedaEstudianteService::MODO_NOMBRE }}') {
        return valor.replace({{ \Illuminate\Support\Js::from(BusquedaEstudianteService::DESCARTE_NOMBRE) }}, '');
    }

    return valor
        .replace({{ \Illuminate\Support\Js::from(BusquedaEstudianteService::DESCARTE_COD_SIS) }}, '')
        .slice(0, {{ BusquedaEstudianteService::LARGO_COD_SIS }});
},
// Solo la vista mock filtra en el navegador; en el componente Livewire el
// filtrado lo hace el Service contra la base, asi que alcanza con `busqueda`.
coincideEstudiante(nombre, sis) {
    const modo = this.modoDe(this.busqueda);

    if (modo === '') {
        return true;
    }

    const termino = this.busqueda.trim().toLowerCase();

    return (modo === this.modoBusqueda ? sis : nombre).toLowerCase().includes(termino);
},
// Requiere que la vista declare `estudiantesBusqueda` con la lista de
// { nombre, sis } que hay en pantalla; si no la declara, no hay con que contar.
sinResultados() {
    if (this.busqueda.trim() === '' || ! Array.isArray(this.estudiantesBusqueda)) {
        return false;
    }

    return ! this.estudiantesBusqueda.some((estudiante) =>
        this.coincideEstudiante(estudiante.nombre, estudiante.sis)
    );
},

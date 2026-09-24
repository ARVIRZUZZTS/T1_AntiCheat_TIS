# views/layouts — Layouts de la aplicación

## Para qué sirve
Define el "esqueleto" visual compartido por todas las páginas: cabecera, navegación, sidebar y dónde se inyecta el contenido de cada página (`{{ $slot }}`).

## Qué contiene
- `app.blade.php` (layout principal con Flowbite + Tailwind, creado con Livewire).

## Rol en la arquitectura
- **Capa:** Presentación (estructura).
- **Conoce:** componentes `ui`.
- **No conoce:** modelos, servicios ni lógica.

Fuente: `arquitectura.md` (sección 4).
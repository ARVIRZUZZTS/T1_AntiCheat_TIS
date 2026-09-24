# Traits — Comportamiento compartido

## Para qué sirve
Comportamiento reutilizable entre componentes Livewire o modelos, sin herencia. Encapsulan lógica transversal (filtros, exportación) que varios componentes necesitan.

## Qué contiene
- `ConFiltros` (búsqueda/orden/paginación en listados).
- `ConExportacion` (descargas CSV/Excel).
- `ConSesionActiva` (estado de sesión del estudiante).

## Rol en la arquitectura
- **Capa:** Transversal (apoyo a Presentación y Dominio).
- **Conoce:** `Support`, `Models` (cuando aplica).
- **No conoce:** controllers ni vistas.

Fuente: `arquitectura.md` (sección 5, Lifecycle → Traits).
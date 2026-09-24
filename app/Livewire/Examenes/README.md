# Livewire/Examenes

## Para qué sirve
Gestión del ciclo de vida de los exámenes: listado con búsqueda/filtros/paginación, creación, edición y detalle.

## Qué contiene
- `Index` (listado con `ConFiltros`).
- `Create` / `Edit` (formularios con validación en vivo).
- `Show` (detalle del examen).

## Rol en la arquitectura
- **Capa:** Presentación (feature `Examenes`).
- **Conoce:** `Services\Examen`, `Requests`, `Traits` (`ConFiltros`).
- **No conoce:** persistencia directa.

Fuente: `arquitectura.md` (sección 4).
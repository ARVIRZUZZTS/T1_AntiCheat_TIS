# Livewire — Capa de Presentación (lógica + UI)

## Para qué sirve
Contiene los componentes Livewire: cada página misma con estado, validación en vivo y acciones. Es el "frontend con lógica" del stack TALL. Cada subcarpeta agrupa una feature del dominio.

## Qué contiene
- Clases de componente por feature: `Examenes/`, `Monitoreo/` (Sprint 1).
- Su vista vive en `resources/views/livewire/<feature>/...blade.php`.
- Operaciones simples (listar, guardar, validar) directamente; las complejas delegan en `Services`/`Actions`.
- `Auth/`, `Dashboard/`, `Reportes/`, `Configuracion/` quedan fuera del alcance del Sprint 1.

## Rol en la arquitectura
- **Capa:** Presentación.
- **Conoce:** `Services`, `Actions`, `Traits`, `Support` (`Context`).
- **No conoce:** detalles de persistencia directa ni reglas de negocio complejas.

Fuente: `arquitectura.md` (secciones 4 y 5).
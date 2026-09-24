# Controllers — Capa HTTP

## Para qué sirve
Orquesta las peticiones HTTP entrantes: decide qué página renderizar (componente Livewire full-page), qué acción disparar o a qué recurso redirigir. **No contiene lógica de negocio.**

## Qué contiene
- Controladores de páginas web (delegan en `Services`/`Actions`).
- Controladores de tránsito (redirects, descargas, exports puntuales) cuando no alcanza Livewire directo.

## Rol en la arquitectura
- **Capa:** HTTP (entrada).
- **Conoce:** `Services`, `Actions`, `Requests`, vistas Livewire.
- **No conoce:** detalles de persistencia ni reglas de negocio complejas.

Fuente: `arquitectura.md` (sección 4).
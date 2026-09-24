# Policies — Autorización por rol/entidad

## Para qué sirve
Centraliza la autorización por rol y entidad: quién puede ver, crear, editar o resolver una alerta, gestionar un examen, etc. Evita lógica de roles dispersa en componentes.

## Qué contiene
- `ExamenPolicy`, `AlertaTrampaPolicy`, `ReportePolicy`.
- Chequeos con `Gate` y roles (`proctor`/`estudiante`).

## Rol en la arquitectura
- **Capa:** Transversal (autorización).
- **Conoce:** `Models`, roles del `User`.
- **No conoce:** presentación, servicios ni vistas.

Fuente: `arquitectura.md` (sección 4).
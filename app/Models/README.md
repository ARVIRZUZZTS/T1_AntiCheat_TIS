# Models — Capa de Persistencia

## Para qué sirve
Representa el mapeo de las tablas de PostgreSQL. Define relaciones, casts, mutadores y consultas de persistencia. **No contiene reglas de negocio de alto nivel** (eso es trabajo de `Services`/`Actions`).

## Qué contiene
- Modelos Eloquent: `User`, `Examen`, `SesionExamen`, `EventoAnomalia`, `AlertaTrampa`, `ConfiguracionDeteccion`.
- Enums de dominio: `Severidad`, `TipoEvento`.
- Traits de Eloquent (Factories, SoftDeletes, etc.).

## Rol en la arquitectura
- **Capa:** Persistencia.
- **Conoce:** las tablas y sus relaciones.
- **No conoce:** vistas, controllers ni requests.

Fuente: `arquitectura.md` (sección 4).
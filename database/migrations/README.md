# database/migrations — Esquema y cambios de BD

## Para qué sirve
Define el esquema de PostgreSQL en código y lo mantiene versionado: creación de tablas, índices, llaves foráneas y sus cambios a lo largo del tiempo.

## Qué contiene
- Migraciones por entidad: `users`, `examenes`, `sesion_examen`, `evento_anomalia`, `alerta_trampa`, `configuracion_deteccion`.
- Usan `Schema` con tipos pgsql (jsonb, timestamps, enum de severidad).

## Rol en la arquitectura
- **Capa:** Persistencia (esquema).
- **Conoce:** la BD (PostgreSQL).
- **No conoce:** dominio ni presentación.

Fuente: `arquitectura.md` (secciones 3 y 4).
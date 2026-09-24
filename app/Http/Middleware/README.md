# Middleware — Capa HTTP

## Para qué sirve
Intercepta las peticiones antes de llegar al controller/componente para aplicar filtros transversales: autenticación, roles, sesión de examen activa, entre otros.

## Qué contiene
- Middlewares propios: `EnsureProctor`, `EnsureSesionExamenActiva`, etc.
- Registro en `bootstrap/app.php` (`withMiddleware`).

## Rol en la arquitectura
- **Capa:** HTTP (entrada).
- **Conoce:** `Models` (para resolver entidades en la petición).
- **No conoce:** `Services`, vistas ni lógica de dominio.

Fuente: `arquitectura.md` (sección 4).
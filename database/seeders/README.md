# database/seeders — Datos base

## Para qué sirve
Puebla la BD con datos base e iniciales: roles/usuarios por defecto, parámetros de configuración de detección y datos de desarrollo reproducible vía `Seeder`.

## Qué contiene
- `DatabaseSeeder` (existente) → llama a seeders específicos.
- `RoleSeeder`/`UserSeeder`, `ConfiguracionDeteccionSeeder`, `ExamenSeeder` (dev).

## Rol en la arquitectura
- **Capa:** Persistencia (datos).
- **Conoce:** `Models`, `Factories`.
- **No conoce:** presentación ni HTTP.

Fuente: `arquitectura.md` (sección 3).
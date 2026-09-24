# database/factories — Fábricas de datos de prueba

## Para qué sirve
Genera datos realistas para pruebas y seeders mediante Laravel Factories, manteniendo el ciclo de vida de las relaciones.

## Qué contiene
- `UserFactory` (existente) y factories por entidad agregada cuando se necesiten.
- Usan `Faker` manteniendo formatos del dominio (ej. `cod_sis` de 9 dígitos).

## Rol en la arquitectura
- **Capa:** Testing / Persistencia.
- **Conoce:** `Models`.
- **No conoce:** presentación ni HTTP.

Fuente: `arquitectura.md` (sección 9, Pest/PHPUnit).
# Providers — Capa de Bootstrap / Composición

## Para qué sirve
Registra servicios y bindings del contenedor, configura integraciones y arranca la aplicación. Es el punto donde se conectan las capas mediante inyección de dependencias.

## Qué contiene
- `AppServiceProvider` (bindings de `Services`, `Actions`, `Context`).
- `RouteServiceProvider`, `EventServiceProvider` y otros proveedores.
- Registro de observadores y políticas.

## Rol en la arquitectura
- **Capa:** Transversal (arranque).
- **Conoce:** todas las capas para registrar sus contratos.
- **No conoce:** detalles de implementación (eso lo resuelve el contenedor).

Fuente: `arquitectura.md` (sección 4 y 8).
# routes — Definición de rutas web

## Para qué sirve
Registra las rutas de la aplicación. Como el frontend es Livewire full-page, los archivos mapean URL → componente (o controlador cuando aplica) y asignan middleware de autenticación/roles.

## Qué contiene
- `web.php` — rutas públicas y autenticadas (login, dashboard, exámenes, monitoreo, reportes, configuración).
- `console.php` — comandos y schedulers (tareas programadas de monitoreo).
- `api.php` (opcional, futuro): la app es web por ahora.

## Rol en la arquitectura
- **Capa:** HTTP (enrutamiento).
- **Conoce:** componentes/controllers y middleware.
- **No conoce:** modelos, servicios ni persistencia.

Fuente: `arquitectura.md` (sección 4 y nota de `routes/api.php`).
# Support — Context Object + helpers

## Para qué sirve
Contiene utilidades transversales y el **Context Object**: un objeto que encapsula la información de la petición actual (usuario, rol, examen/sesión activos, configuración de detección) y evita pasar muchos parámetros o tocar el `session`/`auth()` desde cualquier capa.

## Qué contiene
- `Context.php` (contexto tipado de la operación actual).
- `helpers.php` (funciones globales utilitarias, cargadas en `composer.json`).

## Rol en la arquitectura
- **Capa:** Transversal.
- **Conoce:** nada de presentación; usa `auth()`, `session()`, `Config`.
- **Es conocido por:** casi todas las capas (le inyectan/consumen contexto).

Fuente: `arquitectura.md` (secciones 4, 5 y 8).
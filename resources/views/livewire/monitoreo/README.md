# views/livewire/monitoreo — Vistas de vigilancia en vivo

## Para qué sirve
Renderiza la sesión de vigilancia del proctor: feed de anomalías y detalle de cada alerta.

## Qué contiene
- `en-vivo.blade.php`, `index.blade.php` (sesiones activas).
- `detalle-alerta.blade.php` (revisión de una alerta).
- `registrar-incidencia.blade.php` (formulario de registro de incidencia).

## Rol en la arquitectura
- **Capa:** Presentación (render del feature `Monitoreo`).
- **Conoce:** solo los props de su componente.

Fuente: `arquitectura.md` (secciones 4 y 5).
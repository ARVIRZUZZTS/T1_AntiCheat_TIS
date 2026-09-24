# Livewire/Monitoreo

## Para qué sirve
Sesión de vigilancia en tiempo real: seguir la actividad del estudiante durante el examen, ver eventos de anomalía y decidir acciones (bloquear sesión, revisar alerta).

## Qué contiene
- `EnVivo` (streaming de eventos con `wire:poll` o broadcasting).
- `DetalleAlerta` (revisión de una alerta de trampa).

## Rol en la arquitectura
- **Capa:** Presentación (feature `Monitoreo`).
- **Conoce:** `Services\Monitoreo`, `Services\Deteccion`, `Actions`, `Context`.
- **No conoce:** persistencia directa.

Fuente: `arquitectura.md` (secciones 4 y 5).
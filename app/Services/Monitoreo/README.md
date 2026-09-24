# Services/Monitoreo

## Para qué sirve
Consume y procesa el stream de eventos de una sesión en curso: calcula el estado de la sesión (activa/pausada/bloqueada), prepara las vistas de vigilancia en tiempo real y registra la línea temporal del estudiante.

## Qué contiene
- `ProcesadorDeEventos` (ingesta y normalización de eventos).
- `EstadoDeSesion` (cálculo de estado en vivo).
- `SesionActivaService` (sesiones en curso para el monitor).

## Rol en la arquitectura
- **Capa:** Dominio (adentro de `Services`).
- **Conoce:** `Models` (sesiones, eventos), `Support`.
- **No conoce:** presentación ni HTTP.

Fuente: `arquitectura.md` (secciones 4 y 5).
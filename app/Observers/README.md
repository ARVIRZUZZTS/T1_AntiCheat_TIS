# Observers — Reacciones a eventos de modelos

## Para qué sirve
Reacciona a los eventos del ciclo de vida de Eloquent (`created`, `updated`, `deleted`, …) para ejecutar acciones secundarias sin ensuciar el modelo: auditoría, notificaciones, registro de alertas.

## Qué contiene
- `ExamenObserver`, `SesionExamenObserver`, `EventoAnomaliaObserver`.

## Rol en la arquitectura
- **Capa:** Transversal (eventos de persistencia).
- **Conoce:** `Models`, `Actions` (lógica, no reglas de negocio).
- **No conoce:** presentación ni HTTP.

Fuente: `arquitectura.md` (sección 4).
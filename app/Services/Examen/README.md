# Services/Examen

## Para qué sirve
Orquesta el ciclo de vida de un examen: creación/edición con sus reglas, programación, apertura/cierre de sesiones y el enrollamiento de estudiantes.

## Qué contiene
- `CrearExamenService`, `ActualizarExamenService`, `ProgramarExamenService`.
- `InicioDeSesion` (arranque vigilado de una sesión).

## Rol en la arquitectura
- **Capa:** Dominio (adentro de `Services`).
- **Conoce:** `Models` (examen, sesiones), `Requests` (vía controller), `Support`.
- **No conoce:** presentación ni HTTP.

Fuente: `arquitectura.md` (sección 4).
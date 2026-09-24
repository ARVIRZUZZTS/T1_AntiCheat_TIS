# Actions — Capa de Dominio

## Para qué sirve
Operaciones de dominio puntuales y atómicas (una sola intención) que no necesitan un servicio completo. Son clases únicas con un método `handle()` y aumentan la legibilidad del flujo.

## Qué contiene
- `BloquearSesion`, `RevisarAlerta`, `RegistrarEvento`, `PVencerExamen`.

## Rol en la arquitectura
- **Capa:** Dominio.
- **Conoce:** `Models`, `Support` (`Context`).
- **No conoce:** presentación, HTTP ni vistas.

Fuente: `arquitectura.md` (sección 4 y 5).
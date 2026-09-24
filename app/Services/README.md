# Services — Capa de Dominio

## Para qué sirve
Contiene los servicios del dominio: coordinan reglas de negocio, orquestan operaciones y son inyectados por constructor. **Es donde vive la lógica que "decide".** Cada subcarpeta agrupa una capacidad del sistema.

## Qué contiene
- `Deteccion/` — heurísticas anti-trampa.
- `Monitoreo/` — streaming de eventos de sesión.
- `Reporte/` — agregaciones y exportaciones.
- `Examen/` — ciclo de vida del examen.

## Rol en la arquitectura
- **Capa:** Dominio.
- **Conoce:** `Repositories` (persistencia), otros `Services`, `Support`.
- **No conoce:** controllers, Livewire ni vistas.

Fuente: `arquitectura.md` (sección 4).
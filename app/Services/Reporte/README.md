# Services/Reporte

## Para qué sirve
Genera agregaciones y exportaciones: estadísticas por examen, históricos de anomalías y reportes descargables. Consolida datos para que la vista de reportes solo los muestre.

## Qué contiene
- `ReporteDeAnomalias`, `ReportePorExamen`, `EstadisticasService`.
- Exportadores (CSV/Excel vía `Traits\ConExportacion`).

## Rol en la arquitectura
- **Capa:** Dominio (adentro de `Services`).
- **Conoce:** `Models`, `Support` (`Context`).
- **No conoce:** presentación ni HTTP.

Fuente: `arquitectura.md` (sección 4).
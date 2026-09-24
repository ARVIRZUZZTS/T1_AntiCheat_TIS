# Services/Deteccion

## Para qué sirve
Evalúa la actividad del estudiante y decide si existe (o no) una anomalía de trampa. Es el núcleo crítico del anti-cheat: cada heurística recibe evento/sesión y devuelve un veredicto con nivel de severidad.

## Qué contiene
- Clases de heurísticas: `DeteccionDeCambioVentana`, `DeteccionDeCopiarPegar`, `DeteccionDeConexionRemota`, etc.
- Reglas concretas (`...Rule`) y enums de resultado (`VeredictoDeteccion`, `Severidad`).

## Rol en la arquitectura
- **Capa:** Dominio (adentro de `Services`).
- **Conoce:** `Models` (sesión, eventos), `Support` (`Context`), `ConfiguracionDeteccion`.
- **No conoce:** presentación ni HTTP.

Fuente: `arquitectura.md` (secciones 4 y 5).
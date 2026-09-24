# Resources — Capa HTTP (API futura)

## Para qué sirve
Prepara la respuesta JSON de los modelos cuando (y si) se exponga una API a móvil o terceros. Hoy la app es 100% web con Livewire; esta capa queda reservada para ese escenario.

## Qué contiene
- `ApiResource` por entidad (`ExamenResource`, `SesionExamenResource`, `AlertaTrampaResource`).
- Transformación de datos de salida (campos expuestos, formato de fechas).

## Rol en la arquitectura
- **Capa:** HTTP (salida JSON).
- **Conoce:** `Models`.
- **No conoce:** `Services`, vistas, Livewire.

Fuente: `arquitectura.md` (sección 4 y nota de `routes/api.php` opcional).
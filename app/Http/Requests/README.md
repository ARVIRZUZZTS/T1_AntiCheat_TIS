# Requests — Capa HTTP (validación)

## Para qué sirve
Centraliza la validación y la autorización de la entrada de datos. Define qué datos se aceptan, con qué reglas y qué usuarios pueden hacer la operación. Devuelve datos ya "limpios" y listos para la capa de servicio.

## Qué contiene
- Form Requests por operación: `StoreExamenRequest`, `UpdateExamenRequest`, `RegisterEventoRequest`, etc.
- Reglas (`rules()`) y autorización (`authorize()`).

## Rol en la arquitectura
- **Capa:** HTTP (entrada).
- **Conoce:** `Models` (solo para autorización/consultas simples).
- **No conoce:** `Services`, vistas ni Livewire.

Fuente: `arquitectura.md` (sección 4).
# views/livewire — Vistas de los componentes Livewire

## Para qué sirve
Contiene las vistas Blade (`*.blade.php`) de cada componente Livewire. **Una vista por componente**, en la ruta espejo de su clase en `app/Livewire/<feature>/`.

## Qué contiene
- `examenes/`, `monitoreo/` (módulos del Sprint 1).
- Usan los componentes de `components/ui` y directivas Livewire (`wire:model`, `wire:poll`, `@entangle`, …).
- `auth/`, `dashboard/`, `reportes/`, `configuracion/` quedan fuera del alcance del Sprint 1 (se añadirán en sprints posteriores).

## Rol en la arquitectura
- **Capa:** Presentación (render).
- **Conoce:** solo lo que su componente le pasa.
- **No conoce:** modelos, servicios ni rutas.

Fuente: `arquitectura.md` (secciones 4 y 5).
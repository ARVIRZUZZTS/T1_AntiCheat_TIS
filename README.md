# T1_AntiCheat_TIS

Sistema de control de ingreso a exámenes masivos (TALL + PostgreSQL).

## Requisitos
- PHP 8.2+, Composer
- Node.js 18+ y npm
- PostgreSQL 15+

## Instalación
1. `composer install`
2. `npm install && npm run build`
3. Copiar `.env.example` a `.env` y configurar la base de datos PostgreSQL.
4. `php artisan key:generate`
5. `php artisan migrate`
6. `php artisan db:seed` (opcional, para datos de prueba)

## Ejecución
- `php artisan serve` o configurar el virtual host apuntando a `public/`.

## Documentación
- `INSTALACION.md`: guía detallada del entorno.
- `arquitectura.md`, `codigo.md`, `comentarios.md`: convenciones del proyecto.
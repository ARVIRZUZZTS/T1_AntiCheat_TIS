# Arquitectura del Sistema - T1 AntiCheat TIS

**T1 - Tech One SRL**
**Stack:** Laravel 11 + Livewire 4 + Tailwind 4 + Flowbite 4 (TALL) + PostgreSQL 15

Este documento define la arquitectura del sistema de control de trampas en exámenes.
Se trata de un **monolito en capas** (layered architecture) con el backend y el
frontend en el mismo repositorio y proyecto.

---

## 1. Resumen

La aplicación es un monolito Laravel hospedado en un único repositorio. El frontend
(Blade + Livewire + Tailwind/Flowbite) y el backend (Controllers, Services, Models)
viven en el mismo código, lo que simplifica el despliegue y reduce la fricción entre
equipos.

La organización se basa en dos ideas combinadas:

1. **Arquitectura por capas** (Fowler, 2002): separación horizontal en Presentación,
   HTTP, Aplicación/Dominio y Persistencia.
2. **Agrupación por features dentro de cada capa**: cada capa tiene subcarpetas por
   módulo del dominio (Auth, Examenes, Monitoreo, Reportes, Configuracion).

> Regla de oro: una capa solo puede depender de las capas inferiores, nunca de las
> superiores. La Presentación llama a HTTP/Services; los Services nunca llaman a
> un Controller ni a una vista.

---

## 2. Diagrama de capas

```
+--------------------------------------------------------------+
|  PRESENTACION                                                 |
|  resources/views/{components/ui, livewire, layouts}           |
|  componentes Blade (UI) + componentes Livewire (estado)       |
+--------------------------------------------------------------+
              ▼
+--------------------------------------------------------------+
|  HTTP (entrada)                                               |
|  app/Http/{Controllers, Requests, Middleware}                 |
|  routes/{web, console}.php  → "endpoints"                     |
+--------------------------------------------------------------+
              ▼
+--------------------------------------------------------------+
|  APLICACION / DOMINIO                                         |
|  app/Services  (logica de negocio reutilizable)               |
|  app/Actions   (operaciones de dominio puntuales)             |
|  app/Traits    (comportamiento compartido entre componentes)  |
|  app/Policies  (autorizacion)                                 |
+--------------------------------------------------------------+
              ▼
+--------------------------------------------------------------+
|  PERSISTENCIA                                                 |
|  app/Models (Elooquent)  →  PostgreSQL 15 (puerto 5432)       |
|  database/{migrations, factories, seeders}                    |
+--------------------------------------------------------------+
```

La capa **Support/Core** (Context, helpers) es transversal: puede ser usada por
Presentación, HTTP y Aplicación, pero nunca contiene lógica de negocio propia.

---

## 3. Mapeo de vocabulario del equipo → Laravel

El equipo viene de una mentalidad de componentes/hooks (JS/React). Este es el
mapeo concreto a Laravel para evitar ambigüedad:

| Concepto del equipo      | En Laravel / Livewire                                          |
|--------------------------|----------------------------------------------------------------|
| Componentes de UI        | `resources/views/components/ui/` (Blade, presentacional)        |
| Componentes con lógica   | `app/Livewire/` (Livewire, con estado y eventos)                |
| "Features" / módulos     | Subcarpetas por feature dentro de cada capa                     |
| "Hooks"                  | Ciclo de vida Livewire: `mount()`, `updated($prop)`, `#[Computed]` |
| Lógica reutilizable      | `app/Traits/` + `app/Services/`                                 |
| "Endpoints"              | `routes/web.php` (cada URL renderiza una página o acción)       |
| "Context"                | `app/Support/Context.php` (patrón Context Object)               |
| "Helpers"                | helpers de Laravel + `app/Support/helpers.php` (solo utilidades puras) |

> Importante: **no existen "hooks" de React en Laravel**. El equivalente son los
> métodos de ciclo de vida de Livewire y los Traits para compartir comportamiento.
> No confundir "context" (estado del request actual) con hooks.

### 3.1 Helpers

Laravel ya provee helpers globales listos para usar: `route()`, `url()`, `auth()`,
`config()`, `request()`, `session()`, `cache()`, `view()`, `redirect()`, `response()`,
y `Str::`, `Arr::`, `Number::`. No hay que reimplementarlos.

**Helpers propios** (`app/Support/helpers.php`) son funciones globales de la
aplicación y se registran en `composer.json` en la sección:

```json
"autoload": {
    "files": ["app/Support/helpers.php"]
}
```

**¿Son necesarios?** Solo si aparece una utilidad *pura* (formateo/serialización) que
se repite en toda la aplicación, por ejemplo:

- `formato_tiempo_sesion(segundos)` → "2h 14m"
- `severidad_badge(int $nivel)` → "leve | media | critica"
- `fingerprint(dispositivo)` → hash normalizado del dispositivo del estudiante

Regla: si tiene lógica de negocio → va a un **Service**. Si solo formatea o
transforma datos → puede ser un helper. Ante la duda, primero Service.

### 3.2 Context Object

En Laravel, el estado del request actual está disperso: `auth()->user()`,
`Route::current()`, `request()->ip()`, `session()`. En un sistema de control de
trampas, casi toda la operación depende de "qué examen/sesión está activa",
disponibles a través del routing binding de Laravel.

`app/Support/Context.php` centraliza esa información con el patrón **Context Object**
(Yoder & Marquardt, 2000). Es una clase delgada que **envuelve** a `auth()`, `route()`
y `request()` — no es un singleton con estado propio:

```php
Context::user()       // usuario/proctor actual (envuelve auth()->user())
Context::exam()       // Examen de la URI actual (route model binding)
Context::session()    // SesionExamen activa del monitoreo
Context::isMobile()   // detección de dispositivo (útil para mobile-first)
```

**¿Es necesaria?** No es obligatoria, pero se recomienda en este dominio porque:
- Evita pasar 3-4 parámetros en cadena Controller → Service → Action.
- Unifica el acceso al "contexto de sesión" con una sola fuente de verdad.
- Facilita testeos (se puede reemplazar el resolutor del contexto).
- NO almacena estado mutable; solo agrega request/route/current.

---

## 4. Estructura de carpetas y función de cada una

```
app/
  Http/
    Controllers/          ★ Capa HTTP: orquestan requests HTTP (web o JSON).
                            Devuelven una vista de Livewire, un redirect o un
                            response JSON (si alguna vez hay API). Sin lógica de
                            negocio: delegan en Services/Actions.
    Requests/             ★ Validación. Cada FormRequest define reglas,
                            autorización (authorize()) y datos limpiados
                            (e.g. StoreExamenRequest, RegisterEventoRequest).
    Middleware/           ★ Interceptan requests: e.g. EnsureSesionExamenActiva,
                            EnsureProctor (rol docento/admin).
  Livewire/               ★ Componentes con estado y lógica de UI.
                            Subcarpetas por feature:
                            Auth/        → Login, Logout, Perfil, CambioPassword
                            Dashboard/   → Overview (métricas en vivo)
                            Examenes/    → Index (listado+filtros), Create/Edit,
                                           Show (detalle)
                            Monitoreo/   → EnVivo (sesión de vigilancia en
                                           tiempo real), DetalleAlerta
                            Reportes/    → Generar (filtros+export), Historico
                            Configuracion/ → Sensibilidad (umbrales de detección)
                            Cada clase define propiedades públicas (wire:model),
                            mount()/updated()/[#Computed], y métodos de acción
                            llamados con wire:click. La vista vive en
                            resources/views/livewire/<feature>/...blade.php
  Models/                 ★ Eloquent: mapeo de la base PostgreSQL.
                            User, Examen, SesionExamen, EventoAnomalia,
                            AlertaTrampa, ConfiguracionDeteccion.
                            Incluye enums de dominio (Severidad, TipoEvento).
  Services/               ★ Lógica de negocio reutilizable (capa de aplicación).
                            Subcarpetas por feature:
                            Deteccion/   → heurísticas anti-trampa (cambio de
                                           pestaña, pantalla extra, tiempos de
                                           respuesta, percentiles)
                            Monitoreo/   → streaming de eventos de sesión
                            Reporte/     → agregaciones y exportaciones
                            Examen/      → ciclo de vida del examen
                            Los Services NO conocen vistas ni requests; reciben
                            datos y devuelven resultados/Modelos.
  Actions/                ★ Operaciones de dominio puntuales y accionables:
                            RegistrarEventoTrampa, BloquearSesion,
                            MarcarAlertaRevisada. Clase única con método __invoke().
  Traits/                 ★ Comportamiento reutilizable sin herencia:
                            ConFiltros (búsqueda, orden, paginación),
                            ConAlertas (notificaciones flash reutilizables),
                            ConExportacion. Usados principalmente por
                            componentes Livewire para no duplicar lógica.
  Support/
    Context.php           ★ Context Object: estado del request actual (usuario,
                            examen, sesión, dispositivo).
    helpers.php           ★ Helpers globales puros (opcional). Se autoloadan vía
                            composer.json (files).
  Policies/               ★ Autorización por rol/entidad:
                            ExamenPolicy, SesionExamenPolicy. Se enlazan desde
                            Services o gates.
  Providers/              ★ Proveedores de servicios: registro de bindings de
                            Context, Services e integraciones (eventos de
                            monitoreo con broadcasting si se usa).
  Observers/              ★ Reacción a eventos de modelos (e.g. al crear una
                            SesionExamen, crear el registro de eventos).

resources/
  views/
    components/ui/        ★ UI presentacional 100% reutilizable (sin lógica):
                            x-badge-severidad, x-card-evento, x-tabla-sesiones,
                            x-modal-confirmar, x-stat-card, x-skeleton,
                            x-vacio-estado, x-tabs. Reciben props/slots y usan
                            clases Tailwind/Flowbite. Nada de wire: aquí.
    livewire/             ★ Vistas de cada componente Livewire, espejando la
                            estructura de app/Livewire/:
                            auth/, dashboard/, examenes/, monitoreo/,
                            reportes/, configuracion/.
    layouts/
      app.blade.php       ★ Layout base (ya creado): head con @vite,
                            @livewireStyles, @livewireScripts, y el script CDN
                            de Flowbite antes de </body>.

routes/
  web.php                 ★ "Endpoints" de la aplicación web. Cada GET mapea una
                            URL a un componente Livewire full-page o a un
                            Controller. No agrupar lógica aquí.
  console.php             ★ Comandos artisan personalizados.
  (api.php)               ★ Solo si algún día se expone API JSON (móvil/terceros).

database/
  migrations/             ★ Esquema de PostgreSQL (tablas, índices, FK).
  factories/              ★ Datos de prueba para tests/seeders.
  seeders/                ★ Datos iniciales (roles, config de detección).

tests/                    ★ Unit (services/actions) y Feature (rutas,
                            componentes Livewire, migraciones).

public/build/             ★ Assets compilados con Vite (npm run build).
```

### 4.1 ¿Qué NO va en cada carpeta?

- **Controllers**: NO lógica de negocio, NO consultas Eloquent largas. Máximo
  orquestar y delegar.
- **Livewire/**: NO consultas directas complejas — delegar en Services cuando la
  operación es más que listar/validar.
- **Models/**: NO reglas de negocio de alto nivel (ese es trabajo de Services/
  Actions). Los modelos solo mapean y agregan relaciones/queries de persistencia.
- **components/ui (Blade)**: NO `wire:` ni lógica; si lo necesita, es un
  componente Livewire.

---

## 5. Cuándo usar Blade vs Livewire (decisiones concretas)

Usar **Blade presentacional** (`components/ui/`) cuando el bloque:
- No tiene estado (solo `{{ }}`, slots, props).
- Se repite en varias vistas con el mismo aspecto.
- Sirve para el diseño con Flowbite: botones, badges, tarjetas, tablas,
  modales de confirmación, estados vacíos/loading, KPIs.

Ejemplos en este dominio:
- `x-badge-severidad` para marcar alertas leve/media/crítica.
- `x-card-evento` para una anomalía (cambio de pestaña, pantalla extra).
- `x-tabla-sesiones` (colapsa a cards en pantallas móviles).
- `x-modal-confirmar` para "¿Bloquear la sesión del estudiante X?".
- `x-stat-card` para KPIs del dashboard.
- `x-tabs` para "En vivo / Histórico / Estudiantes".

Usar **Livewire** cuando el bloque:
- Tiene estado (`wire:model`, propiedades).
- Reacciona a eventos (`wire:click`, `wire:poll`, `wire:keydown`).
- Necesita `mount()`, `updated()`, propiedades `#[Computed]`.
- Representa una página completa o un formulario con validación en vivo.

Ejemplos:
- `Dashboard/Overview` (métricas en vivo vía `wire:poll`).
- `Examenes/Index` (búsqueda, filtros, paginación).
- `Monitoreo/EnVivo` (sesión de vigilancia en tiempo real).
- `Reportes/Generar` (filtros de fecha + exportación).
- `Configuracion/Sensibilidad` (umbrales de detección).

> Regla de oro: si el bloque necesita `wire:` → Livewire. Si es solo HTML/slots →
> Blade. Si es una página entera con datos → Livewire full-page.

---

## 6. Convenciones de rutas / endpoints

Cada ruta web es un endpoint. Para páginas interactivas se apunta directo a un
componente Livewire full-page:

```php
Route::get('/', Dashboard\Overview::class);

Route::get('/examenes', Examenes\Index::class);
Route::get('/examenes/{examen}', Examenes\Show::class);

Route::get('/examenes/{examen}/monitoreo', Monitoreo\EnVivo::class);
Route::get('/monitoreo/alertas/{alerta}', Monitoreo\DetalleAlerta::class);

Route::get('/reportes', Reportes\Generar::class);
Route::get('/configuracion', Configuracion\Sensibilidad::class);
```

Los parámetros de ruta (`{examen}`, `{alerta}`) se inyectan en `mount()` del
componente y quedan disponibles vía `Context::exam()`/`Context::session()`. Las rutas
que requieren roles van protegidas con middleware (`auth`, `EnsureProctor`).

---

## 7. Mobile-first

El sistema es web pero **mobile-first**: las vistas se diseñan primero para pantallas
pequeñas y se expanden con breakpoints de Tailwind (`sm:`, `md:`, `lg:`).

- Las tablas densas se convierten en cards en pantallas móviles (ver `x-tabla-sesiones`).
- Los modales y dropdowns usan componentes responsive de Flowbite.
- `Context::isMobile()` permite adaptar decisiones de UI (y reglas de monitoreo) según
  el dispositivo.
- El layout base ya incluye `viewport` responsive.

No es una carpeta separada: es un principio de diseño aplicado en todas las vistas.

---

## 8. Reglas de dependencia entre capas

1. **Presentación → HTTP o Aplicación**: las vistas llaman `wire:` que invocan
   acciones de Livewire; los Livewire delegan a Services/Actions.
2. **HTTP → Aplicación**: Controllers/Middleware/Requests llaman Services/Actions.
3. **Aplicación → Persistencia**: Services/Actions usan Models (repositorios
   implícitos de Eloquent).
4. **Nunca hacia arriba**: un Service no conoce un Controller ni una vista.
5. **Support/Core**: transversal, sin lógica de negocio.

Esta dirección de dependencias es la que defiende Clean Architecture (Martin, 2017):
el código de dominio no debe depender de la UI ni del framework de entrada.

---

## 9. Bibliografía

Arquitectura por capas y MVC:

- Reenskaug, T. (1979). *Models-Views-Controllers*. Xerox PARC. Ensayo original del patrón MVC.
- Fowler, M. (2002). *Patterns of Enterprise Application Architecture*, cap. "Layering". Addison-Wesley.
- Buschmann, F., Meunier, R., Rohnert, H., Sommerlad, P., Stal, M. (1996). *Pattern-Oriented Software Architecture, Vol. 1: A System of Patterns* — patrón "Layers". Wiley.
- Fowler, M. (2010). *GUI Architectures* (artículo sobre evolución del MVC).

Dependencias y dominio:

- Martin, R.C. (2017). *Clean Architecture: A Craftsman's Guide to Software Structure and Design*. Prentice Hall.
- Martin, R.C. (2012). *The Clean Architecture* (artículo).
- Evans, E. (2003). *Domain-Driven Design: Tackling Complexity in the Heart of Software*. Addison-Wesley (entidades, agregados y servicios de dominio).

Context Object, Service Locator e inyección:

- Yoder, J.W., Marquardt, K. (2000). *The Context Object Pattern* — Proceedings of PLoP 2000.
- Fowler, M. (2002). *Patterns of Enterprise Application Architecture* — "Service Locator".
- Fowler, M. (2004). *Inversion of Control Containers and the Dependency Injection Pattern*.

Stack específico:

- *Laravel Documentation* — Architecture Concepts: Request Lifecycle, Service Container, Controllers, Middleware. https://laravel.com/docs/11.x
- *Livewire Documentation* — Components, Component Lifecycle, Computed Properties. https://livewire.laravel.com/docs
- *TallStack* — documentación del stack TALL (Tailwind, Alpine, Laravel, Livewire). https://tallstack.dev
- Otwell, T. (2020). *Laravel: Up and Running*, 2nd ed. O'Reilly (estructura de carpetas y convenciones).
- *Flowbite Documentation* — componentes y temas de Tailwind CSS. https://flowbite.com/docs
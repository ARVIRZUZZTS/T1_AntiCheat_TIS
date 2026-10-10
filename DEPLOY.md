
Guia de Despliegue - Servidor WebTIS (TIS / UMSS)

T1 - Tech One SRL
**Entorno:** Windows 10/11 (empaquetado) + servidor Linux (destino)
**Destino:** `https://techone.tis.cs.umss.edu.bo/`

**Alcance de este manual**: este documento cubre como subir el proyecto al
servidor de la facultad y como volver a subirlo cada vez que haya cambios.
Para las base de datos ver la seccion 8; para el desarrollo local seguir
`INSTALACION.md`.

---

## 1. Como esta armado el servidor

Lo primero que hay que entender es que **el servidor no es una maquina con
terminal**. No hay SSH, no hay `composer`, no hay `php artisan`. Hay
exactamente dos herramientas web:

| Herramienta | URL | Para que |
|---|---|---|
| net2ftp | `https://techone.tis.cs.umss.edu.bo/net2ftp/` | subir archivos (soporta `.zip`) |
| phpPgAdmin | `https://techone.tis.cs.umss.edu.bo/phppgadmin/` | correr SQL sobre PostgreSQL |

El **doc root es la carpeta `public_html`**. Ese nombre lo impone el hosting y
por eso la carpeta `public/` de Laravel se sube renombrada. La disposicion
final en el servidor es:

```
/hosting/leticia/techone/          <- raiz del FTP (lo que ves en net2ftp)
  log/
  vendor/                          <- autoloader de Composer (9.143 archivos)
  server.php
  artisan
  composer.json
  composer.lock
  .env                             <- credenciales y configuracion
  app/
  bootstrap/
  config/
  database/
  resources/
  routes/
  storage/                         <- debe ser ESCRIBIBLE
  public_html/                     <- DOC ROOT (lo que se ve en el navegador)
    index.php
    .htaccess
    build/
      manifest.json
      assets/
```

**Por que `public/` va dentro de `public_html/` y no al reves.**
`public/index.php` resuelve sus dependencias con `__DIR__.'/../'`:

```php
require __DIR__.'/../vendor/autoload.php';        // -> raiz/vendor
(require_once __DIR__.'/../bootstrap/app.php')    // -> raiz/bootstrap
```

O sea, el codigo de la aplicacion tiene que quedar **un nivel por encima** del
doc root. Esa disposicion tiene una ventaja: `app/`, `config/` y `.env` quedan
fuera del doc root, asi que no son accesibles desde el navegador. No hace
falta ningun `.htaccess` de proteccion en la raiz.

### La base de datos es local del servidor

Las credenciales que entrego la facultad usan `127.0.0.1` como host. Eso no es
un error: PostgreSQL corre en el **mismo servidor** que el PHP, y el puerto
`5432` esta cerrado desde internet (esta confirmado, no se puede verificar
nada de la base desde la maquina del desarrollador). La unica forma de entrar a
la base es phpPgAdmin; la unica forma de tocar los archivos es net2ftp.

---

## 2. Que se sube y que no

| Carpeta / archivo | Se sube | Razon |
|---|---|---|
| `app/ bootstrap/ config/ database/ resources/ routes/` | si | codigo de la aplicacion |
| `storage/` (vacias) | si | Laravel escribe vistas compiladas, logs y sesiones |
| `artisan`, `server.php`, `composer.json`, `composer.lock` | si | ver seccion 7 |
| `public/` -> renombrado a `public_html/` | si | doc root |
| `public/build/` | si | assets compilados (CSS/JS) |
| `vendor/` | **una sola vez** | 9.143 archivos, 170 MB. No cambia salvo que corras `composer install` |
| `.env` | **una sola vez** | contiene la contrasena de la base |
| `node_modules/` | no | no se usa en el servidor |
| `tests/`, `phpunit.xml` | no | solo desarrollo |
| `.git/` | no | no va al servidor |

### Ojo con esto

`vendor/` y `public/build/` estan en `.gitignore`. Si clonaste el repositorio
limpio, **no existen** hasta que corras:

```bash
composer install
npm install
npm run build
```

Si falta cualquiera de los dos, la app arranca pero se rompe: sin `vendor/`
no hay autoloader, y sin `public/build/manifest.json` la directiva `@vite`
lanza `Vite manifest not found`. El script de empaquetado verifica las dos
cosas antes de generar los ZIP y avisa si falta algo.

---

## 3. Flujo de trabajo de cada cambio

**No se edita nada directamente en el servidor.** net2ftp tiene un boton Edit,
pero usarlo para codigo es una forma lenta de perder cambios. El ciclo es:

```
1. Editar en la maquina
2. .\tools\build-deploy.ps1
3. Subir el ZIP por net2ftp y descomprimirlo en la raiz
4. Recargar https://techone.tis.cs.umss.edu.bo/
```

El paso 2 genera dos archivos:

| ZIP | Contenido | Destino |
|---|---|---|
| `1_raiz.zip` | `app/ bootstrap/ config/ database/ resources/ routes/ storage/ artisan server.php composer.json composer.lock` | raiz del FTP |
| `2_public_html.zip` | `public_html/` con lo que hay en `public/` | raiz del FTP |

Los dos se descomprimen en la **raiz**, no dentro de una carpeta. Si net2ftp
pregunta donde extraer, elegir la raiz del FTP.

### Por que un script y no comprimir a mano

`Compress-Archive` de PowerShell 5.1 escribe los separadores de carpeta como
`\`. El descompresor de net2ftp (PHP corriendo sobre Linux) no los reconoce
como separadores y crea archivos con nombres raros tipo `app\Http` en vez de
la carpeta `app\Http`. La aplicacion queda rota sin ningun error visible.

`tools/build-deploy.ps1` escribe siempre `/` y ademas verifica los ZIP
generados: si detecta una entrada con `\`, cancela y avisa en vez de dejarte
subir un paquete roto.

### Ejecutar el script

```powershell
# desde la raiz del repositorio
.\tools\build-deploy.ps1

# primer despliegue solamente: incluir tambien el .env
.\tools\build-deploy.ps1 -IncludeEnv
```

Con `-IncludeEnv` el `.env` que se empaqueta es `deploy\.env.server` (el de
produccion) y, si no existe, se cae al `.env` local de la raiz. El `.env`
local **nunca** deberia llegar al servidor: apunta a la base de desarrollo.

Salida:

```
==> Comprobando que el proyecto este completo
==> Preparando los archivos a empaquetar
==> Limpiando storage/ (vistas compiladas, logs, sesiones, cache)
==> Generando los ZIP
==> Verificando los ZIP generados

Listo. Archivos para subir por net2ftp:

Name                 KB
----                 --
1_raiz.zip        182,4
2_public_html.zip  40,5
```

Los ZIP quedan en `deploy/`, que esta en `.gitignore`.

---

## 4. El `.env` del servidor

El `.env` **no** va en los ZIP de los despliegues diarios: lleva la contrasena
de la base y no tiene sentido re-subirlo en cada cambio. Se sube **una sola
vez** (`.\tools\build-deploy.ps1 -IncludeEnv`) y despues se edita en el
servidor con el boton Edit de net2ftp.

El archivo de verdad es **`deploy/.env.server`**. Ese es el que hay que tocar
si cambian credenciales, y es el que empaqueta `-IncludeEnv`. El contenido
actual:

```dotenv
APP_NAME=TechOne
APP_ENV=production
APP_KEY=base64:lSmD/nZEFmym/lQPcc6No782E/XZ1xd7mCABAIJlG+8=
APP_DEBUG=false
APP_TIMEZONE=UTC
APP_URL=https://techone.tis.cs.umss.edu.bo

APP_LOCALE=es
APP_FALLBACK_LOCALE=es
APP_FAKER_LOCALE=es_MX

BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=warning

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=techone_db
DB_USERNAME=techone
DB_PASSWORD=px8vPiRUKuwBXXn

SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync

CACHE_STORE=file
CACHE_PREFIX=

MAIL_MAILER=log
VITE_APP_NAME="${APP_NAME}"
```

Credenciales FTP y de phpPgAdmin: `deploy/credentials.md`.

Tres lineas importan mas que el resto:

| Linea | Por que |
|---|---|
| `APP_DEBUG=false` | con `true` cualquier error muestra en pantalla la contrasena de la base. El hosting es publico |
| `DB_HOST=127.0.0.1` | la base vive en el mismo servidor. Cualquier otro host no llega |
| `APP_KEY` | tiene que ser **el mismo** que el local, si no se invalidan sesiones y cookies |

> **No correr `php artisan migrate` en el servidor.** No hay terminal. El
> esquema ya se creo a mano por phpPgAdmin (ver seccion 8).

---

## 5. Permisos (se hace una sola vez, pero es obligatorio)

Laravel necesita escribir en dos sitios y sin eso la aplicacion devuelve
`500`:

| Carpeta | Para que |
|---|---|
| `storage/framework/views` | Blade compila las vistas aqui |
| `storage/logs` | el logger escribe el log |
| `storage/framework/sessions` | driver de sesion `file` |
| `bootstrap/cache` | manifiesto de paquetes |

En net2ftp: entrar a la carpeta, tildar las subcarpetas, clickear **Chmod**,
marcar todos los permisos, OK.

**net2ftp no hace chmod recursivo**, hay que ir carpeta por carpeta. Son once:

```
storage
storage/app
storage/app/public
storage/framework
storage/framework/cache
storage/framework/cache/data
storage/framework/sessions
storage/framework/testing
storage/framework/views
storage/logs
bootstrap/cache
```

### Si el chmod no alcanza

Si PHP corre como un usuario distinto al del FTP, el chmod no sirve. En ese
caso hay que sacar `storage/` del camino usando el `.env`:

```dotenv
LOG_CHANNEL=errorlog
VIEW_COMPILED_PATH=/tmp/techone_views
SESSION_DRIVER=cookie
```

Es un parche, no la solucion: con `SESSION_DRIVER=cookie` la sesion viaja en la
cookie del navegador, que tiene un limite de 4 KB. Si alguna vez guardan
muchos datos en sesion, van a necesitar `storage/` escribible igual.

---

## 6. Primer despliegue (una sola vez)

1. Subir `vendor/` descomprimido en la raiz. Son 9.143 archivos; la forma
   comoda es comprimirlo localmente y subir el `.zip`.
2. **Cargar la base.** phpPgAdmin -> pestana SQL, con **"Paginar resultados"
   desmarcado**, y correr `database/sql/000_deploy_completo.sql`. Ese unico
   archivo trae esquema (11 ENUM + 33 tablas), datos y sequences sincronizados:
   no hace falta correr `001`, `002` y `003` por separado. Si la base ya
   tiene tablas, no lo corras (ver seccion 8).
3. Subir y descomprimir `1_raiz.zip` en la raiz.
4. Subir y descomprimir `2_public_html.zip` en la raiz.
5. Subir el `.env`: `.\tools\build-deploy.ps1 -IncludeEnv` lo incluye en
   `1_raiz.zip`, o crearlo en la raiz con el boton **New / Edit** de net2ftp a
   partir de `deploy/.env.server` (seccion 4).
6. **Borrar `public_html/index.html` y `public_html/info.php`.** Son los
   archivos de la plantilla de bienvenida de WebTIS. Apache sirve `index.html`
   antes que `index.php`, asi que mientras esten ahi se sigue viendo el cartel
   de bienvenida de la facultad y no la aplicacion. `info.php` ademas expone
   `phpinfo()` publicamente: abrilo una vez para verificar la version de PHP
   (hace falta **8.2 o superior** por Livewire 4) y borralo.
7. Hacer el chmod de la seccion 5.
8. Entrar a `https://techone.tis.cs.umss.edu.bo/`.

---

## 7. Despliegues siguientes (el dia a dia)

1. Editar en la maquina.
2. `.\tools\build-deploy.ps1`
3. net2ftp -> subir `deploy\1_raiz.zip` y `deploy\2_public_html.zip` ->
   descomprimir **en la raiz**.
4. Recargar el sitio con **Ctrl+F5** (a veces el cache del navegador).
5. Si tocaste `public/`, los assets nuevos llegan en `2_public_html.zip`; si
   tocaste `resources/`, van en `1_raiz.zip`. Casi todo el codigo va en el
   primero.

### Archivos que casi nunca hay que tocar

| Archivo | Frecuencia |
|---|---|
| `vendor/` | solo si corres `composer install` / `composer update` |
| `.env` | solo si cambian credenciales o `APP_KEY` |
| `public_html/build/` | cada vez que compiles assets: `npm run build` y re-empaquetar |
| `bootstrap/cache/` | no subirlo. Laravel lo regenera |

---

## 8. La base de datos

Se administra **enteramente por phpPgAdmin**, no hay linea de comandos.

| Que | Donde |
|---|---|
| **Todo de una vez: esquema + datos + sequences** | `database/sql/000_deploy_completo.sql` |
| Esquema solo (11 ENUM + 25 tablas) | `database/sql/001_schema.sql` |
| Tablas de Laravel (`users`, `sessions`, `cache`, `jobs`) | `database/sql/003_tablas_laravel.sql` |
| Datos de prueba basicos | `database/sql/002_seed_data.sql` |
| Parche idempotente sobre una base ya cargada | `database/sql/004_actualizar_usuario.sql` |
| Verificacion | `database/sql/verificar.sql` |

> **Para levantar una base nueva, usa `000_deploy_completo.sql`.** Es un
> `pg_dump` de la base de desarrollo local, asi que trae el mismo esquema y
> **todos** los datos reales (54 estudiantes, 5 examenes, 31 asistencias...),
> no solo el seed de 5 filas. Los `001`/`002`/`003` quedan como referencia y
> para entender que hace cada bloque.
>
> El `000` sale de `pg_dump --inserts`: el flag es obligatorio porque sin el
> pg_dump escribe `COPY FROM stdin`, que phpPgAdmin no sabe ejecutar. Para
> regenerarlo despues de cambiar la base local:
>
> ```powershell
> $env:PGPASSWORD = '<clave local>'
> & 'C:\Program Files\PostgreSQL\15\bin\pg_dump.exe' -h 127.0.0.1 -U techone -d techone_db `
>     --no-owner --no-privileges --encoding=UTF8 --inserts `
>     --file='database\sql\000_deploy_completo.sql'
> ```
>
> Y hay que quitarle a mano las lineas `\restrict` / `\unrestrict` que pg_dump
> 15.19 agrega al principio y al final: son meta-comandos de psql y phpPgAdmin
> los toma como SQL y falla.

### Alternativa: el seeder de Laravel

Si la base ya tiene el esquema pero queres cargar solo los datos, esta
`database/seeders/DeploySeeder.php`: es el snapshot de la base local generado
por `php tools/generate-deploy-seeder.php`. En local:

```bash
php artisan db:seed --class=DeploySeeder
```

Es **destructivo** (hace `TRUNCATE ... CASCADE` antes de insertar) y solo
sirve donde hay terminal. En el servidor no la vas a poder correr: ahi va el
`000_deploy_completo.sql`.


### Regla de oro de phpPgAdmin: desmarcar "Paginar resultados"

Con esa casilla marcada, phpPgAdmin envuelve lo que pegas dentro de:

```sql
SELECT COUNT(*) AS total FROM ( ...lo que escribiste... ) AS sub
```

Un `CREATE TABLE` no puede ser un subquery, asi que **todo** falla con
`syntax error at or near "CREATE"`, aunque el SQL este impecable. El mensaje
es engaÃ±oso: el `LINE 9` que muestra es la linea 9 de tu archivo, no un
problema de esa linea.

La excepcion: `verificar.sql` se puede correr con la casilla
marcada o sin marcar, porque es todo `SELECT`.

### Verificar que quedo bien

Correr `database/sql/verificar.sql`. Debe dar:

| Bloque | Resultado esperado |
|---|---|
| 1 | `base_datos = techone_db`, `usuario_conectado = techone` |
| 2 | 25 filas, todas `existe = true` |
| 3 | 11 filas, los ENUM con sus etiquetas |
| 4 | 25 filas. Con el `000_deploy_completo.sql`: `estudiante` 54, `estudiante_examen` 53, `registro_asistencia` 31, `central_riesgo` 4, `notificacion_docente` 4, `tipo_gestion` 4, `rol` 2, y el resto 5. Con el `002_seed_data.sql` viejo todas dan 5 |
| 5 | 5 filas, todas `huerfanos = 0` |
| 6 | 4 filas de datos cruzados |
| 7 | 4 filas (`0001_01_01_000000_create_users_table`, `..._create_cache_table`, `..._create_jobs_table`, `2026_09_27_000001_migracion_servidor_oficial`). Si la tabla no existe es que cargaste solo el `001`/`002` |

La base completa son **34 tablas**: las 25 del proyecto, las 8 de Laravel y la
tabla `migrations`. Para comprobar solo las 8 de Laravel:

```sql
SELECT count(*) AS tablas_laravel
FROM information_schema.tables
WHERE table_schema = 'public'
  AND table_name IN ('users','password_reset_tokens','sessions',
                     'cache','cache_locks','jobs','job_batches','failed_jobs');
```

---

## 9. Problemas comunes

Todos estos seolvableon durante el primer despliegue. estan ordenados por el
sintoma que se ve en el navegador.

### "Oops! An Error Occurred" sin detalle

Es Laravel con `APP_DEBUG=false`, que es lo correcto en produccion pero oculta
la causa. Para diagnosticar: poner `APP_DEBUG=true` en el `.env` del servidor,
recargar, y **copiar solo el mensaje y el `archivo:linea`** (no la seccion de
entorno: ahi aparece la contrasena de la base). Volver a `false` despues.

### `Vite manifest not found at: .../public/build/manifest.json`

Laravel busca los assets en `public_path('build/manifest.json')`, y
`public_path()` apunta a una carpeta llamada `public`, que en el servidor se
llama `public_html`. Laravel 11 no tiene variable de entorno para cambiar eso:
hay que declararlo en `bootstrap/app.php`:

```php
$publicPath = is_dir($basePath.'/public_html') ? $basePath.'/public_html' : $basePath.'/public';

$app = Application::configure(basePath: $basePath)->...->create();

$app->usePublicPath($publicPath);
$app->instance('path.public', $publicPath);

return $app;
```

Va **despues** de `create()`, no encadenado en el builder:
`ApplicationBuilder` no reenvia `usePublicPath()` y tira un error fatal.

### `file_get_contents(.../composer.json): No such file or directory`

Falta `composer.json` en la raiz. Lo lee `Application::getNamespace()`, que se
llama desde el compilador de componentes Blade cuando encuentra un `<x-...>`
en una vista. Se agradece en este proyecto: `layouts/app.blade.php` usa
`<x-ui.sidebar>`, asi que sin `composer.json` **ninguna** pagina propia
funciona. (La pantalla de bienvenida de Laravel si funciona, porque es HTML
puro. No confundirse.)

### `Permission denied` sobre `storage/logs/laravel.log` o `storage/framework/views/*.php`

Falta el chmod. Ver seccion 5.

### Sigue apareciendo la pantalla de bienvenida de WebTIS

`public_html/index.html` no se borro. Apache lo sirve antes que `index.php`.
Borrarlo.

### No cambia nada despues de subir un ZIP

- Se descomprimio **dentro** de una carpeta en vez de en la raiz. El archivo
  tiene que terminar en `/hosting/leticia/techone/app/...`, no en
  `/hosting/leticia/techone/app/app/...`.
- Se subio el ZIP pero no se eligio la opcion de descomprimir.
- Cache del navegador: `Ctrl+F5`.
- Las vistas de Blade se cachean en `storage/framework/views`. Si se suben
  cambios en `.blade.php` y no se ven, es porque el servidor no puede
  recompilar (falta el chmod) o porque la copia local trae vistas viejas. El
  script de empaquetado limpia `storage/` justamente para que no pase.

### Error de conexion a la base

Casi siempre es que el `.env` no llego. net2ftp puede esconder los archivos que
empiezan con punto en el listado. Verificar que `.env` exista en la raiz.

Sin `.env`, `config/database.php` cae a su valor por defecto, que en Laravel 11
es **SQLite**, no PostgreSQL. La aplicacion arranca igual (si las vistas no
tocan la base) y por eso el fallo es dificil de detectar.

### `Route [materias.detalle] not defined`

Bug del codigo, no del despliegue: `resources/views/pages/materias.blade.php`
enlaza a `route('materias.detalle', $codigo)` pero esa ruta no existe en
`routes/web.php`. La vista de detalle que le corresponde es
`resources/views/pages/materia-estudiantes.blade.php`, que tampoco esta
ruteada. Rompe en local y en el servidor por igual.

---

## 10. Referencia rapida

| Quiero... | Hago esto |
|---|---|
| Subir un cambio | `.\tools\build-deploy.ps1` -> subir los 2 ZIP -> descomprimir en la raiz |
| Ver un error 500 | `APP_DEBUG=true` en el servidor, recargar, leer el mensaje, volver a `false` |
| Cambiar una credencial | Edit `deploy/.env.server` y despues Edit `.env` en net2ftp |
| Recompilar los assets | `npm run build` -> `.\tools\build-deploy.ps1` -> subir `2_public_html.zip` |
| Actualizar dependencias PHP | `composer update` -> subir `vendor/` y `1_raiz.zip` |
| Correr SQL | phpPgAdmin -> pestana SQL -> **desmarcar "Paginar resultados"** |
| Comprobar la base | `database/sql/verificar.sql` en phpPgAdmin |
| Levantar una base nueva | phpPgAdmin -> `database/sql/000_deploy_completo.sql` |
| Actualizar el dump de la base | `pg_dump --inserts` (ver seccion 8) y quitar `\restrict`/`\unrestrict` |
| Actualizar el seeder | `php tools/generate-deploy-seeder.php` |

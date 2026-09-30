
Guia de Instalacion Rapida - Stack TALL + Herramientas de Calidad

T1 - Tech One SRL
**Entorno:** Windows 10 / 11

 **Importante**: El orden de instalacion no es arbitrario. Cada herramienta depende de que la anterior este correctamente configurada.

## BAJAR DEL REPO

Si ya bajaste el repo, asegúrate de cumplir con tener instalado PHP, Composer, npm, node.js y PostgreSQL para proseguir. Luego de haber clonado el repo sigue los siguientes pasos:

1. Desde la raíz del proyecto, ejecutar: `npm install` - instalará las dependencias de flowbite
2. Desde la raíz del proyecto, ejecutar: 

---
## XAMPP / PHP 8.2

1. Descargar el instalador con **PHP 8.2.12** desde: [https://www.apachefriends.org/](https://www.apachefriends.org/)
2. Habilitar únicamente la casilla de PHP

---
## COMPOSER: 

1. Descargar el instalador desde: [https://getcomposer.org/Composer-Setup.exe](https://getcomposer.org/Composer-Setup.exe)
2. En la ventana **"Settings Check"**, verificar que la ruta apunte al PHP de XAMPP: C:\xampp\php\php.exe
3. Mantener habilitada la opción **"Add this PHP to your path?"** para que el comando `php` esté disponible globalmente.
4. Verificar la instalación: composer --version

---
## PostgreSQL 15.19 (base de datos local)

Docker no se usa. El proyecto corre contra un **PostgreSQL instalado
directamente en Windows**, que es lo mismo que corre en el servidor de la
universidad. Asi el `.env` es identico en los dos lados y nunca hay que
cambiar de archivo.

1. Descargar el instalador de la versión **15.19** desde:  [https://www.enterprisedb.com/downloads/postgres-postgresql-downloads](https://www.enterprisedb.com/downloads/postgres-postgresql-downloads)
2. Durante la instalación, mantener el puerto por defecto: **5432**
3. **Registrar la contraseña del usuario `postgres`.** Es la del asistente de
   instalación, no una que elija el proyecto. Si se te olvida, hay que
   resetearla (ver abajo).
4. Crear el rol y la base que usa la aplicación, con las mismas credenciales
   que el servidor oficial:

```sql
CREATE ROLE techone WITH LOGIN PASSWORD 'nFNiJunyQ9szGaE';
CREATE DATABASE techone_db OWNER techone ENCODING 'UTF8';
```

5. Cargar el esquema. Local se hace por migraciones, que es lo que corre
   Laravel de verdad:

```bash
php artisan migrate
```

Eso crea las 25 tablas del proyecto **y** las 8 de Laravel (`users`,
`sessions`, `cache`, `jobs`, etc.).

6. Cargar los datos de prueba. Las migraciones no siembran datos, y el seed es
   SQL:

```bash
psql -U techone -h 127.0.0.1 -d techone_db -f database/sql/002_seed_data.sql
```

7. Comprobar:

```bash
psql -U techone -h 127.0.0.1 -d techone_db -f database/sql/verificar.sql
php artisan db:show
```

Debe dar 34 tablas, 11 ENUM, 5 filas por tabla y 0 huerfanos.

### Si te olvidaste la contraseña de `postgres`

No se puede recuperar. Se resetea en cuatro pasos, con PowerShell **como
administrador** (click derecho > Ejecutar como administrador):

```powershell
# 1. cambiar a trust las 2 lineas "host ... 127.0.0.1/32" y "::1/128"
notepad "C:\Program Files\PostgreSQL\15\data\pg_hba.conf"
# 2. aplicar el cambio sin reiniciar el servicio
& "C:\Program Files\PostgreSQL\15\bin\pg_ctl.exe" reload -D "C:\Program Files\PostgreSQL\15\data"
# 3. cambiar la clave y crear rol + base
psql -U postgres -h 127.0.0.1 -d postgres -c "ALTER ROLE postgres PASSWORD 'tu-clave';"
# 4. restaurar pg_hba.conf a scram-sha-256 y recargar otra vez
```

Devolve las dos lineas a `scram-sha-256` antes de seguir trabajando: con
`trust`, cualquiera que se siente en la PC entra como cualquier usuario.

### Cuidado con el seed

`database/sql/002_seed_data.sql` **no es idempotente**: son 25 `INSERT` sin
`ON CONFLICT`. Correrlo dos veces duplica todos los datos. Para empezar de
cero:

```sql
DROP DATABASE techone_db;
CREATE DATABASE techone_db OWNER techone ENCODING 'UTF8';
```

y volver a correr los pasos 5 y 6.

### Detalle del `.env`

`config/database.php` tiene `sqlite` como valor por defecto de `DB_CONNECTION`.
Si el `.env` no carga, Laravel arranca igual y se conecta a SQLite **sin dar
ningun error**. Si ves que una consulta falla con "no such table", revisá que
`DB_CONNECTION=pgsql` esté en el `.env`.

Nota: el puerto 5432 debe coincidir con el del resto del equipo para evitar conflictos al compartir el archivo.

---
## Laravel / Livewire

1. Instalar el manejador global de Laravel: composer global require laravel/installer
2. Crear proyecto: composer create-project laravel/laravel:^11.0 tis-project
3. Desde la Raíz del proyecto `cd tis-project` composer require livewire/livewire
4. Confirmar la version de laravel con `php artisan --version`
5. Para probar que el servidor da: `php artisan serve`

Nota: Para instalar laravel 11, colocar lo siguiente en un archivo config.son en la ruta: C:\Users\YourUsername\AppData\Roaming\Composer\config.json

{
    "config": {
        "policy": {
            "advisories": {
                "ignore-id": [
				  "PKSA-m5cs-t1y6-qpcs",
				  "PKSA-3r5d-mb8f-1qw9",
				  "PKSA-mdq4-51ck-6kdq",
				  "PKSA-8qx3-n5y5-vvnd",
				  "PKSA-q46n-4fdk-zjr4",
				  "PKSA-qzrn-rnz3-85w1",
				  "PKSA-w7xr-vk7n-rstm"
				]
            }
        }
    }
}

---
## Flowbite

1. npm install flowbite --save
2. Importar el tema por defecto en `resources/css/app.css`:
   `@import "flowbite/src/themes/default";`
3. Registrar el plugin de Flowbite en el mismo archivo:
   `@plugin "flowbite/plugin";`
4. Incluir el script de Flowbite en la plantilla base del layout (resources/views/components/layouts/app.blade.php), agregando la etiqueta `<script src="https://cdn.jsdelivr.net/npm/flowbite@4.0.1/dist/flowbite.min.js"></script>` inmediatamente antes del cierre de la etiqueta `<body>`

Nota: Si no aparece la ruta de app.blade.php, ejecutar php artisan livewire:layout

--- 
## PHPSTAN

1. composer require --dev larastan/larastan

Nota: El resto de las herramientas de verificación automática no son compatibles con la versión de php 8.2 que se esta utilizando. 

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
## PostgreSQL 15.19

1. Descargar el instalador de la versión **15.19** desde:  [https://www.enterprisedb.com/downloads/postgres-postgresql-downloads](https://www.enterprisedb.com/downloads/postgres-postgresql-downloads)
2. Durante la instalación, mantener el puerto por defecto: **5432**
3. Registrar la **contraseña del usuario `postgres`** (será necesaria para conectar Laravel).
4. Verificar la instalación:

Nota: El puerto 5432 debe coincidir con el del resto del equipo para evitar conflictos al compartir el archivo

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
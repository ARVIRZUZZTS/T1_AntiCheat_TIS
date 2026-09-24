
Guia de Instalacion - Base de Datos con Docker (PostgreSQL)

T1 - Tech One SRL
**Entorno:** Windows 10/11 y Linux (multiplataforma)

**Alcance de este manual**: este documento cubre unicamente el contenedor de
**PostgreSQL 15** usado para desarrollo local. PHP, Composer y Node.js se
siguen instalando de forma nativa segun `INSTALACION.md` — Docker aqui
reemplaza solo la instalacion manual de PostgreSQL.

---
## Requisitos previos

- Repositorio clonado y dependencias de PHP/Node ya instaladas (`composer install`,
  `npm install`) segun `INSTALACION.md`.
- Archivo `.env` creado a partir de `.env.example` (`cp .env.example .env` en Linux/Mac,
  `copy .env.example .env` en Windows).

---
## 1. Instalar Docker

### Windows 10/11

1. Descargar **Docker Desktop** desde: [https://www.docker.com/products/docker-desktop/](https://www.docker.com/products/docker-desktop/)
2. Durante la instalacion, dejar habilitado **WSL 2** (Docker Desktop lo pide automaticamente
   si no esta activo; seguir el asistente que ofrece instalarlo).
3. Reiniciar la PC si el instalador lo solicita.
4. Abrir Docker Desktop y esperar a que el icono de la ballena indique "Engine running".
5. Verificar en PowerShell o CMD:
   ```
   docker --version
   docker compose version
   ```

### Linux (Debian/Ubuntu)

1. Instalar Docker Engine desde el repositorio oficial (evitar el paquete `docker.io` de
   los repos de la distro, suele estar desactualizado):
   ```bash
   sudo apt update
   sudo apt install -y ca-certificates curl gnupg
   sudo install -m 0755 -d /etc/apt/keyrings
   curl -fsSL https://download.docker.com/linux/debian/gpg | sudo gpg --dearmor -o /etc/apt/keyrings/docker.gpg
   sudo chmod a+r /etc/apt/keyrings/docker.gpg
   echo \
     "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/debian \
     $(. /etc/os-release && echo "$VERSION_CODENAME") stable" | \
     sudo tee /etc/apt/sources.list.d/docker.list > /dev/null
   sudo apt update
   sudo apt install -y docker-ce docker-ce-cli containerd.io docker-compose-plugin
   ```
   (Para Ubuntu, cambiar `linux/debian` por `linux/ubuntu` en las dos URLs.)
2. Permitir usar Docker sin `sudo` (opcional, requiere cerrar sesion y volver a entrar):
   ```bash
   sudo usermod -aG docker $USER
   ```
3. Verificar:
   ```bash
   docker --version
   docker compose version
   ```

---
## 2. Configurar el `.env` para el contenedor

El `docker-compose.yml` del repositorio lee las credenciales de la base de datos
directamente desde el `.env` del proyecto (mismo archivo que usa Laravel). Editar
estas lineas en `.env`:

```
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5434
DB_DATABASE=t1_anticheat
DB_USERNAME=t1_anticheat
DB_PASSWORD=t1_anticheat
```

> **`DB_PORT`**: `5434` es el puerto usado en este equipo porque `5432`/`5433` ya
> estaban ocupados por otros proyectos. Si en tu maquina esos puertos estan libres,
> podes dejar `5432` (el puerto estandar de PostgreSQL, el mismo que documenta
> `arquitectura.md`). Si al levantar el contenedor da error de *"port is already
> allocated"*, cambia `DB_PORT` a otro puerto libre (ej. `5435`, `5436`...) y vuelve
> a intentar — no hace falta tocar `docker-compose.yml`, el valor se toma del `.env`.

---
## 3. Levantar el contenedor

Desde la raiz del proyecto:

```bash
docker compose up -d
```

Esto:
- Descarga la imagen `postgres:15` (solo la primera vez).
- Crea la base `t1_anticheat` y ejecuta automaticamente el script
  `docker/postgres/init/001_create_schema.sql` (solo la **primera vez** que se crea
  el volumen — si el volumen ya existe, el script no se vuelve a correr).
- Deja el contenedor corriendo en segundo plano, escuchando en `DB_PORT`.

Verificar que quedo sano:

```bash
docker compose ps
```

Deberia verse `t1_anticheat_postgres` con estado `Up ... (healthy)`.

---
## 4. Verificar la conexion desde Laravel

```bash
php artisan db:show
```

Si todo esta bien, muestra la version de PostgreSQL, la base `t1_anticheat` y el
listado de tablas creadas por el script de esquema.

Tambien se puede entrar directo a la base con `psql` dentro del contenedor:

```bash
docker exec -it t1_anticheat_postgres psql -U t1_anticheat -d t1_anticheat
```

(salir con `\q`)

---
## 5. Comandos utiles del dia a dia

| Accion | Comando |
|---|---|
| Levantar el contenedor | `docker compose up -d` |
| Ver logs en vivo | `docker compose logs -f postgres` |
| Detener el contenedor (conserva los datos) | `docker compose down` |
| Detener y **borrar** los datos (reinicia desde cero, vuelve a correr el script de esquema) | `docker compose down -v` |
| Ver estado / salud del contenedor | `docker compose ps` |
| Entrar a una consola `psql` | `docker exec -it t1_anticheat_postgres psql -U t1_anticheat -d t1_anticheat` |

---
## 6. Problemas comunes

**"port is already allocated" al hacer `docker compose up -d`**
Otro proceso (Postgres nativo, otro proyecto en Docker) ya usa ese puerto en tu maquina.
Cambia `DB_PORT` en `.env` a un puerto libre y repite `docker compose up -d`.

**Cambie el script `docker/postgres/init/001_create_schema.sql` pero no veo el cambio**
El script solo se ejecuta la primera vez que se crea el volumen de datos. Para forzar
que se vuelva a correr: `docker compose down -v` (borra los datos actuales) y luego
`docker compose up -d` de nuevo.

**Laravel no conecta (`could not find driver` o similar)**
Verificar que la extension `pdo_pgsql` de PHP este instalada (`php -m | grep pgsql`).
Ver `INSTALACION.md` para la instalacion de PHP y sus extensiones.

---
## 7. Nota sobre alcance

Este `docker-compose.yml` es para **desarrollo local unicamente**. El entorno de
produccion (deploy) se definira aparte cuando se confirme donde y como se va a
hostear el proyecto — no se debe reutilizar este archivo tal cual para el servidor
(puertos expuestos, credenciales de ejemplo, sin variables de entorno seguras).

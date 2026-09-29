-- ============================================================================
--  TECH ONE / T1 - TABLAS DE LARAVEL
--  Motor: PostgreSQL
--  Para: https://techone.tis.cs.umss.edu.bo/phppgadmin/  ->  SQL
--
--  ------------------------------------------------------------------
--  PARA QUE ESTE ARCHIVO EXISTE
--  ------------------------------------------------------------------
--  001_schema.sql creo las 25 tablas del proyecto, pero no las 8 que
--  Laravel da por defecto: users, password_reset_tokens, sessions, cache,
--  cache_locks, jobs, job_batches y failed_jobs.
--
--  En tu maquina las crea "php artisan migrate". En el servidor no hay
--  terminal, asi que van por phpPgAdmin. Este archivo es la salida de
--  "php artisan migrate --pretend", osea que es exactamente lo mismo que
--  corre Laravel, no una copia a mano.
--
--  ------------------------------------------------------------------
--  COMO USAR
--  ------------------------------------------------------------------
--  1. Desmarca "Paginar resultados" / "Paginate results". Con la casilla
--     marcada phpPgAdmin envuelve el script en
--     SELECT COUNT(*) AS total FROM ( ... ) AS sub y todo falla con
--     "syntax error at or near CREATE".
--
--  2. Dale "Ejecutar". No cambia ninguna de las 25 tablas existentes:
--     solo agrega estas 8. Es seguro correrlo dos veces (todo lleva
--     IF NOT EXISTS).
--
--  ------------------------------------------------------------------
--  SI FALLA
--  ------------------------------------------------------------------
--  Si tira "relation already exists" para alguna tabla, es porque esa
--  parte ya corrio. No es un problema: las demas lineas se siguen
--  ejecutando.
-- ============================================================================


-- ---------------------------------------------------------------------------
--  USUARIOS DE LA APLICACION (Laravel)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id                bigserial PRIMARY KEY,
    name              varchar(255) NOT NULL,
    email             varchar(255) NOT NULL,
    email_verified_at timestamp    NULL,
    password          varchar(255) NOT NULL,
    remember_token    varchar(100) NULL,
    created_at        timestamp    NULL,
    updated_at        timestamp    NULL
);

CREATE UNIQUE INDEX IF NOT EXISTS users_email_unique ON users (email);


-- ---------------------------------------------------------------------------
--  RESET DE CONTRASENA
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS password_reset_tokens (
    email      varchar(255) PRIMARY KEY,
    token      varchar(255) NOT NULL,
    created_at timestamp    NULL
);


-- ---------------------------------------------------------------------------
--  SESIONES (driver "file", pero Laravel la pide igual para el driver "database")
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS sessions (
    id            varchar(255) PRIMARY KEY,
    user_id       bigint      NULL,
    ip_address    varchar(45) NULL,
    user_agent    text        NULL,
    payload       text        NOT NULL,
    last_activity integer     NOT NULL
);

CREATE INDEX IF NOT EXISTS sessions_user_id_index       ON sessions (user_id);
CREATE INDEX IF NOT EXISTS sessions_last_activity_index ON sessions (last_activity);


-- ---------------------------------------------------------------------------
--  CACHE (driver "file", igual que arriba)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS cache (
    key        varchar(255) PRIMARY KEY,
    value      text         NOT NULL,
    expiration integer      NOT NULL
);

CREATE TABLE IF NOT EXISTS cache_locks (
    key        varchar(255) PRIMARY KEY,
    owner      varchar(255) NOT NULL,
    expiration integer      NOT NULL
);


-- ---------------------------------------------------------------------------
--  COLAS DE TRABAJO (driver "sync", se crean para poder cambiarlo despues)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS jobs (
    id           bigserial PRIMARY KEY,
    queue        varchar(255) NOT NULL,
    payload      text         NOT NULL,
    attempts     smallint     NOT NULL,
    reserved_at  integer      NULL,
    available_at integer      NOT NULL,
    created_at   integer      NOT NULL
);

CREATE INDEX IF NOT EXISTS jobs_queue_index ON jobs (queue);

CREATE TABLE IF NOT EXISTS job_batches (
    id              varchar(255) PRIMARY KEY,
    name            varchar(255) NOT NULL,
    total_jobs      integer      NOT NULL,
    pending_jobs    integer      NOT NULL,
    failed_jobs     integer      NOT NULL,
    failed_job_ids  text         NOT NULL,
    options         text         NULL,
    cancelled_at    integer      NULL,
    created_at      integer      NOT NULL,
    finished_at     integer      NULL
);

CREATE TABLE IF NOT EXISTS failed_jobs (
    id         bigserial PRIMARY KEY,
    uuid       varchar(255) NOT NULL,
    connection text         NOT NULL,
    queue      text         NOT NULL,
    payload    text         NOT NULL,
    exception  text         NOT NULL,
    failed_at  timestamp    NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE UNIQUE INDEX IF NOT EXISTS failed_jobs_uuid_unique ON failed_jobs (uuid);


-- ============================================================================
--  COMPROBACION
--  Debe devolver 8 filas.
-- ============================================================================
--  SELECT count(*) AS tablas_laravel
--  FROM information_schema.tables
--  WHERE table_schema = 'public'
--    AND table_name IN ('users','password_reset_tokens','sessions',
--                       'cache','cache_locks','jobs','job_batches','failed_jobs');

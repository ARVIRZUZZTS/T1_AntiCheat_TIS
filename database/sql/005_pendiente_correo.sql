-- =============================================================================
-- 005_pendiente_correo.sql
-- Parche para la BD de deploy (PostgreSQL WebTIS): agrega el valor
-- 'pendiente_correo' al CHECK de usuario.estado_cuenta (migracion
-- 2026_11_23_000001_agregar_pendiente_correo_a_estado_cuenta) y registra
-- dicha migracion en la tabla migrations.
--
-- Contexto: el dump database/sql/000_deploy_completo.sql quedo desactualizado
-- y solo permite 'pendiente', 'habilitada', 'rechazada'. El flujo de
-- verificacion de correo (HU 27) requiere 'pendiente_correo'.
--
-- Ejecutar en phpPgAdmin sobre la BD techone_db (o aplicar antes de importar
-- el dump regenerado). Seguro de re-ejecutar: usa IF EXISTS / NOT EXISTS.
-- =============================================================================

BEGIN;

-- 1. Reemplazar el CHECK de estado_cuenta para incluir 'pendiente_correo'
ALTER TABLE usuario DROP CONSTRAINT IF EXISTS usuario_estado_cuenta_check;

ALTER TABLE usuario
    ADD CONSTRAINT usuario_estado_cuenta_check
    CHECK (estado_cuenta IN ('pendiente', 'pendiente_correo', 'habilitada', 'rechazada'));

-- 2. Registrar la migracion faltante (si no existe ya)
INSERT INTO migrations (migration, batch)
SELECT '2026_11_23_000001_agregar_pendiente_correo_a_estado_cuenta',
       COALESCE((SELECT MAX(batch) FROM migrations), 0) + 1
WHERE NOT EXISTS (
    SELECT 1
    FROM migrations
    WHERE migration = '2026_11_23_000001_agregar_pendiente_correo_a_estado_cuenta'
);

COMMIT;

-- ---------------------------------------------------------------------------
-- Opcional: limpiar tablas de cache/sesiones que vengan del desarrollo.
-- Descomentar si se importo el dump con datos de Supabase.
-- ---------------------------------------------------------------------------
-- TRUNCATE TABLE cache, cache_locks RESTART IDENTITY;
-- DELETE FROM sessions;

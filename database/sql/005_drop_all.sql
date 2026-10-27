-- ============================================================================
--  TECH ONE / T1 - LIMPIEZA COMPLETA DE LA BASE (drop all)
--  Motor: PostgreSQL
--  Uso: ejecutar ANTES de 000_deploy_completo.sql en una base existente.
--  Borra el esquema public completo y lo recrea vacio.
-- ============================================================================

DROP SCHEMA IF EXISTS public CASCADE;
CREATE SCHEMA public;
GRANT ALL ON SCHEMA public TO postgres;
GRANT ALL ON SCHEMA public TO public;

-- ============================================================================
--  TECH ONE / T1 - LIMPIEZA INDIVIDUAL DE OBJETOS (public)
--  Borra tablas, vistas, secuencias, enums y funciones uno por uno.
--  No elimina el esquema public. Ejecutar ANTES de 000_deploy_completo.sql.
-- ============================================================================


-- Vistas


-- Tablas

DROP TABLE IF EXISTS public."ambiente" CASCADE;
DROP TABLE IF EXISTS public."auxiliar_curso" CASCADE;
DROP TABLE IF EXISTS public."cache" CASCADE;
DROP TABLE IF EXISTS public."cache_locks" CASCADE;
DROP TABLE IF EXISTS public."central_riesgo" CASCADE;
DROP TABLE IF EXISTS public."configuracion_examen_docente" CASCADE;
DROP TABLE IF EXISTS public."configuracion_examen_material" CASCADE;
DROP TABLE IF EXISTS public."configuracion_examen_norma" CASCADE;
DROP TABLE IF EXISTS public."configuracion_material_personalizado" CASCADE;
DROP TABLE IF EXISTS public."configuracion_norma_personalizada" CASCADE;
DROP TABLE IF EXISTS public."curso" CASCADE;
DROP TABLE IF EXISTS public."curso_tipo_gestion" CASCADE;
DROP TABLE IF EXISTS public."estudiante" CASCADE;
DROP TABLE IF EXISTS public."estudiante_curso" CASCADE;
DROP TABLE IF EXISTS public."estudiante_examen" CASCADE;
DROP TABLE IF EXISTS public."estudiante_examen_ambiente" CASCADE;
DROP TABLE IF EXISTS public."examen" CASCADE;
DROP TABLE IF EXISTS public."examen_ambiente" CASCADE;
DROP TABLE IF EXISTS public."examen_curso" CASCADE;
DROP TABLE IF EXISTS public."examen_material_permitido" CASCADE;
DROP TABLE IF EXISTS public."examen_norma" CASCADE;
DROP TABLE IF EXISTS public."failed_jobs" CASCADE;
DROP TABLE IF EXISTS public."invitacion_examen_compartido" CASCADE;
DROP TABLE IF EXISTS public."job_batches" CASCADE;
DROP TABLE IF EXISTS public."jobs" CASCADE;
DROP TABLE IF EXISTS public."material" CASCADE;
DROP TABLE IF EXISTS public."material_personalizado" CASCADE;
DROP TABLE IF EXISTS public."migrations" CASCADE;
DROP TABLE IF EXISTS public."norma" CASCADE;
DROP TABLE IF EXISTS public."norma_personalizada" CASCADE;
DROP TABLE IF EXISTS public."notificacion_auxiliar" CASCADE;
DROP TABLE IF EXISTS public."notificacion_docente" CASCADE;
DROP TABLE IF EXISTS public."password_reset_tokens" CASCADE;
DROP TABLE IF EXISTS public."registro_asistencia" CASCADE;
DROP TABLE IF EXISTS public."rol" CASCADE;
DROP TABLE IF EXISTS public."rol_usuario" CASCADE;
DROP TABLE IF EXISTS public."sessions" CASCADE;
DROP TABLE IF EXISTS public."tipo_examen" CASCADE;
DROP TABLE IF EXISTS public."tipo_gestion" CASCADE;
DROP TABLE IF EXISTS public."users" CASCADE;
DROP TABLE IF EXISTS public."usuario" CASCADE;

-- Secuencias

DROP SEQUENCE IF EXISTS public."configuracion_examen_docente_id_configuracion_examen_seq" CASCADE;
DROP SEQUENCE IF EXISTS public."configuracion_material_personaliz_id_configuracion_material_seq" CASCADE;
DROP SEQUENCE IF EXISTS public."configuracion_norma_personalizada_id_configuracion_norma_seq" CASCADE;
DROP SEQUENCE IF EXISTS public."failed_jobs_id_seq" CASCADE;
DROP SEQUENCE IF EXISTS public."jobs_id_seq" CASCADE;
DROP SEQUENCE IF EXISTS public."migrations_id_seq" CASCADE;
DROP SEQUENCE IF EXISTS public."users_id_seq" CASCADE;

-- Enums (tipos)

DROP TYPE IF EXISTS public."curso_estado" CASCADE;
DROP TYPE IF EXISTS public."estado_incidencia" CASCADE;
DROP TYPE IF EXISTS public."estado_notificacion" CASCADE;
DROP TYPE IF EXISTS public."estado_usuario" CASCADE;
DROP TYPE IF EXISTS public."estudiante_examen_estado" CASCADE;
DROP TYPE IF EXISTS public."invitacion_estado" CASCADE;
DROP TYPE IF EXISTS public."motivo" CASCADE;
DROP TYPE IF EXISTS public."nombre_tipo_gestion" CASCADE;
DROP TYPE IF EXISTS public."registrador_tipo" CASCADE;
DROP TYPE IF EXISTS public."roles" CASCADE;
DROP TYPE IF EXISTS public."tipo_examen_nombre" CASCADE;
DROP TYPE IF EXISTS public."tipo_infraccion" CASCADE;

-- Funciones


-- Fin

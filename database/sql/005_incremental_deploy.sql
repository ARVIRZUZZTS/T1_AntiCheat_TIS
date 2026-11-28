-- ============================================================================
--  INCREMENTAL DEPLOY - T1 AntiCheat TIS
--  Delta idempotente para bases ya desplegadas (produccion).
--  Generado automaticamente por tools/export-supabase-sql.php
-- ============================================================================

BEGIN;

DO $$ BEGIN CREATE TYPE motivo AS ENUM ('intento de ingreso no autorizado', 'uso de dispositivos electronicos', 'copia o intercambio de respuestas', 'uso de material no autorizado', 'suplantacion de identidad', 'otro', 'intento de ingreso duplicado', 'aula equivocada', 'intento de ingreso de material no autorizado', 'en examen sin registro de ingreso'); EXCEPTION WHEN duplicate_object THEN NULL; END $$;

CREATE SEQUENCE IF NOT EXISTS "configuracion_examen_docente_id_configuracion_examen_seq";
CREATE SEQUENCE IF NOT EXISTS "configuracion_material_personaliz_id_configuracion_material_seq";
CREATE SEQUENCE IF NOT EXISTS "configuracion_norma_personalizada_id_configuracion_norma_seq";

CREATE TABLE IF NOT EXISTS "auxiliar_examen" (
    "id_auxiliar" integer NOT NULL,
    "id_examen" integer NOT NULL,
    "ver_ingreso" boolean DEFAULT true NOT NULL,
    "ver_monitor" boolean DEFAULT true NOT NULL,
    "asignado_por" integer,
    "fecha_asignacion" timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);
DO $$ BEGIN ALTER TABLE "auxiliar_examen" ADD CONSTRAINT "auxiliar_examen_alguna_pagina_check" CHECK ((ver_ingreso OR ver_monitor)); EXCEPTION WHEN OTHERS THEN NULL; END $$;
DO $$ BEGIN ALTER TABLE "auxiliar_examen" ADD CONSTRAINT "auxiliar_examen_asignado_por_foreign" FOREIGN KEY (asignado_por) REFERENCES usuario(id_usuario) ON DELETE SET NULL; EXCEPTION WHEN OTHERS THEN NULL; END $$;
DO $$ BEGIN ALTER TABLE "auxiliar_examen" ADD CONSTRAINT "auxiliar_examen_id_auxiliar_foreign" FOREIGN KEY (id_auxiliar) REFERENCES usuario(id_usuario) ON DELETE CASCADE; EXCEPTION WHEN OTHERS THEN NULL; END $$;
DO $$ BEGIN ALTER TABLE "auxiliar_examen" ADD CONSTRAINT "auxiliar_examen_id_examen_foreign" FOREIGN KEY (id_examen) REFERENCES examen(id_examen) ON DELETE CASCADE; EXCEPTION WHEN OTHERS THEN NULL; END $$;
DO $$ BEGIN ALTER TABLE "auxiliar_examen" ADD CONSTRAINT "auxiliar_examen_pkey" PRIMARY KEY (id_auxiliar, id_examen); EXCEPTION WHEN OTHERS THEN NULL; END $$;
INSERT INTO "auxiliar_examen" ("id_auxiliar","id_examen","ver_ingreso","ver_monitor","asignado_por","fecha_asignacion") VALUES (14,42,TRUE,FALSE,1,'2026-11-22 12:43:25'),(3,42,TRUE,FALSE,1,'2026-11-22 16:06:52') ON CONFLICT DO NOTHING;

CREATE TABLE IF NOT EXISTS "central_riesgo_motivo" (
    "id_registro" integer NOT NULL,
    "motivo" motivo NOT NULL
);
DO $$ BEGIN ALTER TABLE "central_riesgo_motivo" ADD CONSTRAINT "central_riesgo_motivo_id_registro_fkey" FOREIGN KEY (id_registro) REFERENCES central_riesgo(id_registro) ON DELETE CASCADE; EXCEPTION WHEN OTHERS THEN NULL; END $$;
DO $$ BEGIN ALTER TABLE "central_riesgo_motivo" ADD CONSTRAINT "central_riesgo_motivo_pkey" PRIMARY KEY (id_registro, motivo); EXCEPTION WHEN OTHERS THEN NULL; END $$;
INSERT INTO "central_riesgo_motivo" ("id_registro","motivo") VALUES (21,'intento de ingreso de material no autorizado'::motivo),(25,'copia o intercambio de respuestas'::motivo),(23,'otro'::motivo),(28,'intento de ingreso no autorizado'::motivo),(31,'intento de ingreso no autorizado'::motivo),(1,'otro'::motivo),(13,'intento de ingreso no autorizado'::motivo),(2,'uso de dispositivos electronicos'::motivo),(3,'intento de ingreso no autorizado'::motivo),(4,'uso de material no autorizado'::motivo),(5,'otro'::motivo),(6,'uso de dispositivos electronicos'::motivo),(7,'copia o intercambio de respuestas'::motivo),(8,'uso de material no autorizado'::motivo),(10,'copia o intercambio de respuestas'::motivo),(11,'suplantacion de identidad'::motivo),(12,'intento de ingreso no autorizado'::motivo),(15,'uso de dispositivos electronicos'::motivo),(16,'uso de material no autorizado'::motivo),(18,'suplantacion de identidad'::motivo),(19,'otro'::motivo),(22,'intento de ingreso de material no autorizado'::motivo),(24,'uso de dispositivos electronicos'::motivo),(26,'intento de ingreso no autorizado'::motivo),(27,'aula equivocada'::motivo),(29,'otro'::motivo),(30,'otro'::motivo),(32,'copia o intercambio de respuestas'::motivo),(14,'intento de ingreso no autorizado'::motivo),(17,'copia o intercambio de respuestas'::motivo),(20,'uso de dispositivos electronicos'::motivo) ON CONFLICT DO NOTHING;

CREATE TABLE IF NOT EXISTS "configuracion_examen_docente" (
    "id_configuracion_examen" integer DEFAULT nextval('configuracion_examen_docente_id_configuracion_examen_seq'::regclass) NOT NULL,
    "id_examen" integer NOT NULL,
    "id_docente" integer NOT NULL,
    "created_at" timestamp(0) without time zone,
    "updated_at" timestamp(0) without time zone
);
DO $$ BEGIN ALTER TABLE "configuracion_examen_docente" ADD CONSTRAINT "fk_config_docente" FOREIGN KEY (id_docente) REFERENCES usuario(id_usuario); EXCEPTION WHEN OTHERS THEN NULL; END $$;
DO $$ BEGIN ALTER TABLE "configuracion_examen_docente" ADD CONSTRAINT "fk_config_examen" FOREIGN KEY (id_examen) REFERENCES examen(id_examen); EXCEPTION WHEN OTHERS THEN NULL; END $$;
DO $$ BEGIN ALTER TABLE "configuracion_examen_docente" ADD CONSTRAINT "configuracion_examen_docente_pkey" PRIMARY KEY (id_configuracion_examen); EXCEPTION WHEN OTHERS THEN NULL; END $$;
DO $$ BEGIN ALTER TABLE "configuracion_examen_docente" ADD CONSTRAINT "config_examen_docente_unique" UNIQUE (id_examen, id_docente); EXCEPTION WHEN OTHERS THEN NULL; END $$;
INSERT INTO "configuracion_examen_docente" ("id_configuracion_examen","id_examen","id_docente","created_at","updated_at") VALUES (1,1,1,'2026-10-25 15:23:13','2026-10-25 15:23:13'),(2,9,1,'2026-10-25 15:23:13','2026-10-25 15:23:13'),(3,15,5,'2026-10-25 15:23:13','2026-10-25 15:23:13'),(4,14,1,'2026-10-25 15:23:13','2026-10-25 15:23:13'),(5,11,1,'2026-10-25 15:23:13','2026-10-25 15:23:13'),(6,5,2,'2026-10-25 15:23:13','2026-10-25 15:23:13'),(7,12,5,'2026-10-25 15:23:13','2026-10-25 15:23:13'),(8,1,2,'2026-10-25 15:23:13','2026-10-25 15:23:13'),(9,10,1,'2026-10-25 15:23:13','2026-10-25 15:23:13'),(10,6,1,'2026-10-25 15:23:13','2026-10-25 15:23:13'),(11,4,1,'2026-10-25 15:23:13','2026-10-25 15:23:13'),(12,13,1,'2026-10-25 15:23:13','2026-10-25 15:23:13'),(13,12,1,'2026-10-25 15:23:13','2026-10-25 15:23:13'),(14,13,5,'2026-10-25 15:23:13','2026-10-25 15:23:13'),(15,3,5,'2026-10-25 15:23:13','2026-10-25 15:23:13'),(16,8,1,'2026-10-25 15:23:13','2026-10-25 15:23:13'),(17,7,1,'2026-10-25 15:23:13','2026-10-25 15:23:13'),(18,4,2,'2026-10-25 15:23:13','2026-10-25 15:23:13'),(19,16,2,'2026-10-25 15:23:13','2026-10-25 15:23:13'),(20,2,2,'2026-10-25 15:23:13','2026-10-25 15:23:13'),(21,3,1,'2026-10-25 15:23:13','2026-10-25 15:23:13'),(22,14,5,'2026-10-25 15:23:13','2026-10-25 15:23:13'),(23,15,1,'2026-10-25 15:23:13','2026-10-25 15:23:13'),(29,17,1,'2026-10-25 17:08:11','2026-10-25 17:08:11'),(30,18,1,'2026-10-25 17:08:26','2026-10-25 17:08:26'),(31,19,1,'2026-10-25 17:08:38','2026-10-25 17:08:38'),(32,20,1,'2026-10-25 17:08:50','2026-10-25 17:08:50'),(33,21,1,'2026-10-25 17:09:06','2026-10-25 17:09:06'),(35,22,1,'2026-10-25 17:09:28','2026-10-25 17:09:28'),(36,23,1,'2026-10-25 17:09:39','2026-10-25 17:09:39'),(41,24,1,'2026-10-26 01:13:23','2026-10-26 01:13:23'),(42,25,1,'2026-10-26 19:15:55','2026-10-26 19:15:55'),(43,26,1,'2026-10-26 16:49:16','2026-10-26 16:49:16'),(44,1,5,'2026-10-26 17:13:36','2026-10-26 17:13:36'),(45,26,2,'2026-10-26 18:46:57','2026-10-26 18:46:57'),(46,27,1,'2026-10-27 01:09:37','2026-10-27 01:09:37'),(47,28,1,'2026-10-27 03:04:22','2026-10-27 03:04:22'),(48,29,1,'2026-10-27 03:04:33','2026-10-27 03:04:33'),(49,30,1,'2026-10-27 03:04:44','2026-10-27 03:04:44'),(50,31,1,'2026-10-27 03:04:55','2026-10-27 03:04:55'),(51,32,1,'2026-10-27 03:05:06','2026-10-27 03:05:06'),(52,33,1,'2026-10-27 03:05:17','2026-10-27 03:05:17'),(53,34,1,'2026-10-27 03:05:28','2026-10-27 03:05:28'),(54,35,1,'2026-10-27 10:54:25','2026-10-27 10:54:25'),(55,36,1,'2026-11-04 11:06:35','2026-11-04 11:06:35'),(56,37,1,'2026-11-05 02:52:51','2026-11-05 02:52:51'),(57,38,1,'2026-11-10 00:34:33','2026-11-10 00:34:33'),(58,5,1,'2026-11-09 21:29:16','2026-11-09 21:29:16'),(59,39,1,'2026-11-10 02:54:07','2026-11-10 02:54:07'),(60,40,1,'2026-11-14 05:33:09','2026-11-14 05:33:09') ON CONFLICT DO NOTHING;
INSERT INTO "configuracion_examen_docente" ("id_configuracion_examen","id_examen","id_docente","created_at","updated_at") VALUES (61,41,1,'2026-11-16 01:26:17','2026-11-16 01:26:17'),(62,42,1,'2026-11-19 08:39:04','2026-11-19 08:39:04'),(63,43,1,'2026-11-21 20:10:15','2026-11-21 20:10:15'),(64,44,2,'2026-11-22 16:28:31','2026-11-22 16:28:31'),(65,45,1,'2026-11-23 14:20:24','2026-11-23 14:20:24') ON CONFLICT DO NOTHING;

SELECT setval('configuracion_examen_docente_id_configuracion_examen_seq'::regclass, COALESCE((SELECT MAX("id_configuracion_examen") FROM "configuracion_examen_docente"), 1), EXISTS (SELECT 1 FROM "configuracion_examen_docente"));
CREATE TABLE IF NOT EXISTS "configuracion_examen_material" (
    "id_configuracion_examen" integer NOT NULL,
    "id_material" integer NOT NULL
);
DO $$ BEGIN ALTER TABLE "configuracion_examen_material" ADD CONSTRAINT "fk_config_material_config" FOREIGN KEY (id_configuracion_examen) REFERENCES configuracion_examen_docente(id_configuracion_examen) ON DELETE CASCADE; EXCEPTION WHEN OTHERS THEN NULL; END $$;
DO $$ BEGIN ALTER TABLE "configuracion_examen_material" ADD CONSTRAINT "fk_config_material_material" FOREIGN KEY (id_material) REFERENCES material(id_material); EXCEPTION WHEN OTHERS THEN NULL; END $$;
DO $$ BEGIN ALTER TABLE "configuracion_examen_material" ADD CONSTRAINT "configuracion_examen_material_pkey" PRIMARY KEY (id_configuracion_examen, id_material); EXCEPTION WHEN OTHERS THEN NULL; END $$;
INSERT INTO "configuracion_examen_material" ("id_configuracion_examen","id_material") VALUES (1,1),(2,1),(3,2),(3,3),(4,4),(4,1),(5,1),(5,3),(6,5),(8,1),(9,3),(10,5),(11,4),(15,3),(16,1),(16,2),(17,5),(18,4),(20,2),(21,3),(22,4),(22,1),(23,2),(23,3),(41,1),(42,1),(42,2),(42,3),(42,4),(43,2),(43,4),(44,1),(45,2),(45,4),(46,2),(46,4),(54,1),(56,1),(56,4),(57,1),(57,4),(58,5),(60,4),(61,1),(63,3),(65,4) ON CONFLICT DO NOTHING;

CREATE TABLE IF NOT EXISTS "configuracion_examen_norma" (
    "id_configuracion_examen" integer NOT NULL,
    "id_norma" integer NOT NULL
);
DO $$ BEGIN ALTER TABLE "configuracion_examen_norma" ADD CONSTRAINT "fk_config_norma_config" FOREIGN KEY (id_configuracion_examen) REFERENCES configuracion_examen_docente(id_configuracion_examen) ON DELETE CASCADE; EXCEPTION WHEN OTHERS THEN NULL; END $$;
DO $$ BEGIN ALTER TABLE "configuracion_examen_norma" ADD CONSTRAINT "fk_config_norma_norma" FOREIGN KEY (id_norma) REFERENCES norma(id_norma); EXCEPTION WHEN OTHERS THEN NULL; END $$;
DO $$ BEGIN ALTER TABLE "configuracion_examen_norma" ADD CONSTRAINT "configuracion_examen_norma_pkey" PRIMARY KEY (id_configuracion_examen, id_norma); EXCEPTION WHEN OTHERS THEN NULL; END $$;
INSERT INTO "configuracion_examen_norma" ("id_configuracion_examen","id_norma") VALUES (1,1),(2,1),(3,1),(3,4),(4,2),(4,5),(4,1),(4,4),(4,3),(5,5),(6,5),(8,1),(9,2),(10,1),(11,4),(15,3),(16,1),(17,1),(18,4),(20,2),(21,3),(22,2),(22,5),(22,1),(22,4),(22,3),(23,1),(23,4),(41,4),(43,2),(43,3),(44,1),(45,2),(45,3),(46,1),(54,5),(56,1),(56,4),(57,1),(57,2),(57,4),(58,5),(60,1),(61,4),(63,1),(65,4) ON CONFLICT DO NOTHING;

CREATE TABLE IF NOT EXISTS "configuracion_material_personalizado" (
    "id_configuracion_material" integer DEFAULT nextval('configuracion_material_personaliz_id_configuracion_material_seq'::regclass) NOT NULL,
    "id_configuracion_examen" integer NOT NULL,
    "numero_material" integer NOT NULL,
    "descripcion_material" text NOT NULL
);
DO $$ BEGIN ALTER TABLE "configuracion_material_personalizado" ADD CONSTRAINT "fk_config_material_personalizado" FOREIGN KEY (id_configuracion_examen) REFERENCES configuracion_examen_docente(id_configuracion_examen) ON DELETE CASCADE; EXCEPTION WHEN OTHERS THEN NULL; END $$;
DO $$ BEGIN ALTER TABLE "configuracion_material_personalizado" ADD CONSTRAINT "configuracion_material_personalizado_pkey" PRIMARY KEY (id_configuracion_material); EXCEPTION WHEN OTHERS THEN NULL; END $$;
DO $$ BEGIN ALTER TABLE "configuracion_material_personalizado" ADD CONSTRAINT "config_material_numero_unique" UNIQUE (id_configuracion_examen, numero_material); EXCEPTION WHEN OTHERS THEN NULL; END $$;
INSERT INTO "configuracion_material_personalizado" ("id_configuracion_material","id_configuracion_examen","numero_material","descripcion_material") VALUES (1,5,1,'sd23123'),(2,9,1,'asdada'),(3,12,1,'Calculadora programable'),(4,14,1,'Calculadora programable'),(11,42,1,'adasd') ON CONFLICT DO NOTHING;

SELECT setval('configuracion_material_personaliz_id_configuracion_material_seq'::regclass, COALESCE((SELECT MAX("id_configuracion_material") FROM "configuracion_material_personalizado"), 1), EXISTS (SELECT 1 FROM "configuracion_material_personalizado"));
CREATE TABLE IF NOT EXISTS "configuracion_norma_personalizada" (
    "id_configuracion_norma" integer DEFAULT nextval('configuracion_norma_personalizada_id_configuracion_norma_seq'::regclass) NOT NULL,
    "id_configuracion_examen" integer NOT NULL,
    "numero_norma" integer NOT NULL,
    "descripcion_norma" text NOT NULL
);
DO $$ BEGIN ALTER TABLE "configuracion_norma_personalizada" ADD CONSTRAINT "fk_config_norma_personalizada" FOREIGN KEY (id_configuracion_examen) REFERENCES configuracion_examen_docente(id_configuracion_examen) ON DELETE CASCADE; EXCEPTION WHEN OTHERS THEN NULL; END $$;
DO $$ BEGIN ALTER TABLE "configuracion_norma_personalizada" ADD CONSTRAINT "configuracion_norma_personalizada_pkey" PRIMARY KEY (id_configuracion_norma); EXCEPTION WHEN OTHERS THEN NULL; END $$;
DO $$ BEGIN ALTER TABLE "configuracion_norma_personalizada" ADD CONSTRAINT "config_norma_numero_unique" UNIQUE (id_configuracion_examen, numero_norma); EXCEPTION WHEN OTHERS THEN NULL; END $$;
INSERT INTO "configuracion_norma_personalizada" ("id_configuracion_norma","id_configuracion_examen","numero_norma","descripcion_norma") VALUES (1,1,1,'Se permite el uso de calculadora científica no programable.'),(2,1,2,'No se permite hojas adicionales, usar el reverso del examen.'),(3,5,1,'dffgs'),(4,6,1,'Responder únicamente con bolígrafo de tinta negra o azul.'),(5,6,2,'Desactivar y guardar teléfonos móviles en la mochila.'),(6,8,1,'Se permite el uso de calculadora científica no programable.'),(7,8,2,'No se permite hojas adicionales, usar el reverso del examen.'),(8,9,1,'dadad'),(9,11,1,'Uso obligatorio de bata de laboratorio y gafas de seguridad.'),(10,11,2,'Entregar el reporte de datos antes de salir del aula.'),(11,12,1,'Ingreso sin mochilas'),(12,14,1,'Ingreso sin mochilas'),(13,15,1,'Tiempo estricto de 45 minutos. No hay prórroga.'),(14,18,1,'Uso obligatorio de bata de laboratorio y gafas de seguridad.'),(15,18,2,'Entregar el reporte de datos antes de salir del aula.'),(16,20,1,'El código debe compilar sin errores para ser evaluado.'),(17,20,2,'Prohibido el acceso a internet o repositorios externos.'),(18,21,1,'Tiempo estricto de 45 minutos. No hay prórroga.'),(20,42,1,'adsasdsa'),(21,44,1,'Se permite el uso de calculadora científica no programable.'),(22,44,2,'No se permite hojas adicionales, usar el reverso del examen.'),(23,58,1,'Responder únicamente con bolígrafo de tinta negra o azul.'),(24,58,2,'Desactivar y guardar teléfonos móviles en la mochila.') ON CONFLICT DO NOTHING;

SELECT setval('configuracion_norma_personalizada_id_configuracion_norma_seq'::regclass, COALESCE((SELECT MAX("id_configuracion_norma") FROM "configuracion_norma_personalizada"), 1), EXISTS (SELECT 1 FROM "configuracion_norma_personalizada"));
CREATE TABLE IF NOT EXISTS "material_personalizado" (
    "id_examen" integer NOT NULL,
    "numero_material" integer NOT NULL,
    "descripcion_material" text NOT NULL
);
DO $$ BEGIN ALTER TABLE "material_personalizado" ADD CONSTRAINT "fk_material_personalizado_examen" FOREIGN KEY (id_examen) REFERENCES examen(id_examen); EXCEPTION WHEN OTHERS THEN NULL; END $$;
INSERT INTO "material_personalizado" ("id_examen","numero_material","descripcion_material") VALUES (10,1,'asdada'),(11,1,'sd23123'),(13,1,'Calculadora programable'),(25,1,'adasd');

CREATE TABLE IF NOT EXISTS "norma_personalizada" (
    "id_examen" integer NOT NULL,
    "numero_norma" integer NOT NULL,
    "descripcion_norma" text NOT NULL
);
DO $$ BEGIN ALTER TABLE "norma_personalizada" ADD CONSTRAINT "fk_norma_personalizada_examen" FOREIGN KEY (id_examen) REFERENCES examen(id_examen); EXCEPTION WHEN OTHERS THEN NULL; END $$;
INSERT INTO "norma_personalizada" ("id_examen","numero_norma","descripcion_norma") VALUES (1,1,'Se permite el uso de calculadora científica no programable.'),(1,2,'No se permite hojas adicionales, usar el reverso del examen.'),(2,1,'El código debe compilar sin errores para ser evaluado.'),(2,2,'Prohibido el acceso a internet o repositorios externos.'),(3,1,'Tiempo estricto de 45 minutos. No hay prórroga.'),(4,1,'Uso obligatorio de bata de laboratorio y gafas de seguridad.'),(4,2,'Entregar el reporte de datos antes de salir del aula.'),(5,1,'Responder únicamente con bolígrafo de tinta negra o azul.'),(5,2,'Desactivar y guardar teléfonos móviles en la mochila.'),(10,1,'dadad'),(11,1,'dffgs'),(13,1,'Ingreso sin mochilas'),(25,1,'adsasdsa');

CREATE TABLE IF NOT EXISTS "validacion_registro" (
    "id_solicitante" integer NOT NULL,
    "id_validador" integer NOT NULL,
    "decision" character varying(11) NOT NULL,
    "motivo" character varying(300),
    "fecha" timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);
DO $$ BEGIN ALTER TABLE "validacion_registro" ADD CONSTRAINT "validacion_registro_decision_check" CHECK (((decision)::text = ANY ((ARRAY['aprobado'::character varying, 'desaprobado'::character varying])::text[]))); EXCEPTION WHEN OTHERS THEN NULL; END $$;
DO $$ BEGIN ALTER TABLE "validacion_registro" ADD CONSTRAINT "validacion_registro_id_solicitante_foreign" FOREIGN KEY (id_solicitante) REFERENCES usuario(id_usuario) ON DELETE CASCADE; EXCEPTION WHEN OTHERS THEN NULL; END $$;
DO $$ BEGIN ALTER TABLE "validacion_registro" ADD CONSTRAINT "validacion_registro_id_validador_foreign" FOREIGN KEY (id_validador) REFERENCES usuario(id_usuario) ON DELETE CASCADE; EXCEPTION WHEN OTHERS THEN NULL; END $$;
DO $$ BEGIN ALTER TABLE "validacion_registro" ADD CONSTRAINT "validacion_registro_pkey" PRIMARY KEY (id_solicitante, id_validador); EXCEPTION WHEN OTHERS THEN NULL; END $$;
INSERT INTO "validacion_registro" ("id_solicitante","id_validador","decision","motivo","fecha") VALUES (6,1,'aprobado',NULL,'2026-11-08 11:49:48'),(6,2,'aprobado',NULL,'2026-11-08 11:54:26'),(7,2,'desaprobado','Hable con el docente y no es su cuenta','2026-11-08 11:54:53'),(8,6,'desaprobado','me cae mal','2026-11-08 22:25:35'),(11,1,'aprobado',NULL,'2026-11-09 14:42:36'),(11,2,'aprobado',NULL,'2026-11-09 14:44:48'),(12,1,'aprobado',NULL,'2026-11-09 14:53:59'),(12,2,'aprobado',NULL,'2026-11-09 14:55:21'),(15,1,'aprobado',NULL,'2026-11-09 21:29:47'),(15,2,'aprobado',NULL,'2026-11-09 21:31:02') ON CONFLICT DO NOTHING;

DO $$ BEGIN ALTER TABLE notificacion_docente ADD COLUMN fecha_evento timestamp(0) without time zone; EXCEPTION WHEN duplicate_column THEN NULL; END $$;

DO $$ BEGIN
  IF EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema='public' AND table_name='central_riesgo' AND column_name='estado_incidencia') THEN
    UPDATE central_riesgo SET tipo_infraccion = 'tramposo' WHERE tipo_infraccion = 'sospechoso' AND estado_incidencia = 'Confirmado';
  END IF;
END $$;
ALTER TABLE central_riesgo DROP CONSTRAINT IF EXISTS fk_cr_confirmador;
ALTER TABLE central_riesgo DROP COLUMN IF EXISTS id_confirmador;
ALTER TABLE central_riesgo DROP COLUMN IF EXISTS estado_incidencia;

DROP TYPE IF EXISTS estado_incidencia;

COMMIT;

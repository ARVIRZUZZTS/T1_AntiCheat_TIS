-- ============================================================================
--  TECH ONE / T1 - DUMP COMPLETO: esquema + datos (public)
--  Generado por tools/export-supabase-sql.php
--  Motor: PostgreSQL (Supabase)
-- ============================================================================

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;


-- =====================
--  ENUMS
-- =====================

CREATE TYPE public.curso_estado AS ENUM (
    'EnCurso', 'Finalizo'
);

CREATE TYPE public.estado_incidencia AS ENUM (
    'Confirmado', 'Pendiente'
);

CREATE TYPE public.estado_notificacion AS ENUM (
    'visto', 'recibido'
);

CREATE TYPE public.estado_usuario AS ENUM (
    'Activo', 'Baja'
);

CREATE TYPE public.estudiante_examen_estado AS ENUM (
    'habilitado', 'deshabilitado'
);

CREATE TYPE public.invitacion_estado AS ENUM (
    'aceptado', 'rechazado', 'pendiente', 'cancelado'
);

CREATE TYPE public.motivo AS ENUM (
    'intento de ingreso no autorizado', 'uso de dispositivos electronicos', 'copia o intercambio de respuestas', 'uso de material no autorizado', 'suplantacion de identidad', 'otro', 'intento de ingreso duplicado', 'aula equivocada', 'intento de ingreso de material no autorizado', 'en examen sin registro de ingreso'
);

CREATE TYPE public.nombre_tipo_gestion AS ENUM (
    'primer semestre', 'invierno', 'segundo semestre', 'verano'
);

CREATE TYPE public.registrador_tipo AS ENUM (
    'docente', 'auxiliar'
);

CREATE TYPE public.roles AS ENUM (
    'docente', 'auxiliar'
);

CREATE TYPE public.tipo_examen_nombre AS ENUM (
    'examen parcial', 'examen final', 'segunda instancia'
);

CREATE TYPE public.tipo_infraccion AS ENUM (
    'tramposo', 'sospechoso'
);


-- =====================
--  TABLAS
-- =====================

CREATE TABLE public.ambiente (
    id_ambiente integer NOT NULL,
    nombre_ambiente varchar(50) NOT NULL,
    PRIMARY KEY ("id_ambiente")
);

CREATE TABLE public.auxiliar_curso (
    id_auxiliar integer NOT NULL,
    id_curso integer NOT NULL,
    estado public.estado_usuario NOT NULL,
    PRIMARY KEY ("id_auxiliar", "id_curso")
);

CREATE TABLE public.cache (
    key varchar(255) NOT NULL,
    value text NOT NULL,
    expiration integer NOT NULL,
    PRIMARY KEY ("key")
);

CREATE TABLE public.cache_locks (
    key varchar(255) NOT NULL,
    owner varchar(255) NOT NULL,
    expiration integer NOT NULL,
    PRIMARY KEY ("key")
);

CREATE TABLE public.central_riesgo (
    id_registro integer NOT NULL,
    id_registrador integer NOT NULL,
    detalle_motivo varchar(255),
    fecha_registro timestamp NOT NULL,
    tipo_infraccion public.tipo_infraccion NOT NULL,
    id_examen integer NOT NULL,
    sis_estudiante varchar(20) NOT NULL,
    motivo public.motivo NOT NULL,
    estado_incidencia public.estado_incidencia NOT NULL DEFAULT 'Pendiente'::estado_incidencia,
    id_confirmador integer,
    id_curso integer,
    id_estudiante_examen integer,
    PRIMARY KEY ("id_registro")
);

CREATE TABLE public.configuracion_examen_docente (
    id_configuracion_examen integer NOT NULL DEFAULT nextval('configuracion_examen_docente_id_configuracion_examen_seq'::regclass),
    id_examen integer NOT NULL,
    id_docente integer NOT NULL,
    created_at timestamp,
    updated_at timestamp,
    PRIMARY KEY ("id_configuracion_examen")
);

CREATE TABLE public.configuracion_examen_material (
    id_configuracion_examen integer NOT NULL,
    id_material integer NOT NULL,
    PRIMARY KEY ("id_configuracion_examen", "id_material")
);

CREATE TABLE public.configuracion_examen_norma (
    id_configuracion_examen integer NOT NULL,
    id_norma integer NOT NULL,
    PRIMARY KEY ("id_configuracion_examen", "id_norma")
);

CREATE TABLE public.configuracion_material_personalizado (
    id_configuracion_material integer NOT NULL DEFAULT nextval('configuracion_material_personaliz_id_configuracion_material_seq'::regclass),
    id_configuracion_examen integer NOT NULL,
    numero_material integer NOT NULL,
    descripcion_material text NOT NULL,
    PRIMARY KEY ("id_configuracion_material")
);

CREATE TABLE public.configuracion_norma_personalizada (
    id_configuracion_norma integer NOT NULL DEFAULT nextval('configuracion_norma_personalizada_id_configuracion_norma_seq'::regclass),
    id_configuracion_examen integer NOT NULL,
    numero_norma integer NOT NULL,
    descripcion_norma text NOT NULL,
    PRIMARY KEY ("id_configuracion_norma")
);

CREATE TABLE public.curso (
    id_curso integer NOT NULL,
    nombre_curso varchar(100) NOT NULL,
    sis_doc integer NOT NULL,
    fecha_creacion date,
    estado public.curso_estado NOT NULL,
    id_ambiente integer,
    PRIMARY KEY ("id_curso")
);

CREATE TABLE public.curso_tipo_gestion (
    id_curso integer NOT NULL,
    id_tg integer NOT NULL,
    anio smallint NOT NULL,
    PRIMARY KEY ("id_curso", "id_tg")
);

CREATE TABLE public.estudiante (
    sis_estudiante varchar(20) NOT NULL,
    nombre_estudiante varchar(50) NOT NULL,
    apellido_estudiante varchar(50) NOT NULL,
    carrera varchar(80),
    verificado boolean NOT NULL DEFAULT true,
    PRIMARY KEY ("sis_estudiante")
);

CREATE TABLE public.estudiante_curso (
    sis_estudiante varchar(20) NOT NULL,
    id_curso integer NOT NULL,
    PRIMARY KEY ("sis_estudiante", "id_curso")
);

CREATE TABLE public.estudiante_examen (
    id_estudiante_examen integer NOT NULL,
    sis_estudiante varchar(20) NOT NULL,
    id_examen integer NOT NULL,
    estado public.estudiante_examen_estado NOT NULL,
    motivo varchar(255),
    modificado_por integer,
    fecha_modificacion timestamp,
    id_configuracion_examen integer,
    id_curso integer,
    PRIMARY KEY ("id_estudiante_examen")
);

CREATE TABLE public.estudiante_examen_ambiente (
    id_ee integer NOT NULL,
    id_ambiente integer NOT NULL,
    PRIMARY KEY ("id_ee", "id_ambiente")
);

CREATE TABLE public.examen (
    id_examen integer NOT NULL,
    fecha date,
    hora_inicio time,
    duracion integer,
    creador integer NOT NULL,
    tipo_examen integer,
    nombre_examen varchar(100),
    PRIMARY KEY ("id_examen")
);

CREATE TABLE public.examen_ambiente (
    id_examen integer NOT NULL,
    id_ambiente integer NOT NULL,
    PRIMARY KEY ("id_examen", "id_ambiente")
);

CREATE TABLE public.examen_curso (
    id_examen integer NOT NULL,
    id_curso integer NOT NULL,
    PRIMARY KEY ("id_examen", "id_curso")
);

CREATE TABLE public.examen_material_permitido (
    id_examen integer NOT NULL,
    id_material_permitido integer NOT NULL,
    PRIMARY KEY ("id_examen", "id_material_permitido")
);

CREATE TABLE public.examen_norma (
    id_examen integer NOT NULL,
    id_norma integer NOT NULL,
    PRIMARY KEY ("id_examen", "id_norma")
);

CREATE TABLE public.failed_jobs (
    id bigint NOT NULL DEFAULT nextval('failed_jobs_id_seq'::regclass),
    uuid varchar(255) NOT NULL,
    connection text NOT NULL,
    queue text NOT NULL,
    payload text NOT NULL,
    exception text NOT NULL,
    failed_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY ("id")
);

CREATE TABLE public.invitacion_examen_compartido (
    id_invitacion integer NOT NULL,
    id_docente_invitado integer NOT NULL,
    id_examen integer NOT NULL,
    estado public.invitacion_estado NOT NULL,
    id_docente_invitador integer NOT NULL,
    created_at timestamp NOT NULL,
    updated_at timestamp NOT NULL,
    PRIMARY KEY ("id_invitacion")
);

CREATE TABLE public.job_batches (
    id varchar(255) NOT NULL,
    name varchar(255) NOT NULL,
    total_jobs integer NOT NULL,
    pending_jobs integer NOT NULL,
    failed_jobs integer NOT NULL,
    failed_job_ids text NOT NULL,
    options text,
    cancelled_at integer,
    created_at integer NOT NULL,
    finished_at integer,
    PRIMARY KEY ("id")
);

CREATE TABLE public.jobs (
    id bigint NOT NULL DEFAULT nextval('jobs_id_seq'::regclass),
    queue varchar(255) NOT NULL,
    payload text NOT NULL,
    attempts smallint NOT NULL,
    reserved_at integer,
    available_at integer NOT NULL,
    created_at integer NOT NULL,
    PRIMARY KEY ("id")
);

CREATE TABLE public.material (
    id_material integer NOT NULL,
    descripcion varchar(100) NOT NULL,
    PRIMARY KEY ("id_material")
);

CREATE TABLE public.material_personalizado (
    id_examen integer NOT NULL,
    numero_material integer NOT NULL,
    descripcion_material text NOT NULL
);

CREATE TABLE public.migrations (
    id integer NOT NULL DEFAULT nextval('migrations_id_seq'::regclass),
    migration varchar(255) NOT NULL,
    batch integer NOT NULL,
    PRIMARY KEY ("id")
);

CREATE TABLE public.norma (
    id_norma integer NOT NULL,
    detalle_norma varchar(255) NOT NULL,
    PRIMARY KEY ("id_norma")
);

CREATE TABLE public.norma_personalizada (
    id_examen integer NOT NULL,
    numero_norma integer NOT NULL,
    descripcion_norma text NOT NULL
);

CREATE TABLE public.notificacion_auxiliar (
    id_notificacion integer NOT NULL,
    id_curso integer NOT NULL,
    id_ambiente integer NOT NULL,
    estado public.estado_notificacion NOT NULL,
    PRIMARY KEY ("id_notificacion")
);

CREATE TABLE public.notificacion_docente (
    id_notificacion integer NOT NULL,
    id_central_riesgo integer,
    id_curso integer,
    estado public.estado_notificacion NOT NULL,
    oculta boolean NOT NULL DEFAULT false,
    tipo varchar(255) NOT NULL DEFAULT 'posible_tramposo'::character varying,
    id_docente_destinatario integer,
    id_invitacion integer,
    PRIMARY KEY ("id_notificacion")
);

CREATE TABLE public.password_reset_tokens (
    email varchar(255) NOT NULL,
    token varchar(255) NOT NULL,
    created_at timestamp,
    PRIMARY KEY ("email")
);

CREATE TABLE public.registro_asistencia (
    id_ingreso integer NOT NULL,
    hora_ingreso time,
    id_examen integer NOT NULL,
    id_estudiante varchar(20) NOT NULL,
    id_registrador integer NOT NULL,
    id_estudiante_examen integer,
    PRIMARY KEY ("id_ingreso")
);

CREATE TABLE public.rol (
    id_rol integer NOT NULL,
    nombre_rol public.roles NOT NULL,
    PRIMARY KEY ("id_rol")
);

CREATE TABLE public.rol_usuario (
    id_usuario integer NOT NULL,
    id_rol integer NOT NULL,
    PRIMARY KEY ("id_usuario", "id_rol")
);

CREATE TABLE public.sessions (
    id varchar(255) NOT NULL,
    user_id bigint,
    ip_address varchar(45),
    user_agent text,
    payload text NOT NULL,
    last_activity integer NOT NULL,
    PRIMARY KEY ("id")
);

CREATE TABLE public.tipo_examen (
    id_tipo_examen integer NOT NULL,
    nombre_tipo_examen public.tipo_examen_nombre NOT NULL,
    PRIMARY KEY ("id_tipo_examen")
);

CREATE TABLE public.tipo_gestion (
    id_tipo_gestion integer NOT NULL,
    nombre_tipo_gestion public.nombre_tipo_gestion NOT NULL,
    PRIMARY KEY ("id_tipo_gestion")
);

CREATE TABLE public.users (
    id bigint NOT NULL DEFAULT nextval('users_id_seq'::regclass),
    name varchar(255) NOT NULL,
    email varchar(255) NOT NULL,
    email_verified_at timestamp,
    password varchar(255) NOT NULL,
    remember_token varchar(100),
    created_at timestamp,
    updated_at timestamp,
    PRIMARY KEY ("id")
);

CREATE TABLE public.usuario (
    id_usuario integer NOT NULL,
    cod_sis varchar(20) NOT NULL,
    password varchar(100) NOT NULL,
    nombre_usuario varchar(50) NOT NULL,
    apellido varchar(50) NOT NULL,
    correo varchar(254),
    PRIMARY KEY ("id_usuario")
);


-- =====================
--  SECUENCIAS
-- =====================

CREATE SEQUENCE public.configuracion_examen_docente_id_configuracion_examen_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    MAXVALUE 2147483647
    CACHE 1;

ALTER SEQUENCE public.configuracion_examen_docente_id_configuracion_examen_seq OWNED BY public.configuracion_examen_docente.id_configuracion_examen;

CREATE SEQUENCE public.configuracion_material_personaliz_id_configuracion_material_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    MAXVALUE 2147483647
    CACHE 1;

ALTER SEQUENCE public.configuracion_material_personaliz_id_configuracion_material_seq OWNED BY public.configuracion_material_personalizado.id_configuracion_material;

CREATE SEQUENCE public.configuracion_norma_personalizada_id_configuracion_norma_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    MAXVALUE 2147483647
    CACHE 1;

ALTER SEQUENCE public.configuracion_norma_personalizada_id_configuracion_norma_seq OWNED BY public.configuracion_norma_personalizada.id_configuracion_norma;

CREATE SEQUENCE public.migrations_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    MAXVALUE 2147483647
    CACHE 1;

ALTER SEQUENCE public.migrations_id_seq OWNED BY public.migrations.id;

CREATE SEQUENCE public.users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;

CREATE SEQUENCE public.jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

ALTER SEQUENCE public.jobs_id_seq OWNED BY public.jobs.id;

CREATE SEQUENCE public.failed_jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

ALTER SEQUENCE public.failed_jobs_id_seq OWNED BY public.failed_jobs.id;

ALTER TABLE ONLY public.configuracion_examen_docente ALTER COLUMN id_configuracion_examen SET DEFAULT nextval('public.configuracion_examen_docente_id_configuracion_examen_seq'::regclass);

ALTER TABLE ONLY public.configuracion_material_personalizado ALTER COLUMN id_configuracion_material SET DEFAULT nextval('public.configuracion_material_personaliz_id_configuracion_material_seq'::regclass);

ALTER TABLE ONLY public.configuracion_norma_personalizada ALTER COLUMN id_configuracion_norma SET DEFAULT nextval('public.configuracion_norma_personalizada_id_configuracion_norma_seq'::regclass);

ALTER TABLE ONLY public.migrations ALTER COLUMN id SET DEFAULT nextval('public.migrations_id_seq'::regclass);

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);

ALTER TABLE ONLY public.jobs ALTER COLUMN id SET DEFAULT nextval('public.jobs_id_seq'::regclass);

ALTER TABLE ONLY public.failed_jobs ALTER COLUMN id SET DEFAULT nextval('public.failed_jobs_id_seq'::regclass);


-- =====================
--  FOREIGN KEYS
-- =====================

ALTER TABLE ONLY public.central_riesgo ADD CONSTRAINT fk_cr_examen FOREIGN KEY (id_examen) REFERENCES public.examen(id_examen);

ALTER TABLE ONLY public.central_riesgo ADD CONSTRAINT fk_cr_estudiante FOREIGN KEY (sis_estudiante) REFERENCES public.estudiante(sis_estudiante);

ALTER TABLE ONLY public.central_riesgo ADD CONSTRAINT fk_cr_confirmador FOREIGN KEY (id_confirmador) REFERENCES public.usuario(id_usuario);

ALTER TABLE ONLY public.material_personalizado ADD CONSTRAINT fk_material_personalizado_examen FOREIGN KEY (id_examen) REFERENCES public.examen(id_examen);

ALTER TABLE ONLY public.norma_personalizada ADD CONSTRAINT fk_norma_personalizada_examen FOREIGN KEY (id_examen) REFERENCES public.examen(id_examen);

ALTER TABLE ONLY public.invitacion_examen_compartido ADD CONSTRAINT fk_iec_invitador FOREIGN KEY (id_docente_invitador) REFERENCES public.usuario(id_usuario);

ALTER TABLE ONLY public.configuracion_examen_norma ADD CONSTRAINT fk_config_norma_config FOREIGN KEY (id_configuracion_examen) REFERENCES public.configuracion_examen_docente(id_configuracion_examen);

ALTER TABLE ONLY public.configuracion_examen_norma ADD CONSTRAINT fk_config_norma_norma FOREIGN KEY (id_norma) REFERENCES public.norma(id_norma);

ALTER TABLE ONLY public.configuracion_examen_material ADD CONSTRAINT fk_config_material_config FOREIGN KEY (id_configuracion_examen) REFERENCES public.configuracion_examen_docente(id_configuracion_examen);

ALTER TABLE ONLY public.configuracion_examen_material ADD CONSTRAINT fk_config_material_material FOREIGN KEY (id_material) REFERENCES public.material(id_material);

ALTER TABLE ONLY public.configuracion_norma_personalizada ADD CONSTRAINT fk_config_norma_personalizada FOREIGN KEY (id_configuracion_examen) REFERENCES public.configuracion_examen_docente(id_configuracion_examen);

ALTER TABLE ONLY public.configuracion_material_personalizado ADD CONSTRAINT fk_config_material_personalizado FOREIGN KEY (id_configuracion_examen) REFERENCES public.configuracion_examen_docente(id_configuracion_examen);

ALTER TABLE ONLY public.central_riesgo ADD CONSTRAINT central_riesgo_id_curso_foreign FOREIGN KEY (id_curso) REFERENCES public.curso(id_curso);

ALTER TABLE ONLY public.notificacion_docente ADD CONSTRAINT fk_nd_destinatario FOREIGN KEY (id_docente_destinatario) REFERENCES public.usuario(id_usuario);

ALTER TABLE ONLY public.notificacion_docente ADD CONSTRAINT fk_nd_invitacion FOREIGN KEY (id_invitacion) REFERENCES public.invitacion_examen_compartido(id_invitacion);

ALTER TABLE ONLY public.estudiante_examen ADD CONSTRAINT fk_ee_configuracion FOREIGN KEY (id_configuracion_examen) REFERENCES public.configuracion_examen_docente(id_configuracion_examen);

ALTER TABLE ONLY public.estudiante_examen ADD CONSTRAINT fk_ee_curso FOREIGN KEY (id_curso) REFERENCES public.curso(id_curso);

ALTER TABLE ONLY public.registro_asistencia ADD CONSTRAINT fk_ra_inscripcion FOREIGN KEY (id_estudiante_examen) REFERENCES public.estudiante_examen(id_estudiante_examen);

ALTER TABLE ONLY public.central_riesgo ADD CONSTRAINT fk_cr_inscripcion FOREIGN KEY (id_estudiante_examen) REFERENCES public.estudiante_examen(id_estudiante_examen);

ALTER TABLE ONLY public.curso ADD CONSTRAINT fk_curso_docente FOREIGN KEY (sis_doc) REFERENCES public.usuario(id_usuario);

ALTER TABLE ONLY public.examen ADD CONSTRAINT fk_examen_creador FOREIGN KEY (creador) REFERENCES public.usuario(id_usuario);

ALTER TABLE ONLY public.examen ADD CONSTRAINT fk_examen_tipo FOREIGN KEY (tipo_examen) REFERENCES public.tipo_examen(id_tipo_examen);

ALTER TABLE ONLY public.rol_usuario ADD CONSTRAINT fk_ru_usuario FOREIGN KEY (id_usuario) REFERENCES public.usuario(id_usuario);

ALTER TABLE ONLY public.rol_usuario ADD CONSTRAINT fk_ru_rol FOREIGN KEY (id_rol) REFERENCES public.rol(id_rol);

ALTER TABLE ONLY public.curso_tipo_gestion ADD CONSTRAINT fk_ctg_curso FOREIGN KEY (id_curso) REFERENCES public.curso(id_curso);

ALTER TABLE ONLY public.curso_tipo_gestion ADD CONSTRAINT fk_ctg_tg FOREIGN KEY (id_tg) REFERENCES public.tipo_gestion(id_tipo_gestion);

ALTER TABLE ONLY public.estudiante_curso ADD CONSTRAINT fk_ec_estudiante FOREIGN KEY (sis_estudiante) REFERENCES public.estudiante(sis_estudiante);

ALTER TABLE ONLY public.estudiante_curso ADD CONSTRAINT fk_ec_curso FOREIGN KEY (id_curso) REFERENCES public.curso(id_curso);

ALTER TABLE ONLY public.auxiliar_curso ADD CONSTRAINT fk_ac_auxiliar FOREIGN KEY (id_auxiliar) REFERENCES public.usuario(id_usuario);

ALTER TABLE ONLY public.auxiliar_curso ADD CONSTRAINT fk_ac_curso FOREIGN KEY (id_curso) REFERENCES public.curso(id_curso);

ALTER TABLE ONLY public.examen_ambiente ADD CONSTRAINT fk_ea_examen FOREIGN KEY (id_examen) REFERENCES public.examen(id_examen);

ALTER TABLE ONLY public.examen_ambiente ADD CONSTRAINT fk_ea_ambiente FOREIGN KEY (id_ambiente) REFERENCES public.ambiente(id_ambiente);

ALTER TABLE ONLY public.examen_norma ADD CONSTRAINT fk_en_examen FOREIGN KEY (id_examen) REFERENCES public.examen(id_examen);

ALTER TABLE ONLY public.examen_norma ADD CONSTRAINT fk_en_norma FOREIGN KEY (id_norma) REFERENCES public.norma(id_norma);

ALTER TABLE ONLY public.examen_material_permitido ADD CONSTRAINT fk_emp_examen FOREIGN KEY (id_examen) REFERENCES public.examen(id_examen);

ALTER TABLE ONLY public.examen_material_permitido ADD CONSTRAINT fk_emp_material FOREIGN KEY (id_material_permitido) REFERENCES public.material(id_material);

ALTER TABLE ONLY public.examen_curso ADD CONSTRAINT fk_exc_examen FOREIGN KEY (id_examen) REFERENCES public.examen(id_examen);

ALTER TABLE ONLY public.examen_curso ADD CONSTRAINT fk_exc_curso FOREIGN KEY (id_curso) REFERENCES public.curso(id_curso);

ALTER TABLE ONLY public.estudiante_examen ADD CONSTRAINT fk_ee_estudiante FOREIGN KEY (sis_estudiante) REFERENCES public.estudiante(sis_estudiante);

ALTER TABLE ONLY public.estudiante_examen ADD CONSTRAINT fk_ee_examen FOREIGN KEY (id_examen) REFERENCES public.examen(id_examen);

ALTER TABLE ONLY public.estudiante_examen ADD CONSTRAINT fk_ee_modificador FOREIGN KEY (modificado_por) REFERENCES public.usuario(id_usuario);

ALTER TABLE ONLY public.estudiante_examen_ambiente ADD CONSTRAINT fk_eea_ee FOREIGN KEY (id_ee) REFERENCES public.estudiante_examen(id_estudiante_examen);

ALTER TABLE ONLY public.estudiante_examen_ambiente ADD CONSTRAINT fk_eea_ambiente FOREIGN KEY (id_ambiente) REFERENCES public.ambiente(id_ambiente);

ALTER TABLE ONLY public.registro_asistencia ADD CONSTRAINT fk_ra_examen FOREIGN KEY (id_examen) REFERENCES public.examen(id_examen);

ALTER TABLE ONLY public.registro_asistencia ADD CONSTRAINT fk_ra_estudiante FOREIGN KEY (id_estudiante) REFERENCES public.estudiante(sis_estudiante);

ALTER TABLE ONLY public.registro_asistencia ADD CONSTRAINT fk_ra_registrador FOREIGN KEY (id_registrador) REFERENCES public.usuario(id_usuario);

ALTER TABLE ONLY public.central_riesgo ADD CONSTRAINT fk_cr_registrador FOREIGN KEY (id_registrador) REFERENCES public.usuario(id_usuario);

ALTER TABLE ONLY public.notificacion_docente ADD CONSTRAINT fk_nd_central FOREIGN KEY (id_central_riesgo) REFERENCES public.central_riesgo(id_registro);

ALTER TABLE ONLY public.notificacion_docente ADD CONSTRAINT fk_nd_curso FOREIGN KEY (id_curso) REFERENCES public.curso(id_curso);

ALTER TABLE ONLY public.notificacion_auxiliar ADD CONSTRAINT fk_na_curso FOREIGN KEY (id_curso) REFERENCES public.curso(id_curso);

ALTER TABLE ONLY public.notificacion_auxiliar ADD CONSTRAINT fk_na_ambiente FOREIGN KEY (id_ambiente) REFERENCES public.ambiente(id_ambiente);

ALTER TABLE ONLY public.invitacion_examen_compartido ADD CONSTRAINT fk_iec_docente FOREIGN KEY (id_docente_invitado) REFERENCES public.usuario(id_usuario);

ALTER TABLE ONLY public.invitacion_examen_compartido ADD CONSTRAINT fk_iec_examen FOREIGN KEY (id_examen) REFERENCES public.examen(id_examen);

ALTER TABLE ONLY public.curso ADD CONSTRAINT fk_curso_ambiente FOREIGN KEY (id_ambiente) REFERENCES public.ambiente(id_ambiente);

ALTER TABLE ONLY public.configuracion_examen_docente ADD CONSTRAINT fk_config_examen FOREIGN KEY (id_examen) REFERENCES public.examen(id_examen);

ALTER TABLE ONLY public.configuracion_examen_docente ADD CONSTRAINT fk_config_docente FOREIGN KEY (id_docente) REFERENCES public.usuario(id_usuario);


-- =====================
--  INDEXES
-- =====================

CREATE INDEX material_personalizado_id_examen_index ON public.material_personalizado USING btree (id_examen);

CREATE INDEX norma_personalizada_id_examen_index ON public.norma_personalizada USING btree (id_examen);

CREATE UNIQUE INDEX config_norma_numero_unique ON public.configuracion_norma_personalizada USING btree (id_configuracion_examen, numero_norma);

CREATE UNIQUE INDEX config_examen_docente_unique ON public.configuracion_examen_docente USING btree (id_examen, id_docente);

CREATE UNIQUE INDEX config_material_numero_unique ON public.configuracion_material_personalizado USING btree (id_configuracion_examen, numero_material);

CREATE UNIQUE INDEX users_email_unique ON public.users USING btree (email);

CREATE INDEX sessions_user_id_index ON public.sessions USING btree (user_id);

CREATE INDEX sessions_last_activity_index ON public.sessions USING btree (last_activity);

CREATE INDEX jobs_queue_index ON public.jobs USING btree (queue);

CREATE UNIQUE INDEX failed_jobs_uuid_unique ON public.failed_jobs USING btree (uuid);

CREATE INDEX idx_curso_ambiente ON public.curso USING btree (id_ambiente);

CREATE UNIQUE INDEX usuario_cod_sis_key ON public.usuario USING btree (cod_sis);

CREATE UNIQUE INDEX usuario_correo_unique ON public.usuario USING btree (correo);

CREATE INDEX estudiante_examen_config_index ON public.estudiante_examen USING btree (id_configuracion_examen);

CREATE INDEX estudiante_examen_curso_index ON public.estudiante_examen USING btree (id_curso);

CREATE UNIQUE INDEX estudiante_examen_docente_estudiante_unique ON public.estudiante_examen USING btree (id_examen, id_configuracion_examen, sis_estudiante);

CREATE UNIQUE INDEX invitacion_examen_activa_unique ON public.invitacion_examen_compartido USING btree (id_examen, id_docente_invitado) WHERE (estado = ANY (ARRAY['pendiente'::invitacion_estado, 'aceptado'::invitacion_estado]));

CREATE INDEX invitacion_examen_invitador_index ON public.invitacion_examen_compartido USING btree (id_docente_invitador);

CREATE INDEX invitacion_examen_invitado_index ON public.invitacion_examen_compartido USING btree (id_docente_invitado);

CREATE INDEX invitacion_examen_examen_index ON public.invitacion_examen_compartido USING btree (id_examen);

CREATE INDEX notificacion_destinatario_index ON public.notificacion_docente USING btree (id_docente_destinatario);

CREATE UNIQUE INDEX notificacion_invitacion_unique ON public.notificacion_docente USING btree (id_invitacion);

CREATE INDEX registro_asistencia_inscripcion_index ON public.registro_asistencia USING btree (id_estudiante_examen);

CREATE UNIQUE INDEX registro_asistencia_inscripcion_unique ON public.registro_asistencia USING btree (id_estudiante_examen);

CREATE INDEX central_riesgo_id_curso_index ON public.central_riesgo USING btree (id_curso);

CREATE INDEX central_riesgo_inscripcion_index ON public.central_riesgo USING btree (id_estudiante_examen);


-- =====================
--  DATA
-- =====================

INSERT INTO public.ambiente ("id_ambiente", "nombre_ambiente") VALUES (3, 'Laboratorio 1');

INSERT INTO public.ambiente ("id_ambiente", "nombre_ambiente") VALUES (4, 'Laboratorio 2');

INSERT INTO public.ambiente ("id_ambiente", "nombre_ambiente") VALUES (1, 'Aula 623');

INSERT INTO public.ambiente ("id_ambiente", "nombre_ambiente") VALUES (2, 'Aula 692A');

INSERT INTO public.ambiente ("id_ambiente", "nombre_ambiente") VALUES (5, 'Aula 690B');


INSERT INTO public.cache ("key", "value", "expiration") VALUES ('catalogos.examen.tipo.examen final', 'i:2;', 1793063973);

INSERT INTO public.cache ("key", "value", "expiration") VALUES ('monitoreo.informe.27.docente.1', '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MDp7fXM6MTA6InRpbWVzdGFtcHMiO2I6MDtzOjEzOiJ1c2VzVW5pcXVlSWRzIjtiOjA7czo5OiIAKgBoaWRkZW4iO2E6MDp7fXM6MTA6IgAqAHZpc2libGUiO2E6MDp7fXM6MTE6IgAqAGZpbGxhYmxlIjthOjc6e2k6MDtzOjk6ImlkX2V4YW1lbiI7aToxO3M6MTM6Im5vbWJyZV9leGFtZW4iO2k6MjtzOjU6ImZlY2hhIjtpOjM7czoxMToiaG9yYV9pbmljaW8iO2k6NDtzOjg6ImR1cmFjaW9uIjtpOjU7czo3OiJjcmVhZG9yIjtpOjY7czoxMToidGlwb19leGFtZW4iO31zOjEwOiIAKgBndWFyZGVkIjthOjE6e2k6MDtzOjE6IioiO319czoxMToiZXN0dWRpYW50ZXMiO086Mjk6IklsbHVtaW5hdGVcU3VwcG9ydFxDb2xsZWN0aW9uIjoyOntzOjg6IgAqAGl0ZW1zIjthOjUwOntpOjA7YToxMTp7czozOiJzaXMiO3M6OToiMjAyMDAyMTA3IjtzOjE0OiJpZF9pbnNjcmlwY2lvbiI7aTo2MDE7czo2OiJub21icmUiO3M6NzoiRXJuZXN0byI7czo4OiJhcGVsbGlkbyI7czo0OiJWZXJhIjtzOjQ6ImF1bGEiO3M6OToiQXVsYSA2OTJBIjtzOjEzOiJkb2NlbnRlX2NhcmdvIjtzOjEzOiJSb2JlcnRvIFNpbHZhIjtzOjEwOiJoYWJpbGl0YWRvIjtiOjE7czoxMToicmVnaXN0cmFkb3IiO047czoxMjoiaG9yYV9pbmdyZXNvIjtOO3M6MTA6ImluY2lkZW5jaWEiO047czoxNzoiZXN0YWRvX2FzaXN0ZW5jaWEiO3M6OToicGVuZGllbnRlIjt9aToxO2E6MTE6e3M6Mzoic2lzIjtzOjg6IjIwMjEwMDAzIjtzOjE0OiJpZF9pbnNjcmlwY2lvbiI7aTo2MDI7czo2OiJub21icmUiO3M6NToiTWFyaWEiO3M6ODoiYXBlbGxpZG8iO3M6NToiTG9wZXoiO3M6NDoiYXVsYSI7czo5OiJBdWxhIDY5MkEiO3M6MTM6ImRvY2VudGVfY2FyZ28iO3M6MTM6IlJvYmVydG8gU2lsdmEiO3M6MTA6ImhhYmlsaXRhZG8iO2I6MTtzOjExOiJyZWdpc3RyYWRvciI7TjtzOjEyOiJob3JhX2luZ3Jlc28iO047czoxMDoiaW5jaWRlbmNpYSI7TjtzOjE3OiJlc3RhZG9fYXNpc3RlbmNpYSI7czo5OiJwZW5kaWVudGUiO31pOjI7YToxMTp7czozOiJzaXMiO3M6OToiMjAyMTAxMDIyIjtzOjE0OiJpZF9pbnNjcmlwY2lvbiI7aTo2MDM7czo2OiJub21icmUiO3M6NToiQnJ1bm8iO3M6ODoiYXBlbGxpZG8iO3M6NToiRMOtYXoiO3M6NDoiYXVsYSI7czo5OiJBdWxhIDY5MkEiO3M6MTM6ImRvY2VudGVfY2FyZ28iO3M6MTM6IlJvYmVydG8gU2lsdmEiO3M6MTA6ImhhYmlsaXRhZG8iO2I6MTtzOjExOiJyZWdpc3RyYWRvciI7TjtzOjEyOiJob3JhX2luZ3Jlc28iO047czoxMDoiaW5jaWRlbmNpYSI7TjtzOjE3OiJlc3RhZG9fYXNpc3RlbmNpYSI7czo5OiJwZW5kaWVudGUiO31pOjM7YToxMTp7czozOiJzaXMiO3M6OToiMjAyMTAxMDc4IjtzOjE0OiJpZF9pbnNjcmlwY2lvbiI7aTo2MDQ7czo2OiJub21icmUiO3M6NzoiR2FicmllbCI7czo4OiJhcGVsbGlkbyI7czo2OiJNYW1hbmkiO3M6NDoiYXVsYSI7czo5OiJBdWxhIDY5MkEiO3M6MTM6ImRvY2VudGVfY2FyZ28iO3M6MTM6IlJvYmVydG8gU2lsdmEiO3M6MTA6ImhhYmlsaXRhZG8iO2I6MTtzOjExOiJyZWdpc3RyYWRvciI7TjtzOjEyOiJob3JhX2luZ3Jlc28iO047czoxMDoiaW5jaWRlbmNpYSI7TjtzOjE3OiJlc3RhZG9fYXNpc3RlbmNpYSI7czo5OiJwZW5kaWVudGUiO31pOjQ7YToxMTp7czozOiJzaXMiO3M6OToiMjAyMTAxMDkwIjtzOjE0OiJpZF9pbnNjcmlwY2lvbiI7aTo2MDU7czo2OiJub21icmUiO3M6NToiSXbDoW4iO3M6ODoiYXBlbGxpZG8iO3M6NjoiRmxvcmVzIjtzOjQ6ImF1bGEiO3M6OToiQXVsYSA2OTJBIjtzOjEzOiJkb2NlbnRlX2NhcmdvIjtzOjEzOiJSb2JlcnRvIFNpbHZhIjtzOjEwOiJoYWJpbGl0YWRvIjtiOjE7czoxMToicmVnaXN0cmFkb3IiO047czoxMjoiaG9yYV9pbmdyZXNvIjtOO3M6MTA6ImluY2lkZW5jaWEiO047czoxNzoiZXN0YWRvX2FzaXN0ZW5jaWEiO3M6OToicGVuZGllbnRlIjt9aTo1O2E6MTE6e3M6Mzoic2lzIjtzOjk6IjIwMjEwMTExMiI7czoxNDoiaWRfaW5zY3JpcGNpb24iO2k6NjA2O3M6Njoibm9tYnJlIjtzOjU6IktldmluIjtzOjg6ImFwZWxsaWRvIjtzOjY6IkzDs3BleiI7czo0OiJhdWxhIjtzOjk6IkF1bGEgNjkyQSI7czoxMzoiZG9jZW50ZV9jYXJnbyI7czoxMzoiUm9iZXJ0byBTaWx2YSI7czoxMDoiaGFiaWxpdGFkbyI7YjoxO3M6MTE6InJlZ2lzdHJhZG9yIjtOO3M6MTI6ImhvcmFfaW5ncmVzbyI7TjtzOjEwOiJpbmNpZGVuY2lhIjtOO3M6MTc6ImVzdGFkb19hc2lzdGVuY2lhIjtzOjk6InBlbmRpZW50ZSI7fWk6NjthOjExOntzOjM6InNpcyI7czo5OiIyMDIxMDExMzQiO3M6MTQ6ImlkX2luc2NyaXBjaW9uIjtpOjYwNztzOjY6Im5vbWJyZSI7czo2OiJNaWd1ZWwiO3M6ODoiYXBlbGxpZG8iO3M6NDoiU290byI7czo0OiJhdWxhIjtzOjk6IkF1bGEgNjkyQSI7czoxMzoiZG9jZW50ZV9jYXJnbyI7czoxMzoiUm9iZXJ0byBTaWx2YSI7czoxMDoiaGFiaWxpdGFkbyI7YjoxO3M6MTE6InJlZ2lzdHJhZG9yIjtOO3M6MTI6ImhvcmFfaW5ncmVzbyI7TjtzOjEwOiJpbmNpZGVuY2lhIjtOO3M6MTc6ImVzdGFkb19hc2lzdGVuY2lhIjtzOjk6InBlbmRpZW50ZSI7fWk6NzthOjExOntzOjM6InNpcyI7czo5OiIyMDIxMDExNTYiO3M6MTQ6ImlkX2luc2NyaXBjaW9uIjtpOjYwODtzOjY6Im5vbWJyZSI7czo2OiLDk3NjYXIiO3M6ODoiYXBlbGxpZG8iO3M6NjoiUXVpc3BlIjtzOjQ6ImF1bGEiO3M6OToiQXVsYSA2OTJBIjtzOjEzOiJkb2NlbnRlX2NhcmdvIjtzOjEzOiJSb2JlcnRvIFNpbHZhIjtzOjEwOiJoYWJpbGl0YWRvIjtiOjE7czoxMToicmVnaXN0cmFkb3IiO047czoxMjoiaG9yYV9pbmdyZXNvIjtOO3M6MTA6ImluY2lkZW5jaWEiO047czoxNzoiZXN0YWRvX2FzaXN0ZW5jaWEiO3M6OToicGVuZGllbnRlIjt9aTo4O2E6MTE6e3M6Mzoic2lzIjtzOjk6IjIwMjEwMTE3OCI7czoxNDoiaWRfaW5zY3JpcGNpb24iO2k6NjA5O3M6Njoibm9tYnJlIjtzOjU6IlJhw7psIjtzOjg6ImFwZWxsaWRvIjtzOjY6IkNob3F1ZSI7czo0OiJhdWxhIjtzOjk6IkF1bGEgNjkyQSI7czoxMzoiZG9jZW50ZV9jYXJnbyI7czoxMzoiUm9iZXJ0byBTaWx2YSI7czoxMDoiaGFiaWxpdGFkbyI7YjoxO3M6MTE6InJlZ2lzdHJhZG9yIjtOO3M6MTI6ImhvcmFfaW5ncmVzbyI7TjtzOjEwOiJpbmNpZGVuY2lhIjtOO3M6MTc6ImVzdGFkb19hc2lzdGVuY2lhIjtzOjk6InBlbmRpZW50ZSI7fWk6OTthOjExOntzOjM6InNpcyI7czo5OiIyMDIxMDExOTAiO3M6MTQ6ImlkX2luc2NyaXBjaW9uIjtpOjYxMDtzOjY6Im5vbWJyZSI7czo2OiJUb23DoXMiO3M6ODoiYXBlbGxpZG8iO3M6NzoiR2FyY8OtYSI7czo0OiJhdWxhIjtzOjk6IkF1bGEgNjkyQSI7czoxMzoiZG9jZW50ZV9jYXJnbyI7czoxMzoiUm9iZXJ0byBTaWx2YSI7czoxMDoiaGFiaWxpdGFkbyI7YjoxO3M6MTE6InJlZ2lzdHJhZG9yIjtOO3M6MTI6ImhvcmFfaW5ncmVzbyI7TjtzOjEwOiJpbmNpZGVuY2lhIjtOO3M6MTc6ImVzdGFkb19hc2lzdGVuY2lhIjtzOjk6InBlbmRpZW50ZSI7fWk6MTA7YToxMTp7czozOiJzaXMiO3M6OToiMjAyMTAxMjIzIjtzOjE0OiJpZF9pbnNjcmlwY2lvbiI7aTo2MTE7czo2OiJub21icmUiO3M6NjoiWGF2aWVyIjtzOjg6ImFwZWxsaWRvIjtzOjY6IlZhcmdhcyI7czo0OiJhdWxhIjtzOjk6IkF1bGEgNjkyQSI7czoxMzoiZG9jZW50ZV9jYXJnbyI7czoxMzoiUm9iZXJ0byBTaWx2YSI7czoxMDoiaGFiaWxpdGFkbyI7YjoxO3M6MTE6InJlZ2lzdHJhZG9yIjtOO3M6MTI6ImhvcmFfaW5ncmVzbyI7TjtzOjEwOiJpbmNpZGVuY2lhIjtOO3M6MTc6ImVzdGFkb19hc2lzdGVuY2lhIjtzOjk6InBlbmRpZW50ZSI7fWk6MTE7YToxMTp7czozOiJzaXMiO3M6OToiMjAyMTAxMjQ1IjtzOjE0OiJpZF9pbnNjcmlwY2lvbiI7aTo2MTI7czo2OiJub21icmUiO3M6MzoiWm9lIjtzOjg6ImFwZWxsaWRvIjtzOjU6IlNpbGVzIjtzOjQ6ImF1bGEiO3M6OToiQXVsYSA2OTJBIjtzOjEzOiJkb2NlbnRlX2NhcmdvIjtzOjEzOiJSb2JlcnRvIFNpbHZhIjtzOjEwOiJoYWJpbGl0YWRvIjtiOjE7czoxMToicmVnaXN0cmFkb3IiO047czoxMjoiaG9yYV9pbmdyZXNvIjtOO3M6MTA6ImluY2lkZW5jaWEiO047czoxNzoiZXN0YWRvX2FzaXN0ZW5jaWEiO3M6OToicGVuZGllbnRlIjt9aToxMjthOjExOntzOjM6InNpcyI7czo5OiIyMDIxMDEyNjciO3M6MTQ6ImlkX2luc2NyaXBjaW9uIjtpOjYxMztzOjY6Im5vbWJyZSI7czo2OiJCaWFuY2EiO3M6ODoiYXBlbGxpZG8iO3M6NzoiUGFyZWRlcyI7czo0OiJhdWxhIjtzOjk6IkF1bGEgNjkyQSI7czoxMzoiZG9jZW50ZV9jYXJnbyI7czoxMzoiUm9iZXJ0byBTaWx2YSI7czoxMDoiaGFiaWxpdGFkbyI7YjoxO3M6MTE6InJlZ2lzdHJhZG9yIjtOO3M6MTI6ImhvcmFfaW5ncmVzbyI7TjtzOjEwOiJpbmNpZGVuY2lhIjtOO3M6MTc6ImVzdGFkb19hc2lzdGVuY2lhIjtzOjk6InBlbmRpZW50ZSI7fWk6MTM7YToxMTp7czozOiJzaXMiO3M6OToiMjAyMTAxMjg5IjtzOjE0OiJpZF9pbnNjcmlwY2lvbiI7aTo2MTQ7czo2OiJub21icmUiO3M6NzoiRGFuaWVsYSI7czo4OiJhcGVsbGlkbyI7czo3OiJDYWJyZXJhIjtzOjQ6ImF1bGEiO3M6OToiQXVsYSA2OTJBIjtzOjEzOiJkb2NlbnRlX2NhcmdvIjtzOjEzOiJSb2JlcnRvIFNpbHZhIjtzOjEwOiJoYWJpbGl0YWRvIjtiOjE7czoxMToicmVnaXN0cmFkb3IiO047czoxMjoiaG9yYV9pbmdyZXNvIjtOO3M6MTA6ImluY2lkZW5jaWEiO047czoxNzoiZXN0YWRvX2FzaXN0ZW5jaWEiO3M6OToicGVuZGllbnRlIjt9aToxNDthOjExOntzOjM6InNpcyI7czo5OiIyMDIxMDEzMDEiO3M6MTQ6ImlkX2luc2NyaXBjaW9uIjtpOjYxNTtzOjY6Im5vbWJyZSI7czo4OiJGZXJuYW5kYSI7czo4OiJhcGVsbGlkbyI7czo0OiJWZWdhIjtzOjQ6ImF1bGEiO3M6OToiQXVsYSA2OTJBIjtzOjEzOiJkb2NlbnRlX2NhcmdvIjtzOjEzOiJSb2JlcnRvIFNpbHZhIjtzOjEwOiJoYWJpbGl0YWRvIjtiOjE7czoxMToicmVnaXN0cmFkb3IiO047czoxMjoiaG9yYV9pbmdyZXNvIjtOO3M6MTA6ImluY2lkZW5jaWEiO047czoxNzoiZXN0YWRvX2FzaXN0ZW5jaWEiO3M6OToicGVuZGllbnRlIjt9aToxNTthOjExOntzOjM6InNpcyI7czo5OiIyMDIxMDEzMjMiO3M6MTQ6ImlkX2luc2NyaXBjaW9uIjtpOjYxNjtzOjY6Im5vbWJyZSI7czo0OiJIdWdvIjtzOjg6ImFwZWxsaWRvIjtzOjc6IkNhbWFjaG8iO3M6NDoiYXVsYSI7czo5OiJBdWxhIDY5MkEiO3M6MTM6ImRvY2VudGVfY2FyZ28iO3M6MTM6IlJvYmVydG8gU2lsdmEiO3M6MTA6ImhhYmlsaXRhZG8iO2I6MTtzOjExOiJyZWdpc3RyYWRvciI7TjtzOjEyOiJob3JhX2luZ3Jlc28iO047czoxMDoiaW5jaWRlbmNpYSI7TjtzOjE3OiJlc3RhZG9fYXNpc3RlbmNpYSI7czo5OiJwZW5kaWVudGUiO31pOjE2O2E6MTE6e3M6Mzoic2lzIjtzOjk6IjIwMjEwMTM0NSI7czoxNDoiaWRfaW5zY3JpcGNpb24iO2k6NjE3O3M6Njoibm9tYnJlIjtzOjY6IkphdmllciI7czo4OiJhcGVsbGlkbyI7czo4OiJFc3Bpbm96YSI7czo0OiJhdWxhIjtzOjk6IkF1bGEgNjkyQSI7czoxMzoiZG9jZW50ZV9jYXJnbyI7czoxMzoiUm9iZXJ0byBTaWx2YSI7czoxMDoiaGFiaWxpdGFkbyI7YjoxO3M6MTE6InJlZ2lzdHJhZG9yIjtOO3M6MTI6ImhvcmFfaW5ncmVzbyI7TjtzOjEwOiJpbmNpZGVuY2lhIjtOO3M6MTc6ImVzdGFkb19hc2lzdGVuY2lhIjtzOjk6InBlbmRpZW50ZSI7fWk6MTc7YToxMTp7czozOiJzaXMiO3M6OToiMjAyMTAxMzY3IjtzOjE0OiJpZF9pbnNjcmlwY2lvbiI7aTo2MTg7czo2OiJub21icmUiO3M6NDoiTHVpcyI7czo4OiJhcGVsbGlkbyI7czoxMDoiR3V0acOpcnJleiI7czo0OiJhdWxhIjtzOjk6IkF1bGEgNjkyQSI7czoxMzoiZG9jZW50ZV9jYXJnbyI7czoxMzoiUm9iZXJ0byBTaWx2YSI7czoxMDoiaGFiaWxpdGFkbyI7YjoxO3M6MTE6InJlZ2lzdHJhZG9yIjtOO3M6MTI6ImhvcmFfaW5ncmVzbyI7TjtzOjEwOiJpbmNpZGVuY2lhIjtOO3M6MTc6ImVzdGFkb19hc2lzdGVuY2lhIjtzOjk6InBlbmRpZW50ZSI7fWk6MTg7YToxMTp7czozOiJzaXMiO3M6OToiMjAyMTAxMzg5IjtzOjE0OiJpZF9pbnNjcmlwY2lvbiI7aTo2MTk7czo2OiJub21icmUiO3M6ODoiTmljb2zDoXMiO3M6ODoiYXBlbGxpZG8iO3M6NzoiSXJpYXJ0ZSI7czo0OiJhdWxhIjtzOjk6IkF1bGEgNjkyQSI7czoxMzoiZG9jZW50ZV9jYXJnbyI7czoxMzoiUm9iZXJ0byBTaWx2YSI7czoxMDoiaGFiaWxpdGFkbyI7YjoxO3M6MTE6InJlZ2lzdHJhZG9yIjtOO3M6MTI6ImhvcmFfaW5ncmVzbyI7TjtzOjEwOiJpbmNpZGVuY2lhIjtOO3M6MTc6ImVzdGFkb19hc2lzdGVuY2lhIjtzOjk6InBlbmRpZW50ZSI7fWk6MTk7YToxMTp7czozOiJzaXMiO3M6OToiMjAyMTAxNDAxIjtzOjE0OiJpZF9pbnNjcmlwY2lvbiI7aTo2MjA7czo2OiJub21icmUiO3M6NToiUGFibG8iO3M6ODoiYXBlbGxpZG8iO3M6ODoiS293YWxza2kiO3M6NDoiYXVsYSI7czo5OiJBdWxhIDY5MkEiO3M6MTM6ImRvY2VudGVfY2FyZ28iO3M6MTM6IlJvYmVydG8gU2lsdmEiO3M6MTA6ImhhYmlsaXRhZG8iO2I6MTtzOjExOiJyZWdpc3RyYWRvciI7TjtzOjEyOiJob3JhX2luZ3Jlc28iO047czoxMDoiaW5jaWRlbmNpYSI7TjtzOjE3OiJlc3RhZG9fYXNpc3RlbmNpYSI7czo5OiJwZW5kaWVudGUiO31pOjIwO2E6MTE6e3M6Mzoic2lzIjtzOjk6IjIwMjEwMTQyMyI7czoxNDoiaWRfaW5zY3JpcGNpb24iO2k6NjIxO3M6Njoibm9tYnJlIjtzOjY6IlNlcmdpbyI7czo4OiJhcGVsbGlkbyI7czo3OiJNZW5kb3phIjtzOjQ6ImF1bGEiO3M6OToiQXVsYSA2OTJBIjtzOjEzOiJkb2NlbnRlX2NhcmdvIjtzOjEzOiJSb2JlcnRvIFNpbHZhIjtzOjEwOiJoYWJpbGl0YWRvIjtiOjE7czoxMToicmVnaXN0cmFkb3IiO047czoxMjoiaG9yYV9pbmdyZXNvIjtOO3M6MTA6ImluY2lkZW5jaWEiO047czoxNzoiZXN0YWRvX2FzaXN0ZW5jaWEiO3M6OToicGVuZGllbnRlIjt9aToyMTthOjExOntzOjM6InNpcyI7czo5OiIyMDIxMDE0NDUiO3M6MTQ6ImlkX2luc2NyaXBjaW9uIjtpOjYyMjtzOjY6Im5vbWJyZSI7czo2OiJVbGlzZXMiO3M6ODoiYXBlbGxpZG8iO3M6ODoiT3JlbGxhbmEiO3M6NDoiYXVsYSI7czo5OiJBdWxhIDY5MkEiO3M6MTM6ImRvY2VudGVfY2FyZ28iO3M6MTM6IlJvYmVydG8gU2lsdmEiO3M6MTA6ImhhYmlsaXRhZG8iO2I6MTtzOjExOiJyZWdpc3RyYWRvciI7TjtzOjEyOiJob3JhX2luZ3Jlc28iO047czoxMDoiaW5jaWRlbmNpYSI7TjtzOjE3OiJlc3RhZG9fYXNpc3RlbmNpYSI7czo5OiJwZW5kaWVudGUiO31pOjIyO2E6MTE6e3M6Mzoic2lzIjtzOjk6IjIwMjEwMTQ2NyI7czoxNDoiaWRfaW5zY3JpcGNpb24iO2k6NjIzO3M6Njoibm9tYnJlIjtzOjY6IldhbHRlciI7czo4OiJhcGVsbGlkbyI7czo3OiJRdWlyb2dhIjtzOjQ6ImF1bGEiO3M6OToiQXVsYSA2OTJBIjtzOjEzOiJkb2NlbnRlX2NhcmdvIjtzOjEzOiJSb2JlcnRvIFNpbHZhIjtzOjEwOiJoYWJpbGl0YWRvIjtiOjE7czoxMToicmVnaXN0cmFkb3IiO047czoxMjoiaG9yYV9pbmdyZXNvIjtOO3M6MTA6ImluY2lkZW5jaWEiO047czoxNzoiZXN0YWRvX2FzaXN0ZW5jaWEiO3M6OToicGVuZGllbnRlIjt9aToyMzthOjExOntzOjM6InNpcyI7czo5OiIyMDIxMDE0ODkiO3M6MTQ6ImlkX2luc2NyaXBjaW9uIjtpOjYyNDtzOjY6Im5vbWJyZSI7czo1OiJZYW1pbCI7czo4OiJhcGVsbGlkbyI7czo4OiJTw6FuY2hleiI7czo0OiJhdWxhIjtzOjk6IkF1bGEgNjkyQSI7czoxMzoiZG9jZW50ZV9jYXJnbyI7czoxMzoiUm9iZXJ0byBTaWx2YSI7czoxMDoiaGFiaWxpdGFkbyI7YjoxO3M6MTE6InJlZ2lzdHJhZG9yIjtOO3M6MTI6ImhvcmFfaW5ncmVzbyI7TjtzOjEwOiJpbmNpZGVuY2lhIjtOO3M6MTc6ImVzdGFkb19hc2lzdGVuY2lhIjtzOjk6InBlbmRpZW50ZSI7fWk6MjQ7YToxMTp7czozOiJzaXMiO3M6OToiMjAyMjAxMDEzIjtzOjE0OiJpZF9pbnNjcmlwY2lvbiI7aTo2MjU7czo2OiJub21icmUiO3M6MzoiQW5hIjtzOjg6ImFwZWxsaWRvIjtzOjY6IkzDs3BleiI7czo0OiJhdWxhIjtzOjk6IkF1bGEgNjkyQSI7czoxMzoiZG9jZW50ZV9jYXJnbyI7czoxMzoiUm9iZXJ0byBTaWx2YSI7czoxMDoiaGFiaWxpdGFkbyI7YjoxO3M6MTE6InJlZ2lzdHJhZG9yIjtzOjEzOiJSb2JlcnRvIFNpbHZhIjtzOjEyOiJob3JhX2luZ3Jlc28iO3M6ODoiMDE6MTM6MzMiO3M6MTA6ImluY2lkZW5jaWEiO047czoxNzoiZXN0YWRvX2FzaXN0ZW5jaWEiO3M6ODoicHJlc2VudGUiO31pOjI1O2E6MTE6e3M6Mzoic2lzIjtzOjk6IjIwMjIwMTAzMSI7czoxNDoiaWRfaW5zY3JpcGNpb24iO2k6NjI2O3M6Njoibm9tYnJlIjtzOjU6IkNhcmxhIjtzOjg6ImFwZWxsaWRvIjtzOjQ6IlJ1aXoiO3M6NDoiYXVsYSI7czo5OiJBdWxhIDY5MkEiO3M6MTM6ImRvY2VudGVfY2FyZ28iO3M6MTM6IlJvYmVydG8gU2lsdmEiO3M6MTA6ImhhYmlsaXRhZG8iO2I6MTtzOjExOiJyZWdpc3RyYWRvciI7TjtzOjEyOiJob3JhX2luZ3Jlc28iO047czoxMDoiaW5jaWRlbmNpYSI7TjtzOjE3OiJlc3RhZG9fYXNpc3RlbmNpYSI7czo5OiJwZW5kaWVudGUiO31pOjI2O2E6MTE6e3M6Mzoic2lzIjtzOjk6IjIwMjIwMTA1NiI7czoxNDoiaWRfaW5zY3JpcGNpb24iO2k6NjI3O3M6Njoibm9tYnJlIjtzOjc6IkbDoXRpbWEiO3M6ODoiYXBlbGxpZG8iO3M6NjoiUXVpc3BlIjtzOjQ6ImF1bGEiO3M6OToiQXVsYSA2OTJBIjtzOjEzOiJkb2NlbnRlX2NhcmdvIjtzOjEzOiJSb2JlcnRvIFNpbHZhIjtzOjEwOiJoYWJpbGl0YWRvIjtiOjE7czoxMToicmVnaXN0cmFkb3IiO047czoxMjoiaG9yYV9pbmdyZXNvIjtOO3M6MTA6ImluY2lkZW5jaWEiO047czoxNzoiZXN0YWRvX2FzaXN0ZW5jaWEiO3M6OToicGVuZGllbnRlIjt9aToyNzthOjExOntzOjM6InNpcyI7czo5OiIyMDIyMDEwODkiO3M6MTQ6ImlkX2luc2NyaXBjaW9uIjtpOjYyODtzOjY6Im5vbWJyZSI7czo2OiJIZWxlbmEiO3M6ODoiYXBlbGxpZG8iO3M6NjoiQ2hvcXVlIjtzOjQ6ImF1bGEiO3M6OToiQXVsYSA2OTJBIjtzOjEzOiJkb2NlbnRlX2NhcmdvIjtzOjEzOiJSb2JlcnRvIFNpbHZhIjtzOjEwOiJoYWJpbGl0YWRvIjtiOjE7czoxMToicmVnaXN0cmFkb3IiO047czoxMjoiaG9yYV9pbmdyZXNvIjtOO3M6MTA6ImluY2lkZW5jaWEiO047czoxNzoiZXN0YWRvX2FzaXN0ZW5jaWEiO3M6OToicGVuZGllbnRlIjt9aToyODthOjExOntzOjM6InNpcyI7czo5OiIyMDIyMDExMDEiO3M6MTQ6ImlkX2luc2NyaXBjaW9uIjtpOjYyOTtzOjY6Im5vbWJyZSI7czo1OiJKdWxpYSI7czo4OiJhcGVsbGlkbyI7czo3OiJHYXJjw61hIjtzOjQ6ImF1bGEiO3M6OToiQXVsYSA2OTJBIjtzOjEzOiJkb2NlbnRlX2NhcmdvIjtzOjEzOiJSb2JlcnRvIFNpbHZhIjtzOjEwOiJoYWJpbGl0YWRvIjtiOjE7czoxMToicmVnaXN0cmFkb3IiO047czoxMjoiaG9yYV9pbmdyZXNvIjtOO3M6MTA6ImluY2lkZW5jaWEiO047czoxNzoiZXN0YWRvX2FzaXN0ZW5jaWEiO3M6OToicGVuZGllbnRlIjt9aToyOTthOjExOntzOjM6InNpcyI7czo5OiIyMDIyMDExMjMiO3M6MTQ6ImlkX2luc2NyaXBjaW9uIjtpOjYzMDtzOjY6Im5vbWJyZSI7czo1OiJMYXVyYSI7czo4OiJhcGVsbGlkbyI7czo1OiJEw61heiI7czo0OiJhdWxhIjtzOjk6IkF1bGEgNjkyQSI7czoxMzoiZG9jZW50ZV9jYXJnbyI7czoxMzoiUm9iZXJ0byBTaWx2YSI7czoxMDoiaGFiaWxpdGFkbyI7YjoxO3M6MTE6InJlZ2lzdHJhZG9yIjtOO3M6MTI6ImhvcmFfaW5ncmVzbyI7TjtzOjEwOiJpbmNpZGVuY2lhIjtOO3M6MTc6ImVzdGFkb19hc2lzdGVuY2lhIjtzOjk6InBlbmRpZW50ZSI7fWk6MzA7YToxMTp7czozOiJzaXMiO3M6OToiMjAyMjAxMTQ1IjtzOjE0OiJpZF9pbnNjcmlwY2lvbiI7aTo2MzE7czo2OiJub21icmUiO3M6NToiTmFkaWEiO3M6ODoiYXBlbGxpZG8iO3M6NDoiVmVyYSI7czo0OiJhdWxhIjtzOjk6IkF1bGEgNjkyQSI7czoxMzoiZG9jZW50ZV9jYXJnbyI7czoxMzoiUm9iZXJ0byBTaWx2YSI7czoxMDoiaGFiaWxpdGFkbyI7YjoxO3M6MTE6InJlZ2lzdHJhZG9yIjtOO3M6MTI6ImhvcmFfaW5ncmVzbyI7TjtzOjEwOiJpbmNpZGVuY2lhIjtOO3M6MTc6ImVzdGFkb19hc2lzdGVuY2lhIjtzOjk6InBlbmRpZW50ZSI7fWk6MzE7YToxMTp7czozOiJzaXMiO3M6OToiMjAyMjAxMTY3IjtzOjE0OiJpZF9pbnNjcmlwY2lvbiI7aTo2MzI7czo2OiJub21icmUiO3M6ODoiUGF0cmljaWEiO3M6ODoiYXBlbGxpZG8iO3M6NjoiTWFtYW5pIjtzOjQ6ImF1bGEiO3M6OToiQXVsYSA2OTJBIjtzOjEzOiJkb2NlbnRlX2NhcmdvIjtzOjEzOiJSb2JlcnRvIFNpbHZhIjtzOjEwOiJoYWJpbGl0YWRvIjtiOjE7czoxMToicmVnaXN0cmFkb3IiO047czoxMjoiaG9yYV9pbmdyZXNvIjtOO3M6MTA6ImluY2lkZW5jaWEiO047czoxNzoiZXN0YWRvX2FzaXN0ZW5jaWEiO3M6OToicGVuZGllbnRlIjt9aTozMjthOjExOntzOjM6InNpcyI7czo5OiIyMDIyMDExODkiO3M6MTQ6ImlkX2luc2NyaXBjaW9uIjtpOjYzMztzOjY6Im5vbWJyZSI7czo2OiJTb2bDrWEiO3M6ODoiYXBlbGxpZG8iO3M6NjoiRmxvcmVzIjtzOjQ6ImF1bGEiO3M6OToiQXVsYSA2OTJBIjtzOjEzOiJkb2NlbnRlX2NhcmdvIjtzOjEzOiJSb2JlcnRvIFNpbHZhIjtzOjEwOiJoYWJpbGl0YWRvIjtiOjE7czoxMToicmVnaXN0cmFkb3IiO047czoxMjoiaG9yYV9pbmdyZXNvIjtOO3M6MTA6ImluY2lkZW5jaWEiO047czoxNzoiZXN0YWRvX2FzaXN0ZW5jaWEiO3M6OToicGVuZGllbnRlIjt9aTozMzthOjExOntzOjM6InNpcyI7czo5OiIyMDIyMDEyMDEiO3M6MTQ6ImlkX2luc2NyaXBjaW9uIjtpOjYzNDtzOjY6Im5vbWJyZSI7czo3OiJWYWxlcmlhIjtzOjg6ImFwZWxsaWRvIjtzOjY6IkzDs3BleiI7czo0OiJhdWxhIjtzOjk6IkF1bGEgNjkyQSI7czoxMzoiZG9jZW50ZV9jYXJnbyI7czoxMzoiUm9iZXJ0byBTaWx2YSI7czoxMDoiaGFiaWxpdGFkbyI7YjoxO3M6MTE6InJlZ2lzdHJhZG9yIjtOO3M6MTI6ImhvcmFfaW5ncmVzbyI7TjtzOjEwOiJpbmNpZGVuY2lhIjtOO3M6MTc6ImVzdGFkb19hc2lzdGVuY2lhIjtzOjk6InBlbmRpZW50ZSI7fWk6MzQ7YToxMTp7czozOiJzaXMiO3M6OToiMjAyMjAxMjEyIjtzOjE0OiJpZF9pbnNjcmlwY2lvbiI7aTo2MzU7czo2OiJub21icmUiO3M6NToiV2VuZHkiO3M6ODoiYXBlbGxpZG8iO3M6NjoiWmFwYXRhIjtzOjQ6ImF1bGEiO3M6OToiQXVsYSA2OTJBIjtzOjEzOiJkb2NlbnRlX2NhcmdvIjtzOjEzOiJSb2JlcnRvIFNpbHZhIjtzOjEwOiJoYWJpbGl0YWRvIjtiOjE7czoxMToicmVnaXN0cmFkb3IiO047czoxMjoiaG9yYV9pbmdyZXNvIjtOO3M6MTA6ImluY2lkZW5jaWEiO047czoxNzoiZXN0YWRvX2FzaXN0ZW5jaWEiO3M6OToicGVuZGllbnRlIjt9aTozNTthOjExOntzOjM6InNpcyI7czo5OiIyMDIyMDEyMzQiO3M6MTQ6ImlkX2luc2NyaXBjaW9uIjtpOjYzNjtzOjY6Im5vbWJyZSI7czo3OiJZb2xhbmRhIjtzOjg6ImFwZWxsaWRvIjtzOjY6IlRpY29uYSI7czo0OiJhdWxhIjtzOjk6IkF1bGEgNjkyQSI7czoxMzoiZG9jZW50ZV9jYXJnbyI7czoxMzoiUm9iZXJ0byBTaWx2YSI7czoxMDoiaGFiaWxpdGFkbyI7YjoxO3M6MTE6InJlZ2lzdHJhZG9yIjtOO3M6MTI6ImhvcmFfaW5ncmVzbyI7TjtzOjEwOiJpbmNpZGVuY2lhIjtOO3M6MTc6ImVzdGFkb19hc2lzdGVuY2lhIjtzOjk6InBlbmRpZW50ZSI7fWk6MzY7YToxMTp7czozOiJzaXMiO3M6OToiMjAyMjAxMjU2IjtzOjE0OiJpZF9pbnNjcmlwY2lvbiI7aTo2Mzc7czo2OiJub21icmUiO3M6NzoiQW5kcsOpcyI7czo4OiJhcGVsbGlkbyI7czo1OiJSb2phcyI7czo0OiJhdWxhIjtzOjk6IkF1bGEgNjkyQSI7czoxMzoiZG9jZW50ZV9jYXJnbyI7czoxMzoiUm9iZXJ0byBTaWx2YSI7czoxMDoiaGFiaWxpdGFkbyI7YjoxO3M6MTE6InJlZ2lzdHJhZG9yIjtOO3M6MTI6ImhvcmFfaW5ncmVzbyI7TjtzOjEwOiJpbmNpZGVuY2lhIjtOO3M6MTc6ImVzdGFkb19hc2lzdGVuY2lhIjtzOjk6InBlbmRpZW50ZSI7fWk6Mzc7YToxMTp7czozOiJzaXMiO3M6OToiMjAyMjAxMjc4IjtzOjE0OiJpZF9pbnNjcmlwY2lvbiI7aTo2Mzg7czo2OiJub21icmUiO3M6ODoiQ3Jpc3RpYW4iO3M6ODoiYXBlbGxpZG8iO3M6NjoiTW9saW5hIjtzOjQ6ImF1bGEiO3M6OToiQXVsYSA2OTJBIjtzOjEzOiJkb2NlbnRlX2NhcmdvIjtzOjEzOiJSb2JlcnRvIFNpbHZhIjtzOjEwOiJoYWJpbGl0YWRvIjtiOjE7czoxMToicmVnaXN0cmFkb3IiO047czoxMjoiaG9yYV9pbmdyZXNvIjtOO3M6MTA6ImluY2lkZW5jaWEiO047czoxNzoiZXN0YWRvX2FzaXN0ZW5jaWEiO3M6OToicGVuZGllbnRlIjt9aTozODthOjExOntzOjM6InNpcyI7czo5OiIyMDIyMDEyOTAiO3M6MTQ6ImlkX2luc2NyaXBjaW9uIjtpOjYzOTtzOjY6Im5vbWJyZSI7czo3OiJFZHVhcmRvIjtzOjg6ImFwZWxsaWRvIjtzOjc6IlNhbGF6YXIiO3M6NDoiYXVsYSI7czo5OiJBdWxhIDY5MkEiO3M6MTM6ImRvY2VudGVfY2FyZ28iO3M6MTM6IlJvYmVydG8gU2lsdmEiO3M6MTA6ImhhYmlsaXRhZG8iO2I6MTtzOjExOiJyZWdpc3RyYWRvciI7TjtzOjEyOiJob3JhX2luZ3Jlc28iO047czoxMDoiaW5jaWRlbmNpYSI7TjtzOjE3OiJlc3RhZG9fYXNpc3RlbmNpYSI7czo5OiJwZW5kaWVudGUiO31pOjM5O2E6MTE6e3M6Mzoic2lzIjtzOjk6IjIwMjIwMTMxMiI7czoxNDoiaWRfaW5zY3JpcGNpb24iO2k6NjQwO3M6Njoibm9tYnJlIjtzOjc6IkdvbnphbG8iO3M6ODoiYXBlbGxpZG8iO3M6NDoiQXJjZSI7czo0OiJhdWxhIjtzOjk6IkF1bGEgNjkyQSI7czoxMzoiZG9jZW50ZV9jYXJnbyI7czoxMzoiUm9iZXJ0byBTaWx2YSI7czoxMDoiaGFiaWxpdGFkbyI7YjoxO3M6MTE6InJlZ2lzdHJhZG9yIjtOO3M6MTI6ImhvcmFfaW5ncmVzbyI7TjtzOjEwOiJpbmNpZGVuY2lhIjtOO3M6MTc6ImVzdGFkb19hc2lzdGVuY2lhIjtzOjk6InBlbmRpZW50ZSI7fWk6NDA7YToxMTp7czozOiJzaXMiO3M6OToiMjAyMjAxMzM0IjtzOjE0OiJpZF9pbnNjcmlwY2lvbiI7aTo2NDE7czo2OiJub21icmUiO3M6NToiSW7DqXMiO3M6ODoiYXBlbGxpZG8iO3M6NzoiRGVsZ2FkbyI7czo0OiJhdWxhIjtzOjk6IkF1bGEgNjkyQSI7czoxMzoiZG9jZW50ZV9jYXJnbyI7czoxMzoiUm9iZXJ0byBTaWx2YSI7czoxMDoiaGFiaWxpdGFkbyI7YjoxO3M6MTE6InJlZ2lzdHJhZG9yIjtOO3M6MTI6ImhvcmFfaW5ncmVzbyI7TjtzOjEwOiJpbmNpZGVuY2lhIjtOO3M6MTc6ImVzdGFkb19hc2lzdGVuY2lhIjtzOjk6InBlbmRpZW50ZSI7fWk6NDE7YToxMTp7czozOiJzaXMiO3M6OToiMjAyMjAxMzU2IjtzOjE0OiJpZF9pbnNjcmlwY2lvbiI7aTo2NDI7czo2OiJub21icmUiO3M6NToiS2FyZW4iO3M6ODoiYXBlbGxpZG8iO3M6NzoiRnVlbnRlcyI7czo0OiJhdWxhIjtzOjk6IkF1bGEgNjkyQSI7czoxMzoiZG9jZW50ZV9jYXJnbyI7czoxMzoiUm9iZXJ0byBTaWx2YSI7czoxMDoiaGFiaWxpdGFkbyI7YjoxO3M6MTE6InJlZ2lzdHJhZG9yIjtOO3M6MTI6ImhvcmFfaW5ncmVzbyI7TjtzOjEwOiJpbmNpZGVuY2lhIjtOO3M6MTc6ImVzdGFkb19hc2lzdGVuY2lhIjtzOjk6InBlbmRpZW50ZSI7fWk6NDI7YToxMTp7czozOiJzaXMiO3M6OToiMjAyMjAxMzc4IjtzOjE0OiJpZF9pbnNjcmlwY2lvbiI7aTo2NDM7czo2OiJub21icmUiO3M6NjoiTWFyw61hIjtzOjg6ImFwZWxsaWRvIjtzOjc6IkhlcnJlcmEiO3M6NDoiYXVsYSI7czo5OiJBdWxhIDY5MkEiO3M6MTM6ImRvY2VudGVfY2FyZ28iO3M6MTM6IlJvYmVydG8gU2lsdmEiO3M6MTA6ImhhYmlsaXRhZG8iO2I6MTtzOjExOiJyZWdpc3RyYWRvciI7TjtzOjEyOiJob3JhX2luZ3Jlc28iO047czoxMDoiaW5jaWRlbmNpYSI7TjtzOjE3OiJlc3RhZG9fYXNpc3RlbmNpYSI7czo5OiJwZW5kaWVudGUiO31pOjQzO2E6MTE6e3M6Mzoic2lzIjtzOjk6IjIwMjIwMTM5MCI7czoxNDoiaWRfaW5zY3JpcGNpb24iO2k6NjQ0O3M6Njoibm9tYnJlIjtzOjQ6Ik9sZ2EiO3M6ODoiYXBlbGxpZG8iO3M6ODoiSmltw6luZXoiO3M6NDoiYXVsYSI7czo5OiJBdWxhIDY5MkEiO3M6MTM6ImRvY2VudGVfY2FyZ28iO3M6MTM6IlJvYmVydG8gU2lsdmEiO3M6MTA6ImhhYmlsaXRhZG8iO2I6MTtzOjExOiJyZWdpc3RyYWRvciI7TjtzOjEyOiJob3JhX2luZ3Jlc28iO047czoxMDoiaW5jaWRlbmNpYSI7TjtzOjE3OiJlc3RhZG9fYXNpc3RlbmNpYSI7czo5OiJwZW5kaWVudGUiO31pOjQ0O2E6MTE6e3M6Mzoic2lzIjtzOjk6IjIwMjIwMTQxMiI7czoxNDoiaWRfaW5zY3JpcGNpb24iO2k6NjQ1O3M6Njoibm9tYnJlIjtzOjQ6IlJvc2EiO3M6ODoiYXBlbGxpZG8iO3M6NDoiTHVuYSI7czo0OiJhdWxhIjtzOjk6IkF1bGEgNjkyQSI7czoxMzoiZG9jZW50ZV9jYXJnbyI7czoxMzoiUm9iZXJ0byBTaWx2YSI7czoxMDoiaGFiaWxpdGFkbyI7YjoxO3M6MTE6InJlZ2lzdHJhZG9yIjtOO3M6MTI6ImhvcmFfaW5ncmVzbyI7TjtzOjEwOiJpbmNpZGVuY2lhIjtOO3M6MTc6ImVzdGFkb19hc2lzdGVuY2lhIjtzOjk6InBlbmRpZW50ZSI7fWk6NDU7YToxMTp7czozOiJzaXMiO3M6OToiMjAyMjAxNDM0IjtzOjE0OiJpZF9pbnNjcmlwY2lvbiI7aTo2NDY7czo2OiJub21icmUiO3M6NzoiVGF0aWFuYSI7czo4OiJhcGVsbGlkbyI7czo3OiJOw7rDsWV6IjtzOjQ6ImF1bGEiO3M6OToiQXVsYSA2OTJBIjtzOjEzOiJkb2NlbnRlX2NhcmdvIjtzOjEzOiJSb2JlcnRvIFNpbHZhIjtzOjEwOiJoYWJpbGl0YWRvIjtiOjE7czoxMToicmVnaXN0cmFkb3IiO047czoxMjoiaG9yYV9pbmdyZXNvIjtOO3M6MTA6ImluY2lkZW5jaWEiO047czoxNzoiZXN0YWRvX2FzaXN0ZW5jaWEiO3M6OToicGVuZGllbnRlIjt9aTo0NjthOjExOntzOjM6InNpcyI7czo5OiIyMDIyMDE0NTYiO3M6MTQ6ImlkX2luc2NyaXBjaW9uIjtpOjY0NztzOjY6Im5vbWJyZSI7czo5OiJWZXLDs25pY2EiO3M6ODoiYXBlbGxpZG8iO3M6MzoiUGF6IjtzOjQ6ImF1bGEiO3M6OToiQXVsYSA2OTJBIjtzOjEzOiJkb2NlbnRlX2NhcmdvIjtzOjEzOiJSb2JlcnRvIFNpbHZhIjtzOjEwOiJoYWJpbGl0YWRvIjtiOjE7czoxMToicmVnaXN0cmFkb3IiO047czoxMjoiaG9yYV9pbmdyZXNvIjtOO3M6MTA6ImluY2lkZW5jaWEiO047czoxNzoiZXN0YWRvX2FzaXN0ZW5jaWEiO3M6OToicGVuZGllbnRlIjt9aTo0NzthOjExOntzOjM6InNpcyI7czo5OiIyMDIyMDE0NzgiO3M6MTQ6ImlkX2luc2NyaXBjaW9uIjtpOjY0ODtzOjY6Im5vbWJyZSI7czo2OiJYaW1lbmEiO3M6ODoiYXBlbGxpZG8iO3M6ODoiUmFtw61yZXoiO3M6NDoiYXVsYSI7czo5OiJBdWxhIDY5MkEiO3M6MTM6ImRvY2VudGVfY2FyZ28iO3M6MTM6IlJvYmVydG8gU2lsdmEiO3M6MTA6ImhhYmlsaXRhZG8iO2I6MTtzOjExOiJyZWdpc3RyYWRvciI7TjtzOjEyOiJob3JhX2luZ3Jlc28iO047czoxMDoiaW5jaWRlbmNpYSI7TjtzOjE3OiJlc3RhZG9fYXNpc3RlbmNpYSI7czo5OiJwZW5kaWVudGUiO31pOjQ4O2E6MTE6e3M6Mzoic2lzIjtzOjk6IjIwMjIwMTQ5MCI7czoxNDoiaWRfaW5zY3JpcGNpb24iO2k6NjQ5O3M6Njoibm9tYnJlIjtzOjU6IlphaXJhIjtzOjg6ImFwZWxsaWRvIjtzOjY6IlRvbGVkbyI7czo0OiJhdWxhIjtzOjk6IkF1bGEgNjkyQSI7czoxMzoiZG9jZW50ZV9jYXJnbyI7czoxMzoiUm9iZXJ0byBTaWx2YSI7czoxMDoiaGFiaWxpdGFkbyI7YjoxO3M6MTE6InJlZ2lzdHJhZG9yIjtOO3M6MTI6ImhvcmFfaW5ncmVzbyI7TjtzOjEwOiJpbmNpZGVuY2lhIjtOO3M6MTc6ImVzdGFkb19hc2lzdGVuY2lhIjtzOjk6InBlbmRpZW50ZSI7fWk6NDk7YToxMTp7czozOiJzaXMiO3M6OToiMjAyMjAyMDQ1IjtzOjE0OiJpZF9pbnNjcmlwY2lvbiI7aTo2NTA7czo2OiJub21icmUiO3M6NToiRGllZ28iO3M6ODoiYXBlbGxpZG8iO3M6NDoiU290byI7czo0OiJhdWxhIjtzOjk6IkF1bGEgNjkyQSI7czoxMzoiZG9jZW50ZV9jYXJnbyI7czoxMzoiUm9iZXJ0byBTaWx2YSI7czoxMDoiaGFiaWxpdGFkbyI7YjoxO3M6MTE6InJlZ2lzdHJhZG9yIjtOO3M6MTI6ImhvcmFfaW5ncmVzbyI7TjtzOjEwOiJpbmNpZGVuY2lhIjtOO3M6MTc6ImVzdGFkb19hc2lzdGVuY2lhIjtzOjk6InBlbmRpZW50ZSI7fX1zOjI4OiIAKgBlc2NhcGVXaGVuQ2FzdGluZ1RvU3RyaW5nIjtiOjA7fX0=', 1793067379);

INSERT INTO public.cache ("key", "value", "expiration") VALUES ('catalogos.examen.ambientes', 'a:5:{i:0;a:3:{s:2:"id";i:1;s:6:"nombre";s:8:"Aula 623";s:7:"termino";s:8:"aula 623";}i:1;a:3:{s:2:"id";i:5;s:6:"nombre";s:9:"Aula 690B";s:7:"termino";s:9:"aula 690b";}i:2;a:3:{s:2:"id";i:2;s:6:"nombre";s:9:"Aula 692A";s:7:"termino";s:9:"aula 692a";}i:3;a:3:{s:2:"id";i:3;s:6:"nombre";s:13:"Laboratorio 1";s:7:"termino";s:13:"laboratorio 1";}i:4;a:3:{s:2:"id";i:4;s:6:"nombre";s:13:"Laboratorio 2";s:7:"termino";s:13:"laboratorio 2";}}', 1793099009);

INSERT INTO public.cache ("key", "value", "expiration") VALUES ('catalogos.examen.materiales', 'a:5:{i:0;a:2:{s:2:"id";i:4;s:11:"descripcion";s:21:"Apuntes de la materia";}i:1;a:2:{s:2:"id";i:1;s:11:"descripcion";s:22:"Calculadora cientifica";}i:2;a:2:{s:2:"id";i:2;s:11:"descripcion";s:13:"Computadora
";}i:3;a:2:{s:2:"id";i:3;s:11:"descripcion";s:10:"Formulario";}i:4;a:2:{s:2:"id";i:5;s:11:"descripcion";s:25:"Hojas de examen en blanco";}}', 1793099010);

INSERT INTO public.cache ("key", "value", "expiration") VALUES ('catalogos.examen.normas', 'a:5:{i:0;a:2:{s:2:"id";i:2;s:7:"detalle";s:45:"Mantener dispositivos electrónicos guardados";}i:1;a:2:{s:2:"id";i:5;s:7:"detalle";s:34:"No utilizar material no autorizado";}i:2;a:2:{s:2:"id";i:1;s:7:"detalle";s:37:"Portar CI durante toda la evaluación";}i:3;a:2:{s:2:"id";i:4;s:7:"detalle";s:46:"Prohibido hablar, consultar levantando la mano";}i:4;a:2:{s:2:"id";i:3;s:7:"detalle";s:41:"Tolerancia máxima de ingreso: 15 minutos";}}', 1793099012);

INSERT INTO public.cache ("key", "value", "expiration") VALUES ('catalogos.examen.tipo.examen parcial', 'i:1;', 1793099060);

INSERT INTO public.cache ("key", "value", "expiration") VALUES ('monitoreo.informe.29.docente.1', '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cmlhbGVzUGVyc29uYWxpemFkb3MiO086Mzk6IklsbHVtaW5hdGVcRGF0YWJhc2VcRWxvcXVlbnRcQ29sbGVjdGlvbiI6Mjp7czo4OiIAKgBpdGVtcyI7YTowOnt9czoyODoiACoAZXNjYXBlV2hlbkNhc3RpbmdUb1N0cmluZyI7YjowO31zOjIwOiJub3JtYXNQZXJzb25hbGl6YWRhcyI7TzozOToiSWxsdW1pbmF0ZVxEYXRhYmFzZVxFbG9xdWVudFxDb2xsZWN0aW9uIjoyOntzOjg6IgAqAGl0ZW1zIjthOjA6e31zOjI4OiIAKgBlc2NhcGVXaGVuQ2FzdGluZ1RvU3RyaW5nIjtiOjA7fXM6MTA6InRpcG9FeGFtZW4iO086MjE6IkFwcFxNb2RlbHNcVGlwb0V4YW1lbiI6MzA6e3M6MTM6IgAqAGNvbm5lY3Rpb24iO3M6NToicGdzcWwiO3M6ODoiACoAdGFibGUiO3M6MTE6InRpcG9fZXhhbWVuIjtzOjEzOiIAKgBwcmltYXJ5S2V5IjtzOjE0OiJpZF90aXBvX2V4YW1lbiI7czoxMDoiACoAa2V5VHlwZSI7czozOiJpbnQiO3M6MTI6ImluY3JlbWVudGluZyI7YjowO3M6NzoiACoAd2l0aCI7YTowOnt9czoxMjoiACoAd2l0aENvdW50IjthOjA6e31zOjE5OiJwcmV2ZW50c0xhenlMb2FkaW5nIjtiOjA7czoxMDoiACoAcGVyUGFnZSI7aToxNTtzOjY6ImV4aXN0cyI7YjoxO3M6MTg6Indhc1JlY2VudGx5Q3JlYXRlZCI7YjowO3M6Mjg6IgAqAGVzY2FwZVdoZW5DYXN0aW5nVG9TdHJpbmciO2I6MDtzOjEzOiIAKgBhdHRyaWJ1dGVzIjthOjI6e3M6MTQ6ImlkX3RpcG9fZXhhbWVuIjtpOjE7czoxODoibm9tYnJlX3RpcG9fZXhhbWVuIjtzOjE0OiJleGFtZW4gcGFyY2lhbCI7fXM6MTE6IgAqAG9yaWdpbmFsIjthOjI6e3M6MTQ6ImlkX3RpcG9fZXhhbWVuIjtpOjE7czoxODoibm9tYnJlX3RpcG9fZXhhbWVuIjtzOjE0OiJleGFtZW4gcGFyY2lhbCI7fXM6MTA6IgAqAGNoYW5nZXMiO2E6MDp7fXM6ODoiACoAY2FzdHMiO2E6MDp7fXM6MTc6IgAqAGNsYXNzQ2FzdENhY2hlIjthOjA6e31zOjIxOiIAKgBhdHRyaWJ1dGVDYXN0Q2FjaGUiO2E6MDp7fXM6MTM6IgAqAGRhdGVGb3JtYXQiO047czoxMDoiACoAYXBwZW5kcyI7YTowOnt9czoxOToiACoAZGlzcGF0Y2hlc0V2ZW50cyI7YTowOnt9czoxNDoiACoAb2JzZXJ2YWJsZXMiO2E6MDp7fXM6MTI6IgAqAHJlbGF0aW9ucyI7YTowOnt9czoxMDoiACoAdG91Y2hlcyI7YTowOnt9czoxMDoidGltZXN0YW1wcyI7YjowO3M6MTM6InVzZXNVbmlxdWVJZHMiO2I6MDtzOjk6IgAqAGhpZGRlbiI7YTowOnt9czoxMDoiACoAdmlzaWJsZSI7YTowOnt9czoxMToiACoAZmlsbGFibGUiO2E6Mjp7aTowO3M6MTQ6ImlkX3RpcG9fZXhhbWVuIjtpOjE7czoxODoibm9tYnJlX3RpcG9fZXhhbWVuIjt9czoxMDoiACoAZ3VhcmRlZCI7YToxOntpOjA7czoxOiIqIjt9fX1zOjEwOiIAKgB0b3VjaGVzIjthOjA6e31zOjEwOiJ0aW1lc3RhbXBzIjtiOjA7czoxMzoidXNlc1VuaXF1ZUlkcyI7YjowO3M6OToiACoAaGlkZGVuIjthOjA6e31zOjEwOiIAKgB2aXNpYmxlIjthOjA6e31zOjExOiIAKgBmaWxsYWJsZSI7YTo3OntpOjA7czo5OiJpZF9leGFtZW4iO2k6MTtzOjEzOiJub21icmVfZXhhbWVuIjtpOjI7czo1OiJmZWNoYSI7aTozO3M6MTE6ImhvcmFfaW5pY2lvIjtpOjQ7czo4OiJkdXJhY2lvbiI7aTo1O3M6NzoiY3JlYWRvciI7aTo2O3M6MTE6InRpcG9fZXhhbWVuIjt9czoxMDoiACoAZ3VhcmRlZCI7YToxOntpOjA7czoxOiIqIjt9fXM6MTE6ImVzdHVkaWFudGVzIjtPOjI5OiJJbGx1bWluYXRlXFN1cHBvcnRcQ29sbGVjdGlvbiI6Mjp7czo4OiIAKgBpdGVtcyI7YTo1MDp7aTowO2E6MTE6e3M6Mzoic2lzIjtzOjk6IjIwMjAwMjEwNyI7czoxNDoiaWRfaW5zY3JpcGNpb24iO2k6NjUyO3M6Njoibm9tYnJlIjtzOjc6IkVybmVzdG8iO3M6ODoiYXBlbGxpZG8iO3M6NDoiVmVyYSI7czo0OiJhdWxhIjtzOjg6IkF1bGEgNjIzIjtzOjEzOiJkb2NlbnRlX2NhcmdvIjtzOjEzOiJSb2JlcnRvIFNpbHZhIjtzOjEwOiJoYWJpbGl0YWRvIjtiOjE7czoxMToicmVnaXN0cmFkb3IiO047czoxMjoiaG9yYV9pbmdyZXNvIjtOO3M6MTA6ImluY2lkZW5jaWEiO047czoxNzoiZXN0YWRvX2FzaXN0ZW5jaWEiO3M6OToicGVuZGllbnRlIjt9aToxO2E6MTE6e3M6Mzoic2lzIjtzOjg6IjIwMjEwMDAzIjtzOjE0OiJpZF9pbnNjcmlwY2lvbiI7aTo2NTM7czo2OiJub21icmUiO3M6NToiTWFyaWEiO3M6ODoiYXBlbGxpZG8iO3M6NToiTG9wZXoiO3M6NDoiYXVsYSI7czo4OiJBdWxhIDYyMyI7czoxMzoiZG9jZW50ZV9jYXJnbyI7czoxMzoiUm9iZXJ0byBTaWx2YSI7czoxMDoiaGFiaWxpdGFkbyI7YjoxO3M6MTE6InJlZ2lzdHJhZG9yIjtOO3M6MTI6ImhvcmFfaW5ncmVzbyI7TjtzOjEwOiJpbmNpZGVuY2lhIjtOO3M6MTc6ImVzdGFkb19hc2lzdGVuY2lhIjtzOjk6InBlbmRpZW50ZSI7fWk6MjthOjExOntzOjM6InNpcyI7czo5OiIyMDIxMDEwMjIiO3M6MTQ6ImlkX2luc2NyaXBjaW9uIjtpOjY1NDtzOjY6Im5vbWJyZSI7czo1OiJCcnVubyI7czo4OiJhcGVsbGlkbyI7czo1OiJEw61heiI7czo0OiJhdWxhIjtzOjg6IkF1bGEgNjIzIjtzOjEzOiJkb2NlbnRlX2NhcmdvIjtzOjEzOiJSb2JlcnRvIFNpbHZhIjtzOjEwOiJoYWJpbGl0YWRvIjtiOjE7czoxMToicmVnaXN0cmFkb3IiO047czoxMjoiaG9yYV9pbmdyZXNvIjtOO3M6MTA6ImluY2lkZW5jaWEiO047czoxNzoiZXN0YWRvX2FzaXN0ZW5jaWEiO3M6OToicGVuZGllbnRlIjt9aTozO2E6MTE6e3M6Mzoic2lzIjtzOjk6IjIwMjEwMTA3OCI7czoxNDoiaWRfaW5zY3JpcGNpb24iO2k6NjU1O3M6Njoibm9tYnJlIjtzOjc6IkdhYnJpZWwiO3M6ODoiYXBlbGxpZG8iO3M6NjoiTWFtYW5pIjtzOjQ6ImF1bGEiO3M6ODoiQXVsYSA2MjMiO3M6MTM6ImRvY2VudGVfY2FyZ28iO3M6MTM6IlJvYmVydG8gU2lsdmEiO3M6MTA6ImhhYmlsaXRhZG8iO2I6MTtzOjExOiJyZWdpc3RyYWRvciI7TjtzOjEyOiJob3JhX2luZ3Jlc28iO047czoxMDoiaW5jaWRlbmNpYSI7TjtzOjE3OiJlc3RhZG9fYXNpc3RlbmNpYSI7czo5OiJwZW5kaWVudGUiO31pOjQ7YToxMTp7czozOiJzaXMiO3M6OToiMjAyMTAxMDkwIjtzOjE0OiJpZF9pbnNjcmlwY2lvbiI7aTo2NTY7czo2OiJub21icmUiO3M6NToiSXbDoW4iO3M6ODoiYXBlbGxpZG8iO3M6NjoiRmxvcmVzIjtzOjQ6ImF1bGEiO3M6ODoiQXVsYSA2MjMiO3M6MTM6ImRvY2VudGVfY2FyZ28iO3M6MTM6IlJvYmVydG8gU2lsdmEiO3M6MTA6ImhhYmlsaXRhZG8iO2I6MTtzOjExOiJyZWdpc3RyYWRvciI7TjtzOjEyOiJob3JhX2luZ3Jlc28iO047czoxMDoiaW5jaWRlbmNpYSI7TjtzOjE3OiJlc3RhZG9fYXNpc3RlbmNpYSI7czo5OiJwZW5kaWVudGUiO31pOjU7YToxMTp7czozOiJzaXMiO3M6OToiMjAyMTAxMTEyIjtzOjE0OiJpZF9pbnNjcmlwY2lvbiI7aTo2NTc7czo2OiJub21icmUiO3M6NToiS2V2aW4iO3M6ODoiYXBlbGxpZG8iO3M6NjoiTMOzcGV6IjtzOjQ6ImF1bGEiO3M6ODoiQXVsYSA2MjMiO3M6MTM6ImRvY2VudGVfY2FyZ28iO3M6MTM6IlJvYmVydG8gU2lsdmEiO3M6MTA6ImhhYmlsaXRhZG8iO2I6MTtzOjExOiJyZWdpc3RyYWRvciI7TjtzOjEyOiJob3JhX2luZ3Jlc28iO047czoxMDoiaW5jaWRlbmNpYSI7TjtzOjE3OiJlc3RhZG9fYXNpc3RlbmNpYSI7czo5OiJwZW5kaWVudGUiO31pOjY7YToxMTp7czozOiJzaXMiO3M6OToiMjAyMTAxMTM0IjtzOjE0OiJpZF9pbnNjcmlwY2lvbiI7aTo2NTg7czo2OiJub21icmUiO3M6NjoiTWlndWVsIjtzOjg6ImFwZWxsaWRvIjtzOjQ6IlNvdG8iO3M6NDoiYXVsYSI7czo4OiJBdWxhIDYyMyI7czoxMzoiZG9jZW50ZV9jYXJnbyI7czoxMzoiUm9iZXJ0byBTaWx2YSI7czoxMDoiaGFiaWxpdGFkbyI7YjoxO3M6MTE6InJlZ2lzdHJhZG9yIjtOO3M6MTI6ImhvcmFfaW5ncmVzbyI7TjtzOjEwOiJpbmNpZGVuY2lhIjtOO3M6MTc6ImVzdGFkb19hc2lzdGVuY2lhIjtzOjk6InBlbmRpZW50ZSI7fWk6NzthOjExOntzOjM6InNpcyI7czo5OiIyMDIxMDExNTYiO3M6MTQ6ImlkX2luc2NyaXBjaW9uIjtpOjY1OTtzOjY6Im5vbWJyZSI7czo2OiLDk3NjYXIiO3M6ODoiYXBlbGxpZG8iO3M6NjoiUXVpc3BlIjtzOjQ6ImF1bGEiO3M6ODoiQXVsYSA2MjMiO3M6MTM6ImRvY2VudGVfY2FyZ28iO3M6MTM6IlJvYmVydG8gU2lsdmEiO3M6MTA6ImhhYmlsaXRhZG8iO2I6MTtzOjExOiJyZWdpc3RyYWRvciI7TjtzOjEyOiJob3JhX2luZ3Jlc28iO047czoxMDoiaW5jaWRlbmNpYSI7TjtzOjE3OiJlc3RhZG9fYXNpc3RlbmNpYSI7czo5OiJwZW5kaWVudGUiO31pOjg7YToxMTp7czozOiJzaXMiO3M6OToiMjAyMTAxMTc4IjtzOjE0OiJpZF9pbnNjcmlwY2lvbiI7aTo2NjA7czo2OiJub21icmUiO3M6NToiUmHDumwiO3M6ODoiYXBlbGxpZG8iO3M6NjoiQ2hvcXVlIjtzOjQ6ImF1bGEiO3M6ODoiQXVsYSA2MjMiO3M6MTM6ImRvY2VudGVfY2FyZ28iO3M6MTM6IlJvYmVydG8gU2lsdmEiO3M6MTA6ImhhYmlsaXRhZG8iO2I6MTtzOjExOiJyZWdpc3RyYWRvciI7TjtzOjEyOiJob3JhX2luZ3Jlc28iO047czoxMDoiaW5jaWRlbmNpYSI7TjtzOjE3OiJlc3RhZG9fYXNpc3RlbmNpYSI7czo5OiJwZW5kaWVudGUiO31pOjk7YToxMTp7czozOiJzaXMiO3M6OToiMjAyMTAxMTkwIjtzOjE0OiJpZF9pbnNjcmlwY2lvbiI7aTo2NjE7czo2OiJub21icmUiO3M6NjoiVG9tw6FzIjtzOjg6ImFwZWxsaWRvIjtzOjc6IkdhcmPDrWEiO3M6NDoiYXVsYSI7czo4OiJBdWxhIDYyMyI7czoxMzoiZG9jZW50ZV9jYXJnbyI7czoxMzoiUm9iZXJ0byBTaWx2YSI7czoxMDoiaGFiaWxpdGFkbyI7YjoxO3M6MTE6InJlZ2lzdHJhZG9yIjtOO3M6MTI6ImhvcmFfaW5ncmVzbyI7TjtzOjEwOiJpbmNpZGVuY2lhIjtOO3M6MTc6ImVzdGFkb19hc2lzdGVuY2lhIjtzOjk6InBlbmRpZW50ZSI7fWk6MTA7YToxMTp7czozOiJzaXMiO3M6OToiMjAyMTAxMjIzIjtzOjE0OiJpZF9pbnNjcmlwY2lvbiI7aTo2NjI7czo2OiJub21icmUiO3M6NjoiWGF2aWVyIjtzOjg6ImFwZWxsaWRvIjtzOjY6IlZhcmdhcyI7czo0OiJhdWxhIjtzOjg6IkF1bGEgNjIzIjtzOjEzOiJkb2NlbnRlX2NhcmdvIjtzOjEzOiJSb2JlcnRvIFNpbHZhIjtzOjEwOiJoYWJpbGl0YWRvIjtiOjE7czoxMToicmVnaXN0cmFkb3IiO047czoxMjoiaG9yYV9pbmdyZXNvIjtOO3M6MTA6ImluY2lkZW5jaWEiO047czoxNzoiZXN0YWRvX2FzaXN0ZW5jaWEiO3M6OToicGVuZGllbnRlIjt9aToxMTthOjExOntzOjM6InNpcyI7czo5OiIyMDIxMDEyNDUiO3M6MTQ6ImlkX2luc2NyaXBjaW9uIjtpOjY2MztzOjY6Im5vbWJyZSI7czozOiJab2UiO3M6ODoiYXBlbGxpZG8iO3M6NToiU2lsZXMiO3M6NDoiYXVsYSI7czo4OiJBdWxhIDYyMyI7czoxMzoiZG9jZW50ZV9jYXJnbyI7czoxMzoiUm9iZXJ0byBTaWx2YSI7czoxMDoiaGFiaWxpdGFkbyI7YjoxO3M6MTE6InJlZ2lzdHJhZG9yIjtOO3M6MTI6ImhvcmFfaW5ncmVzbyI7TjtzOjEwOiJpbmNpZGVuY2lhIjtOO3M6MTc6ImVzdGFkb19hc2lzdGVuY2lhIjtzOjk6InBlbmRpZW50ZSI7fWk6MTI7YToxMTp7czozOiJzaXMiO3M6OToiMjAyMTAxMjY3IjtzOjE0OiJpZF9pbnNjcmlwY2lvbiI7aTo2NjQ7czo2OiJub21icmUiO3M6NjoiQmlhbmNhIjtzOjg6ImFwZWxsaWRvIjtzOjc6IlBhcmVkZXMiO3M6NDoiYXVsYSI7czo4OiJBdWxhIDYyMyI7czoxMzoiZG9jZW50ZV9jYXJnbyI7czoxMzoiUm9iZXJ0byBTaWx2YSI7czoxMDoiaGFiaWxpdGFkbyI7YjoxO3M6MTE6InJlZ2lzdHJhZG9yIjtOO3M6MTI6ImhvcmFfaW5ncmVzbyI7TjtzOjEwOiJpbmNpZGVuY2lhIjtOO3M6MTc6ImVzdGFkb19hc2lzdGVuY2lhIjtzOjk6InBlbmRpZW50ZSI7fWk6MTM7YToxMTp7czozOiJzaXMiO3M6OToiMjAyMTAxMjg5IjtzOjE0OiJpZF9pbnNjcmlwY2lvbiI7aTo2NjU7czo2OiJub21icmUiO3M6NzoiRGFuaWVsYSI7czo4OiJhcGVsbGlkbyI7czo3OiJDYWJyZXJhIjtzOjQ6ImF1bGEiO3M6ODoiQXVsYSA2MjMiO3M6MTM6ImRvY2VudGVfY2FyZ28iO3M6MTM6IlJvYmVydG8gU2lsdmEiO3M6MTA6ImhhYmlsaXRhZG8iO2I6MTtzOjExOiJyZWdpc3RyYWRvciI7TjtzOjEyOiJob3JhX2luZ3Jlc28iO047czoxMDoiaW5jaWRlbmNpYSI7TjtzOjE3OiJlc3RhZG9fYXNpc3RlbmNpYSI7czo5OiJwZW5kaWVudGUiO31pOjE0O2E6MTE6e3M6Mzoic2lzIjtzOjk6IjIwMjEwMTMwMSI7czoxNDoiaWRfaW5zY3JpcGNpb24iO2k6NjY2O3M6Njoibm9tYnJlIjtzOjg6IkZlcm5hbmRhIjtzOjg6ImFwZWxsaWRvIjtzOjQ6IlZlZ2EiO3M6NDoiYXVsYSI7czo4OiJBdWxhIDYyMyI7czoxMzoiZG9jZW50ZV9jYXJnbyI7czoxMzoiUm9iZXJ0byBTaWx2YSI7czoxMDoiaGFiaWxpdGFkbyI7YjoxO3M6MTE6InJlZ2lzdHJhZG9yIjtOO3M6MTI6ImhvcmFfaW5ncmVzbyI7TjtzOjEwOiJpbmNpZGVuY2lhIjtOO3M6MTc6ImVzdGFkb19hc2lzdGVuY2lhIjtzOjk6InBlbmRpZW50ZSI7fWk6MTU7YToxMTp7czozOiJzaXMiO3M6OToiMjAyMTAxMzIzIjtzOjE0OiJpZF9pbnNjcmlwY2lvbiI7aTo2Njc7czo2OiJub21icmUiO3M6NDoiSHVnbyI7czo4OiJhcGVsbGlkbyI7czo3OiJDYW1hY2hvIjtzOjQ6ImF1bGEiO3M6ODoiQXVsYSA2MjMiO3M6MTM6ImRvY2VudGVfY2FyZ28iO3M6MTM6IlJvYmVydG8gU2lsdmEiO3M6MTA6ImhhYmlsaXRhZG8iO2I6MTtzOjExOiJyZWdpc3RyYWRvciI7TjtzOjEyOiJob3JhX2luZ3Jlc28iO047czoxMDoiaW5jaWRlbmNpYSI7TjtzOjE3OiJlc3RhZG9fYXNpc3RlbmNpYSI7czo5OiJwZW5kaWVudGUiO31pOjE2O2E6MTE6e3M6Mzoic2lzIjtzOjk6IjIwMjEwMTM0NSI7czoxNDoiaWRfaW5zY3JpcGNpb24iO2k6NjY4O3M6Njoibm9tYnJlIjtzOjY6IkphdmllciI7czo4OiJhcGVsbGlkbyI7czo4OiJFc3Bpbm96YSI7czo0OiJhdWxhIjtzOjg6IkF1bGEgNjIzIjtzOjEzOiJkb2NlbnRlX2NhcmdvIjtzOjEzOiJSb2JlcnRvIFNpbHZhIjtzOjEwOiJoYWJpbGl0YWRvIjtiOjE7czoxMToicmVnaXN0cmFkb3IiO047czoxMjoiaG9yYV9pbmdyZXNvIjtOO3M6MTA6ImluY2lkZW5jaWEiO047czoxNzoiZXN0YWRvX2FzaXN0ZW5jaWEiO3M6OToicGVuZGllbnRlIjt9aToxNzthOjExOntzOjM6InNpcyI7czo5OiIyMDIxMDEzNjciO3M6MTQ6ImlkX2luc2NyaXBjaW9uIjtpOjY2OTtzOjY6Im5vbWJyZSI7czo0OiJMdWlzIjtzOjg6ImFwZWxsaWRvIjtzOjEwOiJHdXRpw6lycmV6IjtzOjQ6ImF1bGEiO3M6ODoiQXVsYSA2MjMiO3M6MTM6ImRvY2VudGVfY2FyZ28iO3M6MTM6IlJvYmVydG8gU2lsdmEiO3M6MTA6ImhhYmlsaXRhZG8iO2I6MTtzOjExOiJyZWdpc3RyYWRvciI7TjtzOjEyOiJob3JhX2luZ3Jlc28iO047czoxMDoiaW5jaWRlbmNpYSI7TjtzOjE3OiJlc3RhZG9fYXNpc3RlbmNpYSI7czo5OiJwZW5kaWVudGUiO31pOjE4O2E6MTE6e3M6Mzoic2lzIjtzOjk6IjIwMjEwMTM4OSI7czoxNDoiaWRfaW5zY3JpcGNpb24iO2k6NjcwO3M6Njoibm9tYnJlIjtzOjg6Ik5pY29sw6FzIjtzOjg6ImFwZWxsaWRvIjtzOjc6IklyaWFydGUiO3M6NDoiYXVsYSI7czo4OiJBdWxhIDYyMyI7czoxMzoiZG9jZW50ZV9jYXJnbyI7czoxMzoiUm9iZXJ0byBTaWx2YSI7czoxMDoiaGFiaWxpdGFkbyI7YjoxO3M6MTE6InJlZ2lzdHJhZG9yIjtOO3M6MTI6ImhvcmFfaW5ncmVzbyI7TjtzOjEwOiJpbmNpZGVuY2lhIjtOO3M6MTc6ImVzdGFkb19hc2lzdGVuY2lhIjtzOjk6InBlbmRpZW50ZSI7fWk6MTk7YToxMTp7czozOiJzaXMiO3M6OToiMjAyMTAxNDAxIjtzOjE0OiJpZF9pbnNjcmlwY2lvbiI7aTo2NzE7czo2OiJub21icmUiO3M6NToiUGFibG8iO3M6ODoiYXBlbGxpZG8iO3M6ODoiS293YWxza2kiO3M6NDoiYXVsYSI7czo4OiJBdWxhIDYyMyI7czoxMzoiZG9jZW50ZV9jYXJnbyI7czoxMzoiUm9iZXJ0byBTaWx2YSI7czoxMDoiaGFiaWxpdGFkbyI7YjoxO3M6MTE6InJlZ2lzdHJhZG9yIjtOO3M6MTI6ImhvcmFfaW5ncmVzbyI7TjtzOjEwOiJpbmNpZGVuY2lhIjtOO3M6MTc6ImVzdGFkb19hc2lzdGVuY2lhIjtzOjk6InBlbmRpZW50ZSI7fWk6MjA7YToxMTp7czozOiJzaXMiO3M6OToiMjAyMTAxNDIzIjtzOjE0OiJpZF9pbnNjcmlwY2lvbiI7aTo2NzI7czo2OiJub21icmUiO3M6NjoiU2VyZ2lvIjtzOjg6ImFwZWxsaWRvIjtzOjc6Ik1lbmRvemEiO3M6NDoiYXVsYSI7czo4OiJBdWxhIDYyMyI7czoxMzoiZG9jZW50ZV9jYXJnbyI7czoxMzoiUm9iZXJ0byBTaWx2YSI7czoxMDoiaGFiaWxpdGFkbyI7YjoxO3M6MTE6InJlZ2lzdHJhZG9yIjtOO3M6MTI6ImhvcmFfaW5ncmVzbyI7TjtzOjEwOiJpbmNpZGVuY2lhIjtOO3M6MTc6ImVzdGFkb19hc2lzdGVuY2lhIjtzOjk6InBlbmRpZW50ZSI7fWk6MjE7YToxMTp7czozOiJzaXMiO3M6OToiMjAyMTAxNDQ1IjtzOjE0OiJpZF9pbnNjcmlwY2lvbiI7aTo2NzM7czo2OiJub21icmUiO3M6NjoiVWxpc2VzIjtzOjg6ImFwZWxsaWRvIjtzOjg6Ik9yZWxsYW5hIjtzOjQ6ImF1bGEiO3M6ODoiQXVsYSA2MjMiO3M6MTM6ImRvY2VudGVfY2FyZ28iO3M6MTM6IlJvYmVydG8gU2lsdmEiO3M6MTA6ImhhYmlsaXRhZG8iO2I6MTtzOjExOiJyZWdpc3RyYWRvciI7TjtzOjEyOiJob3JhX2luZ3Jlc28iO047czoxMDoiaW5jaWRlbmNpYSI7TjtzOjE3OiJlc3RhZG9fYXNpc3RlbmNpYSI7czo5OiJwZW5kaWVudGUiO31pOjIyO2E6MTE6e3M6Mzoic2lzIjtzOjk6IjIwMjEwMTQ2NyI7czoxNDoiaWRfaW5zY3JpcGNpb24iO2k6Njc0O3M6Njoibm9tYnJlIjtzOjY6IldhbHRlciI7czo4OiJhcGVsbGlkbyI7czo3OiJRdWlyb2dhIjtzOjQ6ImF1bGEiO3M6ODoiQXVsYSA2MjMiO3M6MTM6ImRvY2VudGVfY2FyZ28iO3M6MTM6IlJvYmVydG8gU2lsdmEiO3M6MTA6ImhhYmlsaXRhZG8iO2I6MTtzOjExOiJyZWdpc3RyYWRvciI7TjtzOjEyOiJob3JhX2luZ3Jlc28iO047czoxMDoiaW5jaWRlbmNpYSI7TjtzOjE3OiJlc3RhZG9fYXNpc3RlbmNpYSI7czo5OiJwZW5kaWVudGUiO31pOjIzO2E6MTE6e3M6Mzoic2lzIjtzOjk6IjIwMjEwMTQ4OSI7czoxNDoiaWRfaW5zY3JpcGNpb24iO2k6Njc1O3M6Njoibm9tYnJlIjtzOjU6IllhbWlsIjtzOjg6ImFwZWxsaWRvIjtzOjg6IlPDoW5jaGV6IjtzOjQ6ImF1bGEiO3M6ODoiQXVsYSA2MjMiO3M6MTM6ImRvY2VudGVfY2FyZ28iO3M6MTM6IlJvYmVydG8gU2lsdmEiO3M6MTA6ImhhYmlsaXRhZG8iO2I6MTtzOjExOiJyZWdpc3RyYWRvciI7TjtzOjEyOiJob3JhX2luZ3Jlc28iO047czoxMDoiaW5jaWRlbmNpYSI7TjtzOjE3OiJlc3RhZG9fYXNpc3RlbmNpYSI7czo5OiJwZW5kaWVudGUiO31pOjI0O2E6MTE6e3M6Mzoic2lzIjtzOjk6IjIwMjIwMTAxMyI7czoxNDoiaWRfaW5zY3JpcGNpb24iO2k6Njc2O3M6Njoibm9tYnJlIjtzOjM6IkFuYSI7czo4OiJhcGVsbGlkbyI7czo2OiJMw7NwZXoiO3M6NDoiYXVsYSI7czo4OiJBdWxhIDYyMyI7czoxMzoiZG9jZW50ZV9jYXJnbyI7czoxMzoiUm9iZXJ0byBTaWx2YSI7czoxMDoiaGFiaWxpdGFkbyI7YjoxO3M6MTE6InJlZ2lzdHJhZG9yIjtOO3M6MTI6ImhvcmFfaW5ncmVzbyI7TjtzOjEwOiJpbmNpZGVuY2lhIjtOO3M6MTc6ImVzdGFkb19hc2lzdGVuY2lhIjtzOjk6InBlbmRpZW50ZSI7fWk6MjU7YToxMTp7czozOiJzaXMiO3M6OToiMjAyMjAxMDMxIjtzOjE0OiJpZF9pbnNjcmlwY2lvbiI7aTo2Nzc7czo2OiJub21icmUiO3M6NToiQ2FybGEiO3M6ODoiYXBlbGxpZG8iO3M6NDoiUnVpeiI7czo0OiJhdWxhIjtzOjg6IkF1bGEgNjIzIjtzOjEzOiJkb2NlbnRlX2NhcmdvIjtzOjEzOiJSb2JlcnRvIFNpbHZhIjtzOjEwOiJoYWJpbGl0YWRvIjtiOjE7czoxMToicmVnaXN0cmFkb3IiO047czoxMjoiaG9yYV9pbmdyZXNvIjtOO3M6MTA6ImluY2lkZW5jaWEiO047czoxNzoiZXN0YWRvX2FzaXN0ZW5jaWEiO3M6OToicGVuZGllbnRlIjt9aToyNjthOjExOntzOjM6InNpcyI7czo5OiIyMDIyMDEwNTYiO3M6MTQ6ImlkX2luc2NyaXBjaW9uIjtpOjY3ODtzOjY6Im5vbWJyZSI7czo3OiJGw6F0aW1hIjtzOjg6ImFwZWxsaWRvIjtzOjY6IlF1aXNwZSI7czo0OiJhdWxhIjtzOjg6IkF1bGEgNjIzIjtzOjEzOiJkb2NlbnRlX2NhcmdvIjtzOjEzOiJSb2JlcnRvIFNpbHZhIjtzOjEwOiJoYWJpbGl0YWRvIjtiOjE7czoxMToicmVnaXN0cmFkb3IiO047czoxMjoiaG9yYV9pbmdyZXNvIjtOO3M6MTA6ImluY2lkZW5jaWEiO047czoxNzoiZXN0YWRvX2FzaXN0ZW5jaWEiO3M6OToicGVuZGllbnRlIjt9aToyNzthOjExOntzOjM6InNpcyI7czo5OiIyMDIyMDEwODkiO3M6MTQ6ImlkX2luc2NyaXBjaW9uIjtpOjY3OTtzOjY6Im5vbWJyZSI7czo2OiJIZWxlbmEiO3M6ODoiYXBlbGxpZG8iO3M6NjoiQ2hvcXVlIjtzOjQ6ImF1bGEiO3M6ODoiQXVsYSA2MjMiO3M6MTM6ImRvY2VudGVfY2FyZ28iO3M6MTM6IlJvYmVydG8gU2lsdmEiO3M6MTA6ImhhYmlsaXRhZG8iO2I6MTtzOjExOiJyZWdpc3RyYWRvciI7TjtzOjEyOiJob3JhX2luZ3Jlc28iO047czoxMDoiaW5jaWRlbmNpYSI7TjtzOjE3OiJlc3RhZG9fYXNpc3RlbmNpYSI7czo5OiJwZW5kaWVudGUiO31pOjI4O2E6MTE6e3M6Mzoic2lzIjtzOjk6IjIwMjIwMTEwMSI7czoxNDoiaWRfaW5zY3JpcGNpb24iO2k6NjgwO3M6Njoibm9tYnJlIjtzOjU6Ikp1bGlhIjtzOjg6ImFwZWxsaWRvIjtzOjc6IkdhcmPDrWEiO3M6NDoiYXVsYSI7czo4OiJBdWxhIDYyMyI7czoxMzoiZG9jZW50ZV9jYXJnbyI7czoxMzoiUm9iZXJ0byBTaWx2YSI7czoxMDoiaGFiaWxpdGFkbyI7YjoxO3M6MTE6InJlZ2lzdHJhZG9yIjtOO3M6MTI6ImhvcmFfaW5ncmVzbyI7TjtzOjEwOiJpbmNpZGVuY2lhIjtOO3M6MTc6ImVzdGFkb19hc2lzdGVuY2lhIjtzOjk6InBlbmRpZW50ZSI7fWk6Mjk7YToxMTp7czozOiJzaXMiO3M6OToiMjAyMjAxMTIzIjtzOjE0OiJpZF9pbnNjcmlwY2lvbiI7aTo2ODE7czo2OiJub21icmUiO3M6NToiTGF1cmEiO3M6ODoiYXBlbGxpZG8iO3M6NToiRMOtYXoiO3M6NDoiYXVsYSI7czo4OiJBdWxhIDYyMyI7czoxMzoiZG9jZW50ZV9jYXJnbyI7czoxMzoiUm9iZXJ0byBTaWx2YSI7czoxMDoiaGFiaWxpdGFkbyI7YjoxO3M6MTE6InJlZ2lzdHJhZG9yIjtOO3M6MTI6ImhvcmFfaW5ncmVzbyI7TjtzOjEwOiJpbmNpZGVuY2lhIjtOO3M6MTc6ImVzdGFkb19hc2lzdGVuY2lhIjtzOjk6InBlbmRpZW50ZSI7fWk6MzA7YToxMTp7czozOiJzaXMiO3M6OToiMjAyMjAxMTQ1IjtzOjE0OiJpZF9pbnNjcmlwY2lvbiI7aTo2ODI7czo2OiJub21icmUiO3M6NToiTmFkaWEiO3M6ODoiYXBlbGxpZG8iO3M6NDoiVmVyYSI7czo0OiJhdWxhIjtzOjg6IkF1bGEgNjIzIjtzOjEzOiJkb2NlbnRlX2NhcmdvIjtzOjEzOiJSb2JlcnRvIFNpbHZhIjtzOjEwOiJoYWJpbGl0YWRvIjtiOjE7czoxMToicmVnaXN0cmFkb3IiO047czoxMjoiaG9yYV9pbmdyZXNvIjtOO3M6MTA6ImluY2lkZW5jaWEiO047czoxNzoiZXN0YWRvX2FzaXN0ZW5jaWEiO3M6OToicGVuZGllbnRlIjt9aTozMTthOjExOntzOjM6InNpcyI7czo5OiIyMDIyMDExNjciO3M6MTQ6ImlkX2luc2NyaXBjaW9uIjtpOjY4MztzOjY6Im5vbWJyZSI7czo4OiJQYXRyaWNpYSI7czo4OiJhcGVsbGlkbyI7czo2OiJNYW1hbmkiO3M6NDoiYXVsYSI7czo4OiJBdWxhIDYyMyI7czoxMzoiZG9jZW50ZV9jYXJnbyI7czoxMzoiUm9iZXJ0byBTaWx2YSI7czoxMDoiaGFiaWxpdGFkbyI7YjoxO3M6MTE6InJlZ2lzdHJhZG9yIjtOO3M6MTI6ImhvcmFfaW5ncmVzbyI7TjtzOjEwOiJpbmNpZGVuY2lhIjtOO3M6MTc6ImVzdGFkb19hc2lzdGVuY2lhIjtzOjk6InBlbmRpZW50ZSI7fWk6MzI7YToxMTp7czozOiJzaXMiO3M6OToiMjAyMjAxMTg5IjtzOjE0OiJpZF9pbnNjcmlwY2lvbiI7aTo2ODQ7czo2OiJub21icmUiO3M6NjoiU29mw61hIjtzOjg6ImFwZWxsaWRvIjtzOjY6IkZsb3JlcyI7czo0OiJhdWxhIjtzOjg6IkF1bGEgNjIzIjtzOjEzOiJkb2NlbnRlX2NhcmdvIjtzOjEzOiJSb2JlcnRvIFNpbHZhIjtzOjEwOiJoYWJpbGl0YWRvIjtiOjE7czoxMToicmVnaXN0cmFkb3IiO047czoxMjoiaG9yYV9pbmdyZXNvIjtOO3M6MTA6ImluY2lkZW5jaWEiO047czoxNzoiZXN0YWRvX2FzaXN0ZW5jaWEiO3M6OToicGVuZGllbnRlIjt9aTozMzthOjExOntzOjM6InNpcyI7czo5OiIyMDIyMDEyMDEiO3M6MTQ6ImlkX2luc2NyaXBjaW9uIjtpOjY4NTtzOjY6Im5vbWJyZSI7czo3OiJWYWxlcmlhIjtzOjg6ImFwZWxsaWRvIjtzOjY6IkzDs3BleiI7czo0OiJhdWxhIjtzOjg6IkF1bGEgNjIzIjtzOjEzOiJkb2NlbnRlX2NhcmdvIjtzOjEzOiJSb2JlcnRvIFNpbHZhIjtzOjEwOiJoYWJpbGl0YWRvIjtiOjE7czoxMToicmVnaXN0cmFkb3IiO047czoxMjoiaG9yYV9pbmdyZXNvIjtOO3M6MTA6ImluY2lkZW5jaWEiO047czoxNzoiZXN0YWRvX2FzaXN0ZW5jaWEiO3M6OToicGVuZGllbnRlIjt9aTozNDthOjExOntzOjM6InNpcyI7czo5OiIyMDIyMDEyMTIiO3M6MTQ6ImlkX2luc2NyaXBjaW9uIjtpOjY4NjtzOjY6Im5vbWJyZSI7czo1OiJXZW5keSI7czo4OiJhcGVsbGlkbyI7czo2OiJaYXBhdGEiO3M6NDoiYXVsYSI7czo4OiJBdWxhIDYyMyI7czoxMzoiZG9jZW50ZV9jYXJnbyI7czoxMzoiUm9iZXJ0byBTaWx2YSI7czoxMDoiaGFiaWxpdGFkbyI7YjoxO3M6MTE6InJlZ2lzdHJhZG9yIjtOO3M6MTI6ImhvcmFfaW5ncmVzbyI7TjtzOjEwOiJpbmNpZGVuY2lhIjtOO3M6MTc6ImVzdGFkb19hc2lzdGVuY2lhIjtzOjk6InBlbmRpZW50ZSI7fWk6MzU7YToxMTp7czozOiJzaXMiO3M6OToiMjAyMjAxMjM0IjtzOjE0OiJpZF9pbnNjcmlwY2lvbiI7aTo2ODc7czo2OiJub21icmUiO3M6NzoiWW9sYW5kYSI7czo4OiJhcGVsbGlkbyI7czo2OiJUaWNvbmEiO3M6NDoiYXVsYSI7czo4OiJBdWxhIDYyMyI7czoxMzoiZG9jZW50ZV9jYXJnbyI7czoxMzoiUm9iZXJ0byBTaWx2YSI7czoxMDoiaGFiaWxpdGFkbyI7YjoxO3M6MTE6InJlZ2lzdHJhZG9yIjtOO3M6MTI6ImhvcmFfaW5ncmVzbyI7TjtzOjEwOiJpbmNpZGVuY2lhIjtOO3M6MTc6ImVzdGFkb19hc2lzdGVuY2lhIjtzOjk6InBlbmRpZW50ZSI7fWk6MzY7YToxMTp7czozOiJzaXMiO3M6OToiMjAyMjAxMjU2IjtzOjE0OiJpZF9pbnNjcmlwY2lvbiI7aTo2ODg7czo2OiJub21icmUiO3M6NzoiQW5kcsOpcyI7czo4OiJhcGVsbGlkbyI7czo1OiJSb2phcyI7czo0OiJhdWxhIjtzOjg6IkF1bGEgNjIzIjtzOjEzOiJkb2NlbnRlX2NhcmdvIjtzOjEzOiJSb2JlcnRvIFNpbHZhIjtzOjEwOiJoYWJpbGl0YWRvIjtiOjE7czoxMToicmVnaXN0cmFkb3IiO3M6MTM6IlJvYmVydG8gU2lsdmEiO3M6MTI6ImhvcmFfaW5ncmVzbyI7czo4OiIxMDo1NjoyNSI7czoxMDoiaW5jaWRlbmNpYSI7TjtzOjE3OiJlc3RhZG9fYXNpc3RlbmNpYSI7czo4OiJwcmVzZW50ZSI7fWk6Mzc7YToxMTp7czozOiJzaXMiO3M6OToiMjAyMjAxMjc4IjtzOjE0OiJpZF9pbnNjcmlwY2lvbiI7aTo2ODk7czo2OiJub21icmUiO3M6ODoiQ3Jpc3RpYW4iO3M6ODoiYXBlbGxpZG8iO3M6NjoiTW9saW5hIjtzOjQ6ImF1bGEiO3M6ODoiQXVsYSA2MjMiO3M6MTM6ImRvY2VudGVfY2FyZ28iO3M6MTM6IlJvYmVydG8gU2lsdmEiO3M6MTA6ImhhYmlsaXRhZG8iO2I6MTtzOjExOiJyZWdpc3RyYWRvciI7TjtzOjEyOiJob3JhX2luZ3Jlc28iO047czoxMDoiaW5jaWRlbmNpYSI7TjtzOjE3OiJlc3RhZG9fYXNpc3RlbmNpYSI7czo5OiJwZW5kaWVudGUiO31pOjM4O2E6MTE6e3M6Mzoic2lzIjtzOjk6IjIwMjIwMTI5MCI7czoxNDoiaWRfaW5zY3JpcGNpb24iO2k6NjkwO3M6Njoibm9tYnJlIjtzOjc6IkVkdWFyZG8iO3M6ODoiYXBlbGxpZG8iO3M6NzoiU2FsYXphciI7czo0OiJhdWxhIjtzOjg6IkF1bGEgNjIzIjtzOjEzOiJkb2NlbnRlX2NhcmdvIjtzOjEzOiJSb2JlcnRvIFNpbHZhIjtzOjEwOiJoYWJpbGl0YWRvIjtiOjE7czoxMToicmVnaXN0cmFkb3IiO047czoxMjoiaG9yYV9pbmdyZXNvIjtOO3M6MTA6ImluY2lkZW5jaWEiO047czoxNzoiZXN0YWRvX2FzaXN0ZW5jaWEiO3M6OToicGVuZGllbnRlIjt9aTozOTthOjExOntzOjM6InNpcyI7czo5OiIyMDIyMDEzMTIiO3M6MTQ6ImlkX2luc2NyaXBjaW9uIjtpOjY5MTtzOjY6Im5vbWJyZSI7czo3OiJHb256YWxvIjtzOjg6ImFwZWxsaWRvIjtzOjQ6IkFyY2UiO3M6NDoiYXVsYSI7czo4OiJBdWxhIDYyMyI7czoxMzoiZG9jZW50ZV9jYXJnbyI7czoxMzoiUm9iZXJ0byBTaWx2YSI7czoxMDoiaGFiaWxpdGFkbyI7YjoxO3M6MTE6InJlZ2lzdHJhZG9yIjtOO3M6MTI6ImhvcmFfaW5ncmVzbyI7TjtzOjEwOiJpbmNpZGVuY2lhIjtOO3M6MTc6ImVzdGFkb19hc2lzdGVuY2lhIjtzOjk6InBlbmRpZW50ZSI7fWk6NDA7YToxMTp7czozOiJzaXMiO3M6OToiMjAyMjAxMzM0IjtzOjE0OiJpZF9pbnNjcmlwY2lvbiI7aTo2OTI7czo2OiJub21icmUiO3M6NToiSW7DqXMiO3M6ODoiYXBlbGxpZG8iO3M6NzoiRGVsZ2FkbyI7czo0OiJhdWxhIjtzOjg6IkF1bGEgNjIzIjtzOjEzOiJkb2NlbnRlX2NhcmdvIjtzOjEzOiJSb2JlcnRvIFNpbHZhIjtzOjEwOiJoYWJpbGl0YWRvIjtiOjE7czoxMToicmVnaXN0cmFkb3IiO047czoxMjoiaG9yYV9pbmdyZXNvIjtOO3M6MTA6ImluY2lkZW5jaWEiO047czoxNzoiZXN0YWRvX2FzaXN0ZW5jaWEiO3M6OToicGVuZGllbnRlIjt9aTo0MTthOjExOntzOjM6InNpcyI7czo5OiIyMDIyMDEzNTYiO3M6MTQ6ImlkX2luc2NyaXBjaW9uIjtpOjY5MztzOjY6Im5vbWJyZSI7czo1OiJLYXJlbiI7czo4OiJhcGVsbGlkbyI7czo3OiJGdWVudGVzIjtzOjQ6ImF1bGEiO3M6ODoiQXVsYSA2MjMiO3M6MTM6ImRvY2VudGVfY2FyZ28iO3M6MTM6IlJvYmVydG8gU2lsdmEiO3M6MTA6ImhhYmlsaXRhZG8iO2I6MTtzOjExOiJyZWdpc3RyYWRvciI7TjtzOjEyOiJob3JhX2luZ3Jlc28iO047czoxMDoiaW5jaWRlbmNpYSI7TjtzOjE3OiJlc3RhZG9fYXNpc3RlbmNpYSI7czo5OiJwZW5kaWVudGUiO31pOjQyO2E6MTE6e3M6Mzoic2lzIjtzOjk6IjIwMjIwMTM3OCI7czoxNDoiaWRfaW5zY3JpcGNpb24iO2k6Njk0O3M6Njoibm9tYnJlIjtzOjY6Ik1hcsOtYSI7czo4OiJhcGVsbGlkbyI7czo3OiJIZXJyZXJhIjtzOjQ6ImF1bGEiO3M6ODoiQXVsYSA2MjMiO3M6MTM6ImRvY2VudGVfY2FyZ28iO3M6MTM6IlJvYmVydG8gU2lsdmEiO3M6MTA6ImhhYmlsaXRhZG8iO2I6MTtzOjExOiJyZWdpc3RyYWRvciI7TjtzOjEyOiJob3JhX2luZ3Jlc28iO047czoxMDoiaW5jaWRlbmNpYSI7TjtzOjE3OiJlc3RhZG9fYXNpc3RlbmNpYSI7czo5OiJwZW5kaWVudGUiO31pOjQzO2E6MTE6e3M6Mzoic2lzIjtzOjk6IjIwMjIwMTM5MCI7czoxNDoiaWRfaW5zY3JpcGNpb24iO2k6Njk1O3M6Njoibm9tYnJlIjtzOjQ6Ik9sZ2EiO3M6ODoiYXBlbGxpZG8iO3M6ODoiSmltw6luZXoiO3M6NDoiYXVsYSI7czo4OiJBdWxhIDYyMyI7czoxMzoiZG9jZW50ZV9jYXJnbyI7czoxMzoiUm9iZXJ0byBTaWx2YSI7czoxMDoiaGFiaWxpdGFkbyI7YjoxO3M6MTE6InJlZ2lzdHJhZG9yIjtOO3M6MTI6ImhvcmFfaW5ncmVzbyI7TjtzOjEwOiJpbmNpZGVuY2lhIjtOO3M6MTc6ImVzdGFkb19hc2lzdGVuY2lhIjtzOjk6InBlbmRpZW50ZSI7fWk6NDQ7YToxMTp7czozOiJzaXMiO3M6OToiMjAyMjAxNDEyIjtzOjE0OiJpZF9pbnNjcmlwY2lvbiI7aTo2OTY7czo2OiJub21icmUiO3M6NDoiUm9zYSI7czo4OiJhcGVsbGlkbyI7czo0OiJMdW5hIjtzOjQ6ImF1bGEiO3M6ODoiQXVsYSA2MjMiO3M6MTM6ImRvY2VudGVfY2FyZ28iO3M6MTM6IlJvYmVydG8gU2lsdmEiO3M6MTA6ImhhYmlsaXRhZG8iO2I6MTtzOjExOiJyZWdpc3RyYWRvciI7TjtzOjEyOiJob3JhX2luZ3Jlc28iO047czoxMDoiaW5jaWRlbmNpYSI7TjtzOjE3OiJlc3RhZG9fYXNpc3RlbmNpYSI7czo5OiJwZW5kaWVudGUiO31pOjQ1O2E6MTE6e3M6Mzoic2lzIjtzOjk6IjIwMjIwMTQzNCI7czoxNDoiaWRfaW5zY3JpcGNpb24iO2k6Njk3O3M6Njoibm9tYnJlIjtzOjc6IlRhdGlhbmEiO3M6ODoiYXBlbGxpZG8iO3M6NzoiTsO6w7FleiI7czo0OiJhdWxhIjtzOjg6IkF1bGEgNjIzIjtzOjEzOiJkb2NlbnRlX2NhcmdvIjtzOjEzOiJSb2JlcnRvIFNpbHZhIjtzOjEwOiJoYWJpbGl0YWRvIjtiOjE7czoxMToicmVnaXN0cmFkb3IiO047czoxMjoiaG9yYV9pbmdyZXNvIjtOO3M6MTA6ImluY2lkZW5jaWEiO047czoxNzoiZXN0YWRvX2FzaXN0ZW5jaWEiO3M6OToicGVuZGllbnRlIjt9aTo0NjthOjExOntzOjM6InNpcyI7czo5OiIyMDIyMDE0NTYiO3M6MTQ6ImlkX2luc2NyaXBjaW9uIjtpOjY5ODtzOjY6Im5vbWJyZSI7czo5OiJWZXLDs25pY2EiO3M6ODoiYXBlbGxpZG8iO3M6MzoiUGF6IjtzOjQ6ImF1bGEiO3M6ODoiQXVsYSA2MjMiO3M6MTM6ImRvY2VudGVfY2FyZ28iO3M6MTM6IlJvYmVydG8gU2lsdmEiO3M6MTA6ImhhYmlsaXRhZG8iO2I6MTtzOjExOiJyZWdpc3RyYWRvciI7TjtzOjEyOiJob3JhX2luZ3Jlc28iO047czoxMDoiaW5jaWRlbmNpYSI7TjtzOjE3OiJlc3RhZG9fYXNpc3RlbmNpYSI7czo5OiJwZW5kaWVudGUiO31pOjQ3O2E6MTE6e3M6Mzoic2lzIjtzOjk6IjIwMjIwMTQ3OCI7czoxNDoiaWRfaW5zY3JpcGNpb24iO2k6Njk5O3M6Njoibm9tYnJlIjtzOjY6IlhpbWVuYSI7czo4OiJhcGVsbGlkbyI7czo4OiJSYW3DrXJleiI7czo0OiJhdWxhIjtzOjg6IkF1bGEgNjIzIjtzOjEzOiJkb2NlbnRlX2NhcmdvIjtzOjEzOiJSb2JlcnRvIFNpbHZhIjtzOjEwOiJoYWJpbGl0YWRvIjtiOjE7czoxMToicmVnaXN0cmFkb3IiO047czoxMjoiaG9yYV9pbmdyZXNvIjtOO3M6MTA6ImluY2lkZW5jaWEiO047czoxNzoiZXN0YWRvX2FzaXN0ZW5jaWEiO3M6OToicGVuZGllbnRlIjt9aTo0ODthOjExOntzOjM6InNpcyI7czo5OiIyMDIyMDE0OTAiO3M6MTQ6ImlkX2luc2NyaXBjaW9uIjtpOjcwMDtzOjY6Im5vbWJyZSI7czo1OiJaYWlyYSI7czo4OiJhcGVsbGlkbyI7czo2OiJUb2xlZG8iO3M6NDoiYXVsYSI7czo4OiJBdWxhIDYyMyI7czoxMzoiZG9jZW50ZV9jYXJnbyI7czoxMzoiUm9iZXJ0byBTaWx2YSI7czoxMDoiaGFiaWxpdGFkbyI7YjoxO3M6MTE6InJlZ2lzdHJhZG9yIjtOO3M6MTI6ImhvcmFfaW5ncmVzbyI7TjtzOjEwOiJpbmNpZGVuY2lhIjtOO3M6MTc6ImVzdGFkb19hc2lzdGVuY2lhIjtzOjk6InBlbmRpZW50ZSI7fWk6NDk7YToxMTp7czozOiJzaXMiO3M6OToiMjAyMjAyMDQ1IjtzOjE0OiJpZF9pbnNjcmlwY2lvbiI7aTo3MDE7czo2OiJub21icmUiO3M6NToiRGllZ28iO3M6ODoiYXBlbGxpZG8iO3M6NDoiU290byI7czo0OiJhdWxhIjtzOjg6IkF1bGEgNjIzIjtzOjEzOiJkb2NlbnRlX2NhcmdvIjtzOjEzOiJSb2JlcnRvIFNpbHZhIjtzOjEwOiJoYWJpbGl0YWRvIjtiOjE7czoxMToicmVnaXN0cmFkb3IiO047czoxMjoiaG9yYV9pbmdyZXNvIjtOO3M6MTA6ImluY2lkZW5jaWEiO047czoxNzoiZXN0YWRvX2FzaXN0ZW5jaWEiO3M6OToicGVuZGllbnRlIjt9fXM6Mjg6IgAqAGVzY2FwZVdoZW5DYXN0aW5nVG9TdHJpbmciO2I6MDt9fQ==', 1793098647);

INSERT INTO public.cache ("key", "value", "expiration") VALUES ('monitoreo.informe.25.docente.1', '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OToiACoAaGlkZGVuIjthOjA6e31zOjEwOiIAKgB2aXNpYmxlIjthOjA6e31zOjExOiIAKgBmaWxsYWJsZSI7YToyOntpOjA7czoxMToiaWRfbWF0ZXJpYWwiO2k6MTtzOjExOiJkZXNjcmlwY2lvbiI7fXM6MTA6IgAqAGd1YXJkZWQiO2E6MTp7aTowO3M6MToiKiI7fX19czoyODoiACoAZXNjYXBlV2hlbkNhc3RpbmdUb1N0cmluZyI7YjowO31zOjY6Im5vcm1hcyI7TzozOToiSWxsdW1pbmF0ZVxEYXRhYmFzZVxFbG9xdWVudFxDb2xsZWN0aW9uIjoyOntzOjg6IgAqAGl0ZW1zIjthOjA6e31zOjI4OiIAKgBlc2NhcGVXaGVuQ2FzdGluZ1RvU3RyaW5nIjtiOjA7fXM6MjQ6Im1hdGVyaWFsZXNQZXJzb25hbGl6YWRvcyI7TzozOToiSWxsdW1pbmF0ZVxEYXRhYmFzZVxFbG9xdWVudFxDb2xsZWN0aW9uIjoyOntzOjg6IgAqAGl0ZW1zIjthOjE6e2k6MDtPOjQ1OiJBcHBcTW9kZWxzXENvbmZpZ3VyYWNpb25NYXRlcmlhbFBlcnNvbmFsaXphZG8iOjMwOntzOjEzOiIAKgBjb25uZWN0aW9uIjtzOjU6InBnc3FsIjtzOjg6IgAqAHRhYmxlIjtzOjM2OiJjb25maWd1cmFjaW9uX21hdGVyaWFsX3BlcnNvbmFsaXphZG8iO3M6MTM6IgAqAHByaW1hcnlLZXkiO3M6MjU6ImlkX2NvbmZpZ3VyYWNpb25fbWF0ZXJpYWwiO3M6MTA6IgAqAGtleVR5cGUiO3M6MzoiaW50IjtzOjEyOiJpbmNyZW1lbnRpbmciO2I6MTtzOjc6IgAqAHdpdGgiO2E6MDp7fXM6MTI6IgAqAHdpdGhDb3VudCI7YTowOnt9czoxOToicHJldmVudHNMYXp5TG9hZGluZyI7YjowO3M6MTA6IgAqAHBlclBhZ2UiO2k6MTU7czo2OiJleGlzdHMiO2I6MTtzOjE4OiJ3YXNSZWNlbnRseUNyZWF0ZWQiO2I6MDtzOjI4OiIAKgBlc2NhcGVXaGVuQ2FzdGluZ1RvU3RyaW5nIjtiOjA7czoxMzoiACoAYXR0cmlidXRlcyI7YTo0OntzOjI1OiJpZF9jb25maWd1cmFjaW9uX21hdGVyaWFsIjtpOjExO3M6MjM6ImlkX2NvbmZpZ3VyYWNpb25fZXhhbWVuIjtpOjQyO3M6MTU6Im51bWVyb19tYXRlcmlhbCI7aToxO3M6MjA6ImRlc2NyaXBjaW9uX21hdGVyaWFsIjtzOjU6ImFkYXNkIjt9czoxMToiACoAb3JpZ2luYWwiO2E6NDp7czoyNToiaWRfY29uZmlndXJhY2lvbl9tYXRlcmlhbCI7aToxMTtzOjIzOiJpZF9jb25maWd1cmFjaW9uX2V4YW1lbiI7aTo0MjtzOjE1OiJudW1lcm9fbWF0ZXJpYWwiO2k6MTtzOjIwOiJkZXNjcmlwY2lvbl9tYXRlcmlhbCI7czo1OiJhZGFzZCI7fXM6MTA6IgAqAGNoYW5nZXMiO2E6MDp7fXM6ODoiACoAY2FzdHMiO2E6MDp7fXM6MTc6IgAqAGNsYXNzQ2FzdENhY2hlIjthOjA6e31zOjIxOiIAKgBhdHRyaWJ1dGVDYXN0Q2FjaGUiO2E6MDp7fXM6MTM6IgAqAGRhdGVGb3JtYXQiO047czoxMDoiACoAYXBwZW5kcyI7YTowOnt9czoxOToiACoAZGlzcGF0Y2hlc0V2ZW50cyI7YTowOnt9czoxNDoiACoAb2JzZXJ2YWJsZXMiO2E6MDp7fXM6MTI6IgAqAHJlbGF0aW9ucyI7YTowOnt9czoxMDoiACoAdG91Y2hlcyI7YTowOnt9czoxMDoidGltZXN0YW1wcyI7YjowO3M6MTM6InVzZXNVbmlxdWVJZHMiO2I6MDtzOjk6IgAqAGhpZGRlbiI7YTowOnt9czoxMDoiACoAdmlzaWJsZSI7YTowOnt9czoxMToiACoAZmlsbGFibGUiO2E6Mzp7aTowO3M6MjM6ImlkX2NvbmZpZ3VyYWNpb25fZXhhbWVuIjtpOjE7czoxNToibnVtZXJvX21hdGVyaWFsIjtpOjI7czoyMDoiZGVzY3JpcGNpb25fbWF0ZXJpYWwiO31zOjEwOiIAKgBndWFyZGVkIjthOjE6e2k6MDtzOjE6IioiO319fXM6Mjg6IgAqAGVzY2FwZVdoZW5DYXN0aW5nVG9TdHJpbmciO2I6MDt9czoyMDoibm9ybWFzUGVyc29uYWxpemFkYXMiO086Mzk6IklsbHVtaW5hdGVcRGF0YWJhc2VcRWxvcXVlbnRcQ29sbGVjdGlvbiI6Mjp7czo4OiIAKgBpdGVtcyI7YToxOntpOjA7Tzo0MjoiQXBwXE1vZGVsc1xDb25maWd1cmFjaW9uTm9ybWFQZXJzb25hbGl6YWRhIjozMDp7czoxMzoiACoAY29ubmVjdGlvbiI7czo1OiJwZ3NxbCI7czo4OiIAKgB0YWJsZSI7czozMzoiY29uZmlndXJhY2lvbl9ub3JtYV9wZXJzb25hbGl6YWRhIjtzOjEzOiIAKgBwcmltYXJ5S2V5IjtzOjIyOiJpZF9jb25maWd1cmFjaW9uX25vcm1hIjtzOjEwOiIAKgBrZXlUeXBlIjtzOjM6ImludCI7czoxMjoiaW5jcmVtZW50aW5nIjtiOjE7czo3OiIAKgB3aXRoIjthOjA6e31zOjEyOiIAKgB3aXRoQ291bnQiO2E6MDp7fXM6MTk6InByZXZlbnRzTGF6eUxvYWRpbmciO2I6MDtzOjEwOiIAKgBwZXJQYWdlIjtpOjE1O3M6NjoiZXhpc3RzIjtiOjE7czoxODoid2FzUmVjZW50bHlDcmVhdGVkIjtiOjA7czoyODoiACoAZXNjYXBlV2hlbkNhc3RpbmdUb1N0cmluZyI7YjowO3M6MTM6IgAqAGF0dHJpYnV0ZXMiO2E6NDp7czoyMjoiaWRfY29uZmlndXJhY2lvbl9ub3JtYSI7aToyMDtzOjIzOiJpZF9jb25maWd1cmFjaW9uX2V4YW1lbiI7aTo0MjtzOjEyOiJudW1lcm9fbm9ybWEiO2k6MTtzOjE3OiJkZXNjcmlwY2lvbl9ub3JtYSI7czo4OiJhZHNhc2RzYSI7fXM6MTE6IgAqAG9yaWdpbmFsIjthOjQ6e3M6MjI6ImlkX2NvbmZpZ3VyYWNpb25fbm9ybWEiO2k6MjA7czoyMzoiaWRfY29uZmlndXJhY2lvbl9leGFtZW4iO2k6NDI7czoxMjoibnVtZXJvX25vcm1hIjtpOjE7czoxNzoiZGVzY3JpcGNpb25fbm9ybWEiO3M6ODoiYWRzYXNkc2EiO31zOjEwOiIAKgBjaGFuZ2VzIjthOjA6e31zOjg6IgAqAGNhc3RzIjthOjA6e31zOjE3OiIAKgBjbGFzc0Nhc3RDYWNoZSI7YTowOnt9czoyMToi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O3M6NzoiY3JlYWRvciI7aTo2O3M6MTE6InRpcG9fZXhhbWVuIjt9czoxMDoiACoAZ3VhcmRlZCI7YToxOntpOjA7czoxOiIqIjt9fXM6MTE6ImVzdHVkaWFudGVzIjtPOjI5OiJJbGx1bWluYXRlXFN1cHBvcnRcQ29sbGVjdGlvbiI6Mjp7czo4OiIAKgBpdGVtcyI7YTo1MDp7aTowO2E6MTE6e3M6Mzoic2lzIjtzOjk6IjIwMjAwMjEwNyI7czoxNDoiaWRfaW5zY3JpcGNpb24iO2k6NTUwO3M6Njoibm9tYnJlIjtzOjc6IkVybmVzdG8iO3M6ODoiYXBlbGxpZG8iO3M6NDoiVmVyYSI7czo0OiJhdWxhIjtzOjk6IkF1bGEgNjkwQiI7czoxMzoiZG9jZW50ZV9jYXJnbyI7czoxMzoiUm9iZXJ0byBTaWx2YSI7czoxMDoiaGFiaWxpdGFkbyI7YjoxO3M6MTE6InJlZ2lzdHJhZG9yIjtOO3M6MTI6ImhvcmFfaW5ncmVzbyI7TjtzOjEwOiJpbmNpZGVuY2lhIjtOO3M6MTc6ImVzdGFkb19hc2lzdGVuY2lhIjtzOjk6InBlbmRpZW50ZSI7fWk6MTthOjExOntzOjM6InNpcyI7czo4OiIyMDIxMDAwMyI7czoxNDoiaWRfaW5zY3JpcGNpb24iO2k6NTUxO3M6Njoibm9tYnJlIjtzOjU6Ik1hcmlhIjtzOjg6ImFwZWxsaWRvIjtzOjU6IkxvcGV6IjtzOjQ6ImF1bGEiO3M6OToiQXVsYSA2OTBCIjtzOjEzOiJkb2NlbnRlX2NhcmdvIjtzOjEzOiJSb2JlcnRvIFNpbHZhIjtzOjEwOiJoYWJpbGl0YWRvIjtiOjE7czoxMToicmVnaXN0cmFkb3IiO047czoxMjoiaG9yYV9pbmdyZXNvIjtOO3M6MTA6ImluY2lkZW5jaWEiO047czoxNzoiZXN0YWRvX2FzaXN0ZW5jaWEiO3M6OToicGVuZGllbnRlIjt9aToyO2E6MTE6e3M6Mzoic2lzIjtzOjk6IjIwMjEwMTAyMiI7czoxNDoiaWRfaW5zY3JpcGNpb24iO2k6NTUyO3M6Njoibm9tYnJlIjtzOjU6IkJydW5vIjtzOjg6ImFwZWxsaWRvIjtzOjU6IkTDrWF6IjtzOjQ6ImF1bGEiO3M6OToiQXVsYSA2OTJBIjtzOjEzOiJkb2NlbnRlX2NhcmdvIjtzOjEzOiJSb2JlcnRvIFNpbHZhIjtzOjEwOiJoYWJpbGl0YWRvIjtiOjE7czoxMToicmVnaXN0cmFkb3IiO047czoxMjoiaG9yYV9pbmdyZXNvIjtOO3M6MTA6ImluY2lkZW5jaWEiO047czoxNzoiZXN0YWRvX2FzaXN0ZW5jaWEiO3M6OToicGVuZGllbnRlIjt9aTozO2E6MTE6e3M6Mzoic2lzIjtzOjk6IjIwMjEwMTA3OCI7czoxNDoiaWRfaW5zY3JpcGNpb24iO2k6NTUzO3M6Njoibm9tYnJlIjtzOjc6IkdhYnJpZWwiO3M6ODoiYXBlbGxpZG8iO3M6NjoiTWFtYW5pIjtzOjQ6ImF1bGEiO3M6OToiQXVsYSA2OTJBIjtzOjEzOiJkb2NlbnRlX2NhcmdvIjtzOjEzOiJSb2JlcnRvIFNpbHZhIjtzOjEwOiJoYWJpbGl0YWRvIjtiOjE7czoxMToicmVnaXN0cmFkb3IiO047czoxMjoiaG9yYV9pbmdyZXNvIjtOO3M6MTA6ImluY2lkZW5jaWEiO047czoxNzoiZXN0YWRvX2FzaXN0ZW5jaWEiO3M6OToicGVuZGllbnRlIjt9aTo0O2E6MTE6e3M6Mzoic2lzIjtzOjk6IjIwMjEwMTA5MCI7czoxNDoiaWRfaW5zY3JpcGNpb24iO2k6NTU0O3M6Njoibm9tYnJlIjtzOjU6Ikl2w6FuIjtzOjg6ImFwZWxsaWRvIjtzOjY6IkZsb3JlcyI7czo0OiJhdWxhIjtzOjk6IkF1bGEgNjkwQiI7czoxMzoiZG9jZW50ZV9jYXJnbyI7czoxMzoiUm9iZXJ0byBTaWx2YSI7czoxMDoiaGFiaWxpdGFkbyI7YjoxO3M6MTE6InJlZ2lzdHJhZG9yIjtOO3M6MTI6ImhvcmFfaW5ncmVzbyI7TjtzOjEwOiJpbmNpZGVuY2lhIjtOO3M6MTc6ImVzdGFkb19hc2lzdGVuY2lhIjtzOjk6InBlbmRpZW50ZSI7fWk6NTthOjExOntzOjM6InNpcyI7czo5OiIyMDIxMDExMTIiO3M6MTQ6ImlkX2luc2NyaXBjaW9uIjtpOjU1NTtzOjY6Im5vbWJyZSI7czo1OiJLZXZpbiI7czo4OiJhcGVsbGlkbyI7czo2OiJMw7NwZXoiO3M6NDoiYXVsYSI7czo5OiJBdWxhIDY5MkEiO3M6MTM6ImRvY2VudGVfY2FyZ28iO3M6MTM6IlJvYmVydG8gU2lsdmEiO3M6MTA6ImhhYmlsaXRhZG8iO2I6MTtzOjExOiJyZWdpc3RyYWRvciI7TjtzOjEyOiJob3JhX2luZ3Jlc28iO047czoxMDoiaW5jaWRlbmNpYSI7TjtzOjE3OiJlc3RhZG9fYXNpc3RlbmNpYSI7czo5OiJwZW5kaWVudGUiO31pOjY7YToxMTp7czozOiJzaXMiO3M6OToiMjAyMTAxMTM0IjtzOjE0OiJpZF9pbnNjcmlwY2lvbiI7aTo1NTY7czo2OiJub21icmUiO3M6NjoiTWlndWVsIjtzOjg6ImFwZWxsaWRvIjtzOjQ6IlNvdG8iO3M6NDoiYXVsYSI7czo5OiJBdWxhIDY5MEIiO3M6MTM6ImRvY2VudGVfY2FyZ28iO3M6MTM6IlJvYmVydG8gU2lsdmEiO3M6MTA6ImhhYmlsaXRhZG8iO2I6MTtzOjExOiJyZWdpc3RyYWRvciI7TjtzOjEyOiJob3JhX2luZ3Jlc28iO047czoxMDoiaW5jaWRlbmNpYSI7TjtzOjE3OiJlc3RhZG9fYXNpc3RlbmNpYSI7czo5OiJwZW5kaWVudGUiO31pOjc7YToxMTp7czozOiJzaXMiO3M6OToiMjAyMTAxMTU2IjtzOjE0OiJpZF9pbnNjcmlwY2lvbiI7aTo1NTc7czo2OiJub21icmUiO3M6Njoiw5NzY2FyIjtzOjg6ImFwZWxsaWRvIjtzOjY6IlF1aXNwZSI7czo0OiJhdWxhIjtzOjk6IkF1bGEgNjkyQSI7czoxMzoiZG9jZW50ZV9jYXJnbyI7czoxMzoiUm9iZXJ0byBTaWx2YSI7czoxMDoiaGFiaWxpdGFkbyI7YjoxO3M6MTE6InJlZ2lzdHJhZG9yIjtOO3M6MTI6ImhvcmFfaW5ncmVzbyI7TjtzOjEwOiJpbmNpZGVuY2lhIjtOO3M6MTc6ImVzdGFkb19hc2lzdGVuY2lhIjtzOjk6InBlbmRpZW50ZSI7fWk6ODthOjExOntzOjM6InNpcyI7czo5OiIyMDIxMDExNzgiO3M6MTQ6ImlkX2luc2NyaXBjaW9uIjtpOjU1ODtzOjY6Im5vbWJyZSI7czo1OiJSYcO6bCI7czo4OiJhcGVsbGlkbyI7czo2OiJDaG9xdWUiO3M6NDoiYXVsYSI7czo5OiJBdWxhIDY5MkEiO3M6MTM6ImRvY2VudGVfY2FyZ28iO3M6MTM6IlJvYmVydG8gU2lsdmEiO3M6MTA6ImhhYmlsaXRhZG8iO2I6MTtzOjExOiJyZWdpc3RyYWRvciI7TjtzOjEyOiJob3JhX2luZ3Jlc28iO047czoxMDoiaW5jaWRlbmNpYSI7TjtzOjE3OiJlc3RhZG9fYXNpc3RlbmNpYSI7czo5OiJwZW5kaWVudGUiO31pOjk7YToxMTp7czozOiJzaXMiO3M6OToiMjAyMTAxMTkwIjtzOjE0OiJpZF9pbnNjcmlwY2lvbiI7aTo1NTk7czo2OiJub21icmUiO3M6NjoiVG9tw6FzIjtzOjg6ImFwZWxsaWRvIjtzOjc6IkdhcmPDrWEiO3M6NDoiYXVsYSI7czo5OiJBdWxhIDY5MEIiO3M6MTM6ImRvY2VudGVfY2FyZ28iO3M6MTM6IlJvYmVydG8gU2lsdmEiO3M6MTA6ImhhYmlsaXRhZG8iO2I6MTtzOjExOiJyZWdpc3RyYWRvciI7TjtzOjEyOiJob3JhX2luZ3Jlc28iO047czoxMDoiaW5jaWRlbmNpYSI7TjtzOjE3OiJlc3RhZG9fYXNpc3RlbmNpYSI7czo5OiJwZW5kaWVudGUiO31pOjEwO2E6MTE6e3M6Mzoic2lzIjtzOjk6IjIwMjEwMTIyMyI7czoxNDoiaWRfaW5zY3JpcGNpb24iO2k6NTYwO3M6Njoibm9tYnJlIjtzOjY6IlhhdmllciI7czo4OiJhcGVsbGlkbyI7czo2OiJWYXJnYXMiO3M6NDoiYXVsYSI7czo5OiJBdWxhIDY5MEIiO3M6MTM6ImRvY2VudGVfY2FyZ28iO3M6MTM6IlJvYmVydG8gU2lsdmEiO3M6MTA6ImhhYmlsaXRhZG8iO2I6MTtzOjExOiJyZWdpc3RyYWRvciI7TjtzOjEyOiJob3JhX2luZ3Jlc28iO047czoxMDoiaW5jaWRlbmNpYSI7TjtzOjE3OiJlc3RhZG9fYXNpc3RlbmNpYSI7czo5OiJwZW5kaWVudGUiO31pOjExO2E6MTE6e3M6Mzoic2lzIjtzOjk6IjIwMjEwMTI0NSI7czoxNDoiaWRfaW5zY3JpcGNpb24iO2k6NTYxO3M6Njoibm9tYnJlIjtzOjM6IlpvZSI7czo4OiJhcGVsbGlkbyI7czo1OiJTaWxlcyI7czo0OiJhdWxhIjtzOjk6IkF1bGEgNjkwQiI7czoxMzoiZG9jZW50ZV9jYXJnbyI7czoxMzoiUm9iZXJ0byBTaWx2YSI7czoxMDoiaGFiaWxpdGFkbyI7YjoxO3M6MTE6InJlZ2lzdHJhZG9yIjtOO3M6MTI6ImhvcmFfaW5ncmVzbyI7TjtzOjEwOiJpbmNpZGVuY2lhIjtOO3M6MTc6ImVzdGFkb19hc2lzdGVuY2lhIjtzOjk6InBlbmRpZW50ZSI7fWk6MTI7YToxMTp7czozOiJzaXMiO3M6OToiMjAyMTAxMjY3IjtzOjE0OiJpZF9pbnNjcmlwY2lvbiI7aTo1NjI7czo2OiJub21icmUiO3M6NjoiQmlhbmNhIjtzOjg6ImFwZWxsaWRvIjtzOjc6IlBhcmVkZXMiO3M6NDoiYXVsYSI7czo5OiJBdWxhIDY5MkEiO3M6MTM6ImRvY2VudGVfY2FyZ28iO3M6MTM6IlJvYmVydG8gU2lsdmEiO3M6MTA6ImhhYmlsaXRhZG8iO2I6MTtzOjExOiJyZWdpc3RyYWRvciI7TjtzOjEyOiJob3JhX2luZ3Jlc28iO047czoxMDoiaW5jaWRlbmNpYSI7TjtzOjE3OiJlc3RhZG9fYXNpc3RlbmNpYSI7czo5OiJwZW5kaWVudGUiO31pOjEzO2E6MTE6e3M6Mzoic2lzIjtzOjk6IjIwMjEwMTI4OSI7czoxNDoiaWRfaW5zY3JpcGNpb24iO2k6NTYzO3M6Njoibm9tYnJlIjtzOjc6IkRhbmllbGEiO3M6ODoiYXBlbGxpZG8iO3M6NzoiQ2FicmVyYSI7czo0OiJhdWxhIjtzOjk6IkF1bGEgNjkwQiI7czoxMzoiZG9jZW50ZV9jYXJnbyI7czoxMzoiUm9iZXJ0byBTaWx2YSI7czoxMDoiaGFiaWxpdGFkbyI7YjoxO3M6MTE6InJlZ2lzdHJhZG9yIjtOO3M6MTI6ImhvcmFfaW5ncmVzbyI7TjtzOjEwOiJpbmNpZGVuY2lhIjtOO3M6MTc6ImVzdGFkb19hc2lzdGVuY2lhIjtzOjk6InBlbmRpZW50ZSI7fWk6MTQ7YToxMTp7czozOiJzaXMiO3M6OToiMjAyMTAxMzAxIjtzOjE0OiJpZF9pbnNjcmlwY2lvbiI7aTo1NjQ7czo2OiJub21icmUiO3M6ODoiRmVybmFuZGEiO3M6ODoiYXBlbGxpZG8iO3M6NDoiVmVnYSI7czo0OiJhdWxhIjtzOjk6IkF1bGEgNjkyQSI7czoxMzoiZG9jZW50ZV9jYXJnbyI7czoxMzoiUm9iZXJ0byBTaWx2YSI7czoxMDoiaGFiaWxpdGFkbyI7YjoxO3M6MTE6InJlZ2lzdHJhZG9yIjtOO3M6MTI6ImhvcmFfaW5ncmVzbyI7TjtzOjEwOiJpbmNpZGVuY2lhIjtOO3M6MTc6ImVzdGFkb19hc2lzdGVuY2lhIjtzOjk6InBlbmRpZW50ZSI7fWk6MTU7YToxMTp7czozOiJzaXMiO3M6OToiMjAyMTAxMzIzIjtzOjE0OiJpZF9pbnNjcmlwY2lvbiI7aTo1NjU7czo2OiJub21icmUiO3M6NDoiSHVnbyI7czo4OiJhcGVsbGlkbyI7czo3OiJDYW1hY2hvIjtzOjQ6ImF1bGEiO3M6OToiQXVsYSA2OTJBIjtzOjEzOiJkb2NlbnRlX2NhcmdvIjtzOjEzOiJSb2JlcnRvIFNpbHZhIjtzOjEwOiJoYWJpbGl0YWRvIjtiOjE7czoxMToicmVnaXN0cmFkb3IiO047czoxMjoiaG9yYV9pbmdyZXNvIjtOO3M6MTA6ImluY2lkZW5jaWEiO047czoxNzoiZXN0YWRvX2FzaXN0ZW5jaWEiO3M6OToicGVuZGllbnRlIjt9aToxNjthOjExOntzOjM6InNpcyI7czo5OiIyMDIxMDEzNDUiO3M6MTQ6ImlkX2luc2NyaXBjaW9uIjtpOjU2NjtzOjY6Im5vbWJyZSI7czo2OiJKYXZpZXIiO3M6ODoiYXBlbGxpZG8iO3M6ODoiRXNwaW5vemEiO3M6NDoiYXVsYSI7czo5OiJBdWxhIDY5MkEiO3M6MTM6ImRvY2VudGVfY2FyZ28iO3M6MTM6IlJvYmVydG8gU2lsdmEiO3M6MTA6ImhhYmlsaXRhZG8iO2I6MTtzOjExOiJyZWdpc3RyYWRvciI7TjtzOjEyOiJob3JhX2luZ3Jlc28iO047czoxMDoiaW5jaWRlbmNpYSI7TjtzOjE3OiJlc3RhZG9fYXNpc3RlbmNpYSI7czo5OiJwZW5kaWVudGUiO31pOjE3O2E6MTE6e3M6Mzoic2lzIjtzOjk6IjIwMjEwMTM2NyI7czoxNDoiaWRfaW5zY3JpcGNpb24iO2k6NTY3O3M6Njoibm9tYnJlIjtzOjQ6Ikx1aXMiO3M6ODoiYXBlbGxpZG8iO3M6MTA6Ikd1dGnDqXJyZXoiO3M6NDoiYXVsYSI7czo5OiJBdWxhIDY5MkEiO3M6MTM6ImRvY2VudGVfY2FyZ28iO3M6MTM6IlJvYmVydG8gU2lsdmEiO3M6MTA6ImhhYmlsaXRhZG8iO2I6MTtzOjExOiJyZWdpc3RyYWRvciI7TjtzOjEyOiJob3JhX2luZ3Jlc28iO047czoxMDoiaW5jaWRlbmNpYSI7TjtzOjE3OiJlc3RhZG9fYXNpc3RlbmNpYSI7czo5OiJwZW5kaWVudGUiO31pOjE4O2E6MTE6e3M6Mzoic2lzIjtzOjk6IjIwMjEwMTM4OSI7czoxNDoiaWRfaW5zY3JpcGNpb24iO2k6NTY4O3M6Njoibm9tYnJlIjtzOjg6Ik5pY29sw6FzIjtzOjg6ImFwZWxsaWRvIjtzOjc6IklyaWFydGUiO3M6NDoiYXVsYSI7czo5OiJBdWxhIDY5MkEiO3M6MTM6ImRvY2VudGVfY2FyZ28iO3M6MTM6IlJvYmVydG8gU2lsdmEiO3M6MTA6ImhhYmlsaXRhZG8iO2I6MTtzOjExOiJyZWdpc3RyYWRvciI7TjtzOjEyOiJob3JhX2luZ3Jlc28iO047czoxMDoiaW5jaWRlbmNpYSI7TjtzOjE3OiJlc3RhZG9fYXNpc3RlbmNpYSI7czo5OiJwZW5kaWVudGUiO31pOjE5O2E6MTE6e3M6Mzoic2lzIjtzOjk6IjIwMjEwMTQwMSI7czoxNDoiaWRfaW5zY3JpcGNpb24iO2k6NTY5O3M6Njoibm9tYnJlIjtzOjU6IlBhYmxvIjtzOjg6ImFwZWxsaWRvIjtzOjg6Iktvd2Fsc2tpIjtzOjQ6ImF1bGEiO3M6OToiQXVsYSA2OTJBIjtzOjEzOiJkb2NlbnRlX2NhcmdvIjtzOjEzOiJSb2JlcnRvIFNpbHZhIjtzOjEwOiJoYWJpbGl0YWRvIjtiOjE7czoxMToicmVnaXN0cmFkb3IiO047czoxMjoiaG9yYV9pbmdyZXNvIjtOO3M6MTA6ImluY2lkZW5jaWEiO047czoxNzoiZXN0YWRvX2FzaXN0ZW5jaWEiO3M6OToicGVuZGllbnRlIjt9aToyMDthOjExOntzOjM6InNpcyI7czo5OiIyMDIxMDE0MjMiO3M6MTQ6ImlkX2luc2NyaXBjaW9uIjtpOjU3MDtzOjY6Im5vbWJyZSI7czo2OiJTZXJnaW8iO3M6ODoiYXBlbGxpZG8iO3M6NzoiTWVuZG96YSI7czo0OiJhdWxhIjtzOjk6IkF1bGEgNjkyQSI7czoxMzoiZG9jZW50ZV9jYXJnbyI7czoxMzoiUm9iZXJ0byBTaWx2YSI7czoxMDoiaGFiaWxpdGFkbyI7YjoxO3M6MTE6InJlZ2lzdHJhZG9yIjtOO3M6MTI6ImhvcmFfaW5ncmVzbyI7TjtzOjEwOiJpbmNpZGVuY2lhIjtOO3M6MTc6ImVzdGFkb19hc2lzdGVuY2lhIjtzOjk6InBlbmRpZW50ZSI7fWk6MjE7YToxMTp7czozOiJzaXMiO3M6OToiMjAyMTAxNDQ1IjtzOjE0OiJpZF9pbnNjcmlwY2lvbiI7aTo1NzE7czo2OiJub21icmUiO3M6NjoiVWxpc2VzIjtzOjg6ImFwZWxsaWRvIjtzOjg6Ik9yZWxsYW5hIjtzOjQ6ImF1bGEiO3M6OToiQXVsYSA2OTBCIjtzOjEzOiJkb2NlbnRlX2NhcmdvIjtzOjEzOiJSb2JlcnRvIFNpbHZhIjtzOjEwOiJoYWJpbGl0YWRvIjtiOjE7czoxMToicmVnaXN0cmFkb3IiO047czoxMjoiaG9yYV9pbmdyZXNvIjtOO3M6MTA6ImluY2lkZW5jaWEiO047czoxNzoiZXN0YWRvX2FzaXN0ZW5jaWEiO3M6OToicGVuZGllbnRlIjt9aToyMjthOjExOntzOjM6InNpcyI7czo5OiIyMDIxMDE0NjciO3M6MTQ6ImlkX2luc2NyaXBjaW9uIjtpOjU3MjtzOjY6Im5vbWJyZSI7czo2OiJXYWx0ZXIiO3M6ODoiYXBlbGxpZG8iO3M6NzoiUXVpcm9nYSI7czo0OiJhdWxhIjtzOjk6IkF1bGEgNjkyQSI7czoxMzoiZG9jZW50ZV9jYXJnbyI7czoxMzoiUm9iZXJ0byBTaWx2YSI7czoxMDoiaGFiaWxpdGFkbyI7YjoxO3M6MTE6InJlZ2lzdHJhZG9yIjtOO3M6MTI6ImhvcmFfaW5ncmVzbyI7TjtzOjEwOiJpbmNpZGVuY2lhIjtOO3M6MTc6ImVzdGFkb19hc2lzdGVuY2lhIjtzOjk6InBlbmRpZW50ZSI7fWk6MjM7YToxMTp7czozOiJzaXMiO3M6OToiMjAyMTAxNDg5IjtzOjE0OiJpZF9pbnNjcmlwY2lvbiI7aTo1NzM7czo2OiJub21icmUiO3M6NToiWWFtaWwiO3M6ODoiYXBlbGxpZG8iO3M6ODoiU8OhbmNoZXoiO3M6NDoiYXVsYSI7czo5OiJBdWxhIDY5MkEiO3M6MTM6ImRvY2VudGVfY2FyZ28iO3M6MTM6IlJvYmVydG8gU2lsdmEiO3M6MTA6ImhhYmlsaXRhZG8iO2I6MTtzOjExOiJyZWdpc3RyYWRvciI7TjtzOjEyOiJob3JhX2luZ3Jlc28iO047czoxMDoiaW5jaWRlbmNpYSI7TjtzOjE3OiJlc3RhZG9fYXNpc3RlbmNpYSI7czo5OiJwZW5kaWVudGUiO31pOjI0O2E6MTE6e3M6Mzoic2lzIjtzOjk6IjIwMjIwMTAxMyI7czoxNDoiaWRfaW5zY3JpcGNpb24iO2k6NTc0O3M6Njoibm9tYnJlIjtzOjM6IkFuYSI7czo4OiJhcGVsbGlkbyI7czo2OiJMw7NwZXoiO3M6NDoiYXVsYSI7czo5OiJBdWxhIDY5MEIiO3M6MTM6ImRvY2VudGVfY2FyZ28iO3M6MTM6IlJvYmVydG8gU2lsdmEiO3M6MTA6ImhhYmlsaXRhZG8iO2I6MTtzOjExOiJyZWdpc3RyYWRvciI7TjtzOjEyOiJob3JhX2luZ3Jlc28iO047czoxMDoiaW5jaWRlbmNpYSI7TjtzOjE3OiJlc3RhZG9fYXNpc3RlbmNpYSI7czo5OiJwZW5kaWVudGUiO31pOjI1O2E6MTE6e3M6Mzoic2lzIjtzOjk6IjIwMjIwMTAzMSI7czoxNDoiaWRfaW5zY3JpcGNpb24iO2k6NTc1O3M6Njoibm9tYnJlIjtzOjU6IkNhcmxhIjtzOjg6ImFwZWxsaWRvIjtzOjQ6IlJ1aXoiO3M6NDoiYXVsYSI7czo5OiJBdWxhIDY5MEIiO3M6MTM6ImRvY2VudGVfY2FyZ28iO3M6MTM6IlJvYmVydG8gU2lsdmEiO3M6MTA6ImhhYmlsaXRhZG8iO2I6MTtzOjExOiJyZWdpc3RyYWRvciI7TjtzOjEyOiJob3JhX2luZ3Jlc28iO047czoxMDoiaW5jaWRlbmNpYSI7TjtzOjE3OiJlc3RhZG9fYXNpc3RlbmNpYSI7czo5OiJwZW5kaWVudGUiO31pOjI2O2E6MTE6e3M6Mzoic2lzIjtzOjk6IjIwMjIwMTA1NiI7czoxNDoiaWRfaW5zY3JpcGNpb24iO2k6NTc2O3M6Njoibm9tYnJlIjtzOjc6IkbDoXRpbWEiO3M6ODoiYXBlbGxpZG8iO3M6NjoiUXVpc3BlIjtzOjQ6ImF1bGEiO3M6OToiQXVsYSA2OTBCIjtzOjEzOiJkb2NlbnRlX2NhcmdvIjtzOjEzOiJSb2JlcnRvIFNpbHZhIjtzOjEwOiJoYWJpbGl0YWRvIjtiOjE7czoxMToicmVnaXN0cmFkb3IiO047czoxMjoiaG9yYV9pbmdyZXNvIjtOO3M6MTA6ImluY2lkZW5jaWEiO047czoxNzoiZXN0YWRvX2FzaXN0ZW5jaWEiO3M6OToicGVuZGllbnRlIjt9aToyNzthOjExOntzOjM6InNpcyI7czo5OiIyMDIyMDEwODkiO3M6MTQ6ImlkX2luc2NyaXBjaW9uIjtpOjU3NztzOjY6Im5vbWJyZSI7czo2OiJIZWxlbmEiO3M6ODoiYXBlbGxpZG8iO3M6NjoiQ2hvcXVlIjtzOjQ6ImF1bGEiO3M6OToiQXVsYSA2OTBCIjtzOjEzOiJkb2NlbnRlX2NhcmdvIjtzOjEzOiJSb2JlcnRvIFNpbHZhIjtzOjEwOiJoYWJpbGl0YWRvIjtiOjE7czoxMToicmVnaXN0cmFkb3IiO047czoxMjoiaG9yYV9pbmdyZXNvIjtOO3M6MTA6ImluY2lkZW5jaWEiO047czoxNzoiZXN0YWRvX2FzaXN0ZW5jaWEiO3M6OToicGVuZGllbnRlIjt9aToyODthOjExOntzOjM6InNpcyI7czo5OiIyMDIyMDExMDEiO3M6MTQ6ImlkX2luc2NyaXBjaW9uIjtpOjU3ODtzOjY6Im5vbWJyZSI7czo1OiJKdWxpYSI7czo4OiJhcGVsbGlkbyI7czo3OiJHYXJjw61hIjtzOjQ6ImF1bGEiO3M6OToiQXVsYSA2OTJBIjtzOjEzOiJkb2NlbnRlX2NhcmdvIjtzOjEzOiJSb2JlcnRvIFNpbHZhIjtzOjEwOiJoYWJpbGl0YWRvIjtiOjE7czoxMToicmVnaXN0cmFkb3IiO047czoxMjoiaG9yYV9pbmdyZXNvIjtOO3M6MTA6ImluY2lkZW5jaWEiO047czoxNzoiZXN0YWRvX2FzaXN0ZW5jaWEiO3M6OToicGVuZGllbnRlIjt9aToyOTthOjExOntzOjM6InNpcyI7czo5OiIyMDIyMDExMjMiO3M6MTQ6ImlkX2luc2NyaXBjaW9uIjtpOjU3OTtzOjY6Im5vbWJyZSI7czo1OiJMYXVyYSI7czo4OiJhcGVsbGlkbyI7czo1OiJEw61heiI7czo0OiJhdWxhIjtzOjk6IkF1bGEgNjkwQiI7czoxMzoiZG9jZW50ZV9jYXJnbyI7czoxMzoiUm9iZXJ0byBTaWx2YSI7czoxMDoiaGFiaWxpdGFkbyI7YjoxO3M6MTE6InJlZ2lzdHJhZG9yIjtOO3M6MTI6ImhvcmFfaW5ncmVzbyI7TjtzOjEwOiJpbmNpZGVuY2lhIjtOO3M6MTc6ImVzdGFkb19hc2lzdGVuY2lhIjtzOjk6InBlbmRpZW50ZSI7fWk6MzA7YToxMTp7czozOiJzaXMiO3M6OToiMjAyMjAxMTQ1IjtzOjE0OiJpZF9pbnNjcmlwY2lvbiI7aTo1ODA7czo2OiJub21icmUiO3M6NToiTmFkaWEiO3M6ODoiYXBlbGxpZG8iO3M6NDoiVmVyYSI7czo0OiJhdWxhIjtzOjk6IkF1bGEgNjkyQSI7czoxMzoiZG9jZW50ZV9jYXJnbyI7czoxMzoiUm9iZXJ0byBTaWx2YSI7czoxMDoiaGFiaWxpdGFkbyI7YjoxO3M6MTE6InJlZ2lzdHJhZG9yIjtOO3M6MTI6ImhvcmFfaW5ncmVzbyI7TjtzOjEwOiJpbmNpZGVuY2lhIjtOO3M6MTc6ImVzdGFkb19hc2lzdGVuY2lhIjtzOjk6InBlbmRpZW50ZSI7fWk6MzE7YToxMTp7czozOiJzaXMiO3M6OToiMjAyMjAxMTY3IjtzOjE0OiJpZF9pbnNjcmlwY2lvbiI7aTo1ODE7czo2OiJub21icmUiO3M6ODoiUGF0cmljaWEiO3M6ODoiYXBlbGxpZG8iO3M6NjoiTWFtYW5pIjtzOjQ6ImF1bGEiO3M6OToiQXVsYSA2OTBCIjtzOjEzOiJkb2NlbnRlX2NhcmdvIjtzOjEzOiJSb2JlcnRvIFNpbHZhIjtzOjEwOiJoYWJpbGl0YWRvIjtiOjE7czoxMToicmVnaXN0cmFkb3IiO047czoxMjoiaG9yYV9pbmdyZXNvIjtOO3M6MTA6ImluY2lkZW5jaWEiO047czoxNzoiZXN0YWRvX2FzaXN0ZW5jaWEiO3M6OToicGVuZGllbnRlIjt9aTozMjthOjExOntzOjM6InNpcyI7czo5OiIyMDIyMDExODkiO3M6MTQ6ImlkX2luc2NyaXBjaW9uIjtpOjU4MjtzOjY6Im5vbWJyZSI7czo2OiJTb2bDrWEiO3M6ODoiYXBlbGxpZG8iO3M6NjoiRmxvcmVzIjtzOjQ6ImF1bGEiO3M6OToiQXVsYSA2OTJBIjtzOjEzOiJkb2NlbnRlX2NhcmdvIjtzOjEzOiJSb2JlcnRvIFNpbHZhIjtzOjEwOiJoYWJpbGl0YWRvIjtiOjE7czoxMToicmVnaXN0cmFkb3IiO047czoxMjoiaG9yYV9pbmdyZXNvIjtOO3M6MTA6ImluY2lkZW5jaWEiO047czoxNzoiZXN0YWRvX2FzaXN0ZW5jaWEiO3M6OToicGVuZGllbnRlIjt9aTozMzthOjExOntzOjM6InNpcyI7czo5OiIyMDIyMDEyMDEiO3M6MTQ6ImlkX2luc2NyaXBjaW9uIjtpOjU4MztzOjY6Im5vbWJyZSI7czo3OiJWYWxlcmlhIjtzOjg6ImFwZWxsaWRvIjtzOjY6IkzDs3BleiI7czo0OiJhdWxhIjtzOjk6IkF1bGEgNjkwQiI7czoxMzoiZG9jZW50ZV9jYXJnbyI7czoxMzoiUm9iZXJ0byBTaWx2YSI7czoxMDoiaGFiaWxpdGFkbyI7YjoxO3M6MTE6InJlZ2lzdHJhZG9yIjtOO3M6MTI6ImhvcmFfaW5ncmVzbyI7TjtzOjEwOiJpbmNpZGVuY2lhIjtOO3M6MTc6ImVzdGFkb19hc2lzdGVuY2lhIjtzOjk6InBlbmRpZW50ZSI7fWk6MzQ7YToxMTp7czozOiJzaXMiO3M6OToiMjAyMjAxMjEyIjtzOjE0OiJpZF9pbnNjcmlwY2lvbiI7aTo1ODQ7czo2OiJub21icmUiO3M6NToiV2VuZHkiO3M6ODoiYXBlbGxpZG8iO3M6NjoiWmFwYXRhIjtzOjQ6ImF1bGEiO3M6OToiQXVsYSA2OTBCIjtzOjEzOiJkb2NlbnRlX2NhcmdvIjtzOjEzOiJSb2JlcnRvIFNpbHZhIjtzOjEwOiJoYWJpbGl0YWRvIjtiOjE7czoxMToicmVnaXN0cmFkb3IiO047czoxMjoiaG9yYV9pbmdyZXNvIjtOO3M6MTA6ImluY2lkZW5jaWEiO047czoxNzoiZXN0YWRvX2FzaXN0ZW5jaWEiO3M6OToicGVuZGllbnRlIjt9aTozNTthOjExOntzOjM6InNpcyI7czo5OiIyMDIyMDEyMzQiO3M6MTQ6ImlkX2luc2NyaXBjaW9uIjtpOjU4NTtzOjY6Im5vbWJyZSI7czo3OiJZb2xhbmRhIjtzOjg6ImFwZWxsaWRvIjtzOjY6IlRpY29uYSI7czo0OiJhdWxhIjtzOjk6IkF1bGEgNjkwQiI7czoxMzoiZG9jZW50ZV9jYXJnbyI7czoxMzoiUm9iZXJ0byBTaWx2YSI7czoxMDoiaGFiaWxpdGFkbyI7YjoxO3M6MTE6InJlZ2lzdHJhZG9yIjtOO3M6MTI6ImhvcmFfaW5ncmVzbyI7TjtzOjEwOiJpbmNpZGVuY2lhIjtOO3M6MTc6ImVzdGFkb19hc2lzdGVuY2lhIjtzOjk6InBlbmRpZW50ZSI7fWk6MzY7YToxMTp7czozOiJzaXMiO3M6OToiMjAyMjAxMjU2IjtzOjE0OiJpZF9pbnNjcmlwY2lvbiI7aTo1ODY7czo2OiJub21icmUiO3M6NzoiQW5kcsOpcyI7czo4OiJhcGVsbGlkbyI7czo1OiJSb2phcyI7czo0OiJhdWxhIjtzOjk6IkF1bGEgNjkyQSI7czoxMzoiZG9jZW50ZV9jYXJnbyI7czoxMzoiUm9iZXJ0byBTaWx2YSI7czoxMDoiaGFiaWxpdGFkbyI7YjoxO3M6MTE6InJlZ2lzdHJhZG9yIjtOO3M6MTI6ImhvcmFfaW5ncmVzbyI7TjtzOjEwOiJpbmNpZGVuY2lhIjtOO3M6MTc6ImVzdGFkb19hc2lzdGVuY2lhIjtzOjk6InBlbmRpZW50ZSI7fWk6Mzc7YToxMTp7czozOiJzaXMiO3M6OToiMjAyMjAxMjc4IjtzOjE0OiJpZF9pbnNjcmlwY2lvbiI7aTo1ODc7czo2OiJub21icmUiO3M6ODoiQ3Jpc3RpYW4iO3M6ODoiYXBlbGxpZG8iO3M6NjoiTW9saW5hIjtzOjQ6ImF1bGEiO3M6OToiQXVsYSA2OTBCIjtzOjEzOiJkb2NlbnRlX2NhcmdvIjtzOjEzOiJSb2JlcnRvIFNpbHZhIjtzOjEwOiJoYWJpbGl0YWRvIjtiOjE7czoxMToicmVnaXN0cmFkb3IiO047czoxMjoiaG9yYV9pbmdyZXNvIjtOO3M6MTA6ImluY2lkZW5jaWEiO047czoxNzoiZXN0YWRvX2FzaXN0ZW5jaWEiO3M6OToicGVuZGllbnRlIjt9aTozODthOjExOntzOjM6InNpcyI7czo5OiIyMDIyMDEyOTAiO3M6MTQ6ImlkX2luc2NyaXBjaW9uIjtpOjU4ODtzOjY6Im5vbWJyZSI7czo3OiJFZHVhcmRvIjtzOjg6ImFwZWxsaWRvIjtzOjc6IlNhbGF6YXIiO3M6NDoiYXVsYSI7czo5OiJBdWxhIDY5MkEiO3M6MTM6ImRvY2VudGVfY2FyZ28iO3M6MTM6IlJvYmVydG8gU2lsdmEiO3M6MTA6ImhhYmlsaXRhZG8iO2I6MTtzOjExOiJyZWdpc3RyYWRvciI7TjtzOjEyOiJob3JhX2luZ3Jlc28iO047czoxMDoiaW5jaWRlbmNpYSI7TjtzOjE3OiJlc3RhZG9fYXNpc3RlbmNpYSI7czo5OiJwZW5kaWVudGUiO31pOjM5O2E6MTE6e3M6Mzoic2lzIjtzOjk6IjIwMjIwMTMxMiI7czoxNDoiaWRfaW5zY3JpcGNpb24iO2k6NTg5O3M6Njoibm9tYnJlIjtzOjc6IkdvbnphbG8iO3M6ODoiYXBlbGxpZG8iO3M6NDoiQXJjZSI7czo0OiJhdWxhIjtzOjk6IkF1bGEgNjkyQSI7czoxMzoiZG9jZW50ZV9jYXJnbyI7czoxMzoiUm9iZXJ0byBTaWx2YSI7czoxMDoiaGFiaWxpdGFkbyI7YjoxO3M6MTE6InJlZ2lzdHJhZG9yIjtOO3M6MTI6ImhvcmFfaW5ncmVzbyI7TjtzOjEwOiJpbmNpZGVuY2lhIjtOO3M6MTc6ImVzdGFkb19hc2lzdGVuY2lhIjtzOjk6InBlbmRpZW50ZSI7fWk6NDA7YToxMTp7czozOiJzaXMiO3M6OToiMjAyMjAxMzM0IjtzOjE0OiJpZF9pbnNjcmlwY2lvbiI7aTo1OTA7czo2OiJub21icmUiO3M6NToiSW7DqXMiO3M6ODoiYXBlbGxpZG8iO3M6NzoiRGVsZ2FkbyI7czo0OiJhdWxhIjtzOjk6IkF1bGEgNjkwQiI7czoxMzoiZG9jZW50ZV9jYXJnbyI7czoxMzoiUm9iZXJ0byBTaWx2YSI7czoxMDoiaGFiaWxpdGFkbyI7YjoxO3M6MTE6InJlZ2lzdHJhZG9yIjtOO3M6MTI6ImhvcmFfaW5ncmVzbyI7TjtzOjEwOiJpbmNpZGVuY2lhIjtOO3M6MTc6ImVzdGFkb19hc2lzdGVuY2lhIjtzOjk6InBlbmRpZW50ZSI7fWk6NDE7YToxMTp7czozOiJzaXMiO3M6OToiMjAyMjAxMzU2IjtzOjE0OiJpZF9pbnNjcmlwY2lvbiI7aTo1OTE7czo2OiJub21icmUiO3M6NToiS2FyZW4iO3M6ODoiYXBlbGxpZG8iO3M6NzoiRnVlbnRlcyI7czo0OiJhdWxhIjtzOjk6IkF1bGEgNjkwQiI7czoxMzoiZG9jZW50ZV9jYXJnbyI7czoxMzoiUm9iZXJ0byBTaWx2YSI7czoxMDoiaGFiaWxpdGFkbyI7YjoxO3M6MTE6InJlZ2lzdHJhZG9yIjtOO3M6MTI6ImhvcmFfaW5ncmVzbyI7TjtzOjEwOiJpbmNpZGVuY2lhIjtOO3M6MTc6ImVzdGFkb19hc2lzdGVuY2lhIjtzOjk6InBlbmRpZW50ZSI7fWk6NDI7YToxMTp7czozOiJzaXMiO3M6OToiMjAyMjAxMzc4IjtzOjE0OiJpZF9pbnNjcmlwY2lvbiI7aTo1OTI7czo2OiJub21icmUiO3M6NjoiTWFyw61hIjtzOjg6ImFwZWxsaWRvIjtzOjc6IkhlcnJlcmEiO3M6NDoiYXVsYSI7czo5OiJBdWxhIDY5MEIiO3M6MTM6ImRvY2VudGVfY2FyZ28iO3M6MTM6IlJvYmVydG8gU2lsdmEiO3M6MTA6ImhhYmlsaXRhZG8iO2I6MTtzOjExOiJyZWdpc3RyYWRvciI7TjtzOjEyOiJob3JhX2luZ3Jlc28iO047czoxMDoiaW5jaWRlbmNpYSI7TjtzOjE3OiJlc3RhZG9fYXNpc3RlbmNpYSI7czo5OiJwZW5kaWVudGUiO31pOjQzO2E6MTE6e3M6Mzoic2lzIjtzOjk6IjIwMjIwMTM5MCI7czoxNDoiaWRfaW5zY3JpcGNpb24iO2k6NTkzO3M6Njoibm9tYnJlIjtzOjQ6Ik9sZ2EiO3M6ODoiYXBlbGxpZG8iO3M6ODoiSmltw6luZXoiO3M6NDoiYXVsYSI7czo5OiJBdWxhIDY5MEIiO3M6MTM6ImRvY2VudGVfY2FyZ28iO3M6MTM6IlJvYmVydG8gU2lsdmEiO3M6MTA6ImhhYmlsaXRhZG8iO2I6MTtzOjExOiJyZWdpc3RyYWRvciI7TjtzOjEyOiJob3JhX2luZ3Jlc28iO047czoxMDoiaW5jaWRlbmNpYSI7TjtzOjE3OiJlc3RhZG9fYXNpc3RlbmNpYSI7czo5OiJwZW5kaWVudGUiO31pOjQ0O2E6MTE6e3M6Mzoic2lzIjtzOjk6IjIwMjIwMTQxMiI7czoxNDoiaWRfaW5zY3JpcGNpb24iO2k6NTk0O3M6Njoibm9tYnJlIjtzOjQ6IlJvc2EiO3M6ODoiYXBlbGxpZG8iO3M6NDoiTHVuYSI7czo0OiJhdWxhIjtzOjk6IkF1bGEgNjkyQSI7czoxMzoiZG9jZW50ZV9jYXJnbyI7czoxMzoiUm9iZXJ0byBTaWx2YSI7czoxMDoiaGFiaWxpdGFkbyI7YjoxO3M6MTE6InJlZ2lzdHJhZG9yIjtOO3M6MTI6ImhvcmFfaW5ncmVzbyI7TjtzOjEwOiJpbmNpZGVuY2lhIjtOO3M6MTc6ImVzdGFkb19hc2lzdGVuY2lhIjtzOjk6InBlbmRpZW50ZSI7fWk6NDU7YToxMTp7czozOiJzaXMiO3M6OToiMjAyMjAxNDM0IjtzOjE0OiJpZF9pbnNjcmlwY2lvbiI7aTo1OTU7czo2OiJub21icmUiO3M6NzoiVGF0aWFuYSI7czo4OiJhcGVsbGlkbyI7czo3OiJOw7rDsWV6IjtzOjQ6ImF1bGEiO3M6OToiQXVsYSA2OTJBIjtzOjEzOiJkb2NlbnRlX2NhcmdvIjtzOjEzOiJSb2JlcnRvIFNpbHZhIjtzOjEwOiJoYWJpbGl0YWRvIjtiOjE7czoxMToicmVnaXN0cmFkb3IiO047czoxMjoiaG9yYV9pbmdyZXNvIjtOO3M6MTA6ImluY2lkZW5jaWEiO047czoxNzoiZXN0YWRvX2FzaXN0ZW5jaWEiO3M6OToicGVuZGllbnRlIjt9aTo0NjthOjExOntzOjM6InNpcyI7czo5OiIyMDIyMDE0NTYiO3M6MTQ6ImlkX2luc2NyaXBjaW9uIjtpOjU5NjtzOjY6Im5vbWJyZSI7czo5OiJWZXLDs25pY2EiO3M6ODoiYXBlbGxpZG8iO3M6MzoiUGF6IjtzOjQ6ImF1bGEiO3M6OToiQXVsYSA2OTBCIjtzOjEzOiJkb2NlbnRlX2NhcmdvIjtzOjEzOiJSb2JlcnRvIFNpbHZhIjtzOjEwOiJoYWJpbGl0YWRvIjtiOjE7czoxMToicmVnaXN0cmFkb3IiO047czoxMjoiaG9yYV9pbmdyZXNvIjtOO3M6MTA6ImluY2lkZW5jaWEiO047czoxNzoiZXN0YWRvX2FzaXN0ZW5jaWEiO3M6OToicGVuZGllbnRlIjt9aTo0NzthOjExOntzOjM6InNpcyI7czo5OiIyMDIyMDE0NzgiO3M6MTQ6ImlkX2luc2NyaXBjaW9uIjtpOjU5NztzOjY6Im5vbWJyZSI7czo2OiJYaW1lbmEiO3M6ODoiYXBlbGxpZG8iO3M6ODoiUmFtw61yZXoiO3M6NDoiYXVsYSI7czo5OiJBdWxhIDY5MEIiO3M6MTM6ImRvY2VudGVfY2FyZ28iO3M6MTM6IlJvYmVydG8gU2lsdmEiO3M6MTA6ImhhYmlsaXRhZG8iO2I6MTtzOjExOiJyZWdpc3RyYWRvciI7TjtzOjEyOiJob3JhX2luZ3Jlc28iO047czoxMDoiaW5jaWRlbmNpYSI7TjtzOjE3OiJlc3RhZG9fYXNpc3RlbmNpYSI7czo5OiJwZW5kaWVudGUiO31pOjQ4O2E6MTE6e3M6Mzoic2lzIjtzOjk6IjIwMjIwMTQ5MCI7czoxNDoiaWRfaW5zY3JpcGNpb24iO2k6NTk4O3M6Njoibm9tYnJlIjtzOjU6IlphaXJhIjtzOjg6ImFwZWxsaWRvIjtzOjY6IlRvbGVkbyI7czo0OiJhdWxhIjtzOjk6IkF1bGEgNjkyQSI7czoxMzoiZG9jZW50ZV9jYXJnbyI7czoxMzoiUm9iZXJ0byBTaWx2YSI7czoxMDoiaGFiaWxpdGFkbyI7YjoxO3M6MTE6InJlZ2lzdHJhZG9yIjtOO3M6MTI6ImhvcmFfaW5ncmVzbyI7TjtzOjEwOiJpbmNpZGVuY2lhIjtOO3M6MTc6ImVzdGFkb19hc2lzdGVuY2lhIjtzOjk6InBlbmRpZW50ZSI7fWk6NDk7YToxMTp7czozOiJzaXMiO3M6OToiMjAyMjAyMDQ1IjtzOjE0OiJpZF9pbnNjcmlwY2lvbiI7aTo1OTk7czo2OiJub21icmUiO3M6NToiRGllZ28iO3M6ODoiYXBlbGxpZG8iO3M6NDoiU290byI7czo0OiJhdWxhIjtzOjk6IkF1bGEgNjkyQSI7czoxMzoiZG9jZW50ZV9jYXJnbyI7czoxMzoiUm9iZXJ0byBTaWx2YSI7czoxMDoiaGFiaWxpdGFkbyI7YjoxO3M6MTE6InJlZ2lzdHJhZG9yIjtOO3M6MTI6ImhvcmFfaW5ncmVzbyI7TjtzOjEwOiJpbmNpZGVuY2lhIjtOO3M6MTc6ImVzdGFkb19hc2lzdGVuY2lhIjtzOjk6InBlbmRpZW50ZSI7fX1zOjI4OiIAKgBlc2NhcGVXaGVuQ2FzdGluZ1RvU3RyaW5nIjtiOjA7fX0=', 1793045877);


INSERT INTO public.estudiante ("sis_estudiante", "nombre_estudiante", "apellido_estudiante", "carrera", "verificado") VALUES ('90600001', 'Maria Fernanda Alejandra', 'Gutierrez Condori Villarroel', 'Ingenieria', TRUE);

INSERT INTO public.estudiante ("sis_estudiante", "nombre_estudiante", "apellido_estudiante", "carrera", "verificado") VALUES ('90600002', 'Luis Alberto Maximiliano', 'Quispe Mamani de la Torre', 'Ingenieria', TRUE);

INSERT INTO public.estudiante ("sis_estudiante", "nombre_estudiante", "apellido_estudiante", "carrera", "verificado") VALUES ('90600003', 'Juan Carlos Eduardo', 'Perez Montano Arancibia', 'Ingenieria', TRUE);

INSERT INTO public.estudiante ("sis_estudiante", "nombre_estudiante", "apellido_estudiante", "carrera", "verificado") VALUES ('90600004', 'Ana Sofia Valentina', 'Rojas Camacho Balderrama', 'Ingenieria', TRUE);

INSERT INTO public.estudiante ("sis_estudiante", "nombre_estudiante", "apellido_estudiante", "carrera", "verificado") VALUES ('20210001', 'Ana', 'Perez', 'Informatica', TRUE);

INSERT INTO public.estudiante ("sis_estudiante", "nombre_estudiante", "apellido_estudiante", "carrera", "verificado") VALUES ('20210002', 'Luis', 'Mamani', 'Informatica', TRUE);

INSERT INTO public.estudiante ("sis_estudiante", "nombre_estudiante", "apellido_estudiante", "carrera", "verificado") VALUES ('20210003', 'Maria', 'Lopez', 'Sistemas', TRUE);

INSERT INTO public.estudiante ("sis_estudiante", "nombre_estudiante", "apellido_estudiante", "carrera", "verificado") VALUES ('20210004', 'Carlos', 'Rojas', 'Informatica', TRUE);

INSERT INTO public.estudiante ("sis_estudiante", "nombre_estudiante", "apellido_estudiante", "carrera", "verificado") VALUES ('20210005', 'Sofia', 'Flores', 'Sistemas', TRUE);

INSERT INTO public.estudiante ("sis_estudiante", "nombre_estudiante", "apellido_estudiante", "carrera", "verificado") VALUES ('202201013', 'Ana', 'López', NULL, TRUE);

INSERT INTO public.estudiante ("sis_estudiante", "nombre_estudiante", "apellido_estudiante", "carrera", "verificado") VALUES ('202101022', 'Bruno', 'Díaz', NULL, TRUE);

INSERT INTO public.estudiante ("sis_estudiante", "nombre_estudiante", "apellido_estudiante", "carrera", "verificado") VALUES ('202201031', 'Carla', 'Ruiz', NULL, TRUE);

INSERT INTO public.estudiante ("sis_estudiante", "nombre_estudiante", "apellido_estudiante", "carrera", "verificado") VALUES ('202202045', 'Diego', 'Soto', NULL, TRUE);

INSERT INTO public.estudiante ("sis_estudiante", "nombre_estudiante", "apellido_estudiante", "carrera", "verificado") VALUES ('202002107', 'Ernesto', 'Vera', NULL, TRUE);

INSERT INTO public.estudiante ("sis_estudiante", "nombre_estudiante", "apellido_estudiante", "carrera", "verificado") VALUES ('202201056', 'Fátima', 'Quispe', NULL, TRUE);

INSERT INTO public.estudiante ("sis_estudiante", "nombre_estudiante", "apellido_estudiante", "carrera", "verificado") VALUES ('202101078', 'Gabriel', 'Mamani', NULL, TRUE);

INSERT INTO public.estudiante ("sis_estudiante", "nombre_estudiante", "apellido_estudiante", "carrera", "verificado") VALUES ('202201089', 'Helena', 'Choque', NULL, TRUE);

INSERT INTO public.estudiante ("sis_estudiante", "nombre_estudiante", "apellido_estudiante", "carrera", "verificado") VALUES ('202101090', 'Iván', 'Flores', NULL, TRUE);

INSERT INTO public.estudiante ("sis_estudiante", "nombre_estudiante", "apellido_estudiante", "carrera", "verificado") VALUES ('202201101', 'Julia', 'García', NULL, TRUE);

INSERT INTO public.estudiante ("sis_estudiante", "nombre_estudiante", "apellido_estudiante", "carrera", "verificado") VALUES ('202101112', 'Kevin', 'López', NULL, TRUE);

INSERT INTO public.estudiante ("sis_estudiante", "nombre_estudiante", "apellido_estudiante", "carrera", "verificado") VALUES ('202201123', 'Laura', 'Díaz', NULL, TRUE);

INSERT INTO public.estudiante ("sis_estudiante", "nombre_estudiante", "apellido_estudiante", "carrera", "verificado") VALUES ('202101134', 'Miguel', 'Soto', NULL, TRUE);

INSERT INTO public.estudiante ("sis_estudiante", "nombre_estudiante", "apellido_estudiante", "carrera", "verificado") VALUES ('202201145', 'Nadia', 'Vera', NULL, TRUE);

INSERT INTO public.estudiante ("sis_estudiante", "nombre_estudiante", "apellido_estudiante", "carrera", "verificado") VALUES ('202101156', 'Óscar', 'Quispe', NULL, TRUE);

INSERT INTO public.estudiante ("sis_estudiante", "nombre_estudiante", "apellido_estudiante", "carrera", "verificado") VALUES ('202201167', 'Patricia', 'Mamani', NULL, TRUE);

INSERT INTO public.estudiante ("sis_estudiante", "nombre_estudiante", "apellido_estudiante", "carrera", "verificado") VALUES ('202101178', 'Raúl', 'Choque', NULL, TRUE);

INSERT INTO public.estudiante ("sis_estudiante", "nombre_estudiante", "apellido_estudiante", "carrera", "verificado") VALUES ('202201189', 'Sofía', 'Flores', NULL, TRUE);

INSERT INTO public.estudiante ("sis_estudiante", "nombre_estudiante", "apellido_estudiante", "carrera", "verificado") VALUES ('202101190', 'Tomás', 'García', NULL, TRUE);

INSERT INTO public.estudiante ("sis_estudiante", "nombre_estudiante", "apellido_estudiante", "carrera", "verificado") VALUES ('202201201', 'Valeria', 'López', NULL, TRUE);

INSERT INTO public.estudiante ("sis_estudiante", "nombre_estudiante", "apellido_estudiante", "carrera", "verificado") VALUES ('202201212', 'Wendy', 'Zapata', NULL, TRUE);

INSERT INTO public.estudiante ("sis_estudiante", "nombre_estudiante", "apellido_estudiante", "carrera", "verificado") VALUES ('202101223', 'Xavier', 'Vargas', NULL, TRUE);

INSERT INTO public.estudiante ("sis_estudiante", "nombre_estudiante", "apellido_estudiante", "carrera", "verificado") VALUES ('202201234', 'Yolanda', 'Ticona', NULL, TRUE);

INSERT INTO public.estudiante ("sis_estudiante", "nombre_estudiante", "apellido_estudiante", "carrera", "verificado") VALUES ('202101245', 'Zoe', 'Siles', NULL, TRUE);

INSERT INTO public.estudiante ("sis_estudiante", "nombre_estudiante", "apellido_estudiante", "carrera", "verificado") VALUES ('202201256', 'Andrés', 'Rojas', NULL, TRUE);

INSERT INTO public.estudiante ("sis_estudiante", "nombre_estudiante", "apellido_estudiante", "carrera", "verificado") VALUES ('202101267', 'Bianca', 'Paredes', NULL, TRUE);

INSERT INTO public.estudiante ("sis_estudiante", "nombre_estudiante", "apellido_estudiante", "carrera", "verificado") VALUES ('202201278', 'Cristian', 'Molina', NULL, TRUE);

INSERT INTO public.estudiante ("sis_estudiante", "nombre_estudiante", "apellido_estudiante", "carrera", "verificado") VALUES ('202101289', 'Daniela', 'Cabrera', NULL, TRUE);

INSERT INTO public.estudiante ("sis_estudiante", "nombre_estudiante", "apellido_estudiante", "carrera", "verificado") VALUES ('202201290', 'Eduardo', 'Salazar', NULL, TRUE);

INSERT INTO public.estudiante ("sis_estudiante", "nombre_estudiante", "apellido_estudiante", "carrera", "verificado") VALUES ('202101301', 'Fernanda', 'Vega', NULL, TRUE);

INSERT INTO public.estudiante ("sis_estudiante", "nombre_estudiante", "apellido_estudiante", "carrera", "verificado") VALUES ('202201312', 'Gonzalo', 'Arce', NULL, TRUE);

INSERT INTO public.estudiante ("sis_estudiante", "nombre_estudiante", "apellido_estudiante", "carrera", "verificado") VALUES ('202101323', 'Hugo', 'Camacho', NULL, TRUE);

INSERT INTO public.estudiante ("sis_estudiante", "nombre_estudiante", "apellido_estudiante", "carrera", "verificado") VALUES ('202201334', 'Inés', 'Delgado', NULL, TRUE);

INSERT INTO public.estudiante ("sis_estudiante", "nombre_estudiante", "apellido_estudiante", "carrera", "verificado") VALUES ('202101345', 'Javier', 'Espinoza', NULL, TRUE);

INSERT INTO public.estudiante ("sis_estudiante", "nombre_estudiante", "apellido_estudiante", "carrera", "verificado") VALUES ('202201356', 'Karen', 'Fuentes', NULL, TRUE);

INSERT INTO public.estudiante ("sis_estudiante", "nombre_estudiante", "apellido_estudiante", "carrera", "verificado") VALUES ('202101367', 'Luis', 'Gutiérrez', NULL, TRUE);

INSERT INTO public.estudiante ("sis_estudiante", "nombre_estudiante", "apellido_estudiante", "carrera", "verificado") VALUES ('202201378', 'María', 'Herrera', NULL, TRUE);

INSERT INTO public.estudiante ("sis_estudiante", "nombre_estudiante", "apellido_estudiante", "carrera", "verificado") VALUES ('202101389', 'Nicolás', 'Iriarte', NULL, TRUE);

INSERT INTO public.estudiante ("sis_estudiante", "nombre_estudiante", "apellido_estudiante", "carrera", "verificado") VALUES ('202201390', 'Olga', 'Jiménez', NULL, TRUE);

INSERT INTO public.estudiante ("sis_estudiante", "nombre_estudiante", "apellido_estudiante", "carrera", "verificado") VALUES ('202101401', 'Pablo', 'Kowalski', NULL, TRUE);

INSERT INTO public.estudiante ("sis_estudiante", "nombre_estudiante", "apellido_estudiante", "carrera", "verificado") VALUES ('202201412', 'Rosa', 'Luna', NULL, TRUE);

INSERT INTO public.estudiante ("sis_estudiante", "nombre_estudiante", "apellido_estudiante", "carrera", "verificado") VALUES ('202101423', 'Sergio', 'Mendoza', NULL, TRUE);

INSERT INTO public.estudiante ("sis_estudiante", "nombre_estudiante", "apellido_estudiante", "carrera", "verificado") VALUES ('202201434', 'Tatiana', 'Núñez', NULL, TRUE);

INSERT INTO public.estudiante ("sis_estudiante", "nombre_estudiante", "apellido_estudiante", "carrera", "verificado") VALUES ('202101445', 'Ulises', 'Orellana', NULL, TRUE);

INSERT INTO public.estudiante ("sis_estudiante", "nombre_estudiante", "apellido_estudiante", "carrera", "verificado") VALUES ('202201456', 'Verónica', 'Paz', NULL, TRUE);

INSERT INTO public.estudiante ("sis_estudiante", "nombre_estudiante", "apellido_estudiante", "carrera", "verificado") VALUES ('202101467', 'Walter', 'Quiroga', NULL, TRUE);

INSERT INTO public.estudiante ("sis_estudiante", "nombre_estudiante", "apellido_estudiante", "carrera", "verificado") VALUES ('202201478', 'Ximena', 'Ramírez', NULL, TRUE);

INSERT INTO public.estudiante ("sis_estudiante", "nombre_estudiante", "apellido_estudiante", "carrera", "verificado") VALUES ('202101489', 'Yamil', 'Sánchez', NULL, TRUE);

INSERT INTO public.estudiante ("sis_estudiante", "nombre_estudiante", "apellido_estudiante", "carrera", "verificado") VALUES ('202201490', 'Zaira', 'Toledo', NULL, TRUE);


INSERT INTO public.material ("id_material", "descripcion") VALUES (1, 'Calculadora cientifica');

INSERT INTO public.material ("id_material", "descripcion") VALUES (2, 'Computadora
');

INSERT INTO public.material ("id_material", "descripcion") VALUES (3, 'Formulario');

INSERT INTO public.material ("id_material", "descripcion") VALUES (4, 'Apuntes de la materia');

INSERT INTO public.material ("id_material", "descripcion") VALUES (5, 'Hojas de examen en blanco');


INSERT INTO public.migrations ("id", "migration", "batch") VALUES (1, '0001_01_01_000000_create_users_table', 1);

INSERT INTO public.migrations ("id", "migration", "batch") VALUES (2, '0001_01_01_000001_create_cache_table', 1);

INSERT INTO public.migrations ("id", "migration", "batch") VALUES (3, '0001_01_01_000002_create_jobs_table', 1);

INSERT INTO public.migrations ("id", "migration", "batch") VALUES (4, '2026_09_27_000001_migracion_servidor_oficial', 1);

INSERT INTO public.migrations ("id", "migration", "batch") VALUES (5, '2026_09_28_000001_actualizar_usuario', 1);

INSERT INTO public.migrations ("id", "migration", "batch") VALUES (6, '2026_10_09_000001_agregar_revision_incidencia', 2);

INSERT INTO public.migrations ("id", "migration", "batch") VALUES (7, '2026_10_10_000001_reparar_enums_y_personalizadas', 3);

INSERT INTO public.migrations ("id", "migration", "batch") VALUES (8, '2026_10_10_000002_quitar_hora_fin_de_examen', 4);

INSERT INTO public.migrations ("id", "migration", "batch") VALUES (9, '2026_10_10_000003_agregar_curso_a_central_riesgo', 5);

INSERT INTO public.migrations ("id", "migration", "batch") VALUES (10, '2026_10_11_000001_agregar_oculta_a_notificacion_docente', 6);

INSERT INTO public.migrations ("id", "migration", "batch") VALUES (11, '2026_10_11_000002_agregar_tipo_a_notificacion_docente', 7);

INSERT INTO public.migrations ("id", "migration", "batch") VALUES (12, '2026_10_10_000004_alinear_central_riesgo', 8);

INSERT INTO public.migrations ("id", "migration", "batch") VALUES (13, '2026_10_17_171754_agregar_motivos_ingreso_a_enum_motivo', 9);

INSERT INTO public.migrations ("id", "migration", "batch") VALUES (14, '2026_10_24_000001_agregar_anio_a_curso_tipo_gestion', 10);

INSERT INTO public.migrations ("id", "migration", "batch") VALUES (15, '2026_10_24_000002_completar_curso_en_central_riesgo', 11);

INSERT INTO public.migrations ("id", "migration", "batch") VALUES (16, '2026_10_24_000003_agregar_ambiente_a_curso_y_nombre_a_examen', 12);

INSERT INTO public.migrations ("id", "migration", "batch") VALUES (17, '2026_10_24_000003_completar_invitaciones_examen', 13);

INSERT INTO public.migrations ("id", "migration", "batch") VALUES (18, '2026_10_24_000004_adaptar_notificaciones_a_invitaciones', 14);

INSERT INTO public.migrations ("id", "migration", "batch") VALUES (19, '2026_10_25_000001_separar_configuracion_examen_por_docente', 15);

INSERT INTO public.migrations ("id", "migration", "batch") VALUES (20, '2026_10_25_000002_asociar_estudiantes_con_docente', 16);

INSERT INTO public.migrations ("id", "migration", "batch") VALUES (21, '2026_10_25_000003_registro_incidencias_estudiante_no_verificado', 17);


INSERT INTO public.norma ("id_norma", "detalle_norma") VALUES (5, 'No utilizar material no autorizado');

INSERT INTO public.norma ("id_norma", "detalle_norma") VALUES (1, 'Portar CI durante toda la evaluación');

INSERT INTO public.norma ("id_norma", "detalle_norma") VALUES (2, 'Mantener dispositivos electrónicos guardados');

INSERT INTO public.norma ("id_norma", "detalle_norma") VALUES (3, 'Tolerancia máxima de ingreso: 15 minutos');

INSERT INTO public.norma ("id_norma", "detalle_norma") VALUES (4, 'Prohibido hablar, consultar levantando la mano');


INSERT INTO public.rol ("id_rol", "nombre_rol") VALUES (1, 'docente');

INSERT INTO public.rol ("id_rol", "nombre_rol") VALUES (2, 'auxiliar');


INSERT INTO public.sessions ("id", "user_id", "ip_address", "user_agent", "payload", "last_activity") VALUES ('VT8oa8KNEQtn3niCgbsS6cdSNVv9tJ41inv4eOmg', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.143.1 Chrome/150.0.7871.250 Electron/43.7.7 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiak5nTkNkMzVYTmIyV0dRREJVeTRsOTl6T1o2S3I1Skp2WURKMVF4UyI7czozOiJ1cmwiO2E6MTp7czo4OiJpbnRlbmRlZCI7czoyMToiaHR0cDovLzEyNy4wLjAuMTo4MDAwIjt9czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1792976103);

INSERT INTO public.sessions ("id", "user_id", "ip_address", "user_agent", "payload", "last_activity") VALUES ('P4JnZjauvnFZ2Qvqx8rC8OAj05Y9oWBGUcVunQ6u', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36 Edg/156.0.0.0', 'YTo1OntzOjY6Il90b2tlbiI7czo0MDoiSjlOUU05aGlyaEo5c1piZEh6WkVMd2pSVDRlNElQbFNOQzdYaUV6dCI7czozOiJ1cmwiO2E6MDp7fXM6OToiX3ByZXZpb3VzIjthOjE6e3M6MzoidXJsIjtzOjM4OiJodHRwOi8vMTI3LjAuMC4xOjgwMDAvZXhhbWVuZXMvY3Vyc28vMSI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjE7fQ==', 1792977490);

INSERT INTO public.sessions ("id", "user_id", "ip_address", "user_agent", "payload", "last_activity") VALUES ('NwPtPhni15VdDyDcgiyimIiGdlA7q0obobLfLCQq', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36 Edg/156.0.0.0', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoidEVDNm42eEdoTXJUTTBPekdLNEJsRnNLcDRaVHB6UUloMzR3ZkZ1cCI7czozOiJ1cmwiO2E6MTp7czo4OiJpbnRlbmRlZCI7czoyMToiaHR0cDovLzEyNy4wLjAuMTo4MDAwIjt9czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1792985386);

INSERT INTO public.sessions ("id", "user_id", "ip_address", "user_agent", "payload", "last_activity") VALUES ('vQmTuMu51zNZ7ieC42NsiNrv51CQaQ6tvpRYIqd6', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36 Edg/156.0.0.0', 'YTo1OntzOjY6Il90b2tlbiI7czo0MDoiM25TM0FtWVBndUNTRldMNmtuNnU1Wm5PSzljT0NuMmsycENGc2ZIcSI7czozOiJ1cmwiO2E6MDp7fXM6OToiX3ByZXZpb3VzIjthOjE6e3M6MzoidXJsIjtzOjM4OiJodHRwOi8vMTI3LjAuMC4xOjgwMDAvZXhhbWVuZXM/Y3Vyc289MSI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjE7fQ==', 1792989899);


INSERT INTO public.tipo_examen ("id_tipo_examen", "nombre_tipo_examen") VALUES (1, 'examen parcial');

INSERT INTO public.tipo_examen ("id_tipo_examen", "nombre_tipo_examen") VALUES (2, 'examen final');

INSERT INTO public.tipo_examen ("id_tipo_examen", "nombre_tipo_examen") VALUES (3, 'segunda instancia');


INSERT INTO public.tipo_gestion ("id_tipo_gestion", "nombre_tipo_gestion") VALUES (1, 'primer semestre');

INSERT INTO public.tipo_gestion ("id_tipo_gestion", "nombre_tipo_gestion") VALUES (2, 'invierno');

INSERT INTO public.tipo_gestion ("id_tipo_gestion", "nombre_tipo_gestion") VALUES (3, 'segundo semestre');

INSERT INTO public.tipo_gestion ("id_tipo_gestion", "nombre_tipo_gestion") VALUES (4, 'verano');


INSERT INTO public.usuario ("id_usuario", "cod_sis", "password", "nombre_usuario", "apellido", "correo") VALUES (1, 'DOC001', '$2y$12$Q2NT4rFjKIiDm5rYZmhrVuvdRUoOeHRfbgkwKI3GZTsb5kn6olG1C', 'Roberto', 'Silva', NULL);

INSERT INTO public.usuario ("id_usuario", "cod_sis", "password", "nombre_usuario", "apellido", "correo") VALUES (2, 'DOC002', '$2y$12$cR9BiLcmfx2slphCGEJcfOrRHsfYSyY7dB2EX3ReggaZstvKvWhGK', 'Patricia', 'Rojas', NULL);

INSERT INTO public.usuario ("id_usuario", "cod_sis", "password", "nombre_usuario", "apellido", "correo") VALUES (3, 'AUX001', '$2y$12$88aNYUoZRhyfw9oA4BdsAujllCecA3ZpOgoe3ltMHkgpndwPwNDWq', 'Diego', 'Mendoza', NULL);

INSERT INTO public.usuario ("id_usuario", "cod_sis", "password", "nombre_usuario", "apellido", "correo") VALUES (4, 'AUX002', '$2y$12$mzepLz/sQ9IvxSgRk0vUC.WL.mYTSPu/xIaH0NOaQHVI13ZGdi9Y2', 'Sofia', 'Castro', NULL);

INSERT INTO public.usuario ("id_usuario", "cod_sis", "password", "nombre_usuario", "apellido", "correo") VALUES (5, 'DOC003', '$2y$12$0hAV63bJ8fER57b4eyNCI.77qGdXZW3nsEIcsV1yZlnsI0JHbfgZW', 'Fernando', 'Vargas', NULL);


INSERT INTO public.curso ("id_curso", "nombre_curso", "sis_doc", "fecha_creacion", "estado", "id_ambiente") VALUES (1, 'Calculo I', 1, '2024-02-01', 'EnCurso', NULL);

INSERT INTO public.curso ("id_curso", "nombre_curso", "sis_doc", "fecha_creacion", "estado", "id_ambiente") VALUES (2, 'Fisica I', 2, '2024-02-01', 'EnCurso', NULL);

INSERT INTO public.curso ("id_curso", "nombre_curso", "sis_doc", "fecha_creacion", "estado", "id_ambiente") VALUES (4, 'Algebra Lineal', 1, '2024-02-03', 'Finalizo', NULL);

INSERT INTO public.curso ("id_curso", "nombre_curso", "sis_doc", "fecha_creacion", "estado", "id_ambiente") VALUES (5, 'Quimica General', 2, '2024-02-04', 'EnCurso', NULL);

INSERT INTO public.curso ("id_curso", "nombre_curso", "sis_doc", "fecha_creacion", "estado", "id_ambiente") VALUES (7, 'Introduccion a la Programacion - Grupo 4', 2, '2026-10-24', 'EnCurso', NULL);

INSERT INTO public.curso ("id_curso", "nombre_curso", "sis_doc", "fecha_creacion", "estado", "id_ambiente") VALUES (8, 'Introduccion a la Programacion - Grupo 15A', 2, '2026-10-24', 'EnCurso', NULL);

INSERT INTO public.curso ("id_curso", "nombre_curso", "sis_doc", "fecha_creacion", "estado", "id_ambiente") VALUES (3, 'Programacion I', 1, '2024-02-02', 'EnCurso', NULL);

INSERT INTO public.curso ("id_curso", "nombre_curso", "sis_doc", "fecha_creacion", "estado", "id_ambiente") VALUES (6, 'Programacion I - Grupo 1', 2, '2026-10-24', 'EnCurso', NULL);


INSERT INTO public.examen ("id_examen", "fecha", "hora_inicio", "duracion", "creador", "tipo_examen", "nombre_examen") VALUES (1, '2024-06-10', '08:00:00', 120, 1, 1, NULL);

INSERT INTO public.examen ("id_examen", "fecha", "hora_inicio", "duracion", "creador", "tipo_examen", "nombre_examen") VALUES (2, '2024-06-11', '10:00:00', 120, 2, 2, NULL);

INSERT INTO public.examen ("id_examen", "fecha", "hora_inicio", "duracion", "creador", "tipo_examen", "nombre_examen") VALUES (3, '2024-06-12', '14:00:00', 120, 5, 3, NULL);

INSERT INTO public.examen ("id_examen", "fecha", "hora_inicio", "duracion", "creador", "tipo_examen", "nombre_examen") VALUES (4, '2024-06-13', '08:00:00', 120, 1, 1, NULL);

INSERT INTO public.examen ("id_examen", "fecha", "hora_inicio", "duracion", "creador", "tipo_examen", "nombre_examen") VALUES (5, '2024-06-14', '16:00:00', 120, 2, 2, NULL);

INSERT INTO public.examen ("id_examen", "fecha", "hora_inicio", "duracion", "creador", "tipo_examen", "nombre_examen") VALUES (8, '0262-12-10', '08:15:00', 90, 1, 2, NULL);

INSERT INTO public.examen ("id_examen", "fecha", "hora_inicio", "duracion", "creador", "tipo_examen", "nombre_examen") VALUES (27, '2026-10-27', '06:38:30', 90, 1, 2, NULL);

INSERT INTO public.examen ("id_examen", "fecha", "hora_inicio", "duracion", "creador", "tipo_examen", "nombre_examen") VALUES (28, '2026-10-27', '10:52:09', 90, 1, 1, NULL);

INSERT INTO public.examen ("id_examen", "fecha", "hora_inicio", "duracion", "creador", "tipo_examen", "nombre_examen") VALUES (29, '2026-10-27', '10:55:31', 90, 1, 1, NULL);

INSERT INTO public.examen ("id_examen", "fecha", "hora_inicio", "duracion", "creador", "tipo_examen", "nombre_examen") VALUES (35, '2026-10-27', '07:17:57', 90, 1, 1, NULL);

INSERT INTO public.examen ("id_examen", "fecha", "hora_inicio", "duracion", "creador", "tipo_examen", "nombre_examen") VALUES (14, '2026-10-24', '18:20:40', 90, 5, 2, NULL);

INSERT INTO public.examen ("id_examen", "fecha", "hora_inicio", "duracion", "creador", "tipo_examen", "nombre_examen") VALUES (10, '2004-05-12', '12:32:00', 180, 1, 1, NULL);

INSERT INTO public.examen ("id_examen", "fecha", "hora_inicio", "duracion", "creador", "tipo_examen", "nombre_examen") VALUES (11, '2001-06-13', '00:32:00', 90, 1, 2, NULL);

INSERT INTO public.examen ("id_examen", "fecha", "hora_inicio", "duracion", "creador", "tipo_examen", "nombre_examen") VALUES (17, '2026-10-25', '18:10:11', 90, 1, 1, NULL);

INSERT INTO public.examen ("id_examen", "fecha", "hora_inicio", "duracion", "creador", "tipo_examen", "nombre_examen") VALUES (18, '2026-10-25', '18:14:47', 90, 1, 1, NULL);

INSERT INTO public.examen ("id_examen", "fecha", "hora_inicio", "duracion", "creador", "tipo_examen", "nombre_examen") VALUES (19, '2026-10-25', '18:30:08', 90, 1, 1, NULL);

INSERT INTO public.examen ("id_examen", "fecha", "hora_inicio", "duracion", "creador", "tipo_examen", "nombre_examen") VALUES (20, '2026-10-25', '18:35:23', 90, 1, 1, NULL);

INSERT INTO public.examen ("id_examen", "fecha", "hora_inicio", "duracion", "creador", "tipo_examen", "nombre_examen") VALUES (13, '1900-12-31', '23:59:00', 88, 5, 1, NULL);

INSERT INTO public.examen ("id_examen", "fecha", "hora_inicio", "duracion", "creador", "tipo_examen", "nombre_examen") VALUES (15, '1998-07-03', '07:08:00', 90, 5, 2, NULL);

INSERT INTO public.examen ("id_examen", "fecha", "hora_inicio", "duracion", "creador", "tipo_examen", "nombre_examen") VALUES (9, '2024-07-15', '08:30:00', 90, 1, 2, NULL);

INSERT INTO public.examen ("id_examen", "fecha", "hora_inicio", "duracion", "creador", "tipo_examen", "nombre_examen") VALUES (12, '2024-07-16', '23:59:00', 90, 5, 1, NULL);

INSERT INTO public.examen ("id_examen", "fecha", "hora_inicio", "duracion", "creador", "tipo_examen", "nombre_examen") VALUES (7, '2024-07-17', '18:45:00', 90, 1, 1, NULL);

INSERT INTO public.examen ("id_examen", "fecha", "hora_inicio", "duracion", "creador", "tipo_examen", "nombre_examen") VALUES (6, '2024-07-18', '18:45:00', 90, 1, 1, NULL);

INSERT INTO public.examen ("id_examen", "fecha", "hora_inicio", "duracion", "creador", "tipo_examen", "nombre_examen") VALUES (21, '2026-10-25', '18:42:34', 90, 1, 1, NULL);

INSERT INTO public.examen ("id_examen", "fecha", "hora_inicio", "duracion", "creador", "tipo_examen", "nombre_examen") VALUES (22, '2026-10-25', '19:05:16', 90, 1, 1, NULL);

INSERT INTO public.examen ("id_examen", "fecha", "hora_inicio", "duracion", "creador", "tipo_examen", "nombre_examen") VALUES (23, '2026-10-25', '20:11:56', 90, 1, 1, NULL);

INSERT INTO public.examen ("id_examen", "fecha", "hora_inicio", "duracion", "creador", "tipo_examen", "nombre_examen") VALUES (24, '2026-10-26', '12:45:00', 90, 1, 1, NULL);

INSERT INTO public.examen ("id_examen", "fecha", "hora_inicio", "duracion", "creador", "tipo_examen", "nombre_examen") VALUES (25, '2026-10-26', '20:14:09', 90, 1, 1, NULL);

INSERT INTO public.examen ("id_examen", "fecha", "hora_inicio", "duracion", "creador", "tipo_examen", "nombre_examen") VALUES (16, '2026-10-26', '18:56:34', 90, 2, 1, NULL);

INSERT INTO public.examen ("id_examen", "fecha", "hora_inicio", "duracion", "creador", "tipo_examen", "nombre_examen") VALUES (26, '2026-10-26', '21:57:12', 120, 1, 3, NULL);

INSERT INTO public.examen ("id_examen", "fecha", "hora_inicio", "duracion", "creador", "tipo_examen", "nombre_examen") VALUES (30, '2026-11-03', '09:00:00', 90, 1, 1, NULL);

INSERT INTO public.examen ("id_examen", "fecha", "hora_inicio", "duracion", "creador", "tipo_examen", "nombre_examen") VALUES (31, '2026-11-05', '09:00:00', 90, 1, 1, NULL);

INSERT INTO public.examen ("id_examen", "fecha", "hora_inicio", "duracion", "creador", "tipo_examen", "nombre_examen") VALUES (32, '2026-11-07', '09:00:00', 90, 1, 1, NULL);

INSERT INTO public.examen ("id_examen", "fecha", "hora_inicio", "duracion", "creador", "tipo_examen", "nombre_examen") VALUES (33, '2026-11-09', '09:00:00', 90, 1, 1, NULL);

INSERT INTO public.examen ("id_examen", "fecha", "hora_inicio", "duracion", "creador", "tipo_examen", "nombre_examen") VALUES (34, '2026-11-11', '09:00:00', 90, 1, 1, NULL);


INSERT INTO public.rol_usuario ("id_usuario", "id_rol") VALUES (1, 1);

INSERT INTO public.rol_usuario ("id_usuario", "id_rol") VALUES (2, 1);

INSERT INTO public.rol_usuario ("id_usuario", "id_rol") VALUES (3, 2);

INSERT INTO public.rol_usuario ("id_usuario", "id_rol") VALUES (4, 2);

INSERT INTO public.rol_usuario ("id_usuario", "id_rol") VALUES (5, 1);


INSERT INTO public.auxiliar_curso ("id_auxiliar", "id_curso", "estado") VALUES (3, 1, 'Activo');

INSERT INTO public.auxiliar_curso ("id_auxiliar", "id_curso", "estado") VALUES (4, 2, 'Activo');

INSERT INTO public.auxiliar_curso ("id_auxiliar", "id_curso", "estado") VALUES (3, 3, 'Activo');

INSERT INTO public.auxiliar_curso ("id_auxiliar", "id_curso", "estado") VALUES (4, 4, 'Baja');

INSERT INTO public.auxiliar_curso ("id_auxiliar", "id_curso", "estado") VALUES (3, 5, 'Activo');


INSERT INTO public.configuracion_examen_docente ("id_configuracion_examen", "id_examen", "id_docente", "created_at", "updated_at") VALUES (1, 1, 1, '2026-10-25 15:23:13', '2026-10-25 15:23:13');

INSERT INTO public.configuracion_examen_docente ("id_configuracion_examen", "id_examen", "id_docente", "created_at", "updated_at") VALUES (2, 9, 1, '2026-10-25 15:23:13', '2026-10-25 15:23:13');

INSERT INTO public.configuracion_examen_docente ("id_configuracion_examen", "id_examen", "id_docente", "created_at", "updated_at") VALUES (3, 15, 5, '2026-10-25 15:23:13', '2026-10-25 15:23:13');

INSERT INTO public.configuracion_examen_docente ("id_configuracion_examen", "id_examen", "id_docente", "created_at", "updated_at") VALUES (4, 14, 1, '2026-10-25 15:23:13', '2026-10-25 15:23:13');

INSERT INTO public.configuracion_examen_docente ("id_configuracion_examen", "id_examen", "id_docente", "created_at", "updated_at") VALUES (5, 11, 1, '2026-10-25 15:23:13', '2026-10-25 15:23:13');

INSERT INTO public.configuracion_examen_docente ("id_configuracion_examen", "id_examen", "id_docente", "created_at", "updated_at") VALUES (6, 5, 2, '2026-10-25 15:23:13', '2026-10-25 15:23:13');

INSERT INTO public.configuracion_examen_docente ("id_configuracion_examen", "id_examen", "id_docente", "created_at", "updated_at") VALUES (7, 12, 5, '2026-10-25 15:23:13', '2026-10-25 15:23:13');

INSERT INTO public.configuracion_examen_docente ("id_configuracion_examen", "id_examen", "id_docente", "created_at", "updated_at") VALUES (8, 1, 2, '2026-10-25 15:23:13', '2026-10-25 15:23:13');

INSERT INTO public.configuracion_examen_docente ("id_configuracion_examen", "id_examen", "id_docente", "created_at", "updated_at") VALUES (9, 10, 1, '2026-10-25 15:23:13', '2026-10-25 15:23:13');

INSERT INTO public.configuracion_examen_docente ("id_configuracion_examen", "id_examen", "id_docente", "created_at", "updated_at") VALUES (10, 6, 1, '2026-10-25 15:23:13', '2026-10-25 15:23:13');

INSERT INTO public.configuracion_examen_docente ("id_configuracion_examen", "id_examen", "id_docente", "created_at", "updated_at") VALUES (11, 4, 1, '2026-10-25 15:23:13', '2026-10-25 15:23:13');

INSERT INTO public.configuracion_examen_docente ("id_configuracion_examen", "id_examen", "id_docente", "created_at", "updated_at") VALUES (12, 13, 1, '2026-10-25 15:23:13', '2026-10-25 15:23:13');

INSERT INTO public.configuracion_examen_docente ("id_configuracion_examen", "id_examen", "id_docente", "created_at", "updated_at") VALUES (13, 12, 1, '2026-10-25 15:23:13', '2026-10-25 15:23:13');

INSERT INTO public.configuracion_examen_docente ("id_configuracion_examen", "id_examen", "id_docente", "created_at", "updated_at") VALUES (14, 13, 5, '2026-10-25 15:23:13', '2026-10-25 15:23:13');

INSERT INTO public.configuracion_examen_docente ("id_configuracion_examen", "id_examen", "id_docente", "created_at", "updated_at") VALUES (15, 3, 5, '2026-10-25 15:23:13', '2026-10-25 15:23:13');

INSERT INTO public.configuracion_examen_docente ("id_configuracion_examen", "id_examen", "id_docente", "created_at", "updated_at") VALUES (16, 8, 1, '2026-10-25 15:23:13', '2026-10-25 15:23:13');

INSERT INTO public.configuracion_examen_docente ("id_configuracion_examen", "id_examen", "id_docente", "created_at", "updated_at") VALUES (17, 7, 1, '2026-10-25 15:23:13', '2026-10-25 15:23:13');

INSERT INTO public.configuracion_examen_docente ("id_configuracion_examen", "id_examen", "id_docente", "created_at", "updated_at") VALUES (18, 4, 2, '2026-10-25 15:23:13', '2026-10-25 15:23:13');

INSERT INTO public.configuracion_examen_docente ("id_configuracion_examen", "id_examen", "id_docente", "created_at", "updated_at") VALUES (19, 16, 2, '2026-10-25 15:23:13', '2026-10-25 15:23:13');

INSERT INTO public.configuracion_examen_docente ("id_configuracion_examen", "id_examen", "id_docente", "created_at", "updated_at") VALUES (20, 2, 2, '2026-10-25 15:23:13', '2026-10-25 15:23:13');

INSERT INTO public.configuracion_examen_docente ("id_configuracion_examen", "id_examen", "id_docente", "created_at", "updated_at") VALUES (21, 3, 1, '2026-10-25 15:23:13', '2026-10-25 15:23:13');

INSERT INTO public.configuracion_examen_docente ("id_configuracion_examen", "id_examen", "id_docente", "created_at", "updated_at") VALUES (22, 14, 5, '2026-10-25 15:23:13', '2026-10-25 15:23:13');

INSERT INTO public.configuracion_examen_docente ("id_configuracion_examen", "id_examen", "id_docente", "created_at", "updated_at") VALUES (23, 15, 1, '2026-10-25 15:23:13', '2026-10-25 15:23:13');

INSERT INTO public.configuracion_examen_docente ("id_configuracion_examen", "id_examen", "id_docente", "created_at", "updated_at") VALUES (29, 17, 1, '2026-10-25 17:08:11', '2026-10-25 17:08:11');

INSERT INTO public.configuracion_examen_docente ("id_configuracion_examen", "id_examen", "id_docente", "created_at", "updated_at") VALUES (30, 18, 1, '2026-10-25 17:08:26', '2026-10-25 17:08:26');

INSERT INTO public.configuracion_examen_docente ("id_configuracion_examen", "id_examen", "id_docente", "created_at", "updated_at") VALUES (31, 19, 1, '2026-10-25 17:08:38', '2026-10-25 17:08:38');

INSERT INTO public.configuracion_examen_docente ("id_configuracion_examen", "id_examen", "id_docente", "created_at", "updated_at") VALUES (32, 20, 1, '2026-10-25 17:08:50', '2026-10-25 17:08:50');

INSERT INTO public.configuracion_examen_docente ("id_configuracion_examen", "id_examen", "id_docente", "created_at", "updated_at") VALUES (33, 21, 1, '2026-10-25 17:09:06', '2026-10-25 17:09:06');

INSERT INTO public.configuracion_examen_docente ("id_configuracion_examen", "id_examen", "id_docente", "created_at", "updated_at") VALUES (35, 22, 1, '2026-10-25 17:09:28', '2026-10-25 17:09:28');

INSERT INTO public.configuracion_examen_docente ("id_configuracion_examen", "id_examen", "id_docente", "created_at", "updated_at") VALUES (36, 23, 1, '2026-10-25 17:09:39', '2026-10-25 17:09:39');

INSERT INTO public.configuracion_examen_docente ("id_configuracion_examen", "id_examen", "id_docente", "created_at", "updated_at") VALUES (41, 24, 1, '2026-10-26 01:13:23', '2026-10-26 01:13:23');

INSERT INTO public.configuracion_examen_docente ("id_configuracion_examen", "id_examen", "id_docente", "created_at", "updated_at") VALUES (42, 25, 1, '2026-10-26 19:15:55', '2026-10-26 19:15:55');

INSERT INTO public.configuracion_examen_docente ("id_configuracion_examen", "id_examen", "id_docente", "created_at", "updated_at") VALUES (43, 26, 1, '2026-10-26 16:49:16', '2026-10-26 16:49:16');

INSERT INTO public.configuracion_examen_docente ("id_configuracion_examen", "id_examen", "id_docente", "created_at", "updated_at") VALUES (44, 1, 5, '2026-10-26 17:13:36', '2026-10-26 17:13:36');

INSERT INTO public.configuracion_examen_docente ("id_configuracion_examen", "id_examen", "id_docente", "created_at", "updated_at") VALUES (45, 26, 2, '2026-10-26 18:46:57', '2026-10-26 18:46:57');

INSERT INTO public.configuracion_examen_docente ("id_configuracion_examen", "id_examen", "id_docente", "created_at", "updated_at") VALUES (46, 27, 1, '2026-10-27 01:09:37', '2026-10-27 01:09:37');

INSERT INTO public.configuracion_examen_docente ("id_configuracion_examen", "id_examen", "id_docente", "created_at", "updated_at") VALUES (47, 28, 1, '2026-10-27 03:04:22', '2026-10-27 03:04:22');

INSERT INTO public.configuracion_examen_docente ("id_configuracion_examen", "id_examen", "id_docente", "created_at", "updated_at") VALUES (48, 29, 1, '2026-10-27 03:04:33', '2026-10-27 03:04:33');

INSERT INTO public.configuracion_examen_docente ("id_configuracion_examen", "id_examen", "id_docente", "created_at", "updated_at") VALUES (49, 30, 1, '2026-10-27 03:04:44', '2026-10-27 03:04:44');

INSERT INTO public.configuracion_examen_docente ("id_configuracion_examen", "id_examen", "id_docente", "created_at", "updated_at") VALUES (50, 31, 1, '2026-10-27 03:04:55', '2026-10-27 03:04:55');

INSERT INTO public.configuracion_examen_docente ("id_configuracion_examen", "id_examen", "id_docente", "created_at", "updated_at") VALUES (51, 32, 1, '2026-10-27 03:05:06', '2026-10-27 03:05:06');

INSERT INTO public.configuracion_examen_docente ("id_configuracion_examen", "id_examen", "id_docente", "created_at", "updated_at") VALUES (52, 33, 1, '2026-10-27 03:05:17', '2026-10-27 03:05:17');

INSERT INTO public.configuracion_examen_docente ("id_configuracion_examen", "id_examen", "id_docente", "created_at", "updated_at") VALUES (53, 34, 1, '2026-10-27 03:05:28', '2026-10-27 03:05:28');

INSERT INTO public.configuracion_examen_docente ("id_configuracion_examen", "id_examen", "id_docente", "created_at", "updated_at") VALUES (54, 35, 1, '2026-10-27 10:54:25', '2026-10-27 10:54:25');


INSERT INTO public.curso_tipo_gestion ("id_curso", "id_tg", "anio") VALUES (1, 1, 2024);

INSERT INTO public.curso_tipo_gestion ("id_curso", "id_tg", "anio") VALUES (2, 1, 2024);

INSERT INTO public.curso_tipo_gestion ("id_curso", "id_tg", "anio") VALUES (4, 3, 2024);

INSERT INTO public.curso_tipo_gestion ("id_curso", "id_tg", "anio") VALUES (5, 4, 2024);

INSERT INTO public.curso_tipo_gestion ("id_curso", "id_tg", "anio") VALUES (3, 2, 2024);

INSERT INTO public.curso_tipo_gestion ("id_curso", "id_tg", "anio") VALUES (6, 3, 2026);

INSERT INTO public.curso_tipo_gestion ("id_curso", "id_tg", "anio") VALUES (7, 1, 2026);

INSERT INTO public.curso_tipo_gestion ("id_curso", "id_tg", "anio") VALUES (8, 1, 2026);


INSERT INTO public.estudiante_curso ("sis_estudiante", "id_curso") VALUES ('20210001', 1);

INSERT INTO public.estudiante_curso ("sis_estudiante", "id_curso") VALUES ('20210002', 2);

INSERT INTO public.estudiante_curso ("sis_estudiante", "id_curso") VALUES ('20210003', 3);

INSERT INTO public.estudiante_curso ("sis_estudiante", "id_curso") VALUES ('20210004', 4);

INSERT INTO public.estudiante_curso ("sis_estudiante", "id_curso") VALUES ('20210005', 5);

INSERT INTO public.estudiante_curso ("sis_estudiante", "id_curso") VALUES ('202201013', 3);

INSERT INTO public.estudiante_curso ("sis_estudiante", "id_curso") VALUES ('202101022', 3);

INSERT INTO public.estudiante_curso ("sis_estudiante", "id_curso") VALUES ('202201031', 3);

INSERT INTO public.estudiante_curso ("sis_estudiante", "id_curso") VALUES ('202202045', 3);

INSERT INTO public.estudiante_curso ("sis_estudiante", "id_curso") VALUES ('202002107', 3);

INSERT INTO public.estudiante_curso ("sis_estudiante", "id_curso") VALUES ('202201056', 3);

INSERT INTO public.estudiante_curso ("sis_estudiante", "id_curso") VALUES ('202101078', 3);

INSERT INTO public.estudiante_curso ("sis_estudiante", "id_curso") VALUES ('202201089', 3);

INSERT INTO public.estudiante_curso ("sis_estudiante", "id_curso") VALUES ('202101090', 3);

INSERT INTO public.estudiante_curso ("sis_estudiante", "id_curso") VALUES ('202201101', 3);

INSERT INTO public.estudiante_curso ("sis_estudiante", "id_curso") VALUES ('202101112', 3);

INSERT INTO public.estudiante_curso ("sis_estudiante", "id_curso") VALUES ('202201123', 3);

INSERT INTO public.estudiante_curso ("sis_estudiante", "id_curso") VALUES ('202101134', 3);

INSERT INTO public.estudiante_curso ("sis_estudiante", "id_curso") VALUES ('202201145', 3);

INSERT INTO public.estudiante_curso ("sis_estudiante", "id_curso") VALUES ('202101156', 3);

INSERT INTO public.estudiante_curso ("sis_estudiante", "id_curso") VALUES ('202201167', 3);

INSERT INTO public.estudiante_curso ("sis_estudiante", "id_curso") VALUES ('202101178', 3);

INSERT INTO public.estudiante_curso ("sis_estudiante", "id_curso") VALUES ('202201189', 3);

INSERT INTO public.estudiante_curso ("sis_estudiante", "id_curso") VALUES ('202101190', 3);

INSERT INTO public.estudiante_curso ("sis_estudiante", "id_curso") VALUES ('202201201', 3);

INSERT INTO public.estudiante_curso ("sis_estudiante", "id_curso") VALUES ('202201212', 3);

INSERT INTO public.estudiante_curso ("sis_estudiante", "id_curso") VALUES ('202101223', 3);

INSERT INTO public.estudiante_curso ("sis_estudiante", "id_curso") VALUES ('202201234', 3);

INSERT INTO public.estudiante_curso ("sis_estudiante", "id_curso") VALUES ('202101245', 3);

INSERT INTO public.estudiante_curso ("sis_estudiante", "id_curso") VALUES ('202201256', 3);

INSERT INTO public.estudiante_curso ("sis_estudiante", "id_curso") VALUES ('202101267', 3);

INSERT INTO public.estudiante_curso ("sis_estudiante", "id_curso") VALUES ('202201278', 3);

INSERT INTO public.estudiante_curso ("sis_estudiante", "id_curso") VALUES ('202101289', 3);

INSERT INTO public.estudiante_curso ("sis_estudiante", "id_curso") VALUES ('202201290', 3);

INSERT INTO public.estudiante_curso ("sis_estudiante", "id_curso") VALUES ('202101301', 3);

INSERT INTO public.estudiante_curso ("sis_estudiante", "id_curso") VALUES ('202201312', 3);

INSERT INTO public.estudiante_curso ("sis_estudiante", "id_curso") VALUES ('202101323', 3);

INSERT INTO public.estudiante_curso ("sis_estudiante", "id_curso") VALUES ('202201334', 3);

INSERT INTO public.estudiante_curso ("sis_estudiante", "id_curso") VALUES ('202101345', 3);

INSERT INTO public.estudiante_curso ("sis_estudiante", "id_curso") VALUES ('202201356', 3);

INSERT INTO public.estudiante_curso ("sis_estudiante", "id_curso") VALUES ('202101367', 3);

INSERT INTO public.estudiante_curso ("sis_estudiante", "id_curso") VALUES ('202201378', 3);

INSERT INTO public.estudiante_curso ("sis_estudiante", "id_curso") VALUES ('202101389', 3);

INSERT INTO public.estudiante_curso ("sis_estudiante", "id_curso") VALUES ('202201390', 3);

INSERT INTO public.estudiante_curso ("sis_estudiante", "id_curso") VALUES ('202101401', 3);

INSERT INTO public.estudiante_curso ("sis_estudiante", "id_curso") VALUES ('202201412', 3);

INSERT INTO public.estudiante_curso ("sis_estudiante", "id_curso") VALUES ('202101423', 3);

INSERT INTO public.estudiante_curso ("sis_estudiante", "id_curso") VALUES ('202201434', 3);

INSERT INTO public.estudiante_curso ("sis_estudiante", "id_curso") VALUES ('202101445', 3);

INSERT INTO public.estudiante_curso ("sis_estudiante", "id_curso") VALUES ('202201456', 3);

INSERT INTO public.estudiante_curso ("sis_estudiante", "id_curso") VALUES ('202101467', 3);

INSERT INTO public.estudiante_curso ("sis_estudiante", "id_curso") VALUES ('202201478', 3);

INSERT INTO public.estudiante_curso ("sis_estudiante", "id_curso") VALUES ('202101489', 3);

INSERT INTO public.estudiante_curso ("sis_estudiante", "id_curso") VALUES ('202201490', 3);


INSERT INTO public.examen_ambiente ("id_examen", "id_ambiente") VALUES (1, 1);

INSERT INTO public.examen_ambiente ("id_examen", "id_ambiente") VALUES (2, 2);

INSERT INTO public.examen_ambiente ("id_examen", "id_ambiente") VALUES (3, 3);

INSERT INTO public.examen_ambiente ("id_examen", "id_ambiente") VALUES (4, 4);

INSERT INTO public.examen_ambiente ("id_examen", "id_ambiente") VALUES (5, 5);

INSERT INTO public.examen_ambiente ("id_examen", "id_ambiente") VALUES (6, 2);

INSERT INTO public.examen_ambiente ("id_examen", "id_ambiente") VALUES (7, 2);

INSERT INTO public.examen_ambiente ("id_examen", "id_ambiente") VALUES (8, 1);

INSERT INTO public.examen_ambiente ("id_examen", "id_ambiente") VALUES (9, 1);

INSERT INTO public.examen_ambiente ("id_examen", "id_ambiente") VALUES (10, 5);

INSERT INTO public.examen_ambiente ("id_examen", "id_ambiente") VALUES (11, 2);

INSERT INTO public.examen_ambiente ("id_examen", "id_ambiente") VALUES (12, 2);

INSERT INTO public.examen_ambiente ("id_examen", "id_ambiente") VALUES (13, 2);

INSERT INTO public.examen_ambiente ("id_examen", "id_ambiente") VALUES (14, 1);

INSERT INTO public.examen_ambiente ("id_examen", "id_ambiente") VALUES (15, 5);

INSERT INTO public.examen_ambiente ("id_examen", "id_ambiente") VALUES (16, 5);

INSERT INTO public.examen_ambiente ("id_examen", "id_ambiente") VALUES (17, 1);

INSERT INTO public.examen_ambiente ("id_examen", "id_ambiente") VALUES (18, 1);

INSERT INTO public.examen_ambiente ("id_examen", "id_ambiente") VALUES (19, 1);

INSERT INTO public.examen_ambiente ("id_examen", "id_ambiente") VALUES (20, 1);

INSERT INTO public.examen_ambiente ("id_examen", "id_ambiente") VALUES (21, 1);

INSERT INTO public.examen_ambiente ("id_examen", "id_ambiente") VALUES (22, 1);

INSERT INTO public.examen_ambiente ("id_examen", "id_ambiente") VALUES (23, 1);

INSERT INTO public.examen_ambiente ("id_examen", "id_ambiente") VALUES (24, 1);

INSERT INTO public.examen_ambiente ("id_examen", "id_ambiente") VALUES (24, 5);

INSERT INTO public.examen_ambiente ("id_examen", "id_ambiente") VALUES (25, 2);

INSERT INTO public.examen_ambiente ("id_examen", "id_ambiente") VALUES (25, 5);

INSERT INTO public.examen_ambiente ("id_examen", "id_ambiente") VALUES (26, 5);

INSERT INTO public.examen_ambiente ("id_examen", "id_ambiente") VALUES (26, 2);

INSERT INTO public.examen_ambiente ("id_examen", "id_ambiente") VALUES (27, 2);

INSERT INTO public.examen_ambiente ("id_examen", "id_ambiente") VALUES (28, 1);

INSERT INTO public.examen_ambiente ("id_examen", "id_ambiente") VALUES (29, 1);

INSERT INTO public.examen_ambiente ("id_examen", "id_ambiente") VALUES (30, 1);

INSERT INTO public.examen_ambiente ("id_examen", "id_ambiente") VALUES (31, 1);

INSERT INTO public.examen_ambiente ("id_examen", "id_ambiente") VALUES (32, 1);

INSERT INTO public.examen_ambiente ("id_examen", "id_ambiente") VALUES (33, 1);

INSERT INTO public.examen_ambiente ("id_examen", "id_ambiente") VALUES (34, 1);

INSERT INTO public.examen_ambiente ("id_examen", "id_ambiente") VALUES (35, 5);


INSERT INTO public.examen_curso ("id_examen", "id_curso") VALUES (1, 1);

INSERT INTO public.examen_curso ("id_examen", "id_curso") VALUES (2, 2);

INSERT INTO public.examen_curso ("id_examen", "id_curso") VALUES (3, 3);

INSERT INTO public.examen_curso ("id_examen", "id_curso") VALUES (4, 4);

INSERT INTO public.examen_curso ("id_examen", "id_curso") VALUES (5, 5);

INSERT INTO public.examen_curso ("id_examen", "id_curso") VALUES (13, 3);

INSERT INTO public.examen_curso ("id_examen", "id_curso") VALUES (14, 3);

INSERT INTO public.examen_curso ("id_examen", "id_curso") VALUES (6, 1);

INSERT INTO public.examen_curso ("id_examen", "id_curso") VALUES (7, 1);

INSERT INTO public.examen_curso ("id_examen", "id_curso") VALUES (8, 1);

INSERT INTO public.examen_curso ("id_examen", "id_curso") VALUES (15, 3);

INSERT INTO public.examen_curso ("id_examen", "id_curso") VALUES (9, 1);

INSERT INTO public.examen_curso ("id_examen", "id_curso") VALUES (16, 7);

INSERT INTO public.examen_curso ("id_examen", "id_curso") VALUES (10, 1);

INSERT INTO public.examen_curso ("id_examen", "id_curso") VALUES (11, 1);

INSERT INTO public.examen_curso ("id_examen", "id_curso") VALUES (17, 3);

INSERT INTO public.examen_curso ("id_examen", "id_curso") VALUES (18, 3);

INSERT INTO public.examen_curso ("id_examen", "id_curso") VALUES (19, 3);

INSERT INTO public.examen_curso ("id_examen", "id_curso") VALUES (20, 3);

INSERT INTO public.examen_curso ("id_examen", "id_curso") VALUES (21, 3);

INSERT INTO public.examen_curso ("id_examen", "id_curso") VALUES (12, 3);

INSERT INTO public.examen_curso ("id_examen", "id_curso") VALUES (22, 3);

INSERT INTO public.examen_curso ("id_examen", "id_curso") VALUES (23, 3);

INSERT INTO public.examen_curso ("id_examen", "id_curso") VALUES (24, 1);

INSERT INTO public.examen_curso ("id_examen", "id_curso") VALUES (25, 3);

INSERT INTO public.examen_curso ("id_examen", "id_curso") VALUES (26, 1);

INSERT INTO public.examen_curso ("id_examen", "id_curso") VALUES (27, 3);

INSERT INTO public.examen_curso ("id_examen", "id_curso") VALUES (28, 1);

INSERT INTO public.examen_curso ("id_examen", "id_curso") VALUES (29, 3);

INSERT INTO public.examen_curso ("id_examen", "id_curso") VALUES (30, 4);

INSERT INTO public.examen_curso ("id_examen", "id_curso") VALUES (31, 1);

INSERT INTO public.examen_curso ("id_examen", "id_curso") VALUES (32, 3);

INSERT INTO public.examen_curso ("id_examen", "id_curso") VALUES (33, 4);

INSERT INTO public.examen_curso ("id_examen", "id_curso") VALUES (34, 1);

INSERT INTO public.examen_curso ("id_examen", "id_curso") VALUES (35, 3);


INSERT INTO public.examen_material_permitido ("id_examen", "id_material_permitido") VALUES (1, 1);

INSERT INTO public.examen_material_permitido ("id_examen", "id_material_permitido") VALUES (2, 2);

INSERT INTO public.examen_material_permitido ("id_examen", "id_material_permitido") VALUES (3, 3);

INSERT INTO public.examen_material_permitido ("id_examen", "id_material_permitido") VALUES (4, 4);

INSERT INTO public.examen_material_permitido ("id_examen", "id_material_permitido") VALUES (5, 5);

INSERT INTO public.examen_material_permitido ("id_examen", "id_material_permitido") VALUES (6, 5);

INSERT INTO public.examen_material_permitido ("id_examen", "id_material_permitido") VALUES (7, 5);

INSERT INTO public.examen_material_permitido ("id_examen", "id_material_permitido") VALUES (8, 1);

INSERT INTO public.examen_material_permitido ("id_examen", "id_material_permitido") VALUES (8, 2);

INSERT INTO public.examen_material_permitido ("id_examen", "id_material_permitido") VALUES (9, 1);

INSERT INTO public.examen_material_permitido ("id_examen", "id_material_permitido") VALUES (10, 3);

INSERT INTO public.examen_material_permitido ("id_examen", "id_material_permitido") VALUES (11, 1);

INSERT INTO public.examen_material_permitido ("id_examen", "id_material_permitido") VALUES (11, 3);

INSERT INTO public.examen_material_permitido ("id_examen", "id_material_permitido") VALUES (14, 4);

INSERT INTO public.examen_material_permitido ("id_examen", "id_material_permitido") VALUES (14, 1);

INSERT INTO public.examen_material_permitido ("id_examen", "id_material_permitido") VALUES (15, 2);

INSERT INTO public.examen_material_permitido ("id_examen", "id_material_permitido") VALUES (15, 3);

INSERT INTO public.examen_material_permitido ("id_examen", "id_material_permitido") VALUES (24, 1);

INSERT INTO public.examen_material_permitido ("id_examen", "id_material_permitido") VALUES (25, 4);

INSERT INTO public.examen_material_permitido ("id_examen", "id_material_permitido") VALUES (25, 1);

INSERT INTO public.examen_material_permitido ("id_examen", "id_material_permitido") VALUES (25, 2);

INSERT INTO public.examen_material_permitido ("id_examen", "id_material_permitido") VALUES (25, 3);

INSERT INTO public.examen_material_permitido ("id_examen", "id_material_permitido") VALUES (26, 4);

INSERT INTO public.examen_material_permitido ("id_examen", "id_material_permitido") VALUES (26, 2);

INSERT INTO public.examen_material_permitido ("id_examen", "id_material_permitido") VALUES (27, 4);

INSERT INTO public.examen_material_permitido ("id_examen", "id_material_permitido") VALUES (27, 2);

INSERT INTO public.examen_material_permitido ("id_examen", "id_material_permitido") VALUES (35, 1);


INSERT INTO public.examen_norma ("id_examen", "id_norma") VALUES (1, 1);

INSERT INTO public.examen_norma ("id_examen", "id_norma") VALUES (2, 2);

INSERT INTO public.examen_norma ("id_examen", "id_norma") VALUES (3, 3);

INSERT INTO public.examen_norma ("id_examen", "id_norma") VALUES (4, 4);

INSERT INTO public.examen_norma ("id_examen", "id_norma") VALUES (5, 5);

INSERT INTO public.examen_norma ("id_examen", "id_norma") VALUES (6, 1);

INSERT INTO public.examen_norma ("id_examen", "id_norma") VALUES (7, 1);

INSERT INTO public.examen_norma ("id_examen", "id_norma") VALUES (8, 1);

INSERT INTO public.examen_norma ("id_examen", "id_norma") VALUES (9, 1);

INSERT INTO public.examen_norma ("id_examen", "id_norma") VALUES (10, 2);

INSERT INTO public.examen_norma ("id_examen", "id_norma") VALUES (11, 5);

INSERT INTO public.examen_norma ("id_examen", "id_norma") VALUES (14, 2);

INSERT INTO public.examen_norma ("id_examen", "id_norma") VALUES (14, 5);

INSERT INTO public.examen_norma ("id_examen", "id_norma") VALUES (14, 1);

INSERT INTO public.examen_norma ("id_examen", "id_norma") VALUES (14, 4);

INSERT INTO public.examen_norma ("id_examen", "id_norma") VALUES (14, 3);

INSERT INTO public.examen_norma ("id_examen", "id_norma") VALUES (15, 1);

INSERT INTO public.examen_norma ("id_examen", "id_norma") VALUES (15, 4);

INSERT INTO public.examen_norma ("id_examen", "id_norma") VALUES (24, 4);

INSERT INTO public.examen_norma ("id_examen", "id_norma") VALUES (26, 2);

INSERT INTO public.examen_norma ("id_examen", "id_norma") VALUES (26, 3);

INSERT INTO public.examen_norma ("id_examen", "id_norma") VALUES (27, 1);

INSERT INTO public.examen_norma ("id_examen", "id_norma") VALUES (35, 5);


INSERT INTO public.invitacion_examen_compartido ("id_invitacion", "id_docente_invitado", "id_examen", "estado", "id_docente_invitador", "created_at", "updated_at") VALUES (1, 2, 1, 'aceptado', 1, '2026-10-25 15:14:15', '2026-10-25 15:14:15');

INSERT INTO public.invitacion_examen_compartido ("id_invitacion", "id_docente_invitado", "id_examen", "estado", "id_docente_invitador", "created_at", "updated_at") VALUES (2, 5, 2, 'pendiente', 2, '2026-10-25 15:14:15', '2026-10-25 15:14:15');

INSERT INTO public.invitacion_examen_compartido ("id_invitacion", "id_docente_invitado", "id_examen", "estado", "id_docente_invitador", "created_at", "updated_at") VALUES (3, 1, 3, 'rechazado', 5, '2026-10-25 15:14:15', '2026-10-25 15:14:15');

INSERT INTO public.invitacion_examen_compartido ("id_invitacion", "id_docente_invitado", "id_examen", "estado", "id_docente_invitador", "created_at", "updated_at") VALUES (4, 2, 4, 'aceptado', 1, '2026-10-25 15:14:15', '2026-10-25 15:14:15');

INSERT INTO public.invitacion_examen_compartido ("id_invitacion", "id_docente_invitado", "id_examen", "estado", "id_docente_invitador", "created_at", "updated_at") VALUES (5, 5, 5, 'pendiente', 2, '2026-10-25 15:14:15', '2026-10-25 15:14:15');

INSERT INTO public.invitacion_examen_compartido ("id_invitacion", "id_docente_invitado", "id_examen", "estado", "id_docente_invitador", "created_at", "updated_at") VALUES (6, 5, 1, 'cancelado', 1, '2026-10-26 17:12:31', '2026-10-26 17:12:46');

INSERT INTO public.invitacion_examen_compartido ("id_invitacion", "id_docente_invitado", "id_examen", "estado", "id_docente_invitador", "created_at", "updated_at") VALUES (7, 5, 1, 'aceptado', 1, '2026-10-26 17:13:14', '2026-10-26 17:13:34');

INSERT INTO public.invitacion_examen_compartido ("id_invitacion", "id_docente_invitado", "id_examen", "estado", "id_docente_invitador", "created_at", "updated_at") VALUES (8, 5, 4, 'cancelado', 1, '2026-10-26 17:15:38', '2026-10-26 17:16:18');

INSERT INTO public.invitacion_examen_compartido ("id_invitacion", "id_docente_invitado", "id_examen", "estado", "id_docente_invitador", "created_at", "updated_at") VALUES (9, 2, 26, 'aceptado', 1, '2026-10-26 18:24:15', '2026-10-26 18:46:55');


INSERT INTO public.material_personalizado ("id_examen", "numero_material", "descripcion_material") VALUES (10, 1, 'asdada');

INSERT INTO public.material_personalizado ("id_examen", "numero_material", "descripcion_material") VALUES (11, 1, 'sd23123');

INSERT INTO public.material_personalizado ("id_examen", "numero_material", "descripcion_material") VALUES (13, 1, 'Calculadora programable');

INSERT INTO public.material_personalizado ("id_examen", "numero_material", "descripcion_material") VALUES (25, 1, 'adasd');


INSERT INTO public.norma_personalizada ("id_examen", "numero_norma", "descripcion_norma") VALUES (1, 1, 'Se permite el uso de calculadora científica no programable.');

INSERT INTO public.norma_personalizada ("id_examen", "numero_norma", "descripcion_norma") VALUES (1, 2, 'No se permite hojas adicionales, usar el reverso del examen.');

INSERT INTO public.norma_personalizada ("id_examen", "numero_norma", "descripcion_norma") VALUES (2, 1, 'El código debe compilar sin errores para ser evaluado.');

INSERT INTO public.norma_personalizada ("id_examen", "numero_norma", "descripcion_norma") VALUES (2, 2, 'Prohibido el acceso a internet o repositorios externos.');

INSERT INTO public.norma_personalizada ("id_examen", "numero_norma", "descripcion_norma") VALUES (3, 1, 'Tiempo estricto de 45 minutos. No hay prórroga.');

INSERT INTO public.norma_personalizada ("id_examen", "numero_norma", "descripcion_norma") VALUES (4, 1, 'Uso obligatorio de bata de laboratorio y gafas de seguridad.');

INSERT INTO public.norma_personalizada ("id_examen", "numero_norma", "descripcion_norma") VALUES (4, 2, 'Entregar el reporte de datos antes de salir del aula.');

INSERT INTO public.norma_personalizada ("id_examen", "numero_norma", "descripcion_norma") VALUES (5, 1, 'Responder únicamente con bolígrafo de tinta negra o azul.');

INSERT INTO public.norma_personalizada ("id_examen", "numero_norma", "descripcion_norma") VALUES (5, 2, 'Desactivar y guardar teléfonos móviles en la mochila.');

INSERT INTO public.norma_personalizada ("id_examen", "numero_norma", "descripcion_norma") VALUES (10, 1, 'dadad');

INSERT INTO public.norma_personalizada ("id_examen", "numero_norma", "descripcion_norma") VALUES (11, 1, 'dffgs');

INSERT INTO public.norma_personalizada ("id_examen", "numero_norma", "descripcion_norma") VALUES (13, 1, 'Ingreso sin mochilas');

INSERT INTO public.norma_personalizada ("id_examen", "numero_norma", "descripcion_norma") VALUES (25, 1, 'adsasdsa');


INSERT INTO public.notificacion_auxiliar ("id_notificacion", "id_curso", "id_ambiente", "estado") VALUES (1, 1, 1, 'recibido');

INSERT INTO public.notificacion_auxiliar ("id_notificacion", "id_curso", "id_ambiente", "estado") VALUES (2, 2, 2, 'visto');

INSERT INTO public.notificacion_auxiliar ("id_notificacion", "id_curso", "id_ambiente", "estado") VALUES (3, 3, 3, 'recibido');

INSERT INTO public.notificacion_auxiliar ("id_notificacion", "id_curso", "id_ambiente", "estado") VALUES (4, 4, 4, 'visto');

INSERT INTO public.notificacion_auxiliar ("id_notificacion", "id_curso", "id_ambiente", "estado") VALUES (5, 5, 5, 'recibido');


INSERT INTO public.configuracion_examen_material ("id_configuracion_examen", "id_material") VALUES (1, 1);

INSERT INTO public.configuracion_examen_material ("id_configuracion_examen", "id_material") VALUES (2, 1);

INSERT INTO public.configuracion_examen_material ("id_configuracion_examen", "id_material") VALUES (3, 2);

INSERT INTO public.configuracion_examen_material ("id_configuracion_examen", "id_material") VALUES (3, 3);

INSERT INTO public.configuracion_examen_material ("id_configuracion_examen", "id_material") VALUES (4, 4);

INSERT INTO public.configuracion_examen_material ("id_configuracion_examen", "id_material") VALUES (4, 1);

INSERT INTO public.configuracion_examen_material ("id_configuracion_examen", "id_material") VALUES (5, 1);

INSERT INTO public.configuracion_examen_material ("id_configuracion_examen", "id_material") VALUES (5, 3);

INSERT INTO public.configuracion_examen_material ("id_configuracion_examen", "id_material") VALUES (6, 5);

INSERT INTO public.configuracion_examen_material ("id_configuracion_examen", "id_material") VALUES (8, 1);

INSERT INTO public.configuracion_examen_material ("id_configuracion_examen", "id_material") VALUES (9, 3);

INSERT INTO public.configuracion_examen_material ("id_configuracion_examen", "id_material") VALUES (10, 5);

INSERT INTO public.configuracion_examen_material ("id_configuracion_examen", "id_material") VALUES (11, 4);

INSERT INTO public.configuracion_examen_material ("id_configuracion_examen", "id_material") VALUES (15, 3);

INSERT INTO public.configuracion_examen_material ("id_configuracion_examen", "id_material") VALUES (16, 1);

INSERT INTO public.configuracion_examen_material ("id_configuracion_examen", "id_material") VALUES (16, 2);

INSERT INTO public.configuracion_examen_material ("id_configuracion_examen", "id_material") VALUES (17, 5);

INSERT INTO public.configuracion_examen_material ("id_configuracion_examen", "id_material") VALUES (18, 4);

INSERT INTO public.configuracion_examen_material ("id_configuracion_examen", "id_material") VALUES (20, 2);

INSERT INTO public.configuracion_examen_material ("id_configuracion_examen", "id_material") VALUES (21, 3);

INSERT INTO public.configuracion_examen_material ("id_configuracion_examen", "id_material") VALUES (22, 4);

INSERT INTO public.configuracion_examen_material ("id_configuracion_examen", "id_material") VALUES (22, 1);

INSERT INTO public.configuracion_examen_material ("id_configuracion_examen", "id_material") VALUES (23, 2);

INSERT INTO public.configuracion_examen_material ("id_configuracion_examen", "id_material") VALUES (23, 3);

INSERT INTO public.configuracion_examen_material ("id_configuracion_examen", "id_material") VALUES (41, 1);

INSERT INTO public.configuracion_examen_material ("id_configuracion_examen", "id_material") VALUES (42, 1);

INSERT INTO public.configuracion_examen_material ("id_configuracion_examen", "id_material") VALUES (42, 2);

INSERT INTO public.configuracion_examen_material ("id_configuracion_examen", "id_material") VALUES (42, 3);

INSERT INTO public.configuracion_examen_material ("id_configuracion_examen", "id_material") VALUES (42, 4);

INSERT INTO public.configuracion_examen_material ("id_configuracion_examen", "id_material") VALUES (43, 2);

INSERT INTO public.configuracion_examen_material ("id_configuracion_examen", "id_material") VALUES (43, 4);

INSERT INTO public.configuracion_examen_material ("id_configuracion_examen", "id_material") VALUES (44, 1);

INSERT INTO public.configuracion_examen_material ("id_configuracion_examen", "id_material") VALUES (45, 2);

INSERT INTO public.configuracion_examen_material ("id_configuracion_examen", "id_material") VALUES (45, 4);

INSERT INTO public.configuracion_examen_material ("id_configuracion_examen", "id_material") VALUES (46, 2);

INSERT INTO public.configuracion_examen_material ("id_configuracion_examen", "id_material") VALUES (46, 4);

INSERT INTO public.configuracion_examen_material ("id_configuracion_examen", "id_material") VALUES (54, 1);


INSERT INTO public.configuracion_examen_norma ("id_configuracion_examen", "id_norma") VALUES (1, 1);

INSERT INTO public.configuracion_examen_norma ("id_configuracion_examen", "id_norma") VALUES (2, 1);

INSERT INTO public.configuracion_examen_norma ("id_configuracion_examen", "id_norma") VALUES (3, 1);

INSERT INTO public.configuracion_examen_norma ("id_configuracion_examen", "id_norma") VALUES (3, 4);

INSERT INTO public.configuracion_examen_norma ("id_configuracion_examen", "id_norma") VALUES (4, 2);

INSERT INTO public.configuracion_examen_norma ("id_configuracion_examen", "id_norma") VALUES (4, 5);

INSERT INTO public.configuracion_examen_norma ("id_configuracion_examen", "id_norma") VALUES (4, 1);

INSERT INTO public.configuracion_examen_norma ("id_configuracion_examen", "id_norma") VALUES (4, 4);

INSERT INTO public.configuracion_examen_norma ("id_configuracion_examen", "id_norma") VALUES (4, 3);

INSERT INTO public.configuracion_examen_norma ("id_configuracion_examen", "id_norma") VALUES (5, 5);

INSERT INTO public.configuracion_examen_norma ("id_configuracion_examen", "id_norma") VALUES (6, 5);

INSERT INTO public.configuracion_examen_norma ("id_configuracion_examen", "id_norma") VALUES (8, 1);

INSERT INTO public.configuracion_examen_norma ("id_configuracion_examen", "id_norma") VALUES (9, 2);

INSERT INTO public.configuracion_examen_norma ("id_configuracion_examen", "id_norma") VALUES (10, 1);

INSERT INTO public.configuracion_examen_norma ("id_configuracion_examen", "id_norma") VALUES (11, 4);

INSERT INTO public.configuracion_examen_norma ("id_configuracion_examen", "id_norma") VALUES (15, 3);

INSERT INTO public.configuracion_examen_norma ("id_configuracion_examen", "id_norma") VALUES (16, 1);

INSERT INTO public.configuracion_examen_norma ("id_configuracion_examen", "id_norma") VALUES (17, 1);

INSERT INTO public.configuracion_examen_norma ("id_configuracion_examen", "id_norma") VALUES (18, 4);

INSERT INTO public.configuracion_examen_norma ("id_configuracion_examen", "id_norma") VALUES (20, 2);

INSERT INTO public.configuracion_examen_norma ("id_configuracion_examen", "id_norma") VALUES (21, 3);

INSERT INTO public.configuracion_examen_norma ("id_configuracion_examen", "id_norma") VALUES (22, 2);

INSERT INTO public.configuracion_examen_norma ("id_configuracion_examen", "id_norma") VALUES (22, 5);

INSERT INTO public.configuracion_examen_norma ("id_configuracion_examen", "id_norma") VALUES (22, 1);

INSERT INTO public.configuracion_examen_norma ("id_configuracion_examen", "id_norma") VALUES (22, 4);

INSERT INTO public.configuracion_examen_norma ("id_configuracion_examen", "id_norma") VALUES (22, 3);

INSERT INTO public.configuracion_examen_norma ("id_configuracion_examen", "id_norma") VALUES (23, 1);

INSERT INTO public.configuracion_examen_norma ("id_configuracion_examen", "id_norma") VALUES (23, 4);

INSERT INTO public.configuracion_examen_norma ("id_configuracion_examen", "id_norma") VALUES (41, 4);

INSERT INTO public.configuracion_examen_norma ("id_configuracion_examen", "id_norma") VALUES (43, 2);

INSERT INTO public.configuracion_examen_norma ("id_configuracion_examen", "id_norma") VALUES (43, 3);

INSERT INTO public.configuracion_examen_norma ("id_configuracion_examen", "id_norma") VALUES (44, 1);

INSERT INTO public.configuracion_examen_norma ("id_configuracion_examen", "id_norma") VALUES (45, 2);

INSERT INTO public.configuracion_examen_norma ("id_configuracion_examen", "id_norma") VALUES (45, 3);

INSERT INTO public.configuracion_examen_norma ("id_configuracion_examen", "id_norma") VALUES (46, 1);

INSERT INTO public.configuracion_examen_norma ("id_configuracion_examen", "id_norma") VALUES (54, 5);


INSERT INTO public.configuracion_material_personalizado ("id_configuracion_material", "id_configuracion_examen", "numero_material", "descripcion_material") VALUES (1, 5, 1, 'sd23123');

INSERT INTO public.configuracion_material_personalizado ("id_configuracion_material", "id_configuracion_examen", "numero_material", "descripcion_material") VALUES (2, 9, 1, 'asdada');

INSERT INTO public.configuracion_material_personalizado ("id_configuracion_material", "id_configuracion_examen", "numero_material", "descripcion_material") VALUES (3, 12, 1, 'Calculadora programable');

INSERT INTO public.configuracion_material_personalizado ("id_configuracion_material", "id_configuracion_examen", "numero_material", "descripcion_material") VALUES (4, 14, 1, 'Calculadora programable');

INSERT INTO public.configuracion_material_personalizado ("id_configuracion_material", "id_configuracion_examen", "numero_material", "descripcion_material") VALUES (11, 42, 1, 'adasd');


INSERT INTO public.configuracion_norma_personalizada ("id_configuracion_norma", "id_configuracion_examen", "numero_norma", "descripcion_norma") VALUES (1, 1, 1, 'Se permite el uso de calculadora científica no programable.');

INSERT INTO public.configuracion_norma_personalizada ("id_configuracion_norma", "id_configuracion_examen", "numero_norma", "descripcion_norma") VALUES (2, 1, 2, 'No se permite hojas adicionales, usar el reverso del examen.');

INSERT INTO public.configuracion_norma_personalizada ("id_configuracion_norma", "id_configuracion_examen", "numero_norma", "descripcion_norma") VALUES (3, 5, 1, 'dffgs');

INSERT INTO public.configuracion_norma_personalizada ("id_configuracion_norma", "id_configuracion_examen", "numero_norma", "descripcion_norma") VALUES (4, 6, 1, 'Responder únicamente con bolígrafo de tinta negra o azul.');

INSERT INTO public.configuracion_norma_personalizada ("id_configuracion_norma", "id_configuracion_examen", "numero_norma", "descripcion_norma") VALUES (5, 6, 2, 'Desactivar y guardar teléfonos móviles en la mochila.');

INSERT INTO public.configuracion_norma_personalizada ("id_configuracion_norma", "id_configuracion_examen", "numero_norma", "descripcion_norma") VALUES (6, 8, 1, 'Se permite el uso de calculadora científica no programable.');

INSERT INTO public.configuracion_norma_personalizada ("id_configuracion_norma", "id_configuracion_examen", "numero_norma", "descripcion_norma") VALUES (7, 8, 2, 'No se permite hojas adicionales, usar el reverso del examen.');

INSERT INTO public.configuracion_norma_personalizada ("id_configuracion_norma", "id_configuracion_examen", "numero_norma", "descripcion_norma") VALUES (8, 9, 1, 'dadad');

INSERT INTO public.configuracion_norma_personalizada ("id_configuracion_norma", "id_configuracion_examen", "numero_norma", "descripcion_norma") VALUES (9, 11, 1, 'Uso obligatorio de bata de laboratorio y gafas de seguridad.');

INSERT INTO public.configuracion_norma_personalizada ("id_configuracion_norma", "id_configuracion_examen", "numero_norma", "descripcion_norma") VALUES (10, 11, 2, 'Entregar el reporte de datos antes de salir del aula.');

INSERT INTO public.configuracion_norma_personalizada ("id_configuracion_norma", "id_configuracion_examen", "numero_norma", "descripcion_norma") VALUES (11, 12, 1, 'Ingreso sin mochilas');

INSERT INTO public.configuracion_norma_personalizada ("id_configuracion_norma", "id_configuracion_examen", "numero_norma", "descripcion_norma") VALUES (12, 14, 1, 'Ingreso sin mochilas');

INSERT INTO public.configuracion_norma_personalizada ("id_configuracion_norma", "id_configuracion_examen", "numero_norma", "descripcion_norma") VALUES (13, 15, 1, 'Tiempo estricto de 45 minutos. No hay prórroga.');

INSERT INTO public.configuracion_norma_personalizada ("id_configuracion_norma", "id_configuracion_examen", "numero_norma", "descripcion_norma") VALUES (14, 18, 1, 'Uso obligatorio de bata de laboratorio y gafas de seguridad.');

INSERT INTO public.configuracion_norma_personalizada ("id_configuracion_norma", "id_configuracion_examen", "numero_norma", "descripcion_norma") VALUES (15, 18, 2, 'Entregar el reporte de datos antes de salir del aula.');

INSERT INTO public.configuracion_norma_personalizada ("id_configuracion_norma", "id_configuracion_examen", "numero_norma", "descripcion_norma") VALUES (16, 20, 1, 'El código debe compilar sin errores para ser evaluado.');

INSERT INTO public.configuracion_norma_personalizada ("id_configuracion_norma", "id_configuracion_examen", "numero_norma", "descripcion_norma") VALUES (17, 20, 2, 'Prohibido el acceso a internet o repositorios externos.');

INSERT INTO public.configuracion_norma_personalizada ("id_configuracion_norma", "id_configuracion_examen", "numero_norma", "descripcion_norma") VALUES (18, 21, 1, 'Tiempo estricto de 45 minutos. No hay prórroga.');

INSERT INTO public.configuracion_norma_personalizada ("id_configuracion_norma", "id_configuracion_examen", "numero_norma", "descripcion_norma") VALUES (20, 42, 1, 'adsasdsa');

INSERT INTO public.configuracion_norma_personalizada ("id_configuracion_norma", "id_configuracion_examen", "numero_norma", "descripcion_norma") VALUES (21, 44, 1, 'Se permite el uso de calculadora científica no programable.');

INSERT INTO public.configuracion_norma_personalizada ("id_configuracion_norma", "id_configuracion_examen", "numero_norma", "descripcion_norma") VALUES (22, 44, 2, 'No se permite hojas adicionales, usar el reverso del examen.');


INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (199, '202002107', 17, 'habilitado', NULL, NULL, NULL, 29, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (200, '20210003', 17, 'habilitado', NULL, NULL, NULL, 29, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (201, '202101022', 17, 'habilitado', NULL, NULL, NULL, 29, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (202, '202101078', 17, 'habilitado', NULL, NULL, NULL, 29, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (204, '202101112', 17, 'habilitado', NULL, NULL, NULL, 29, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (205, '202101134', 17, 'habilitado', NULL, NULL, NULL, 29, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (206, '202101156', 17, 'habilitado', NULL, NULL, NULL, 29, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (207, '202101178', 17, 'habilitado', NULL, NULL, NULL, 29, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (208, '202101190', 17, 'habilitado', NULL, NULL, NULL, 29, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (209, '202101223', 17, 'habilitado', NULL, NULL, NULL, 29, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (210, '202101245', 17, 'habilitado', NULL, NULL, NULL, 29, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (211, '202101267', 17, 'habilitado', NULL, NULL, NULL, 29, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (212, '202101289', 17, 'habilitado', NULL, NULL, NULL, 29, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (213, '202101301', 17, 'habilitado', NULL, NULL, NULL, 29, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (214, '202101323', 17, 'habilitado', NULL, NULL, NULL, 29, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (215, '202101345', 17, 'habilitado', NULL, NULL, NULL, 29, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (216, '202101367', 17, 'habilitado', NULL, NULL, NULL, 29, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (217, '202101389', 17, 'habilitado', NULL, NULL, NULL, 29, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (218, '202101401', 17, 'habilitado', NULL, NULL, NULL, 29, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (219, '202101423', 17, 'habilitado', NULL, NULL, NULL, 29, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (220, '202101445', 17, 'habilitado', NULL, NULL, NULL, 29, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (221, '202101467', 17, 'habilitado', NULL, NULL, NULL, 29, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (222, '202101489', 17, 'habilitado', NULL, NULL, NULL, 29, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (224, '202201031', 17, 'habilitado', NULL, NULL, NULL, 29, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (225, '202201056', 17, 'habilitado', NULL, NULL, NULL, 29, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (226, '202201089', 17, 'habilitado', NULL, NULL, NULL, 29, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (227, '202201101', 17, 'habilitado', NULL, NULL, NULL, 29, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (228, '202201123', 17, 'habilitado', NULL, NULL, NULL, 29, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (229, '202201145', 17, 'habilitado', NULL, NULL, NULL, 29, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (230, '202201167', 17, 'habilitado', NULL, NULL, NULL, 29, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (231, '202201189', 17, 'habilitado', NULL, NULL, NULL, 29, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (232, '202201201', 17, 'habilitado', NULL, NULL, NULL, 29, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (233, '202201212', 17, 'habilitado', NULL, NULL, NULL, 29, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (234, '202201234', 17, 'habilitado', NULL, NULL, NULL, 29, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (235, '202201256', 17, 'habilitado', NULL, NULL, NULL, 29, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (236, '202201278', 17, 'habilitado', NULL, NULL, NULL, 29, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (237, '202201290', 17, 'habilitado', NULL, NULL, NULL, 29, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (238, '202201312', 17, 'habilitado', NULL, NULL, NULL, 29, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (239, '202201334', 17, 'habilitado', NULL, NULL, NULL, 29, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (240, '202201356', 17, 'habilitado', NULL, NULL, NULL, 29, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (241, '202201378', 17, 'habilitado', NULL, NULL, NULL, 29, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (242, '202201390', 17, 'habilitado', NULL, NULL, NULL, 29, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (243, '202201412', 17, 'habilitado', NULL, NULL, NULL, 29, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (244, '202201434', 17, 'habilitado', NULL, NULL, NULL, 29, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (246, '202201478', 17, 'habilitado', NULL, NULL, NULL, 29, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (247, '202201490', 17, 'habilitado', NULL, NULL, NULL, 29, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (248, '202202045', 17, 'habilitado', NULL, NULL, NULL, 29, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (249, '202002107', 18, 'habilitado', NULL, NULL, NULL, 30, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (250, '20210003', 18, 'habilitado', NULL, NULL, NULL, 30, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (251, '202101022', 18, 'habilitado', NULL, NULL, NULL, 30, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (252, '202101078', 18, 'habilitado', NULL, NULL, NULL, 30, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (254, '202101112', 18, 'habilitado', NULL, NULL, NULL, 30, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (255, '202101134', 18, 'habilitado', NULL, NULL, NULL, 30, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (256, '202101156', 18, 'habilitado', NULL, NULL, NULL, 30, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (257, '202101178', 18, 'habilitado', NULL, NULL, NULL, 30, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (258, '202101190', 18, 'habilitado', NULL, NULL, NULL, 30, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (259, '202101223', 18, 'habilitado', NULL, NULL, NULL, 30, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (260, '202101245', 18, 'habilitado', NULL, NULL, NULL, 30, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (261, '202101267', 18, 'habilitado', NULL, NULL, NULL, 30, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (262, '202101289', 18, 'habilitado', NULL, NULL, NULL, 30, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (263, '202101301', 18, 'habilitado', NULL, NULL, NULL, 30, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (264, '202101323', 18, 'habilitado', NULL, NULL, NULL, 30, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (265, '202101345', 18, 'habilitado', NULL, NULL, NULL, 30, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (266, '202101367', 18, 'habilitado', NULL, NULL, NULL, 30, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (267, '202101389', 18, 'habilitado', NULL, NULL, NULL, 30, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (268, '202101401', 18, 'habilitado', NULL, NULL, NULL, 30, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (269, '202101423', 18, 'habilitado', NULL, NULL, NULL, 30, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (270, '202101445', 18, 'habilitado', NULL, NULL, NULL, 30, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (271, '202101467', 18, 'habilitado', NULL, NULL, NULL, 30, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (272, '202101489', 18, 'habilitado', NULL, NULL, NULL, 30, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (273, '202201013', 18, 'habilitado', NULL, NULL, NULL, 30, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (274, '202201031', 18, 'habilitado', NULL, NULL, NULL, 30, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (275, '202201056', 18, 'habilitado', NULL, NULL, NULL, 30, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (276, '202201089', 18, 'habilitado', NULL, NULL, NULL, 30, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (277, '202201101', 18, 'habilitado', NULL, NULL, NULL, 30, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (203, '202101090', 17, 'deshabilitado', 'Rechazado en el control de ingreso', 1, '2026-10-27 07:01:32', 29, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (253, '202101090', 18, 'deshabilitado', 'Rechazado en el control de ingreso', 1, '2026-10-27 07:01:33', 30, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (109, '202101090', 5, 'deshabilitado', 'Rechazado en el control de ingreso', 1, '2026-10-27 07:01:33', 6, NULL);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (245, '202201456', 17, 'deshabilitado', 'Rechazado en el control de ingreso', 1, '2026-10-27 07:01:40', 29, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (107, '202101078', 5, 'deshabilitado', NULL, NULL, NULL, 6, NULL);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (108, '202201089', 5, 'habilitado', NULL, NULL, NULL, 6, NULL);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (110, '202201101', 5, 'habilitado', NULL, NULL, NULL, 6, NULL);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (111, '202101112', 5, 'habilitado', NULL, NULL, NULL, 6, NULL);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (112, '202201123', 5, 'habilitado', NULL, NULL, NULL, 6, NULL);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (113, '202101134', 5, 'deshabilitado', NULL, NULL, NULL, 6, NULL);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (1, '20210001', 1, 'habilitado', NULL, NULL, NULL, 1, 1);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (2, '20210002', 2, 'habilitado', NULL, NULL, NULL, 20, 2);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (3, '20210003', 3, 'habilitado', NULL, NULL, NULL, 21, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (4, '20210004', 4, 'deshabilitado', NULL, NULL, NULL, 11, 4);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (5, '20210005', 5, 'habilitado', NULL, NULL, NULL, 6, 5);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (101, '202201013', 5, 'habilitado', NULL, NULL, NULL, 6, NULL);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (102, '202101022', 5, 'habilitado', NULL, NULL, NULL, 6, NULL);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (103, '202201031', 5, 'habilitado', NULL, NULL, NULL, 6, NULL);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (104, '202202045', 5, 'deshabilitado', NULL, NULL, NULL, 6, NULL);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (105, '202002107', 5, 'habilitado', NULL, NULL, NULL, 6, NULL);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (106, '202201056', 5, 'habilitado', NULL, NULL, NULL, 6, NULL);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (114, '202201145', 5, 'habilitado', NULL, NULL, NULL, 6, NULL);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (115, '202101156', 5, 'habilitado', NULL, NULL, NULL, 6, NULL);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (116, '202201167', 5, 'habilitado', NULL, NULL, NULL, 6, NULL);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (117, '202101178', 5, 'habilitado', NULL, NULL, NULL, 6, NULL);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (118, '202201189', 5, 'habilitado', NULL, NULL, NULL, 6, NULL);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (119, '202101190', 5, 'deshabilitado', NULL, NULL, NULL, 6, NULL);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (120, '202201201', 5, 'habilitado', NULL, NULL, NULL, 6, NULL);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (121, '202201212', 5, 'habilitado', NULL, NULL, NULL, 6, NULL);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (122, '202101223', 5, 'habilitado', NULL, NULL, NULL, 6, NULL);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (123, '202201234', 5, 'habilitado', NULL, NULL, NULL, 6, NULL);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (124, '202101245', 5, 'habilitado', NULL, NULL, NULL, 6, NULL);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (125, '202201256', 5, 'habilitado', NULL, NULL, NULL, 6, NULL);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (278, '202201123', 18, 'habilitado', NULL, NULL, NULL, 30, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (279, '202201145', 18, 'habilitado', NULL, NULL, NULL, 30, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (280, '202201167', 18, 'habilitado', NULL, NULL, NULL, 30, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (281, '202201189', 18, 'habilitado', NULL, NULL, NULL, 30, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (282, '202201201', 18, 'habilitado', NULL, NULL, NULL, 30, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (283, '202201212', 18, 'habilitado', NULL, NULL, NULL, 30, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (284, '202201234', 18, 'habilitado', NULL, NULL, NULL, 30, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (285, '202201256', 18, 'habilitado', NULL, NULL, NULL, 30, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (286, '202201278', 18, 'habilitado', NULL, NULL, NULL, 30, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (287, '202201290', 18, 'habilitado', NULL, NULL, NULL, 30, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (288, '202201312', 18, 'habilitado', NULL, NULL, NULL, 30, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (289, '202201334', 18, 'habilitado', NULL, NULL, NULL, 30, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (290, '202201356', 18, 'habilitado', NULL, NULL, NULL, 30, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (291, '202201378', 18, 'habilitado', NULL, NULL, NULL, 30, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (292, '202201390', 18, 'habilitado', NULL, NULL, NULL, 30, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (293, '202201412', 18, 'habilitado', NULL, NULL, NULL, 30, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (294, '202201434', 18, 'habilitado', NULL, NULL, NULL, 30, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (296, '202201478', 18, 'habilitado', NULL, NULL, NULL, 30, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (297, '202201490', 18, 'habilitado', NULL, NULL, NULL, 30, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (126, '202101267', 5, 'habilitado', NULL, NULL, NULL, 6, NULL);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (127, '202201278', 5, 'habilitado', NULL, NULL, NULL, 6, NULL);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (128, '202101289', 5, 'habilitado', NULL, NULL, NULL, 6, NULL);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (129, '202201290', 5, 'habilitado', NULL, NULL, NULL, 6, NULL);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (130, '202101301', 5, 'habilitado', NULL, NULL, NULL, 6, NULL);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (131, '202201312', 5, 'habilitado', NULL, NULL, NULL, 6, NULL);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (132, '202101323', 5, 'habilitado', NULL, NULL, NULL, 6, NULL);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (133, '202201334', 5, 'habilitado', NULL, NULL, NULL, 6, NULL);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (134, '202101345', 5, 'habilitado', NULL, NULL, NULL, 6, NULL);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (135, '202201356', 5, 'habilitado', NULL, NULL, NULL, 6, NULL);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (136, '202101367', 5, 'habilitado', NULL, NULL, NULL, 6, NULL);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (137, '202201378', 5, 'habilitado', NULL, NULL, NULL, 6, NULL);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (138, '202101389', 5, 'habilitado', NULL, NULL, NULL, 6, NULL);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (139, '202201390', 5, 'habilitado', NULL, NULL, NULL, 6, NULL);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (140, '202101401', 5, 'habilitado', NULL, NULL, NULL, 6, NULL);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (141, '202201412', 5, 'habilitado', NULL, NULL, NULL, 6, NULL);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (142, '202101423', 5, 'habilitado', NULL, NULL, NULL, 6, NULL);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (143, '202201434', 5, 'habilitado', NULL, NULL, NULL, 6, NULL);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (144, '202101445', 5, 'habilitado', NULL, NULL, NULL, 6, NULL);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (146, '202101467', 5, 'habilitado', NULL, NULL, NULL, 6, NULL);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (147, '202201478', 5, 'habilitado', NULL, NULL, NULL, 6, NULL);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (148, '202101489', 5, 'habilitado', NULL, NULL, NULL, 6, NULL);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (149, '202201490', 5, 'habilitado', NULL, NULL, NULL, 6, NULL);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (150, '202201013', 14, 'habilitado', NULL, 1, '2026-10-12 21:31:00', 4, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (151, '202101022', 14, 'deshabilitado', 'no hizo su tarea', 1, '2026-10-12 03:31:43', 4, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (152, '202201031', 14, 'deshabilitado', 'Rechazado en el control de ingreso', 1, '2026-10-11 02:15:11', 4, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (153, '202202045', 14, 'habilitado', NULL, NULL, NULL, 4, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (154, '202002107', 14, 'habilitado', NULL, NULL, NULL, 4, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (155, '202201056', 14, 'deshabilitado', 'Rechazado en el control de ingreso', 1, '2026-10-12 02:39:11', 4, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (156, '202101078', 14, 'deshabilitado', 'Rechazado en el control de ingreso', 2, '2026-10-24 18:21:31', 4, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (157, '202201089', 14, 'habilitado', NULL, NULL, NULL, 4, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (159, '202201101', 14, 'habilitado', NULL, NULL, NULL, 4, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (160, '202101112', 14, 'deshabilitado', 'Deshabilitado para este examen', 3, '2026-10-10 08:00:00', 4, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (161, '202201123', 14, 'deshabilitado', 'Rechazado en el control de ingreso', 1, '2026-10-12 16:32:52', 4, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (162, '202101134', 14, 'habilitado', NULL, NULL, NULL, 4, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (163, '202201145', 14, 'habilitado', NULL, NULL, NULL, 4, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (164, '202101156', 14, 'habilitado', NULL, NULL, NULL, 4, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (165, '202201167', 14, 'deshabilitado', 'Rechazado en el control de ingreso', 1, '2026-10-10 22:24:13', 4, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (166, '202101178', 14, 'habilitado', NULL, NULL, NULL, 4, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (167, '202201189', 14, 'deshabilitado', 'Rechazado en el control de ingreso', 1, '2026-10-10 22:23:39', 4, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (168, '202101190', 14, 'habilitado', NULL, NULL, NULL, 4, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (169, '202201201', 14, 'habilitado', NULL, NULL, NULL, 4, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (170, '202201212', 14, 'habilitado', NULL, NULL, NULL, 4, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (171, '202101223', 14, 'habilitado', NULL, NULL, NULL, 4, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (172, '202201234', 14, 'deshabilitado', 'Deshabilitado para este examen', 3, '2026-10-10 08:00:00', 4, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (173, '202101245', 14, 'habilitado', NULL, NULL, NULL, 4, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (174, '202201256', 14, 'habilitado', NULL, 1, '2026-10-12 21:32:58', 4, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (175, '202101267', 14, 'habilitado', NULL, 1, '2026-10-17 20:43:58', 4, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (176, '202201278', 14, 'habilitado', NULL, NULL, NULL, 4, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (177, '202101289', 14, 'habilitado', NULL, NULL, NULL, 4, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (178, '202201290', 14, 'deshabilitado', 'Deshabilitado para este examen', 3, '2026-10-10 08:00:00', 4, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (179, '202101301', 14, 'habilitado', NULL, NULL, NULL, 4, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (180, '202201312', 14, 'habilitado', NULL, NULL, NULL, 4, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (181, '202101323', 14, 'habilitado', NULL, NULL, NULL, 4, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (182, '202201334', 14, 'deshabilitado', 'Deshabilitado para este examen', 3, '2026-10-10 08:00:00', 4, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (183, '202101345', 14, 'habilitado', NULL, NULL, NULL, 4, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (184, '202201356', 14, 'habilitado', NULL, NULL, NULL, 4, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (185, '202101367', 14, 'habilitado', NULL, NULL, NULL, 4, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (186, '202201378', 14, 'deshabilitado', 'Deshabilitado para este examen', 3, '2026-10-10 08:00:00', 4, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (187, '202101389', 14, 'habilitado', NULL, NULL, NULL, 4, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (188, '202201390', 14, 'habilitado', NULL, NULL, NULL, 4, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (189, '202101401', 14, 'habilitado', NULL, NULL, NULL, 4, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (190, '202201412', 14, 'habilitado', NULL, NULL, NULL, 4, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (191, '202101423', 14, 'habilitado', NULL, NULL, NULL, 4, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (192, '202201434', 14, 'habilitado', NULL, NULL, NULL, 4, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (193, '202101445', 14, 'habilitado', NULL, NULL, NULL, 4, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (195, '202101467', 14, 'habilitado', NULL, NULL, NULL, 4, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (196, '202201478', 14, 'habilitado', NULL, NULL, NULL, 4, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (197, '202101489', 14, 'habilitado', NULL, NULL, NULL, 4, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (198, '202201490', 14, 'deshabilitado', 'Rechazado en el control de ingreso', 1, '2026-10-11 02:19:14', 4, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (298, '202202045', 18, 'habilitado', NULL, NULL, NULL, 30, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (299, '202002107', 19, 'habilitado', NULL, NULL, NULL, 31, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (300, '20210003', 19, 'habilitado', NULL, NULL, NULL, 31, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (301, '202101022', 19, 'habilitado', NULL, NULL, NULL, 31, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (302, '202101078', 19, 'habilitado', NULL, NULL, NULL, 31, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (304, '202101112', 19, 'habilitado', NULL, NULL, NULL, 31, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (305, '202101134', 19, 'habilitado', NULL, NULL, NULL, 31, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (306, '202101156', 19, 'habilitado', NULL, NULL, NULL, 31, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (307, '202101178', 19, 'habilitado', NULL, NULL, NULL, 31, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (308, '202101190', 19, 'habilitado', NULL, NULL, NULL, 31, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (309, '202101223', 19, 'habilitado', NULL, NULL, NULL, 31, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (310, '202101245', 19, 'habilitado', NULL, NULL, NULL, 31, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (311, '202101267', 19, 'habilitado', NULL, NULL, NULL, 31, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (312, '202101289', 19, 'habilitado', NULL, NULL, NULL, 31, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (303, '202101090', 19, 'deshabilitado', 'Rechazado en el control de ingreso', 1, '2026-10-27 07:01:35', 31, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (295, '202201456', 18, 'deshabilitado', 'Rechazado en el control de ingreso', 1, '2026-10-27 07:01:41', 30, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (145, '202201456', 5, 'deshabilitado', 'Rechazado en el control de ingreso', 1, '2026-10-27 07:01:42', 6, NULL);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (194, '202201456', 14, 'deshabilitado', 'Rechazado en el control de ingreso', 1, '2026-10-27 07:01:42', 4, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (313, '202101301', 19, 'habilitado', NULL, NULL, NULL, 31, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (314, '202101323', 19, 'habilitado', NULL, NULL, NULL, 31, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (315, '202101345', 19, 'habilitado', NULL, NULL, NULL, 31, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (316, '202101367', 19, 'habilitado', NULL, NULL, NULL, 31, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (317, '202101389', 19, 'habilitado', NULL, NULL, NULL, 31, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (318, '202101401', 19, 'habilitado', NULL, NULL, NULL, 31, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (319, '202101423', 19, 'habilitado', NULL, NULL, NULL, 31, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (320, '202101445', 19, 'habilitado', NULL, NULL, NULL, 31, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (321, '202101467', 19, 'habilitado', NULL, NULL, NULL, 31, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (322, '202101489', 19, 'habilitado', NULL, NULL, NULL, 31, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (323, '202201013', 19, 'habilitado', NULL, NULL, NULL, 31, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (324, '202201031', 19, 'habilitado', NULL, NULL, NULL, 31, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (325, '202201056', 19, 'habilitado', NULL, NULL, NULL, 31, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (326, '202201089', 19, 'habilitado', NULL, NULL, NULL, 31, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (327, '202201101', 19, 'habilitado', NULL, NULL, NULL, 31, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (328, '202201123', 19, 'habilitado', NULL, NULL, NULL, 31, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (329, '202201145', 19, 'habilitado', NULL, NULL, NULL, 31, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (330, '202201167', 19, 'habilitado', NULL, NULL, NULL, 31, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (331, '202201189', 19, 'habilitado', NULL, NULL, NULL, 31, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (332, '202201201', 19, 'habilitado', NULL, NULL, NULL, 31, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (333, '202201212', 19, 'habilitado', NULL, NULL, NULL, 31, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (334, '202201234', 19, 'habilitado', NULL, NULL, NULL, 31, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (335, '202201256', 19, 'habilitado', NULL, NULL, NULL, 31, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (336, '202201278', 19, 'habilitado', NULL, NULL, NULL, 31, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (337, '202201290', 19, 'habilitado', NULL, NULL, NULL, 31, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (338, '202201312', 19, 'habilitado', NULL, NULL, NULL, 31, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (339, '202201334', 19, 'habilitado', NULL, NULL, NULL, 31, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (340, '202201356', 19, 'habilitado', NULL, NULL, NULL, 31, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (341, '202201378', 19, 'habilitado', NULL, NULL, NULL, 31, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (342, '202201390', 19, 'habilitado', NULL, NULL, NULL, 31, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (343, '202201412', 19, 'habilitado', NULL, NULL, NULL, 31, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (344, '202201434', 19, 'habilitado', NULL, NULL, NULL, 31, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (346, '202201478', 19, 'habilitado', NULL, NULL, NULL, 31, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (347, '202201490', 19, 'habilitado', NULL, NULL, NULL, 31, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (348, '202202045', 19, 'habilitado', NULL, NULL, NULL, 31, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (349, '202002107', 20, 'habilitado', NULL, NULL, NULL, 32, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (350, '20210003', 20, 'habilitado', NULL, NULL, NULL, 32, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (351, '202101022', 20, 'habilitado', NULL, NULL, NULL, 32, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (352, '202101078', 20, 'habilitado', NULL, NULL, NULL, 32, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (354, '202101112', 20, 'habilitado', NULL, NULL, NULL, 32, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (355, '202101134', 20, 'habilitado', NULL, NULL, NULL, 32, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (356, '202101156', 20, 'habilitado', NULL, NULL, NULL, 32, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (357, '202101178', 20, 'habilitado', NULL, NULL, NULL, 32, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (358, '202101190', 20, 'habilitado', NULL, NULL, NULL, 32, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (359, '202101223', 20, 'habilitado', NULL, NULL, NULL, 32, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (360, '202101245', 20, 'habilitado', NULL, NULL, NULL, 32, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (361, '202101267', 20, 'habilitado', NULL, NULL, NULL, 32, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (362, '202101289', 20, 'habilitado', NULL, NULL, NULL, 32, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (363, '202101301', 20, 'habilitado', NULL, NULL, NULL, 32, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (364, '202101323', 20, 'habilitado', NULL, NULL, NULL, 32, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (365, '202101345', 20, 'habilitado', NULL, NULL, NULL, 32, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (366, '202101367', 20, 'habilitado', NULL, NULL, NULL, 32, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (367, '202101389', 20, 'habilitado', NULL, NULL, NULL, 32, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (368, '202101401', 20, 'habilitado', NULL, NULL, NULL, 32, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (369, '202101423', 20, 'habilitado', NULL, NULL, NULL, 32, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (370, '202101445', 20, 'habilitado', NULL, NULL, NULL, 32, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (371, '202101467', 20, 'habilitado', NULL, NULL, NULL, 32, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (372, '202101489', 20, 'habilitado', NULL, NULL, NULL, 32, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (373, '202201013', 20, 'habilitado', NULL, NULL, NULL, 32, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (374, '202201031', 20, 'habilitado', NULL, NULL, NULL, 32, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (375, '202201056', 20, 'habilitado', NULL, NULL, NULL, 32, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (376, '202201089', 20, 'habilitado', NULL, NULL, NULL, 32, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (377, '202201101', 20, 'habilitado', NULL, NULL, NULL, 32, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (378, '202201123', 20, 'habilitado', NULL, NULL, NULL, 32, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (379, '202201145', 20, 'habilitado', NULL, NULL, NULL, 32, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (380, '202201167', 20, 'habilitado', NULL, NULL, NULL, 32, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (381, '202201189', 20, 'habilitado', NULL, NULL, NULL, 32, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (382, '202201201', 20, 'habilitado', NULL, NULL, NULL, 32, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (383, '202201212', 20, 'habilitado', NULL, NULL, NULL, 32, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (384, '202201234', 20, 'habilitado', NULL, NULL, NULL, 32, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (385, '202201256', 20, 'habilitado', NULL, NULL, NULL, 32, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (386, '202201278', 20, 'habilitado', NULL, NULL, NULL, 32, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (387, '202201290', 20, 'habilitado', NULL, NULL, NULL, 32, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (388, '202201312', 20, 'habilitado', NULL, NULL, NULL, 32, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (389, '202201334', 20, 'habilitado', NULL, NULL, NULL, 32, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (390, '202201356', 20, 'habilitado', NULL, NULL, NULL, 32, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (391, '202201378', 20, 'habilitado', NULL, NULL, NULL, 32, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (392, '202201390', 20, 'habilitado', NULL, NULL, NULL, 32, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (393, '202201412', 20, 'habilitado', NULL, NULL, NULL, 32, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (394, '202201434', 20, 'habilitado', NULL, NULL, NULL, 32, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (396, '202201478', 20, 'habilitado', NULL, NULL, NULL, 32, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (397, '202201490', 20, 'habilitado', NULL, NULL, NULL, 32, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (398, '202202045', 20, 'habilitado', NULL, NULL, NULL, 32, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (399, '202002107', 21, 'habilitado', NULL, NULL, NULL, 33, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (400, '20210003', 21, 'habilitado', NULL, NULL, NULL, 33, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (401, '202101022', 21, 'habilitado', NULL, NULL, NULL, 33, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (402, '202101078', 21, 'habilitado', NULL, NULL, NULL, 33, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (404, '202101112', 21, 'habilitado', NULL, NULL, NULL, 33, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (405, '202101134', 21, 'habilitado', NULL, NULL, NULL, 33, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (406, '202101156', 21, 'habilitado', NULL, NULL, NULL, 33, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (407, '202101178', 21, 'habilitado', NULL, NULL, NULL, 33, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (408, '202101190', 21, 'habilitado', NULL, NULL, NULL, 33, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (409, '202101223', 21, 'habilitado', NULL, NULL, NULL, 33, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (410, '202101245', 21, 'habilitado', NULL, NULL, NULL, 33, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (411, '202101267', 21, 'habilitado', NULL, NULL, NULL, 33, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (412, '202101289', 21, 'habilitado', NULL, NULL, NULL, 33, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (413, '202101301', 21, 'habilitado', NULL, NULL, NULL, 33, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (414, '202101323', 21, 'habilitado', NULL, NULL, NULL, 33, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (415, '202101345', 21, 'habilitado', NULL, NULL, NULL, 33, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (416, '202101367', 21, 'habilitado', NULL, NULL, NULL, 33, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (417, '202101389', 21, 'habilitado', NULL, NULL, NULL, 33, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (418, '202101401', 21, 'habilitado', NULL, NULL, NULL, 33, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (419, '202101423', 21, 'habilitado', NULL, NULL, NULL, 33, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (420, '202101445', 21, 'habilitado', NULL, NULL, NULL, 33, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (421, '202101467', 21, 'habilitado', NULL, NULL, NULL, 33, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (422, '202101489', 21, 'habilitado', NULL, NULL, NULL, 33, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (423, '202201013', 21, 'habilitado', NULL, NULL, NULL, 33, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (424, '202201031', 21, 'habilitado', NULL, NULL, NULL, 33, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (425, '202201056', 21, 'habilitado', NULL, NULL, NULL, 33, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (426, '202201089', 21, 'habilitado', NULL, NULL, NULL, 33, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (427, '202201101', 21, 'habilitado', NULL, NULL, NULL, 33, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (428, '202201123', 21, 'habilitado', NULL, NULL, NULL, 33, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (429, '202201145', 21, 'habilitado', NULL, NULL, NULL, 33, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (430, '202201167', 21, 'habilitado', NULL, NULL, NULL, 33, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (431, '202201189', 21, 'habilitado', NULL, NULL, NULL, 33, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (432, '202201201', 21, 'habilitado', NULL, NULL, NULL, 33, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (345, '202201456', 19, 'deshabilitado', 'Rechazado en el control de ingreso', 1, '2026-10-27 07:01:43', 31, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (433, '202201212', 21, 'habilitado', NULL, NULL, NULL, 33, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (434, '202201234', 21, 'habilitado', NULL, NULL, NULL, 33, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (435, '202201256', 21, 'habilitado', NULL, NULL, NULL, 33, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (436, '202201278', 21, 'habilitado', NULL, NULL, NULL, 33, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (437, '202201290', 21, 'habilitado', NULL, NULL, NULL, 33, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (438, '202201312', 21, 'habilitado', NULL, NULL, NULL, 33, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (439, '202201334', 21, 'habilitado', NULL, NULL, NULL, 33, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (440, '202201356', 21, 'habilitado', NULL, NULL, NULL, 33, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (441, '202201378', 21, 'habilitado', NULL, NULL, NULL, 33, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (442, '202201390', 21, 'habilitado', NULL, NULL, NULL, 33, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (443, '202201412', 21, 'habilitado', NULL, NULL, NULL, 33, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (444, '202201434', 21, 'habilitado', NULL, NULL, NULL, 33, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (446, '202201478', 21, 'habilitado', NULL, NULL, NULL, 33, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (447, '202201490', 21, 'habilitado', NULL, NULL, NULL, 33, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (448, '202202045', 21, 'habilitado', NULL, NULL, NULL, 33, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (449, '202002107', 22, 'habilitado', NULL, NULL, NULL, 35, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (450, '20210003', 22, 'habilitado', NULL, NULL, NULL, 35, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (451, '202101022', 22, 'habilitado', NULL, NULL, NULL, 35, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (452, '202101078', 22, 'habilitado', NULL, NULL, NULL, 35, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (454, '202101112', 22, 'habilitado', NULL, NULL, NULL, 35, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (455, '202101134', 22, 'habilitado', NULL, NULL, NULL, 35, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (456, '202101156', 22, 'habilitado', NULL, NULL, NULL, 35, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (457, '202101178', 22, 'habilitado', NULL, NULL, NULL, 35, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (458, '202101190', 22, 'habilitado', NULL, NULL, NULL, 35, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (459, '202101223', 22, 'habilitado', NULL, NULL, NULL, 35, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (460, '202101245', 22, 'habilitado', NULL, NULL, NULL, 35, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (461, '202101267', 22, 'habilitado', NULL, NULL, NULL, 35, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (462, '202101289', 22, 'habilitado', NULL, NULL, NULL, 35, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (463, '202101301', 22, 'habilitado', NULL, NULL, NULL, 35, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (464, '202101323', 22, 'habilitado', NULL, NULL, NULL, 35, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (465, '202101345', 22, 'habilitado', NULL, NULL, NULL, 35, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (466, '202101367', 22, 'habilitado', NULL, NULL, NULL, 35, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (467, '202101389', 22, 'habilitado', NULL, NULL, NULL, 35, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (468, '202101401', 22, 'habilitado', NULL, NULL, NULL, 35, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (469, '202101423', 22, 'habilitado', NULL, NULL, NULL, 35, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (470, '202101445', 22, 'habilitado', NULL, NULL, NULL, 35, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (471, '202101467', 22, 'habilitado', NULL, NULL, NULL, 35, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (472, '202101489', 22, 'habilitado', NULL, NULL, NULL, 35, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (473, '202201013', 22, 'habilitado', NULL, NULL, NULL, 35, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (474, '202201031', 22, 'habilitado', NULL, NULL, NULL, 35, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (475, '202201056', 22, 'habilitado', NULL, NULL, NULL, 35, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (476, '202201089', 22, 'habilitado', NULL, NULL, NULL, 35, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (477, '202201101', 22, 'habilitado', NULL, NULL, NULL, 35, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (478, '202201123', 22, 'habilitado', NULL, NULL, NULL, 35, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (479, '202201145', 22, 'habilitado', NULL, NULL, NULL, 35, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (480, '202201167', 22, 'habilitado', NULL, NULL, NULL, 35, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (481, '202201189', 22, 'habilitado', NULL, NULL, NULL, 35, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (482, '202201201', 22, 'habilitado', NULL, NULL, NULL, 35, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (483, '202201212', 22, 'habilitado', NULL, NULL, NULL, 35, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (484, '202201234', 22, 'habilitado', NULL, NULL, NULL, 35, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (485, '202201256', 22, 'habilitado', NULL, NULL, NULL, 35, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (486, '202201278', 22, 'habilitado', NULL, NULL, NULL, 35, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (487, '202201290', 22, 'habilitado', NULL, NULL, NULL, 35, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (488, '202201312', 22, 'habilitado', NULL, NULL, NULL, 35, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (489, '202201334', 22, 'habilitado', NULL, NULL, NULL, 35, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (490, '202201356', 22, 'habilitado', NULL, NULL, NULL, 35, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (491, '202201378', 22, 'habilitado', NULL, NULL, NULL, 35, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (492, '202201390', 22, 'habilitado', NULL, NULL, NULL, 35, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (493, '202201412', 22, 'habilitado', NULL, NULL, NULL, 35, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (494, '202201434', 22, 'habilitado', NULL, NULL, NULL, 35, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (496, '202201478', 22, 'habilitado', NULL, NULL, NULL, 35, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (497, '202201490', 22, 'habilitado', NULL, NULL, NULL, 35, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (498, '202202045', 22, 'habilitado', NULL, NULL, NULL, 35, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (499, '202002107', 23, 'habilitado', NULL, NULL, NULL, 36, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (500, '20210003', 23, 'habilitado', NULL, NULL, NULL, 36, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (501, '202101022', 23, 'habilitado', NULL, NULL, NULL, 36, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (502, '202101078', 23, 'habilitado', NULL, NULL, NULL, 36, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (445, '202201456', 21, 'deshabilitado', 'Rechazado en el control de ingreso', 1, '2026-10-27 07:01:44', 33, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (495, '202201456', 22, 'deshabilitado', 'Rechazado en el control de ingreso', 1, '2026-10-27 07:01:44', 35, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (504, '202101112', 23, 'habilitado', NULL, NULL, NULL, 36, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (505, '202101134', 23, 'habilitado', NULL, NULL, NULL, 36, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (506, '202101156', 23, 'habilitado', NULL, NULL, NULL, 36, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (508, '202101190', 23, 'habilitado', NULL, NULL, NULL, 36, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (509, '202101223', 23, 'habilitado', NULL, NULL, NULL, 36, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (510, '202101245', 23, 'habilitado', NULL, NULL, NULL, 36, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (511, '202101267', 23, 'habilitado', NULL, NULL, NULL, 36, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (512, '202101289', 23, 'habilitado', NULL, NULL, NULL, 36, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (513, '202101301', 23, 'habilitado', NULL, NULL, NULL, 36, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (514, '202101323', 23, 'habilitado', NULL, NULL, NULL, 36, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (515, '202101345', 23, 'habilitado', NULL, NULL, NULL, 36, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (516, '202101367', 23, 'habilitado', NULL, NULL, NULL, 36, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (517, '202101389', 23, 'habilitado', NULL, NULL, NULL, 36, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (518, '202101401', 23, 'habilitado', NULL, NULL, NULL, 36, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (519, '202101423', 23, 'habilitado', NULL, NULL, NULL, 36, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (520, '202101445', 23, 'habilitado', NULL, NULL, NULL, 36, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (521, '202101467', 23, 'habilitado', NULL, NULL, NULL, 36, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (522, '202101489', 23, 'habilitado', NULL, NULL, NULL, 36, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (523, '202201013', 23, 'habilitado', NULL, NULL, NULL, 36, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (524, '202201031', 23, 'habilitado', NULL, NULL, NULL, 36, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (525, '202201056', 23, 'habilitado', NULL, NULL, NULL, 36, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (526, '202201089', 23, 'habilitado', NULL, NULL, NULL, 36, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (527, '202201101', 23, 'habilitado', NULL, NULL, NULL, 36, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (528, '202201123', 23, 'habilitado', NULL, NULL, NULL, 36, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (529, '202201145', 23, 'habilitado', NULL, NULL, NULL, 36, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (530, '202201167', 23, 'habilitado', NULL, NULL, NULL, 36, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (531, '202201189', 23, 'habilitado', NULL, NULL, NULL, 36, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (532, '202201201', 23, 'habilitado', NULL, NULL, NULL, 36, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (533, '202201212', 23, 'habilitado', NULL, NULL, NULL, 36, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (534, '202201234', 23, 'habilitado', NULL, NULL, NULL, 36, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (535, '202201256', 23, 'habilitado', NULL, NULL, NULL, 36, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (536, '202201278', 23, 'habilitado', NULL, NULL, NULL, 36, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (537, '202201290', 23, 'habilitado', NULL, NULL, NULL, 36, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (538, '202201312', 23, 'habilitado', NULL, NULL, NULL, 36, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (539, '202201334', 23, 'habilitado', NULL, NULL, NULL, 36, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (540, '202201356', 23, 'habilitado', NULL, NULL, NULL, 36, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (541, '202201378', 23, 'habilitado', NULL, NULL, NULL, 36, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (542, '202201390', 23, 'habilitado', NULL, NULL, NULL, 36, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (543, '202201412', 23, 'habilitado', NULL, NULL, NULL, 36, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (544, '202201434', 23, 'habilitado', NULL, NULL, NULL, 36, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (546, '202201478', 23, 'habilitado', NULL, NULL, NULL, 36, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (547, '202201490', 23, 'habilitado', NULL, NULL, NULL, 36, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (548, '202202045', 23, 'habilitado', NULL, NULL, NULL, 36, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (223, '202201013', 17, 'deshabilitado', 'Rechazado en el control de ingreso', 1, '2026-10-25 18:14:00', 29, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (601, '202002107', 27, 'deshabilitado', NULL, NULL, NULL, 46, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (507, '202101178', 23, 'habilitado', NULL, 1, '2026-10-26 01:08:08', 36, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (549, '20210001', 24, 'habilitado', NULL, NULL, NULL, 41, 1);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (550, '202002107', 25, 'habilitado', NULL, NULL, NULL, 42, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (551, '20210003', 25, 'habilitado', NULL, NULL, NULL, 42, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (552, '202101022', 25, 'habilitado', NULL, NULL, NULL, 42, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (553, '202101078', 25, 'habilitado', NULL, NULL, NULL, 42, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (555, '202101112', 25, 'habilitado', NULL, NULL, NULL, 42, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (556, '202101134', 25, 'habilitado', NULL, NULL, NULL, 42, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (557, '202101156', 25, 'habilitado', NULL, NULL, NULL, 42, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (558, '202101178', 25, 'habilitado', NULL, NULL, NULL, 42, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (559, '202101190', 25, 'habilitado', NULL, NULL, NULL, 42, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (560, '202101223', 25, 'habilitado', NULL, NULL, NULL, 42, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (561, '202101245', 25, 'habilitado', NULL, NULL, NULL, 42, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (562, '202101267', 25, 'habilitado', NULL, NULL, NULL, 42, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (563, '202101289', 25, 'habilitado', NULL, NULL, NULL, 42, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (564, '202101301', 25, 'habilitado', NULL, NULL, NULL, 42, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (565, '202101323', 25, 'habilitado', NULL, NULL, NULL, 42, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (566, '202101345', 25, 'habilitado', NULL, NULL, NULL, 42, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (567, '202101367', 25, 'habilitado', NULL, NULL, NULL, 42, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (568, '202101389', 25, 'habilitado', NULL, NULL, NULL, 42, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (569, '202101401', 25, 'habilitado', NULL, NULL, NULL, 42, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (570, '202101423', 25, 'habilitado', NULL, NULL, NULL, 42, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (571, '202101445', 25, 'habilitado', NULL, NULL, NULL, 42, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (572, '202101467', 25, 'habilitado', NULL, NULL, NULL, 42, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (573, '202101489', 25, 'habilitado', NULL, NULL, NULL, 42, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (574, '202201013', 25, 'habilitado', NULL, NULL, NULL, 42, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (575, '202201031', 25, 'habilitado', NULL, NULL, NULL, 42, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (576, '202201056', 25, 'habilitado', NULL, NULL, NULL, 42, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (577, '202201089', 25, 'habilitado', NULL, NULL, NULL, 42, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (578, '202201101', 25, 'habilitado', NULL, NULL, NULL, 42, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (579, '202201123', 25, 'habilitado', NULL, NULL, NULL, 42, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (580, '202201145', 25, 'habilitado', NULL, NULL, NULL, 42, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (581, '202201167', 25, 'habilitado', NULL, NULL, NULL, 42, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (582, '202201189', 25, 'habilitado', NULL, NULL, NULL, 42, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (583, '202201201', 25, 'habilitado', NULL, NULL, NULL, 42, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (584, '202201212', 25, 'habilitado', NULL, NULL, NULL, 42, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (585, '202201234', 25, 'habilitado', NULL, NULL, NULL, 42, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (586, '202201256', 25, 'habilitado', NULL, NULL, NULL, 42, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (587, '202201278', 25, 'habilitado', NULL, NULL, NULL, 42, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (588, '202201290', 25, 'habilitado', NULL, NULL, NULL, 42, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (589, '202201312', 25, 'habilitado', NULL, NULL, NULL, 42, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (590, '202201334', 25, 'habilitado', NULL, NULL, NULL, 42, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (591, '202201356', 25, 'habilitado', NULL, NULL, NULL, 42, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (592, '202201378', 25, 'habilitado', NULL, NULL, NULL, 42, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (593, '202201390', 25, 'habilitado', NULL, NULL, NULL, 42, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (594, '202201412', 25, 'habilitado', NULL, NULL, NULL, 42, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (595, '202201434', 25, 'habilitado', NULL, NULL, NULL, 42, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (597, '202201478', 25, 'habilitado', NULL, NULL, NULL, 42, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (598, '202201490', 25, 'habilitado', NULL, NULL, NULL, 42, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (599, '202202045', 25, 'habilitado', NULL, NULL, NULL, 42, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (600, '20210001', 26, 'habilitado', NULL, NULL, NULL, 43, 1);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (602, '20210003', 27, 'habilitado', NULL, NULL, NULL, 46, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (603, '202101022', 27, 'habilitado', NULL, NULL, NULL, 46, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (604, '202101078', 27, 'habilitado', NULL, NULL, NULL, 46, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (606, '202101112', 27, 'habilitado', NULL, NULL, NULL, 46, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (607, '202101134', 27, 'habilitado', NULL, NULL, NULL, 46, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (609, '202101178', 27, 'habilitado', NULL, NULL, NULL, 46, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (610, '202101190', 27, 'habilitado', NULL, NULL, NULL, 46, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (611, '202101223', 27, 'habilitado', NULL, NULL, NULL, 46, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (612, '202101245', 27, 'habilitado', NULL, NULL, NULL, 46, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (545, '202201456', 23, 'deshabilitado', 'Rechazado en el control de ingreso', 1, '2026-10-27 07:01:45', 36, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (608, '202101156', 27, 'deshabilitado', NULL, NULL, NULL, 46, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (503, '202101090', 23, 'deshabilitado', 'Rechazado en el control de ingreso', 1, '2026-10-27 07:01:37', 36, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (554, '202101090', 25, 'deshabilitado', 'Rechazado en el control de ingreso', 1, '2026-10-27 07:01:37', 42, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (605, '202101090', 27, 'deshabilitado', 'Rechazado en el control de ingreso', 1, '2026-10-27 07:01:38', 46, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (596, '202201456', 25, 'deshabilitado', 'Rechazado en el control de ingreso', 1, '2026-10-27 07:01:45', 42, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (613, '202101267', 27, 'habilitado', NULL, NULL, NULL, 46, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (615, '202101301', 27, 'habilitado', NULL, NULL, NULL, 46, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (616, '202101323', 27, 'habilitado', NULL, NULL, NULL, 46, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (617, '202101345', 27, 'habilitado', NULL, NULL, NULL, 46, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (618, '202101367', 27, 'habilitado', NULL, NULL, NULL, 46, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (619, '202101389', 27, 'habilitado', NULL, NULL, NULL, 46, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (620, '202101401', 27, 'habilitado', NULL, NULL, NULL, 46, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (621, '202101423', 27, 'habilitado', NULL, NULL, NULL, 46, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (623, '202101467', 27, 'habilitado', NULL, NULL, NULL, 46, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (624, '202101489', 27, 'habilitado', NULL, NULL, NULL, 46, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (625, '202201013', 27, 'habilitado', NULL, NULL, NULL, 46, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (626, '202201031', 27, 'habilitado', NULL, NULL, NULL, 46, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (627, '202201056', 27, 'habilitado', NULL, NULL, NULL, 46, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (628, '202201089', 27, 'habilitado', NULL, NULL, NULL, 46, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (629, '202201101', 27, 'habilitado', NULL, NULL, NULL, 46, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (630, '202201123', 27, 'habilitado', NULL, NULL, NULL, 46, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (631, '202201145', 27, 'habilitado', NULL, NULL, NULL, 46, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (632, '202201167', 27, 'habilitado', NULL, NULL, NULL, 46, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (634, '202201201', 27, 'habilitado', NULL, NULL, NULL, 46, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (635, '202201212', 27, 'habilitado', NULL, NULL, NULL, 46, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (636, '202201234', 27, 'habilitado', NULL, NULL, NULL, 46, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (637, '202201256', 27, 'habilitado', NULL, NULL, NULL, 46, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (638, '202201278', 27, 'habilitado', NULL, NULL, NULL, 46, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (639, '202201290', 27, 'habilitado', NULL, NULL, NULL, 46, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (640, '202201312', 27, 'habilitado', NULL, NULL, NULL, 46, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (641, '202201334', 27, 'habilitado', NULL, NULL, NULL, 46, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (643, '202201378', 27, 'habilitado', NULL, NULL, NULL, 46, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (644, '202201390', 27, 'habilitado', NULL, NULL, NULL, 46, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (646, '202201434', 27, 'habilitado', NULL, NULL, NULL, 46, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (648, '202201478', 27, 'habilitado', NULL, NULL, NULL, 46, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (649, '202201490', 27, 'habilitado', NULL, NULL, NULL, 46, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (650, '202202045', 27, 'habilitado', NULL, NULL, NULL, 46, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (652, '202002107', 29, 'habilitado', NULL, NULL, NULL, 48, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (653, '20210003', 29, 'habilitado', NULL, NULL, NULL, 48, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (654, '202101022', 29, 'habilitado', NULL, NULL, NULL, 48, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (655, '202101078', 29, 'habilitado', NULL, NULL, NULL, 48, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (657, '202101112', 29, 'habilitado', NULL, NULL, NULL, 48, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (658, '202101134', 29, 'habilitado', NULL, NULL, NULL, 48, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (659, '202101156', 29, 'habilitado', NULL, NULL, NULL, 48, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (660, '202101178', 29, 'habilitado', NULL, NULL, NULL, 48, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (661, '202101190', 29, 'habilitado', NULL, NULL, NULL, 48, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (662, '202101223', 29, 'habilitado', NULL, NULL, NULL, 48, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (663, '202101245', 29, 'habilitado', NULL, NULL, NULL, 48, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (664, '202101267', 29, 'habilitado', NULL, NULL, NULL, 48, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (665, '202101289', 29, 'habilitado', NULL, NULL, NULL, 48, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (666, '202101301', 29, 'habilitado', NULL, NULL, NULL, 48, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (667, '202101323', 29, 'habilitado', NULL, NULL, NULL, 48, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (668, '202101345', 29, 'habilitado', NULL, NULL, NULL, 48, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (669, '202101367', 29, 'habilitado', NULL, NULL, NULL, 48, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (670, '202101389', 29, 'habilitado', NULL, NULL, NULL, 48, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (671, '202101401', 29, 'habilitado', NULL, NULL, NULL, 48, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (672, '202101423', 29, 'habilitado', NULL, NULL, NULL, 48, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (673, '202101445', 29, 'habilitado', NULL, NULL, NULL, 48, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (674, '202101467', 29, 'habilitado', NULL, NULL, NULL, 48, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (675, '202101489', 29, 'habilitado', NULL, NULL, NULL, 48, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (676, '202201013', 29, 'habilitado', NULL, NULL, NULL, 48, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (677, '202201031', 29, 'habilitado', NULL, NULL, NULL, 48, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (678, '202201056', 29, 'habilitado', NULL, NULL, NULL, 48, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (679, '202201089', 29, 'habilitado', NULL, NULL, NULL, 48, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (680, '202201101', 29, 'habilitado', NULL, NULL, NULL, 48, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (681, '202201123', 29, 'habilitado', NULL, NULL, NULL, 48, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (682, '202201145', 29, 'habilitado', NULL, NULL, NULL, 48, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (683, '202201167', 29, 'habilitado', NULL, NULL, NULL, 48, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (684, '202201189', 29, 'habilitado', NULL, NULL, NULL, 48, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (685, '202201201', 29, 'habilitado', NULL, NULL, NULL, 48, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (686, '202201212', 29, 'habilitado', NULL, NULL, NULL, 48, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (687, '202201234', 29, 'habilitado', NULL, NULL, NULL, 48, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (688, '202201256', 29, 'habilitado', NULL, NULL, NULL, 48, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (689, '202201278', 29, 'habilitado', NULL, NULL, NULL, 48, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (690, '202201290', 29, 'habilitado', NULL, NULL, NULL, 48, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (691, '202201312', 29, 'habilitado', NULL, NULL, NULL, 48, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (692, '202201334', 29, 'habilitado', NULL, NULL, NULL, 48, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (693, '202201356', 29, 'habilitado', NULL, NULL, NULL, 48, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (694, '202201378', 29, 'habilitado', NULL, NULL, NULL, 48, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (695, '202201390', 29, 'habilitado', NULL, NULL, NULL, 48, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (696, '202201412', 29, 'habilitado', NULL, NULL, NULL, 48, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (697, '202201434', 29, 'habilitado', NULL, NULL, NULL, 48, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (699, '202201478', 29, 'habilitado', NULL, NULL, NULL, 48, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (700, '202201490', 29, 'habilitado', NULL, NULL, NULL, 48, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (701, '202202045', 29, 'habilitado', NULL, NULL, NULL, 48, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (702, '20210004', 30, 'habilitado', NULL, NULL, NULL, 49, 4);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (703, '20210001', 31, 'habilitado', NULL, NULL, NULL, 50, 1);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (704, '202002107', 32, 'habilitado', NULL, NULL, NULL, 51, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (705, '20210003', 32, 'habilitado', NULL, NULL, NULL, 51, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (706, '202101022', 32, 'habilitado', NULL, NULL, NULL, 51, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (707, '202101078', 32, 'habilitado', NULL, NULL, NULL, 51, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (709, '202101112', 32, 'habilitado', NULL, NULL, NULL, 51, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (710, '202101134', 32, 'habilitado', NULL, NULL, NULL, 51, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (711, '202101156', 32, 'habilitado', NULL, NULL, NULL, 51, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (712, '202101178', 32, 'habilitado', NULL, NULL, NULL, 51, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (713, '202101190', 32, 'habilitado', NULL, NULL, NULL, 51, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (714, '202101223', 32, 'habilitado', NULL, NULL, NULL, 51, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (715, '202101245', 32, 'habilitado', NULL, NULL, NULL, 51, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (716, '202101267', 32, 'habilitado', NULL, NULL, NULL, 51, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (717, '202101289', 32, 'habilitado', NULL, NULL, NULL, 51, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (718, '202101301', 32, 'habilitado', NULL, NULL, NULL, 51, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (719, '202101323', 32, 'habilitado', NULL, NULL, NULL, 51, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (720, '202101345', 32, 'habilitado', NULL, NULL, NULL, 51, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (721, '202101367', 32, 'habilitado', NULL, NULL, NULL, 51, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (722, '202101389', 32, 'habilitado', NULL, NULL, NULL, 51, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (723, '202101401', 32, 'habilitado', NULL, NULL, NULL, 51, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (724, '202101423', 32, 'habilitado', NULL, NULL, NULL, 51, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (725, '202101445', 32, 'habilitado', NULL, NULL, NULL, 51, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (726, '202101467', 32, 'habilitado', NULL, NULL, NULL, 51, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (727, '202101489', 32, 'habilitado', NULL, NULL, NULL, 51, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (728, '202201013', 32, 'habilitado', NULL, NULL, NULL, 51, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (729, '202201031', 32, 'habilitado', NULL, NULL, NULL, 51, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (730, '202201056', 32, 'habilitado', NULL, NULL, NULL, 51, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (731, '202201089', 32, 'habilitado', NULL, NULL, NULL, 51, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (732, '202201101', 32, 'habilitado', NULL, NULL, NULL, 51, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (614, '202101289', 27, 'deshabilitado', NULL, NULL, NULL, 46, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (622, '202101445', 27, 'deshabilitado', 'Rechazado en el control de ingreso', 1, '2026-10-27 04:38:08', 46, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (642, '202201356', 27, 'deshabilitado', 'Rechazado en el control de ingreso', 1, '2026-10-27 06:28:13', 46, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (656, '202101090', 29, 'deshabilitado', 'Rechazado en el control de ingreso', 1, '2026-10-27 07:01:38', 48, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (647, '202201456', 27, 'deshabilitado', 'Rechazado en el control de ingreso', 1, '2026-10-27 07:01:46', 46, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (733, '202201123', 32, 'habilitado', NULL, NULL, NULL, 51, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (734, '202201145', 32, 'habilitado', NULL, NULL, NULL, 51, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (735, '202201167', 32, 'habilitado', NULL, NULL, NULL, 51, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (736, '202201189', 32, 'habilitado', NULL, NULL, NULL, 51, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (737, '202201201', 32, 'habilitado', NULL, NULL, NULL, 51, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (738, '202201212', 32, 'habilitado', NULL, NULL, NULL, 51, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (739, '202201234', 32, 'habilitado', NULL, NULL, NULL, 51, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (740, '202201256', 32, 'habilitado', NULL, NULL, NULL, 51, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (741, '202201278', 32, 'habilitado', NULL, NULL, NULL, 51, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (742, '202201290', 32, 'habilitado', NULL, NULL, NULL, 51, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (743, '202201312', 32, 'habilitado', NULL, NULL, NULL, 51, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (744, '202201334', 32, 'habilitado', NULL, NULL, NULL, 51, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (745, '202201356', 32, 'habilitado', NULL, NULL, NULL, 51, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (746, '202201378', 32, 'habilitado', NULL, NULL, NULL, 51, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (747, '202201390', 32, 'habilitado', NULL, NULL, NULL, 51, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (748, '202201412', 32, 'habilitado', NULL, NULL, NULL, 51, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (749, '202201434', 32, 'habilitado', NULL, NULL, NULL, 51, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (751, '202201478', 32, 'habilitado', NULL, NULL, NULL, 51, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (752, '202201490', 32, 'habilitado', NULL, NULL, NULL, 51, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (753, '202202045', 32, 'habilitado', NULL, NULL, NULL, 51, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (754, '20210004', 33, 'habilitado', NULL, NULL, NULL, 52, 4);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (755, '20210001', 34, 'habilitado', NULL, NULL, NULL, 53, 1);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (651, '20210001', 28, 'deshabilitado', NULL, NULL, NULL, 47, 1);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (633, '202201189', 27, 'deshabilitado', NULL, NULL, NULL, 46, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (645, '202201412', 27, 'deshabilitado', 'Rechazado en el control de ingreso', 1, '2026-10-27 04:39:25', 46, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (756, '202002107', 35, 'habilitado', NULL, NULL, NULL, 54, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (757, '20210003', 35, 'habilitado', NULL, NULL, NULL, 54, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (758, '202101022', 35, 'habilitado', NULL, NULL, NULL, 54, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (759, '202101078', 35, 'habilitado', NULL, NULL, NULL, 54, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (761, '202101112', 35, 'habilitado', NULL, NULL, NULL, 54, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (762, '202101134', 35, 'habilitado', NULL, NULL, NULL, 54, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (763, '202101156', 35, 'habilitado', NULL, NULL, NULL, 54, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (764, '202101178', 35, 'habilitado', NULL, NULL, NULL, 54, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (765, '202101190', 35, 'habilitado', NULL, NULL, NULL, 54, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (766, '202101223', 35, 'habilitado', NULL, NULL, NULL, 54, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (767, '202101245', 35, 'habilitado', NULL, NULL, NULL, 54, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (768, '202101267', 35, 'habilitado', NULL, NULL, NULL, 54, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (770, '202101301', 35, 'habilitado', NULL, NULL, NULL, 54, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (771, '202101323', 35, 'habilitado', NULL, NULL, NULL, 54, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (772, '202101345', 35, 'habilitado', NULL, NULL, NULL, 54, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (773, '202101367', 35, 'habilitado', NULL, NULL, NULL, 54, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (774, '202101389', 35, 'habilitado', NULL, NULL, NULL, 54, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (775, '202101401', 35, 'habilitado', NULL, NULL, NULL, 54, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (776, '202101423', 35, 'habilitado', NULL, NULL, NULL, 54, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (777, '202101445', 35, 'habilitado', NULL, NULL, NULL, 54, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (778, '202101467', 35, 'habilitado', NULL, NULL, NULL, 54, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (779, '202101489', 35, 'habilitado', NULL, NULL, NULL, 54, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (780, '202201013', 35, 'habilitado', NULL, NULL, NULL, 54, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (781, '202201031', 35, 'habilitado', NULL, NULL, NULL, 54, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (782, '202201056', 35, 'habilitado', NULL, NULL, NULL, 54, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (783, '202201089', 35, 'habilitado', NULL, NULL, NULL, 54, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (784, '202201101', 35, 'habilitado', NULL, NULL, NULL, 54, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (785, '202201123', 35, 'habilitado', NULL, NULL, NULL, 54, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (786, '202201145', 35, 'habilitado', NULL, NULL, NULL, 54, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (787, '202201167', 35, 'habilitado', NULL, NULL, NULL, 54, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (788, '202201189', 35, 'habilitado', NULL, NULL, NULL, 54, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (789, '202201201', 35, 'habilitado', NULL, NULL, NULL, 54, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (791, '202201234', 35, 'habilitado', NULL, NULL, NULL, 54, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (792, '202201256', 35, 'habilitado', NULL, NULL, NULL, 54, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (793, '202201278', 35, 'habilitado', NULL, NULL, NULL, 54, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (794, '202201290', 35, 'habilitado', NULL, NULL, NULL, 54, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (795, '202201312', 35, 'habilitado', NULL, NULL, NULL, 54, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (796, '202201334', 35, 'habilitado', NULL, NULL, NULL, 54, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (797, '202201356', 35, 'habilitado', NULL, NULL, NULL, 54, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (798, '202201378', 35, 'habilitado', NULL, NULL, NULL, 54, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (799, '202201390', 35, 'habilitado', NULL, NULL, NULL, 54, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (800, '202201412', 35, 'habilitado', NULL, NULL, NULL, 54, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (801, '202201434', 35, 'habilitado', NULL, NULL, NULL, 54, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (803, '202201478', 35, 'habilitado', NULL, NULL, NULL, 54, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (804, '202201490', 35, 'habilitado', NULL, NULL, NULL, 54, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (805, '202202045', 35, 'habilitado', NULL, NULL, NULL, 54, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (158, '202101090', 14, 'deshabilitado', 'Rechazado en el control de ingreso', 1, '2026-10-27 07:01:34', 4, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (353, '202101090', 20, 'deshabilitado', 'Rechazado en el control de ingreso', 1, '2026-10-27 07:01:35', 32, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (403, '202101090', 21, 'deshabilitado', 'Rechazado en el control de ingreso', 1, '2026-10-27 07:01:36', 33, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (453, '202101090', 22, 'deshabilitado', 'Rechazado en el control de ingreso', 1, '2026-10-27 07:01:36', 35, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (708, '202101090', 32, 'deshabilitado', 'Rechazado en el control de ingreso', 1, '2026-10-27 07:01:39', 51, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (760, '202101090', 35, 'deshabilitado', 'Rechazado en el control de ingreso', 1, '2026-10-27 07:01:39', 54, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (395, '202201456', 20, 'deshabilitado', 'Rechazado en el control de ingreso', 1, '2026-10-27 07:01:43', 32, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (698, '202201456', 29, 'deshabilitado', 'Rechazado en el control de ingreso', 1, '2026-10-27 07:01:46', 48, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (750, '202201456', 32, 'deshabilitado', 'Rechazado en el control de ingreso', 1, '2026-10-27 07:01:47', 51, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (802, '202201456', 35, 'deshabilitado', 'Rechazado en el control de ingreso', 1, '2026-10-27 07:01:48', 54, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (790, '202201212', 35, 'deshabilitado', 'Rechazado en el control de ingreso', 1, '2026-10-27 07:04:39', 54, 3);

INSERT INTO public.estudiante_examen ("id_estudiante_examen", "sis_estudiante", "id_examen", "estado", "motivo", "modificado_por", "fecha_modificacion", "id_configuracion_examen", "id_curso") VALUES (769, '202101289', 35, 'deshabilitado', 'Rechazado en el control de ingreso', 1, '2026-10-27 07:18:14', 54, 3);


INSERT INTO public.central_riesgo ("id_registro", "id_registrador", "detalle_motivo", "fecha_registro", "tipo_infraccion", "id_examen", "sis_estudiante", "motivo", "estado_incidencia", "id_confirmador", "id_curso", "id_estudiante_examen") VALUES (21, 1, NULL, '2026-10-25 18:11:43', 'tramposo', 17, '202201013', 'intento de ingreso de material no autorizado', 'Pendiente', NULL, 3, 223);

INSERT INTO public.central_riesgo ("id_registro", "id_registrador", "detalle_motivo", "fecha_registro", "tipo_infraccion", "id_examen", "sis_estudiante", "motivo", "estado_incidencia", "id_confirmador", "id_curso", "id_estudiante_examen") VALUES (25, 1, NULL, '2026-10-27 05:11:45', 'tramposo', 27, '202201013', 'copia o intercambio de respuestas', 'Pendiente', NULL, 3, 625);

INSERT INTO public.central_riesgo ("id_registro", "id_registrador", "detalle_motivo", "fecha_registro", "tipo_infraccion", "id_examen", "sis_estudiante", "motivo", "estado_incidencia", "id_confirmador", "id_curso", "id_estudiante_examen") VALUES (23, 3, 'Prueba central de riesgos', '2026-10-27 04:25:19', 'tramposo', 27, '202101423', 'otro', 'Pendiente', NULL, 3, 621);

INSERT INTO public.central_riesgo ("id_registro", "id_registrador", "detalle_motivo", "fecha_registro", "tipo_infraccion", "id_examen", "sis_estudiante", "motivo", "estado_incidencia", "id_confirmador", "id_curso", "id_estudiante_examen") VALUES (28, 1, NULL, '2026-10-27 06:28:08', 'tramposo', 27, '202201356', 'intento de ingreso no autorizado', 'Pendiente', NULL, 3, 642);

INSERT INTO public.central_riesgo ("id_registro", "id_registrador", "detalle_motivo", "fecha_registro", "tipo_infraccion", "id_examen", "sis_estudiante", "motivo", "estado_incidencia", "id_confirmador", "id_curso", "id_estudiante_examen") VALUES (31, 1, NULL, '2026-10-27 07:04:36', 'sospechoso', 35, '202201212', 'intento de ingreso no autorizado', 'Pendiente', NULL, 3, 790);

INSERT INTO public.central_riesgo ("id_registro", "id_registrador", "detalle_motivo", "fecha_registro", "tipo_infraccion", "id_examen", "sis_estudiante", "motivo", "estado_incidencia", "id_confirmador", "id_curso", "id_estudiante_examen") VALUES (13, 1, NULL, '2026-10-10 19:30:17', 'sospechoso', 14, '20210004', 'intento de ingreso no autorizado', 'Pendiente', NULL, 3, NULL);

INSERT INTO public.central_riesgo ("id_registro", "id_registrador", "detalle_motivo", "fecha_registro", "tipo_infraccion", "id_examen", "sis_estudiante", "motivo", "estado_incidencia", "id_confirmador", "id_curso", "id_estudiante_examen") VALUES (1, 3, 'Estudiante mirando hacia otro lado', '2024-06-10 08:30:00', 'sospechoso', 1, '20210001', 'otro', 'Confirmado', 1, 1, 1);

INSERT INTO public.central_riesgo ("id_registro", "id_registrador", "detalle_motivo", "fecha_registro", "tipo_infraccion", "id_examen", "sis_estudiante", "motivo", "estado_incidencia", "id_confirmador", "id_curso", "id_estudiante_examen") VALUES (2, 4, 'Uso de celular durante el examen', '2024-06-11 10:30:00', 'tramposo', 2, '20210002', 'uso de dispositivos electronicos', 'Confirmado', NULL, 2, 2);

INSERT INTO public.central_riesgo ("id_registro", "id_registrador", "detalle_motivo", "fecha_registro", "tipo_infraccion", "id_examen", "sis_estudiante", "motivo", "estado_incidencia", "id_confirmador", "id_curso", "id_estudiante_examen") VALUES (3, 3, 'Posible intento de ingreso no autorizado', '2024-06-12 14:30:00', 'sospechoso', 3, '20210003', 'intento de ingreso no autorizado', 'Pendiente', NULL, 3, 3);

INSERT INTO public.central_riesgo ("id_registro", "id_registrador", "detalle_motivo", "fecha_registro", "tipo_infraccion", "id_examen", "sis_estudiante", "motivo", "estado_incidencia", "id_confirmador", "id_curso", "id_estudiante_examen") VALUES (4, 4, 'Uso de material no autorizado', '2024-06-13 08:45:00', 'tramposo', 4, '20210004', 'uso de material no autorizado', 'Confirmado', NULL, 4, 4);

INSERT INTO public.central_riesgo ("id_registro", "id_registrador", "detalle_motivo", "fecha_registro", "tipo_infraccion", "id_examen", "sis_estudiante", "motivo", "estado_incidencia", "id_confirmador", "id_curso", "id_estudiante_examen") VALUES (5, 3, 'Comportamiento sospechoso', '2024-06-14 16:30:00', 'sospechoso', 5, '20210005', 'otro', 'Pendiente', NULL, 5, 5);

INSERT INTO public.central_riesgo ("id_registro", "id_registrador", "detalle_motivo", "fecha_registro", "tipo_infraccion", "id_examen", "sis_estudiante", "motivo", "estado_incidencia", "id_confirmador", "id_curso", "id_estudiante_examen") VALUES (6, 3, 'Uso de celular durante el examen', '2026-10-10 08:40:00', 'tramposo', 14, '202201089', 'uso de dispositivos electronicos', 'Confirmado', 1, 3, 157);

INSERT INTO public.central_riesgo ("id_registro", "id_registrador", "detalle_motivo", "fecha_registro", "tipo_infraccion", "id_examen", "sis_estudiante", "motivo", "estado_incidencia", "id_confirmador", "id_curso", "id_estudiante_examen") VALUES (7, 3, 'Intercambio de respuestas con el compañero de al lado', '2026-10-10 08:40:00', 'tramposo', 14, '202101078', 'copia o intercambio de respuestas', 'Confirmado', 1, 3, 156);

INSERT INTO public.central_riesgo ("id_registro", "id_registrador", "detalle_motivo", "fecha_registro", "tipo_infraccion", "id_examen", "sis_estudiante", "motivo", "estado_incidencia", "id_confirmador", "id_curso", "id_estudiante_examen") VALUES (8, 3, 'Material no autorizado sobre la mesa', '2026-10-10 08:40:00', 'tramposo', 14, '202201056', 'uso de material no autorizado', 'Confirmado', 1, 3, 155);

INSERT INTO public.central_riesgo ("id_registro", "id_registrador", "detalle_motivo", "fecha_registro", "tipo_infraccion", "id_examen", "sis_estudiante", "motivo", "estado_incidencia", "id_confirmador", "id_curso", "id_estudiante_examen") VALUES (9, 1, NULL, '2026-10-10 20:38:58', 'sospechoso', 14, '202101289', 'uso de dispositivos electronicos', 'Pendiente', NULL, 3, 177);

INSERT INTO public.central_riesgo ("id_registro", "id_registrador", "detalle_motivo", "fecha_registro", "tipo_infraccion", "id_examen", "sis_estudiante", "motivo", "estado_incidencia", "id_confirmador", "id_curso", "id_estudiante_examen") VALUES (10, 1, NULL, '2026-10-10 21:50:57', 'sospechoso', 14, '202201013', 'copia o intercambio de respuestas', 'Pendiente', NULL, 3, 150);

INSERT INTO public.central_riesgo ("id_registro", "id_registrador", "detalle_motivo", "fecha_registro", "tipo_infraccion", "id_examen", "sis_estudiante", "motivo", "estado_incidencia", "id_confirmador", "id_curso", "id_estudiante_examen") VALUES (11, 1, NULL, '2026-10-10 22:23:21', 'sospechoso', 14, '202201123', 'suplantacion de identidad', 'Pendiente', NULL, 3, 161);

INSERT INTO public.central_riesgo ("id_registro", "id_registrador", "detalle_motivo", "fecha_registro", "tipo_infraccion", "id_examen", "sis_estudiante", "motivo", "estado_incidencia", "id_confirmador", "id_curso", "id_estudiante_examen") VALUES (12, 1, NULL, '2026-10-10 22:30:46', 'sospechoso', 14, '202201256', 'intento de ingreso no autorizado', 'Pendiente', NULL, 3, 174);

INSERT INTO public.central_riesgo ("id_registro", "id_registrador", "detalle_motivo", "fecha_registro", "tipo_infraccion", "id_examen", "sis_estudiante", "motivo", "estado_incidencia", "id_confirmador", "id_curso", "id_estudiante_examen") VALUES (15, 1, NULL, '2026-10-11 01:43:49', 'sospechoso', 14, '202202045', 'uso de dispositivos electronicos', 'Pendiente', NULL, 3, 153);

INSERT INTO public.central_riesgo ("id_registro", "id_registrador", "detalle_motivo", "fecha_registro", "tipo_infraccion", "id_examen", "sis_estudiante", "motivo", "estado_incidencia", "id_confirmador", "id_curso", "id_estudiante_examen") VALUES (16, 1, NULL, '2026-10-11 02:13:20', 'sospechoso', 14, '202201201', 'uso de material no autorizado', 'Pendiente', NULL, 3, 169);

INSERT INTO public.central_riesgo ("id_registro", "id_registrador", "detalle_motivo", "fecha_registro", "tipo_infraccion", "id_examen", "sis_estudiante", "motivo", "estado_incidencia", "id_confirmador", "id_curso", "id_estudiante_examen") VALUES (18, 1, NULL, '2026-10-12 03:34:01', 'tramposo', 14, '202101178', 'suplantacion de identidad', 'Pendiente', NULL, 3, 166);

INSERT INTO public.central_riesgo ("id_registro", "id_registrador", "detalle_motivo", "fecha_registro", "tipo_infraccion", "id_examen", "sis_estudiante", "motivo", "estado_incidencia", "id_confirmador", "id_curso", "id_estudiante_examen") VALUES (19, 1, 'tramposo', '2026-10-12 00:13:17', 'tramposo', 14, '202101090', 'otro', 'Pendiente', NULL, 3, 158);

INSERT INTO public.central_riesgo ("id_registro", "id_registrador", "detalle_motivo", "fecha_registro", "tipo_infraccion", "id_examen", "sis_estudiante", "motivo", "estado_incidencia", "id_confirmador", "id_curso", "id_estudiante_examen") VALUES (22, 1, NULL, '2026-10-25 18:32:25', 'tramposo', 19, '202201278', 'intento de ingreso de material no autorizado', 'Pendiente', NULL, 3, 336);

INSERT INTO public.central_riesgo ("id_registro", "id_registrador", "detalle_motivo", "fecha_registro", "tipo_infraccion", "id_examen", "sis_estudiante", "motivo", "estado_incidencia", "id_confirmador", "id_curso", "id_estudiante_examen") VALUES (24, 1, NULL, '2026-10-27 04:55:06', 'tramposo', 27, '202202045', 'uso de dispositivos electronicos', 'Pendiente', NULL, 3, 650);

INSERT INTO public.central_riesgo ("id_registro", "id_registrador", "detalle_motivo", "fecha_registro", "tipo_infraccion", "id_examen", "sis_estudiante", "motivo", "estado_incidencia", "id_confirmador", "id_curso", "id_estudiante_examen") VALUES (26, 1, NULL, '2026-10-27 06:12:54', 'tramposo', 27, '202201456', 'intento de ingreso no autorizado', 'Pendiente', NULL, 3, 647);

INSERT INTO public.central_riesgo ("id_registro", "id_registrador", "detalle_motivo", "fecha_registro", "tipo_infraccion", "id_examen", "sis_estudiante", "motivo", "estado_incidencia", "id_confirmador", "id_curso", "id_estudiante_examen") VALUES (27, 1, NULL, '2026-10-27 06:13:32', 'tramposo', 27, '202101401', 'aula equivocada', 'Pendiente', NULL, 3, 620);

INSERT INTO public.central_riesgo ("id_registro", "id_registrador", "detalle_motivo", "fecha_registro", "tipo_infraccion", "id_examen", "sis_estudiante", "motivo", "estado_incidencia", "id_confirmador", "id_curso", "id_estudiante_examen") VALUES (29, 5, 'Prueba central de riesgos (examen pasado)', '2026-10-25 06:35:37', 'tramposo', 17, '202101489', 'otro', 'Pendiente', NULL, 3, 222);

INSERT INTO public.central_riesgo ("id_registro", "id_registrador", "detalle_motivo", "fecha_registro", "tipo_infraccion", "id_examen", "sis_estudiante", "motivo", "estado_incidencia", "id_confirmador", "id_curso", "id_estudiante_examen") VALUES (30, 5, 'Prueba central de riesgos (examen pasado)', '2026-10-25 06:35:39', 'tramposo', 17, '202101223', 'otro', 'Pendiente', NULL, 3, 209);

INSERT INTO public.central_riesgo ("id_registro", "id_registrador", "detalle_motivo", "fecha_registro", "tipo_infraccion", "id_examen", "sis_estudiante", "motivo", "estado_incidencia", "id_confirmador", "id_curso", "id_estudiante_examen") VALUES (32, 1, NULL, '2026-10-27 07:18:09', 'tramposo', 35, '202101289', 'copia o intercambio de respuestas', 'Pendiente', NULL, 3, 769);

INSERT INTO public.central_riesgo ("id_registro", "id_registrador", "detalle_motivo", "fecha_registro", "tipo_infraccion", "id_examen", "sis_estudiante", "motivo", "estado_incidencia", "id_confirmador", "id_curso", "id_estudiante_examen") VALUES (14, 1, NULL, '2026-10-11 01:15:50', 'sospechoso', 1, '20210001', 'intento de ingreso no autorizado', 'Pendiente', NULL, 1, 1);

INSERT INTO public.central_riesgo ("id_registro", "id_registrador", "detalle_motivo", "fecha_registro", "tipo_infraccion", "id_examen", "sis_estudiante", "motivo", "estado_incidencia", "id_confirmador", "id_curso", "id_estudiante_examen") VALUES (17, 1, NULL, '2026-10-12 01:30:52', 'tramposo', 14, '202101467', 'copia o intercambio de respuestas', 'Pendiente', NULL, 3, 195);

INSERT INTO public.central_riesgo ("id_registro", "id_registrador", "detalle_motivo", "fecha_registro", "tipo_infraccion", "id_examen", "sis_estudiante", "motivo", "estado_incidencia", "id_confirmador", "id_curso", "id_estudiante_examen") VALUES (20, 1, 'Se la vio sacando su celular', '2026-10-12 16:34:44', 'tramposo', 14, '202201212', 'uso de dispositivos electronicos', 'Pendiente', NULL, 3, 170);


INSERT INTO public.estudiante_examen_ambiente ("id_ee", "id_ambiente") VALUES (1, 1);

INSERT INTO public.estudiante_examen_ambiente ("id_ee", "id_ambiente") VALUES (2, 2);

INSERT INTO public.estudiante_examen_ambiente ("id_ee", "id_ambiente") VALUES (3, 3);

INSERT INTO public.estudiante_examen_ambiente ("id_ee", "id_ambiente") VALUES (4, 4);

INSERT INTO public.estudiante_examen_ambiente ("id_ee", "id_ambiente") VALUES (5, 5);

INSERT INTO public.estudiante_examen_ambiente ("id_ee", "id_ambiente") VALUES (549, 1);

INSERT INTO public.estudiante_examen_ambiente ("id_ee", "id_ambiente") VALUES (589, 2);

INSERT INTO public.estudiante_examen_ambiente ("id_ee", "id_ambiente") VALUES (563, 5);

INSERT INTO public.estudiante_examen_ambiente ("id_ee", "id_ambiente") VALUES (565, 2);

INSERT INTO public.estudiante_examen_ambiente ("id_ee", "id_ambiente") VALUES (577, 5);

INSERT INTO public.estudiante_examen_ambiente ("id_ee", "id_ambiente") VALUES (558, 2);

INSERT INTO public.estudiante_examen_ambiente ("id_ee", "id_ambiente") VALUES (590, 5);

INSERT INTO public.estudiante_examen_ambiente ("id_ee", "id_ambiente") VALUES (552, 2);

INSERT INTO public.estudiante_examen_ambiente ("id_ee", "id_ambiente") VALUES (579, 5);

INSERT INTO public.estudiante_examen_ambiente ("id_ee", "id_ambiente") VALUES (566, 2);

INSERT INTO public.estudiante_examen_ambiente ("id_ee", "id_ambiente") VALUES (554, 5);

INSERT INTO public.estudiante_examen_ambiente ("id_ee", "id_ambiente") VALUES (582, 2);

INSERT INTO public.estudiante_examen_ambiente ("id_ee", "id_ambiente") VALUES (591, 5);

INSERT INTO public.estudiante_examen_ambiente ("id_ee", "id_ambiente") VALUES (578, 2);

INSERT INTO public.estudiante_examen_ambiente ("id_ee", "id_ambiente") VALUES (559, 5);

INSERT INTO public.estudiante_examen_ambiente ("id_ee", "id_ambiente") VALUES (567, 2);

INSERT INTO public.estudiante_examen_ambiente ("id_ee", "id_ambiente") VALUES (592, 5);

INSERT INTO public.estudiante_examen_ambiente ("id_ee", "id_ambiente") VALUES (568, 2);

INSERT INTO public.estudiante_examen_ambiente ("id_ee", "id_ambiente") VALUES (593, 5);

INSERT INTO public.estudiante_examen_ambiente ("id_ee", "id_ambiente") VALUES (569, 2);

INSERT INTO public.estudiante_examen_ambiente ("id_ee", "id_ambiente") VALUES (551, 5);

INSERT INTO public.estudiante_examen_ambiente ("id_ee", "id_ambiente") VALUES (594, 2);

INSERT INTO public.estudiante_examen_ambiente ("id_ee", "id_ambiente") VALUES (574, 5);

INSERT INTO public.estudiante_examen_ambiente ("id_ee", "id_ambiente") VALUES (555, 2);

INSERT INTO public.estudiante_examen_ambiente ("id_ee", "id_ambiente") VALUES (583, 5);

INSERT INTO public.estudiante_examen_ambiente ("id_ee", "id_ambiente") VALUES (553, 2);

INSERT INTO public.estudiante_examen_ambiente ("id_ee", "id_ambiente") VALUES (581, 5);

INSERT INTO public.estudiante_examen_ambiente ("id_ee", "id_ambiente") VALUES (570, 2);

INSERT INTO public.estudiante_examen_ambiente ("id_ee", "id_ambiente") VALUES (587, 5);

INSERT INTO public.estudiante_examen_ambiente ("id_ee", "id_ambiente") VALUES (595, 2);

INSERT INTO public.estudiante_examen_ambiente ("id_ee", "id_ambiente") VALUES (571, 5);

INSERT INTO public.estudiante_examen_ambiente ("id_ee", "id_ambiente") VALUES (562, 2);

INSERT INTO public.estudiante_examen_ambiente ("id_ee", "id_ambiente") VALUES (596, 5);

INSERT INTO public.estudiante_examen_ambiente ("id_ee", "id_ambiente") VALUES (572, 2);

INSERT INTO public.estudiante_examen_ambiente ("id_ee", "id_ambiente") VALUES (576, 5);

INSERT INTO public.estudiante_examen_ambiente ("id_ee", "id_ambiente") VALUES (557, 2);

INSERT INTO public.estudiante_examen_ambiente ("id_ee", "id_ambiente") VALUES (597, 5);

INSERT INTO public.estudiante_examen_ambiente ("id_ee", "id_ambiente") VALUES (586, 2);

INSERT INTO public.estudiante_examen_ambiente ("id_ee", "id_ambiente") VALUES (575, 5);

INSERT INTO public.estudiante_examen_ambiente ("id_ee", "id_ambiente") VALUES (588, 2);

INSERT INTO public.estudiante_examen_ambiente ("id_ee", "id_ambiente") VALUES (561, 5);

INSERT INTO public.estudiante_examen_ambiente ("id_ee", "id_ambiente") VALUES (599, 2);

INSERT INTO public.estudiante_examen_ambiente ("id_ee", "id_ambiente") VALUES (556, 5);

INSERT INTO public.estudiante_examen_ambiente ("id_ee", "id_ambiente") VALUES (573, 2);

INSERT INTO public.estudiante_examen_ambiente ("id_ee", "id_ambiente") VALUES (585, 5);

INSERT INTO public.estudiante_examen_ambiente ("id_ee", "id_ambiente") VALUES (598, 2);

INSERT INTO public.estudiante_examen_ambiente ("id_ee", "id_ambiente") VALUES (560, 5);

INSERT INTO public.estudiante_examen_ambiente ("id_ee", "id_ambiente") VALUES (564, 2);

INSERT INTO public.estudiante_examen_ambiente ("id_ee", "id_ambiente") VALUES (550, 5);

INSERT INTO public.estudiante_examen_ambiente ("id_ee", "id_ambiente") VALUES (580, 2);

INSERT INTO public.estudiante_examen_ambiente ("id_ee", "id_ambiente") VALUES (584, 5);


INSERT INTO public.registro_asistencia ("id_ingreso", "hora_ingreso", "id_examen", "id_estudiante", "id_registrador", "id_estudiante_examen") VALUES (338, '18:14:12', 17, '202201256', 1, 235);

INSERT INTO public.registro_asistencia ("id_ingreso", "hora_ingreso", "id_examen", "id_estudiante", "id_registrador", "id_estudiante_examen") VALUES (339, '18:14:14', 17, '202101267', 1, 211);

INSERT INTO public.registro_asistencia ("id_ingreso", "hora_ingreso", "id_examen", "id_estudiante", "id_registrador", "id_estudiante_examen") VALUES (340, '18:14:16', 17, '202101022', 1, 201);

INSERT INTO public.registro_asistencia ("id_ingreso", "hora_ingreso", "id_examen", "id_estudiante", "id_registrador", "id_estudiante_examen") VALUES (341, '18:19:45', 18, '202201013', 1, 273);

INSERT INTO public.registro_asistencia ("id_ingreso", "hora_ingreso", "id_examen", "id_estudiante", "id_registrador", "id_estudiante_examen") VALUES (342, '18:19:57', 18, '202201256', 1, 285);

INSERT INTO public.registro_asistencia ("id_ingreso", "hora_ingreso", "id_examen", "id_estudiante", "id_registrador", "id_estudiante_examen") VALUES (343, '18:19:59', 18, '202101267', 1, 261);

INSERT INTO public.registro_asistencia ("id_ingreso", "hora_ingreso", "id_examen", "id_estudiante", "id_registrador", "id_estudiante_examen") VALUES (344, '18:20:01', 18, '202101022', 1, 251);

INSERT INTO public.registro_asistencia ("id_ingreso", "hora_ingreso", "id_examen", "id_estudiante", "id_registrador", "id_estudiante_examen") VALUES (345, '18:30:13', 19, '202201013', 1, 323);

INSERT INTO public.registro_asistencia ("id_ingreso", "hora_ingreso", "id_examen", "id_estudiante", "id_registrador", "id_estudiante_examen") VALUES (346, '18:30:14', 19, '202201256', 1, 335);

INSERT INTO public.registro_asistencia ("id_ingreso", "hora_ingreso", "id_examen", "id_estudiante", "id_registrador", "id_estudiante_examen") VALUES (347, '18:30:16', 19, '202101267', 1, 311);

INSERT INTO public.registro_asistencia ("id_ingreso", "hora_ingreso", "id_examen", "id_estudiante", "id_registrador", "id_estudiante_examen") VALUES (348, '18:30:18', 19, '202101022', 1, 301);

INSERT INTO public.registro_asistencia ("id_ingreso", "hora_ingreso", "id_examen", "id_estudiante", "id_registrador", "id_estudiante_examen") VALUES (349, '18:35:30', 20, '202201013', 1, 373);

INSERT INTO public.registro_asistencia ("id_ingreso", "hora_ingreso", "id_examen", "id_estudiante", "id_registrador", "id_estudiante_examen") VALUES (350, '18:35:42', 20, '202201256', 1, 385);

INSERT INTO public.registro_asistencia ("id_ingreso", "hora_ingreso", "id_examen", "id_estudiante", "id_registrador", "id_estudiante_examen") VALUES (351, '20:13:13', 23, '202201013', 1, 523);

INSERT INTO public.registro_asistencia ("id_ingreso", "hora_ingreso", "id_examen", "id_estudiante", "id_registrador", "id_estudiante_examen") VALUES (352, '20:13:25', 23, '202201256', 1, 535);

INSERT INTO public.registro_asistencia ("id_ingreso", "hora_ingreso", "id_examen", "id_estudiante", "id_registrador", "id_estudiante_examen") VALUES (353, '20:13:27', 23, '202101267', 1, 511);

INSERT INTO public.registro_asistencia ("id_ingreso", "hora_ingreso", "id_examen", "id_estudiante", "id_registrador", "id_estudiante_examen") VALUES (354, '01:13:33', 27, '202201013', 1, 625);

INSERT INTO public.registro_asistencia ("id_ingreso", "hora_ingreso", "id_examen", "id_estudiante", "id_registrador", "id_estudiante_examen") VALUES (355, '04:36:48', 27, '202101423', 1, 621);

INSERT INTO public.registro_asistencia ("id_ingreso", "hora_ingreso", "id_examen", "id_estudiante", "id_registrador", "id_estudiante_examen") VALUES (356, '04:40:43', 27, '202201056', 1, 627);

INSERT INTO public.registro_asistencia ("id_ingreso", "hora_ingreso", "id_examen", "id_estudiante", "id_registrador", "id_estudiante_examen") VALUES (357, '04:41:02', 27, '202202045', 1, 650);

INSERT INTO public.registro_asistencia ("id_ingreso", "hora_ingreso", "id_examen", "id_estudiante", "id_registrador", "id_estudiante_examen") VALUES (358, '04:52:43', 27, '202101156', 1, 608);

INSERT INTO public.registro_asistencia ("id_ingreso", "hora_ingreso", "id_examen", "id_estudiante", "id_registrador", "id_estudiante_examen") VALUES (359, '04:54:23', 27, '202201490', 1, 649);

INSERT INTO public.registro_asistencia ("id_ingreso", "hora_ingreso", "id_examen", "id_estudiante", "id_registrador", "id_estudiante_examen") VALUES (360, '04:54:41', 27, '202101245', 1, 612);

INSERT INTO public.registro_asistencia ("id_ingreso", "hora_ingreso", "id_examen", "id_estudiante", "id_registrador", "id_estudiante_examen") VALUES (361, '06:27:49', 27, '202201123', 1, 630);

INSERT INTO public.registro_asistencia ("id_ingreso", "hora_ingreso", "id_examen", "id_estudiante", "id_registrador", "id_estudiante_examen") VALUES (362, '10:56:25', 29, '202201256', 1, 688);

INSERT INTO public.registro_asistencia ("id_ingreso", "hora_ingreso", "id_examen", "id_estudiante", "id_registrador", "id_estudiante_examen") VALUES (363, '07:03:41', 35, '202101267', 1, 768);

INSERT INTO public.registro_asistencia ("id_ingreso", "hora_ingreso", "id_examen", "id_estudiante", "id_registrador", "id_estudiante_examen") VALUES (1, '07:45:00', 1, '20210001', 3, 1);

INSERT INTO public.registro_asistencia ("id_ingreso", "hora_ingreso", "id_examen", "id_estudiante", "id_registrador", "id_estudiante_examen") VALUES (2, '09:45:00', 2, '20210002', 4, 2);

INSERT INTO public.registro_asistencia ("id_ingreso", "hora_ingreso", "id_examen", "id_estudiante", "id_registrador", "id_estudiante_examen") VALUES (3, '13:45:00', 3, '20210003', 3, 3);

INSERT INTO public.registro_asistencia ("id_ingreso", "hora_ingreso", "id_examen", "id_estudiante", "id_registrador", "id_estudiante_examen") VALUES (4, '07:50:00', 4, '20210004', 4, 4);

INSERT INTO public.registro_asistencia ("id_ingreso", "hora_ingreso", "id_examen", "id_estudiante", "id_registrador", "id_estudiante_examen") VALUES (5, '15:45:00', 5, '20210005', 3, 5);

INSERT INTO public.registro_asistencia ("id_ingreso", "hora_ingreso", "id_examen", "id_estudiante", "id_registrador", "id_estudiante_examen") VALUES (201, '16:45:00', 5, '202201013', 1, 101);

INSERT INTO public.registro_asistencia ("id_ingreso", "hora_ingreso", "id_examen", "id_estudiante", "id_registrador", "id_estudiante_examen") VALUES (202, '16:16:00', 5, '202101022', 2, 102);

INSERT INTO public.registro_asistencia ("id_ingreso", "hora_ingreso", "id_examen", "id_estudiante", "id_registrador", "id_estudiante_examen") VALUES (203, '16:16:00', 5, '202002107', 2, 105);

INSERT INTO public.registro_asistencia ("id_ingreso", "hora_ingreso", "id_examen", "id_estudiante", "id_registrador", "id_estudiante_examen") VALUES (204, '16:21:00', 5, '202101078', 1, 107);

INSERT INTO public.registro_asistencia ("id_ingreso", "hora_ingreso", "id_examen", "id_estudiante", "id_registrador", "id_estudiante_examen") VALUES (205, '16:26:00', 5, '202201101', 1, 110);

INSERT INTO public.registro_asistencia ("id_ingreso", "hora_ingreso", "id_examen", "id_estudiante", "id_registrador", "id_estudiante_examen") VALUES (206, '16:02:00', 5, '202201123', 2, 112);

INSERT INTO public.registro_asistencia ("id_ingreso", "hora_ingreso", "id_examen", "id_estudiante", "id_registrador", "id_estudiante_examen") VALUES (207, '16:33:00', 5, '202101156', 2, 115);

INSERT INTO public.registro_asistencia ("id_ingreso", "hora_ingreso", "id_examen", "id_estudiante", "id_registrador", "id_estudiante_examen") VALUES (208, '16:46:00', 5, '202201189', 1, 118);

INSERT INTO public.registro_asistencia ("id_ingreso", "hora_ingreso", "id_examen", "id_estudiante", "id_registrador", "id_estudiante_examen") VALUES (301, '16:41:00', 5, '202101223', 1, 122);

INSERT INTO public.registro_asistencia ("id_ingreso", "hora_ingreso", "id_examen", "id_estudiante", "id_registrador", "id_estudiante_examen") VALUES (302, '16:52:00', 5, '202201234', 1, 123);

INSERT INTO public.registro_asistencia ("id_ingreso", "hora_ingreso", "id_examen", "id_estudiante", "id_registrador", "id_estudiante_examen") VALUES (303, '16:39:00', 5, '202201256', 2, 125);

INSERT INTO public.registro_asistencia ("id_ingreso", "hora_ingreso", "id_examen", "id_estudiante", "id_registrador", "id_estudiante_examen") VALUES (304, '16:29:00', 5, '202101267', 1, 126);

INSERT INTO public.registro_asistencia ("id_ingreso", "hora_ingreso", "id_examen", "id_estudiante", "id_registrador", "id_estudiante_examen") VALUES (305, '16:54:00', 5, '202101289', 2, 128);

INSERT INTO public.registro_asistencia ("id_ingreso", "hora_ingreso", "id_examen", "id_estudiante", "id_registrador", "id_estudiante_examen") VALUES (306, '16:20:00', 5, '202201290', 2, 129);

INSERT INTO public.registro_asistencia ("id_ingreso", "hora_ingreso", "id_examen", "id_estudiante", "id_registrador", "id_estudiante_examen") VALUES (307, '16:05:00', 5, '202201312', 2, 131);

INSERT INTO public.registro_asistencia ("id_ingreso", "hora_ingreso", "id_examen", "id_estudiante", "id_registrador", "id_estudiante_examen") VALUES (308, '16:24:00', 5, '202101323', 1, 132);

INSERT INTO public.registro_asistencia ("id_ingreso", "hora_ingreso", "id_examen", "id_estudiante", "id_registrador", "id_estudiante_examen") VALUES (309, '16:37:00', 5, '202101345', 1, 134);

INSERT INTO public.registro_asistencia ("id_ingreso", "hora_ingreso", "id_examen", "id_estudiante", "id_registrador", "id_estudiante_examen") VALUES (310, '16:02:00', 5, '202201356', 2, 135);

INSERT INTO public.registro_asistencia ("id_ingreso", "hora_ingreso", "id_examen", "id_estudiante", "id_registrador", "id_estudiante_examen") VALUES (311, '16:52:00', 5, '202201378', 1, 137);

INSERT INTO public.registro_asistencia ("id_ingreso", "hora_ingreso", "id_examen", "id_estudiante", "id_registrador", "id_estudiante_examen") VALUES (364, '07:04:09', 35, '202002107', 1, 756);

INSERT INTO public.registro_asistencia ("id_ingreso", "hora_ingreso", "id_examen", "id_estudiante", "id_registrador", "id_estudiante_examen") VALUES (365, '07:04:11', 35, '202101289', 1, 769);

INSERT INTO public.registro_asistencia ("id_ingreso", "hora_ingreso", "id_examen", "id_estudiante", "id_registrador", "id_estudiante_examen") VALUES (366, '07:04:14', 35, '202101323', 1, 771);

INSERT INTO public.registro_asistencia ("id_ingreso", "hora_ingreso", "id_examen", "id_estudiante", "id_registrador", "id_estudiante_examen") VALUES (367, '07:04:16', 35, '202201378', 1, 798);

INSERT INTO public.registro_asistencia ("id_ingreso", "hora_ingreso", "id_examen", "id_estudiante", "id_registrador", "id_estudiante_examen") VALUES (312, '16:10:00', 5, '202101389', 2, 138);

INSERT INTO public.registro_asistencia ("id_ingreso", "hora_ingreso", "id_examen", "id_estudiante", "id_registrador", "id_estudiante_examen") VALUES (313, '16:38:00', 5, '202101401', 1, 140);

INSERT INTO public.registro_asistencia ("id_ingreso", "hora_ingreso", "id_examen", "id_estudiante", "id_registrador", "id_estudiante_examen") VALUES (314, '16:45:00', 5, '202201412', 2, 141);

INSERT INTO public.registro_asistencia ("id_ingreso", "hora_ingreso", "id_examen", "id_estudiante", "id_registrador", "id_estudiante_examen") VALUES (315, '16:10:00', 5, '202201434', 2, 143);

INSERT INTO public.registro_asistencia ("id_ingreso", "hora_ingreso", "id_examen", "id_estudiante", "id_registrador", "id_estudiante_examen") VALUES (316, '16:53:00', 5, '202101445', 1, 144);

INSERT INTO public.registro_asistencia ("id_ingreso", "hora_ingreso", "id_examen", "id_estudiante", "id_registrador", "id_estudiante_examen") VALUES (317, '16:31:00', 5, '202101467', 2, 146);

INSERT INTO public.registro_asistencia ("id_ingreso", "hora_ingreso", "id_examen", "id_estudiante", "id_registrador", "id_estudiante_examen") VALUES (318, '16:09:00', 5, '202201478', 2, 147);

INSERT INTO public.registro_asistencia ("id_ingreso", "hora_ingreso", "id_examen", "id_estudiante", "id_registrador", "id_estudiante_examen") VALUES (319, '16:17:00', 5, '202201490', 1, 149);

INSERT INTO public.registro_asistencia ("id_ingreso", "hora_ingreso", "id_examen", "id_estudiante", "id_registrador", "id_estudiante_examen") VALUES (320, '08:05:00', 14, '202101178', 3, 166);

INSERT INTO public.registro_asistencia ("id_ingreso", "hora_ingreso", "id_examen", "id_estudiante", "id_registrador", "id_estudiante_examen") VALUES (321, '08:10:00', 14, '202101467', 3, 195);

INSERT INTO public.registro_asistencia ("id_ingreso", "hora_ingreso", "id_examen", "id_estudiante", "id_registrador", "id_estudiante_examen") VALUES (322, '08:15:00', 14, '202101090', 3, 158);

INSERT INTO public.registro_asistencia ("id_ingreso", "hora_ingreso", "id_examen", "id_estudiante", "id_registrador", "id_estudiante_examen") VALUES (323, '08:20:00', 14, '202201478', 3, 196);

INSERT INTO public.registro_asistencia ("id_ingreso", "hora_ingreso", "id_examen", "id_estudiante", "id_registrador", "id_estudiante_examen") VALUES (324, '08:25:00', 14, '202101323', 3, 181);

INSERT INTO public.registro_asistencia ("id_ingreso", "hora_ingreso", "id_examen", "id_estudiante", "id_registrador", "id_estudiante_examen") VALUES (325, '08:30:00', 14, '202101345', 3, 183);

INSERT INTO public.registro_asistencia ("id_ingreso", "hora_ingreso", "id_examen", "id_estudiante", "id_registrador", "id_estudiante_examen") VALUES (326, '08:35:00', 14, '202201212', 3, 170);

INSERT INTO public.registro_asistencia ("id_ingreso", "hora_ingreso", "id_examen", "id_estudiante", "id_registrador", "id_estudiante_examen") VALUES (327, '08:40:00', 14, '202201412', 3, 190);

INSERT INTO public.registro_asistencia ("id_ingreso", "hora_ingreso", "id_examen", "id_estudiante", "id_registrador", "id_estudiante_examen") VALUES (328, '08:45:00', 14, '202101289', 3, 177);

INSERT INTO public.registro_asistencia ("id_ingreso", "hora_ingreso", "id_examen", "id_estudiante", "id_registrador", "id_estudiante_examen") VALUES (329, '08:50:00', 14, '202101223', 3, 171);

INSERT INTO public.registro_asistencia ("id_ingreso", "hora_ingreso", "id_examen", "id_estudiante", "id_registrador", "id_estudiante_examen") VALUES (330, '20:37:39', 14, '202201434', 1, 192);

INSERT INTO public.registro_asistencia ("id_ingreso", "hora_ingreso", "id_examen", "id_estudiante", "id_registrador", "id_estudiante_examen") VALUES (331, '20:38:00', 14, '202201390', 1, 188);

INSERT INTO public.registro_asistencia ("id_ingreso", "hora_ingreso", "id_examen", "id_estudiante", "id_registrador", "id_estudiante_examen") VALUES (332, '22:22:56', 14, '202201123', 1, 161);

INSERT INTO public.registro_asistencia ("id_ingreso", "hora_ingreso", "id_examen", "id_estudiante", "id_registrador", "id_estudiante_examen") VALUES (333, '01:43:31', 14, '202202045', 1, 153);

INSERT INTO public.registro_asistencia ("id_ingreso", "hora_ingreso", "id_examen", "id_estudiante", "id_registrador", "id_estudiante_examen") VALUES (334, '02:13:01', 14, '202201201', 1, 169);

INSERT INTO public.registro_asistencia ("id_ingreso", "hora_ingreso", "id_examen", "id_estudiante", "id_registrador", "id_estudiante_examen") VALUES (335, '03:38:36', 14, '202101134', 1, 162);

INSERT INTO public.registro_asistencia ("id_ingreso", "hora_ingreso", "id_examen", "id_estudiante", "id_registrador", "id_estudiante_examen") VALUES (336, '01:51:36', 14, '202201145', 1, 163);

INSERT INTO public.registro_asistencia ("id_ingreso", "hora_ingreso", "id_examen", "id_estudiante", "id_registrador", "id_estudiante_examen") VALUES (337, '18:21:49', 14, '202101423', 2, 191);


INSERT INTO public.notificacion_docente ("id_notificacion", "id_central_riesgo", "id_curso", "estado", "oculta", "tipo", "id_docente_destinatario", "id_invitacion") VALUES (2, 2, 2, 'recibido', FALSE, 'posible_tramposo', NULL, NULL);

INSERT INTO public.notificacion_docente ("id_notificacion", "id_central_riesgo", "id_curso", "estado", "oculta", "tipo", "id_docente_destinatario", "id_invitacion") VALUES (3, 3, 3, 'visto', FALSE, 'posible_tramposo', NULL, NULL);

INSERT INTO public.notificacion_docente ("id_notificacion", "id_central_riesgo", "id_curso", "estado", "oculta", "tipo", "id_docente_destinatario", "id_invitacion") VALUES (5, 5, 5, 'visto', FALSE, 'posible_tramposo', NULL, NULL);

INSERT INTO public.notificacion_docente ("id_notificacion", "id_central_riesgo", "id_curso", "estado", "oculta", "tipo", "id_docente_destinatario", "id_invitacion") VALUES (6, 17, 3, 'recibido', FALSE, 'central_riesgo', NULL, NULL);

INSERT INTO public.notificacion_docente ("id_notificacion", "id_central_riesgo", "id_curso", "estado", "oculta", "tipo", "id_docente_destinatario", "id_invitacion") VALUES (4, 4, 4, 'recibido', TRUE, 'posible_tramposo', NULL, NULL);

INSERT INTO public.notificacion_docente ("id_notificacion", "id_central_riesgo", "id_curso", "estado", "oculta", "tipo", "id_docente_destinatario", "id_invitacion") VALUES (1, 1, 1, 'visto', TRUE, 'posible_tramposo', NULL, NULL);

INSERT INTO public.notificacion_docente ("id_notificacion", "id_central_riesgo", "id_curso", "estado", "oculta", "tipo", "id_docente_destinatario", "id_invitacion") VALUES (7, 18, 3, 'recibido', FALSE, 'central_riesgo', NULL, NULL);

INSERT INTO public.notificacion_docente ("id_notificacion", "id_central_riesgo", "id_curso", "estado", "oculta", "tipo", "id_docente_destinatario", "id_invitacion") VALUES (8, 19, 3, 'recibido', FALSE, 'central_riesgo', NULL, NULL);

INSERT INTO public.notificacion_docente ("id_notificacion", "id_central_riesgo", "id_curso", "estado", "oculta", "tipo", "id_docente_destinatario", "id_invitacion") VALUES (9, 20, 3, 'recibido', FALSE, 'central_riesgo', NULL, NULL);

INSERT INTO public.notificacion_docente ("id_notificacion", "id_central_riesgo", "id_curso", "estado", "oculta", "tipo", "id_docente_destinatario", "id_invitacion") VALUES (10, 21, 3, 'recibido', FALSE, 'central_riesgo', NULL, NULL);

INSERT INTO public.notificacion_docente ("id_notificacion", "id_central_riesgo", "id_curso", "estado", "oculta", "tipo", "id_docente_destinatario", "id_invitacion") VALUES (11, 22, 3, 'recibido', FALSE, 'central_riesgo', NULL, NULL);

INSERT INTO public.notificacion_docente ("id_notificacion", "id_central_riesgo", "id_curso", "estado", "oculta", "tipo", "id_docente_destinatario", "id_invitacion") VALUES (12, NULL, NULL, 'recibido', TRUE, 'invitacion_examen', 5, 6);

INSERT INTO public.notificacion_docente ("id_notificacion", "id_central_riesgo", "id_curso", "estado", "oculta", "tipo", "id_docente_destinatario", "id_invitacion") VALUES (13, NULL, NULL, 'recibido', FALSE, 'respuesta_invitacion', 1, 7);

INSERT INTO public.notificacion_docente ("id_notificacion", "id_central_riesgo", "id_curso", "estado", "oculta", "tipo", "id_docente_destinatario", "id_invitacion") VALUES (14, NULL, NULL, 'recibido', TRUE, 'invitacion_examen', 5, 8);

INSERT INTO public.notificacion_docente ("id_notificacion", "id_central_riesgo", "id_curso", "estado", "oculta", "tipo", "id_docente_destinatario", "id_invitacion") VALUES (15, NULL, NULL, 'recibido', FALSE, 'respuesta_invitacion', 1, 9);

INSERT INTO public.notificacion_docente ("id_notificacion", "id_central_riesgo", "id_curso", "estado", "oculta", "tipo", "id_docente_destinatario", "id_invitacion") VALUES (16, 24, 3, 'recibido', FALSE, 'central_riesgo', NULL, NULL);

INSERT INTO public.notificacion_docente ("id_notificacion", "id_central_riesgo", "id_curso", "estado", "oculta", "tipo", "id_docente_destinatario", "id_invitacion") VALUES (17, 25, 3, 'recibido', FALSE, 'central_riesgo', NULL, NULL);

INSERT INTO public.notificacion_docente ("id_notificacion", "id_central_riesgo", "id_curso", "estado", "oculta", "tipo", "id_docente_destinatario", "id_invitacion") VALUES (18, 26, 3, 'recibido', FALSE, 'central_riesgo', NULL, NULL);

INSERT INTO public.notificacion_docente ("id_notificacion", "id_central_riesgo", "id_curso", "estado", "oculta", "tipo", "id_docente_destinatario", "id_invitacion") VALUES (19, 27, 3, 'recibido', FALSE, 'central_riesgo', NULL, NULL);

INSERT INTO public.notificacion_docente ("id_notificacion", "id_central_riesgo", "id_curso", "estado", "oculta", "tipo", "id_docente_destinatario", "id_invitacion") VALUES (20, 28, 3, 'recibido', FALSE, 'central_riesgo', NULL, NULL);

INSERT INTO public.notificacion_docente ("id_notificacion", "id_central_riesgo", "id_curso", "estado", "oculta", "tipo", "id_docente_destinatario", "id_invitacion") VALUES (21, 31, 3, 'recibido', FALSE, 'posible_tramposo', NULL, NULL);

INSERT INTO public.notificacion_docente ("id_notificacion", "id_central_riesgo", "id_curso", "estado", "oculta", "tipo", "id_docente_destinatario", "id_invitacion") VALUES (22, 32, 3, 'recibido', FALSE, 'central_riesgo', NULL, NULL);



-- =====================
--  SEQUENCE VALUES
-- =====================

SELECT pg_catalog.setval('public.configuracion_examen_docente_id_configuracion_examen_seq', 54, true);

SELECT pg_catalog.setval('public.configuracion_material_personaliz_id_configuracion_material_seq', 11, true);

SELECT pg_catalog.setval('public.configuracion_norma_personalizada_id_configuracion_norma_seq', 22, true);

SELECT pg_catalog.setval('public.migrations_id_seq', 21, true);

SELECT pg_catalog.setval('public.users_id_seq', , false);

SELECT pg_catalog.setval('public.jobs_id_seq', , false);

SELECT pg_catalog.setval('public.failed_jobs_id_seq', , false);

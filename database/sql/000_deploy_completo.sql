-- ============================================================================
--  TECH ONE / T1 - DUMP COMPLETO: esquema + datos + tablas de Laravel
--  Motor: PostgreSQL
--  Subir via: https://techone.tis.cs.umss.edu.bo/phppgadmin/  ->  SQL
--
--  ------------------------------------------------------------------
--  COMO USAR (importante)
--  ------------------------------------------------------------------
--  1. Desmarca la casilla "Paginar resultados" / "Paginate results".
--     Si la dejas marcada, phpPgAdmin envuelve el script en
--     SELECT COUNT(*) AS total FROM ( ... ) AS sub y TODO falla con
--     "syntax error at or near CREATE", aunque el SQL este perfecto.
--
--  2. Pega este contenido completo en el textarea y dale "Ejecutar".
--     (O usa el input "Cargar un archivo" de esa misma pagina.)
--
--  3. La base seleccionada tiene que ser la misma a la que apunta
--     DB_DATABASE en el .env del servidor: techone_db.
--
--  ------------------------------------------------------------------
--  QUE CONTIENE
--  ------------------------------------------------------------------
--  - Los 11 tipos ENUM y las 33 tablas del proyecto (25 del dominio +
--    8 de Laravel) mas la tabla migrations.
--  - Todos los datos de la base de desarrollo: los 5 usuarios de prueba
--    con sus passwords ya hasheados, 54 estudiantes, 5 examenes, 31
--    registros de asistencia, etc.
--  - Los sequences quedan sincronizados (setval), asi que los INSERT que
--    haga la aplicacion despues no chocan con las filas ya cargadas.
--
--  ------------------------------------------------------------------
--  BASE VACIA O YA CARGADA
--  ------------------------------------------------------------------
--  Este script NO es idempotente: crea tipos y tablas. Si la base ya
--  tiene las 25 tablas, no lo vuelvas a correr (tira "already exists").
--  Para una base ya existente usa 004_actualizar_usuario.sql.
--
--  ------------------------------------------------------------------
--  COMO SE GENERO
--  ------------------------------------------------------------------
--  pg_dump -h 127.0.0.1 -U techone -d techone_db \
--          --no-owner --no-privileges --encoding=UTF8 --inserts
--
--  El flag --inserts es obligatorio aca: sin el, pg_dump escribe COPY
--  FROM stdin, y phpPgAdmin no sabe ejecutar COPY. Igual se le quitan
--  las lineas \restrict / \unrestrict, que son meta-comandos de psql y
--  tambien rompen phpPgAdmin.
-- ============================================================================

--
-- PostgreSQL database dump
--

-- Dumped from database version 15.19
-- Dumped by pg_dump version 15.19

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

--
-- Name: curso_estado; Type: TYPE; Schema: public; Owner: -
--

CREATE TYPE public.curso_estado AS ENUM (
    'EnCurso',
    'Finalizo'
);


--
-- Name: estado_incidencia; Type: TYPE; Schema: public; Owner: -
--

CREATE TYPE public.estado_incidencia AS ENUM (
    'Confirmado',
    'Pendiente'
);


--
-- Name: estado_notificacion; Type: TYPE; Schema: public; Owner: -
--

CREATE TYPE public.estado_notificacion AS ENUM (
    'visto',
    'recibido'
);


--
-- Name: estado_usuario; Type: TYPE; Schema: public; Owner: -
--

CREATE TYPE public.estado_usuario AS ENUM (
    'Activo',
    'Baja'
);


--
-- Name: estudiante_examen_estado; Type: TYPE; Schema: public; Owner: -
--

CREATE TYPE public.estudiante_examen_estado AS ENUM (
    'habilitado',
    'deshabilitado'
);


--
-- Name: invitacion_estado; Type: TYPE; Schema: public; Owner: -
--

CREATE TYPE public.invitacion_estado AS ENUM (
    'aceptado',
    'rechazado',
    'pendiente'
);


--
-- Name: nombre_tipo_gestion; Type: TYPE; Schema: public; Owner: -
--

CREATE TYPE public.nombre_tipo_gestion AS ENUM (
    'Primer sem',
    'inv',
    'Seg sem',
    'ver'
);


--
-- Name: registrador_tipo; Type: TYPE; Schema: public; Owner: -
--

CREATE TYPE public.registrador_tipo AS ENUM (
    'docente',
    'auxiliar'
);


--
-- Name: roles; Type: TYPE; Schema: public; Owner: -
--

CREATE TYPE public.roles AS ENUM (
    'docente',
    'auxiliar'
);


--
-- Name: tipo_examen_nombre; Type: TYPE; Schema: public; Owner: -
--

CREATE TYPE public.tipo_examen_nombre AS ENUM (
    'PP',
    'SP',
    'FINAL',
    'SI',
    'PARCIAL',
    'PRACTICA'
);


--
-- Name: tipo_infraccion; Type: TYPE; Schema: public; Owner: -
--

CREATE TYPE public.tipo_infraccion AS ENUM (
    'tramposo',
    'sospechoso',
    'pendiente',
    'aula equivocada'
);


SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: ambiente; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.ambiente (
    id_ambiente integer NOT NULL,
    nombre_ambiente character varying(50) NOT NULL
);


--
-- Name: auxiliar_curso; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.auxiliar_curso (
    id_auxiliar integer NOT NULL,
    id_curso integer NOT NULL,
    estado public.estado_usuario NOT NULL
);


--
-- Name: cache; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cache (
    key character varying(255) NOT NULL,
    value text NOT NULL,
    expiration integer NOT NULL
);


--
-- Name: cache_locks; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cache_locks (
    key character varying(255) NOT NULL,
    owner character varying(255) NOT NULL,
    expiration integer NOT NULL
);


--
-- Name: central_riesgo; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.central_riesgo (
    id_registro integer NOT NULL,
    id_ingreso integer NOT NULL,
    id_registrador integer NOT NULL,
    detalle_motivo character varying(255),
    fecha_registro date,
    tipo_infraccion public.tipo_infraccion NOT NULL,
    estado_incidencia public.estado_incidencia NOT NULL
);


--
-- Name: curso; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.curso (
    id_curso integer NOT NULL,
    nombre_curso character varying(100) NOT NULL,
    sis_doc integer NOT NULL,
    fecha_creacion date,
    estado public.curso_estado NOT NULL
);


--
-- Name: curso_tipo_gestion; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.curso_tipo_gestion (
    id_curso integer NOT NULL,
    id_tg integer NOT NULL
);


--
-- Name: estudiante; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.estudiante (
    sis_estudiante character varying(20) NOT NULL,
    nombre_estudiante character varying(50) NOT NULL,
    apellido_estudiante character varying(50) NOT NULL,
    carrera character varying(80)
);


--
-- Name: estudiante_curso; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.estudiante_curso (
    sis_estudiante character varying(20) NOT NULL,
    id_curso integer NOT NULL
);


--
-- Name: estudiante_examen; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.estudiante_examen (
    id_estudiante_examen integer NOT NULL,
    sis_estudiante character varying(20) NOT NULL,
    id_examen integer NOT NULL,
    estado public.estudiante_examen_estado NOT NULL,
    motivo character varying(255),
    modificado_por integer,
    fecha_modificacion timestamp without time zone
);


--
-- Name: estudiante_examen_ambiente; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.estudiante_examen_ambiente (
    id_ee integer NOT NULL,
    id_ambiente integer NOT NULL
);


--
-- Name: examen; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.examen (
    id_examen integer NOT NULL,
    fecha date,
    hora_inicio time without time zone,
    hora_fin time without time zone,
    duracion integer,
    creador integer NOT NULL,
    tipo_examen integer NOT NULL
);


--
-- Name: examen_ambiente; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.examen_ambiente (
    id_examen integer NOT NULL,
    id_ambiente integer NOT NULL
);


--
-- Name: examen_curso; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.examen_curso (
    id_examen integer NOT NULL,
    id_curso integer NOT NULL
);


--
-- Name: examen_material_permitido; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.examen_material_permitido (
    id_examen integer NOT NULL,
    id_material_permitido integer NOT NULL
);


--
-- Name: examen_norma; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.examen_norma (
    id_examen integer NOT NULL,
    id_norma integer NOT NULL
);


--
-- Name: failed_jobs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.failed_jobs (
    id bigint NOT NULL,
    uuid character varying(255) NOT NULL,
    connection text NOT NULL,
    queue text NOT NULL,
    payload text NOT NULL,
    exception text NOT NULL,
    failed_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.failed_jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.failed_jobs_id_seq OWNED BY public.failed_jobs.id;


--
-- Name: invitacion_examen_compartido; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.invitacion_examen_compartido (
    id_invitacion integer NOT NULL,
    id_docente_invitado integer NOT NULL,
    id_examen integer NOT NULL,
    estado public.invitacion_estado NOT NULL
);


--
-- Name: job_batches; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.job_batches (
    id character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    total_jobs integer NOT NULL,
    pending_jobs integer NOT NULL,
    failed_jobs integer NOT NULL,
    failed_job_ids text NOT NULL,
    options text,
    cancelled_at integer,
    created_at integer NOT NULL,
    finished_at integer
);


--
-- Name: jobs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.jobs (
    id bigint NOT NULL,
    queue character varying(255) NOT NULL,
    payload text NOT NULL,
    attempts smallint NOT NULL,
    reserved_at integer,
    available_at integer NOT NULL,
    created_at integer NOT NULL
);


--
-- Name: jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.jobs_id_seq OWNED BY public.jobs.id;


--
-- Name: material; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.material (
    id_material integer NOT NULL,
    descripcion character varying(100) NOT NULL
);


--
-- Name: migrations; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.migrations (
    id integer NOT NULL,
    migration character varying(255) NOT NULL,
    batch integer NOT NULL
);


--
-- Name: migrations_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.migrations_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: migrations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.migrations_id_seq OWNED BY public.migrations.id;


--
-- Name: norma; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.norma (
    id_norma integer NOT NULL,
    detalle_norma character varying(255) NOT NULL
);


--
-- Name: notificacion_auxiliar; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.notificacion_auxiliar (
    id_notificacion integer NOT NULL,
    id_curso integer NOT NULL,
    id_ambiente integer NOT NULL,
    estado public.estado_notificacion NOT NULL
);


--
-- Name: notificacion_docente; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.notificacion_docente (
    id_notificacion integer NOT NULL,
    id_central_riesgo integer NOT NULL,
    id_curso integer NOT NULL,
    estado public.estado_notificacion NOT NULL
);


--
-- Name: password_reset_tokens; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.password_reset_tokens (
    email character varying(255) NOT NULL,
    token character varying(255) NOT NULL,
    created_at timestamp(0) without time zone
);


--
-- Name: registro_asistencia; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.registro_asistencia (
    id_ingreso integer NOT NULL,
    hora_ingreso time without time zone,
    id_examen integer NOT NULL,
    id_estudiante character varying(20) NOT NULL,
    id_registrador integer NOT NULL
);


--
-- Name: rol; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.rol (
    id_rol integer NOT NULL,
    nombre_rol public.roles NOT NULL
);


--
-- Name: rol_usuario; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.rol_usuario (
    id_usuario integer NOT NULL,
    id_rol integer NOT NULL
);


--
-- Name: sessions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.sessions (
    id character varying(255) NOT NULL,
    user_id bigint,
    ip_address character varying(45),
    user_agent text,
    payload text NOT NULL,
    last_activity integer NOT NULL
);


--
-- Name: tipo_examen; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tipo_examen (
    id_tipo_examen integer NOT NULL,
    nombre_tipo_examen public.tipo_examen_nombre NOT NULL
);


--
-- Name: tipo_gestion; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tipo_gestion (
    id_tipo_gestion integer NOT NULL,
    nombre_tipo_gestion public.nombre_tipo_gestion NOT NULL
);


--
-- Name: users; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.users (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    email character varying(255) NOT NULL,
    email_verified_at timestamp(0) without time zone,
    password character varying(255) NOT NULL,
    remember_token character varying(100),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;


--
-- Name: usuario; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.usuario (
    id_usuario integer NOT NULL,
    cod_sis character varying(20) NOT NULL,
    password character varying(100) NOT NULL,
    nombre_usuario character varying(50) NOT NULL,
    apellido character varying(50) NOT NULL
);


--
-- Name: failed_jobs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs ALTER COLUMN id SET DEFAULT nextval('public.failed_jobs_id_seq'::regclass);


--
-- Name: jobs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jobs ALTER COLUMN id SET DEFAULT nextval('public.jobs_id_seq'::regclass);


--
-- Name: migrations id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.migrations ALTER COLUMN id SET DEFAULT nextval('public.migrations_id_seq'::regclass);


--
-- Name: users id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


--
-- Data for Name: ambiente; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.ambiente VALUES (1, 'Aula 101');
INSERT INTO public.ambiente VALUES (2, 'Aula 102');
INSERT INTO public.ambiente VALUES (3, 'Laboratorio A');
INSERT INTO public.ambiente VALUES (4, 'Laboratorio B');
INSERT INTO public.ambiente VALUES (5, 'Aula Magna');


--
-- Data for Name: auxiliar_curso; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.auxiliar_curso VALUES (3, 1, 'Activo');
INSERT INTO public.auxiliar_curso VALUES (4, 2, 'Activo');
INSERT INTO public.auxiliar_curso VALUES (3, 3, 'Activo');
INSERT INTO public.auxiliar_curso VALUES (4, 4, 'Baja');
INSERT INTO public.auxiliar_curso VALUES (3, 5, 'Activo');


--
-- Data for Name: cache; Type: TABLE DATA; Schema: public; Owner: -
--



--
-- Data for Name: cache_locks; Type: TABLE DATA; Schema: public; Owner: -
--



--
-- Data for Name: central_riesgo; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.central_riesgo VALUES (1, 1, 3, 'Estudiante mirando hacia otro lado', '2024-06-10', 'sospechoso', 'Pendiente');
INSERT INTO public.central_riesgo VALUES (2, 2, 4, 'Uso de celular durante el examen', '2024-06-11', 'tramposo', 'Confirmado');
INSERT INTO public.central_riesgo VALUES (3, 3, 3, 'Llegada tarde al examen', '2024-06-12', 'pendiente', 'Pendiente');
INSERT INTO public.central_riesgo VALUES (4, 4, 4, 'Estudiante en aula equivocada', '2024-06-13', 'aula equivocada', 'Confirmado');


--
-- Data for Name: curso; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.curso VALUES (1, 'Calculo I', 1, '2024-02-01', 'EnCurso');
INSERT INTO public.curso VALUES (2, 'Fisica I', 2, '2024-02-05', 'EnCurso');
INSERT INTO public.curso VALUES (3, 'Programacion I', 5, '2024-02-10', 'EnCurso');
INSERT INTO public.curso VALUES (4, 'Algebra Lineal', 1, '2024-02-15', 'Finalizo');
INSERT INTO public.curso VALUES (5, 'Quimica General', 2, '2024-02-20', 'EnCurso');


--
-- Data for Name: curso_tipo_gestion; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.curso_tipo_gestion VALUES (1, 1);
INSERT INTO public.curso_tipo_gestion VALUES (2, 1);
INSERT INTO public.curso_tipo_gestion VALUES (3, 1);
INSERT INTO public.curso_tipo_gestion VALUES (4, 2);
INSERT INTO public.curso_tipo_gestion VALUES (5, 3);


--
-- Data for Name: estudiante; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.estudiante VALUES ('20210001', 'Juan', 'Perez', 'Ingenieria de Sistemas');
INSERT INTO public.estudiante VALUES ('20210002', 'Maria', 'Lopez', 'Ingenieria Civil');
INSERT INTO public.estudiante VALUES ('20210003', 'Carlos', 'Gomez', 'Ingenieria Electronica');
INSERT INTO public.estudiante VALUES ('20210004', 'Ana', 'Martinez', 'Ingenieria Industrial');
INSERT INTO public.estudiante VALUES ('20210005', 'Luis', 'Fernandez', 'Ingenieria de Sistemas');
INSERT INTO public.estudiante VALUES ('202201013', 'Ana', 'López', NULL);
INSERT INTO public.estudiante VALUES ('202101022', 'Bruno', 'Díaz', NULL);
INSERT INTO public.estudiante VALUES ('202201031', 'Carla', 'Ruiz', NULL);
INSERT INTO public.estudiante VALUES ('202202045', 'Diego', 'Soto', NULL);
INSERT INTO public.estudiante VALUES ('202002107', 'Ernesto', 'Vera', NULL);
INSERT INTO public.estudiante VALUES ('202201056', 'Fátima', 'Quispe', NULL);
INSERT INTO public.estudiante VALUES ('202101078', 'Gabriel', 'Mamani', NULL);
INSERT INTO public.estudiante VALUES ('202201089', 'Helena', 'Choque', NULL);
INSERT INTO public.estudiante VALUES ('202101090', 'Iván', 'Flores', NULL);
INSERT INTO public.estudiante VALUES ('202201101', 'Julia', 'García', NULL);
INSERT INTO public.estudiante VALUES ('202101112', 'Kevin', 'López', NULL);
INSERT INTO public.estudiante VALUES ('202201123', 'Laura', 'Díaz', NULL);
INSERT INTO public.estudiante VALUES ('202101134', 'Miguel', 'Soto', NULL);
INSERT INTO public.estudiante VALUES ('202201145', 'Nadia', 'Vera', NULL);
INSERT INTO public.estudiante VALUES ('202101156', 'Óscar', 'Quispe', NULL);
INSERT INTO public.estudiante VALUES ('202201167', 'Patricia', 'Mamani', NULL);
INSERT INTO public.estudiante VALUES ('202101178', 'Raúl', 'Choque', NULL);
INSERT INTO public.estudiante VALUES ('202201189', 'Sofía', 'Flores', NULL);
INSERT INTO public.estudiante VALUES ('202101190', 'Tomás', 'García', NULL);
INSERT INTO public.estudiante VALUES ('202201201', 'Valeria', 'López', NULL);
INSERT INTO public.estudiante VALUES ('202201212', 'Wendy', 'Zapata', NULL);
INSERT INTO public.estudiante VALUES ('202101223', 'Xavier', 'Vargas', NULL);
INSERT INTO public.estudiante VALUES ('202201234', 'Yolanda', 'Ticona', NULL);
INSERT INTO public.estudiante VALUES ('202101245', 'Zoe', 'Siles', NULL);
INSERT INTO public.estudiante VALUES ('202201256', 'Andrés', 'Rojas', NULL);
INSERT INTO public.estudiante VALUES ('202101267', 'Bianca', 'Paredes', NULL);
INSERT INTO public.estudiante VALUES ('202201278', 'Cristian', 'Molina', NULL);
INSERT INTO public.estudiante VALUES ('202101289', 'Daniela', 'Cabrera', NULL);
INSERT INTO public.estudiante VALUES ('202201290', 'Eduardo', 'Salazar', NULL);
INSERT INTO public.estudiante VALUES ('202101301', 'Fernanda', 'Vega', NULL);
INSERT INTO public.estudiante VALUES ('202201312', 'Gonzalo', 'Arce', NULL);
INSERT INTO public.estudiante VALUES ('202101323', 'Hugo', 'Camacho', NULL);
INSERT INTO public.estudiante VALUES ('202201334', 'Inés', 'Delgado', NULL);
INSERT INTO public.estudiante VALUES ('202101345', 'Javier', 'Espinoza', NULL);
INSERT INTO public.estudiante VALUES ('202201356', 'Karen', 'Fuentes', NULL);
INSERT INTO public.estudiante VALUES ('202101367', 'Luis', 'Gutiérrez', NULL);
INSERT INTO public.estudiante VALUES ('202201378', 'María', 'Herrera', NULL);
INSERT INTO public.estudiante VALUES ('202101389', 'Nicolás', 'Iriarte', NULL);
INSERT INTO public.estudiante VALUES ('202201390', 'Olga', 'Jiménez', NULL);
INSERT INTO public.estudiante VALUES ('202101401', 'Pablo', 'Kowalski', NULL);
INSERT INTO public.estudiante VALUES ('202201412', 'Rosa', 'Luna', NULL);
INSERT INTO public.estudiante VALUES ('202101423', 'Sergio', 'Mendoza', NULL);
INSERT INTO public.estudiante VALUES ('202201434', 'Tatiana', 'Núñez', NULL);
INSERT INTO public.estudiante VALUES ('202101445', 'Ulises', 'Orellana', NULL);
INSERT INTO public.estudiante VALUES ('202201456', 'Verónica', 'Paz', NULL);
INSERT INTO public.estudiante VALUES ('202101467', 'Walter', 'Quiroga', NULL);
INSERT INTO public.estudiante VALUES ('202201478', 'Ximena', 'Ramírez', NULL);
INSERT INTO public.estudiante VALUES ('202101489', 'Yamil', 'Sánchez', NULL);
INSERT INTO public.estudiante VALUES ('202201490', 'Zaira', 'Toledo', NULL);


--
-- Data for Name: estudiante_curso; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.estudiante_curso VALUES ('20210001', 1);
INSERT INTO public.estudiante_curso VALUES ('20210002', 2);
INSERT INTO public.estudiante_curso VALUES ('20210003', 3);
INSERT INTO public.estudiante_curso VALUES ('20210004', 4);
INSERT INTO public.estudiante_curso VALUES ('20210005', 5);


--
-- Data for Name: estudiante_examen; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.estudiante_examen VALUES (1, '20210001', 1, 'habilitado', NULL, NULL, NULL);
INSERT INTO public.estudiante_examen VALUES (2, '20210002', 2, 'habilitado', NULL, NULL, NULL);
INSERT INTO public.estudiante_examen VALUES (3, '20210003', 3, 'deshabilitado', 'No cumple requisitos', NULL, NULL);
INSERT INTO public.estudiante_examen VALUES (4, '20210004', 4, 'habilitado', NULL, NULL, NULL);
INSERT INTO public.estudiante_examen VALUES (101, '202201013', 5, 'habilitado', NULL, NULL, NULL);
INSERT INTO public.estudiante_examen VALUES (102, '202101022', 5, 'habilitado', NULL, NULL, NULL);
INSERT INTO public.estudiante_examen VALUES (103, '202201031', 5, 'habilitado', NULL, NULL, NULL);
INSERT INTO public.estudiante_examen VALUES (104, '202202045', 5, 'deshabilitado', NULL, NULL, NULL);
INSERT INTO public.estudiante_examen VALUES (105, '202002107', 5, 'habilitado', NULL, NULL, NULL);
INSERT INTO public.estudiante_examen VALUES (106, '202201056', 5, 'habilitado', NULL, NULL, NULL);
INSERT INTO public.estudiante_examen VALUES (107, '202101078', 5, 'deshabilitado', NULL, NULL, NULL);
INSERT INTO public.estudiante_examen VALUES (108, '202201089', 5, 'habilitado', NULL, NULL, NULL);
INSERT INTO public.estudiante_examen VALUES (109, '202101090', 5, 'habilitado', NULL, NULL, NULL);
INSERT INTO public.estudiante_examen VALUES (110, '202201101', 5, 'habilitado', NULL, NULL, NULL);
INSERT INTO public.estudiante_examen VALUES (111, '202101112', 5, 'habilitado', NULL, NULL, NULL);
INSERT INTO public.estudiante_examen VALUES (112, '202201123', 5, 'habilitado', NULL, NULL, NULL);
INSERT INTO public.estudiante_examen VALUES (113, '202101134', 5, 'deshabilitado', NULL, NULL, NULL);
INSERT INTO public.estudiante_examen VALUES (114, '202201145', 5, 'habilitado', NULL, NULL, NULL);
INSERT INTO public.estudiante_examen VALUES (115, '202101156', 5, 'habilitado', NULL, NULL, NULL);
INSERT INTO public.estudiante_examen VALUES (116, '202201167', 5, 'habilitado', NULL, NULL, NULL);
INSERT INTO public.estudiante_examen VALUES (117, '202101178', 5, 'habilitado', NULL, NULL, NULL);
INSERT INTO public.estudiante_examen VALUES (118, '202201189', 5, 'habilitado', NULL, NULL, NULL);
INSERT INTO public.estudiante_examen VALUES (119, '202101190', 5, 'deshabilitado', NULL, NULL, NULL);
INSERT INTO public.estudiante_examen VALUES (120, '202201201', 5, 'habilitado', NULL, NULL, NULL);
INSERT INTO public.estudiante_examen VALUES (121, '202201212', 5, 'habilitado', NULL, NULL, NULL);
INSERT INTO public.estudiante_examen VALUES (122, '202101223', 5, 'habilitado', NULL, NULL, NULL);
INSERT INTO public.estudiante_examen VALUES (123, '202201234', 5, 'habilitado', NULL, NULL, NULL);
INSERT INTO public.estudiante_examen VALUES (124, '202101245', 5, 'habilitado', NULL, NULL, NULL);
INSERT INTO public.estudiante_examen VALUES (125, '202201256', 5, 'habilitado', NULL, NULL, NULL);
INSERT INTO public.estudiante_examen VALUES (126, '202101267', 5, 'habilitado', NULL, NULL, NULL);
INSERT INTO public.estudiante_examen VALUES (127, '202201278', 5, 'habilitado', NULL, NULL, NULL);
INSERT INTO public.estudiante_examen VALUES (128, '202101289', 5, 'habilitado', NULL, NULL, NULL);
INSERT INTO public.estudiante_examen VALUES (129, '202201290', 5, 'habilitado', NULL, NULL, NULL);
INSERT INTO public.estudiante_examen VALUES (130, '202101301', 5, 'habilitado', NULL, NULL, NULL);
INSERT INTO public.estudiante_examen VALUES (131, '202201312', 5, 'habilitado', NULL, NULL, NULL);
INSERT INTO public.estudiante_examen VALUES (132, '202101323', 5, 'habilitado', NULL, NULL, NULL);
INSERT INTO public.estudiante_examen VALUES (133, '202201334', 5, 'habilitado', NULL, NULL, NULL);
INSERT INTO public.estudiante_examen VALUES (134, '202101345', 5, 'habilitado', NULL, NULL, NULL);
INSERT INTO public.estudiante_examen VALUES (135, '202201356', 5, 'habilitado', NULL, NULL, NULL);
INSERT INTO public.estudiante_examen VALUES (136, '202101367', 5, 'habilitado', NULL, NULL, NULL);
INSERT INTO public.estudiante_examen VALUES (137, '202201378', 5, 'habilitado', NULL, NULL, NULL);
INSERT INTO public.estudiante_examen VALUES (138, '202101389', 5, 'habilitado', NULL, NULL, NULL);
INSERT INTO public.estudiante_examen VALUES (139, '202201390', 5, 'habilitado', NULL, NULL, NULL);
INSERT INTO public.estudiante_examen VALUES (140, '202101401', 5, 'habilitado', NULL, NULL, NULL);
INSERT INTO public.estudiante_examen VALUES (141, '202201412', 5, 'habilitado', NULL, NULL, NULL);
INSERT INTO public.estudiante_examen VALUES (142, '202101423', 5, 'habilitado', NULL, NULL, NULL);
INSERT INTO public.estudiante_examen VALUES (143, '202201434', 5, 'habilitado', NULL, NULL, NULL);
INSERT INTO public.estudiante_examen VALUES (144, '202101445', 5, 'habilitado', NULL, NULL, NULL);
INSERT INTO public.estudiante_examen VALUES (145, '202201456', 5, 'habilitado', NULL, NULL, NULL);
INSERT INTO public.estudiante_examen VALUES (146, '202101467', 5, 'habilitado', NULL, NULL, NULL);
INSERT INTO public.estudiante_examen VALUES (147, '202201478', 5, 'habilitado', NULL, NULL, NULL);
INSERT INTO public.estudiante_examen VALUES (148, '202101489', 5, 'habilitado', NULL, NULL, NULL);
INSERT INTO public.estudiante_examen VALUES (149, '202201490', 5, 'habilitado', NULL, NULL, NULL);


--
-- Data for Name: estudiante_examen_ambiente; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.estudiante_examen_ambiente VALUES (1, 1);
INSERT INTO public.estudiante_examen_ambiente VALUES (2, 2);
INSERT INTO public.estudiante_examen_ambiente VALUES (3, 3);
INSERT INTO public.estudiante_examen_ambiente VALUES (4, 4);


--
-- Data for Name: examen; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.examen VALUES (1, '2024-06-10', '08:00:00', '10:00:00', 120, 1, 1);
INSERT INTO public.examen VALUES (2, '2024-06-11', '10:00:00', '12:00:00', 120, 2, 2);
INSERT INTO public.examen VALUES (3, '2024-06-12', '14:00:00', '16:00:00', 120, 5, 3);
INSERT INTO public.examen VALUES (4, '2024-06-13', '08:00:00', '09:30:00', 90, 1, 4);
INSERT INTO public.examen VALUES (5, '2024-06-14', '16:00:00', '18:00:00', 120, 2, 5);


--
-- Data for Name: examen_ambiente; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.examen_ambiente VALUES (1, 1);
INSERT INTO public.examen_ambiente VALUES (2, 2);
INSERT INTO public.examen_ambiente VALUES (3, 3);
INSERT INTO public.examen_ambiente VALUES (4, 4);
INSERT INTO public.examen_ambiente VALUES (5, 5);


--
-- Data for Name: examen_curso; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.examen_curso VALUES (1, 1);
INSERT INTO public.examen_curso VALUES (2, 2);
INSERT INTO public.examen_curso VALUES (3, 3);
INSERT INTO public.examen_curso VALUES (4, 4);
INSERT INTO public.examen_curso VALUES (5, 5);


--
-- Data for Name: examen_material_permitido; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.examen_material_permitido VALUES (1, 1);
INSERT INTO public.examen_material_permitido VALUES (2, 2);
INSERT INTO public.examen_material_permitido VALUES (3, 3);
INSERT INTO public.examen_material_permitido VALUES (4, 4);
INSERT INTO public.examen_material_permitido VALUES (5, 5);


--
-- Data for Name: examen_norma; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.examen_norma VALUES (1, 1);
INSERT INTO public.examen_norma VALUES (2, 2);
INSERT INTO public.examen_norma VALUES (3, 3);
INSERT INTO public.examen_norma VALUES (4, 4);
INSERT INTO public.examen_norma VALUES (5, 5);


--
-- Data for Name: failed_jobs; Type: TABLE DATA; Schema: public; Owner: -
--



--
-- Data for Name: invitacion_examen_compartido; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.invitacion_examen_compartido VALUES (1, 2, 1, 'aceptado');
INSERT INTO public.invitacion_examen_compartido VALUES (2, 5, 2, 'pendiente');
INSERT INTO public.invitacion_examen_compartido VALUES (3, 1, 3, 'rechazado');
INSERT INTO public.invitacion_examen_compartido VALUES (4, 2, 4, 'aceptado');
INSERT INTO public.invitacion_examen_compartido VALUES (5, 5, 5, 'pendiente');


--
-- Data for Name: job_batches; Type: TABLE DATA; Schema: public; Owner: -
--



--
-- Data for Name: jobs; Type: TABLE DATA; Schema: public; Owner: -
--



--
-- Data for Name: material; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.material VALUES (1, 'Calculadora cientifica');
INSERT INTO public.material VALUES (2, 'Formulario impreso');
INSERT INTO public.material VALUES (3, 'Tabla periodica');
INSERT INTO public.material VALUES (4, 'Regla y compas');
INSERT INTO public.material VALUES (5, 'Hoja de apuntes');


--
-- Data for Name: migrations; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.migrations VALUES (5, '0001_01_01_000000_create_users_table', 1);
INSERT INTO public.migrations VALUES (6, '0001_01_01_000001_create_cache_table', 1);
INSERT INTO public.migrations VALUES (7, '0001_01_01_000002_create_jobs_table', 1);
INSERT INTO public.migrations VALUES (8, '2026_09_27_000001_migracion_servidor_oficial', 1);


--
-- Data for Name: norma; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.norma VALUES (1, 'No usar celulares durante el examen');
INSERT INTO public.norma VALUES (2, 'Prohibido hablar con otros estudiantes');
INSERT INTO public.norma VALUES (3, 'Solo material autorizado en la mesa');
INSERT INTO public.norma VALUES (4, 'Prohibido salir del aula sin permiso');
INSERT INTO public.norma VALUES (5, 'No se permite calculadora programable');


--
-- Data for Name: notificacion_auxiliar; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.notificacion_auxiliar VALUES (1, 1, 1, 'visto');
INSERT INTO public.notificacion_auxiliar VALUES (2, 2, 2, 'recibido');
INSERT INTO public.notificacion_auxiliar VALUES (3, 3, 3, 'visto');
INSERT INTO public.notificacion_auxiliar VALUES (4, 4, 4, 'recibido');
INSERT INTO public.notificacion_auxiliar VALUES (5, 5, 5, 'visto');


--
-- Data for Name: notificacion_docente; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.notificacion_docente VALUES (1, 1, 1, 'visto');
INSERT INTO public.notificacion_docente VALUES (2, 2, 2, 'recibido');
INSERT INTO public.notificacion_docente VALUES (3, 3, 3, 'visto');
INSERT INTO public.notificacion_docente VALUES (4, 4, 4, 'recibido');


--
-- Data for Name: password_reset_tokens; Type: TABLE DATA; Schema: public; Owner: -
--



--
-- Data for Name: registro_asistencia; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.registro_asistencia VALUES (1, '08:05:00', 1, '20210001', 3);
INSERT INTO public.registro_asistencia VALUES (2, '10:03:00', 2, '20210002', 4);
INSERT INTO public.registro_asistencia VALUES (3, '14:10:00', 3, '20210003', 3);
INSERT INTO public.registro_asistencia VALUES (4, '08:02:00', 4, '20210004', 4);
INSERT INTO public.registro_asistencia VALUES (201, '16:45:00', 5, '202201013', 1);
INSERT INTO public.registro_asistencia VALUES (202, '16:16:00', 5, '202101022', 2);
INSERT INTO public.registro_asistencia VALUES (203, '16:16:00', 5, '202002107', 2);
INSERT INTO public.registro_asistencia VALUES (204, '16:21:00', 5, '202101078', 1);
INSERT INTO public.registro_asistencia VALUES (205, '16:26:00', 5, '202201101', 1);
INSERT INTO public.registro_asistencia VALUES (206, '16:02:00', 5, '202201123', 2);
INSERT INTO public.registro_asistencia VALUES (207, '16:33:00', 5, '202101156', 2);
INSERT INTO public.registro_asistencia VALUES (208, '16:46:00', 5, '202201189', 1);
INSERT INTO public.registro_asistencia VALUES (301, '16:41:00', 5, '202101223', 1);
INSERT INTO public.registro_asistencia VALUES (302, '16:52:00', 5, '202201234', 1);
INSERT INTO public.registro_asistencia VALUES (303, '16:39:00', 5, '202201256', 2);
INSERT INTO public.registro_asistencia VALUES (304, '16:29:00', 5, '202101267', 1);
INSERT INTO public.registro_asistencia VALUES (305, '16:54:00', 5, '202101289', 2);
INSERT INTO public.registro_asistencia VALUES (306, '16:20:00', 5, '202201290', 2);
INSERT INTO public.registro_asistencia VALUES (307, '16:05:00', 5, '202201312', 2);
INSERT INTO public.registro_asistencia VALUES (308, '16:24:00', 5, '202101323', 1);
INSERT INTO public.registro_asistencia VALUES (309, '16:37:00', 5, '202101345', 1);
INSERT INTO public.registro_asistencia VALUES (310, '16:02:00', 5, '202201356', 2);
INSERT INTO public.registro_asistencia VALUES (311, '16:52:00', 5, '202201378', 1);
INSERT INTO public.registro_asistencia VALUES (312, '16:10:00', 5, '202101389', 2);
INSERT INTO public.registro_asistencia VALUES (313, '16:38:00', 5, '202101401', 1);
INSERT INTO public.registro_asistencia VALUES (314, '16:45:00', 5, '202201412', 2);
INSERT INTO public.registro_asistencia VALUES (315, '16:10:00', 5, '202201434', 2);
INSERT INTO public.registro_asistencia VALUES (316, '16:53:00', 5, '202101445', 1);
INSERT INTO public.registro_asistencia VALUES (317, '16:31:00', 5, '202101467', 2);
INSERT INTO public.registro_asistencia VALUES (318, '16:09:00', 5, '202201478', 2);
INSERT INTO public.registro_asistencia VALUES (319, '16:17:00', 5, '202201490', 1);


--
-- Data for Name: rol; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.rol VALUES (1, 'docente');
INSERT INTO public.rol VALUES (2, 'auxiliar');


--
-- Data for Name: rol_usuario; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.rol_usuario VALUES (1, 1);
INSERT INTO public.rol_usuario VALUES (2, 1);
INSERT INTO public.rol_usuario VALUES (3, 2);
INSERT INTO public.rol_usuario VALUES (4, 2);
INSERT INTO public.rol_usuario VALUES (5, 1);


--
-- Data for Name: sessions; Type: TABLE DATA; Schema: public; Owner: -
--



--
-- Data for Name: tipo_examen; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.tipo_examen VALUES (1, 'PP');
INSERT INTO public.tipo_examen VALUES (2, 'SP');
INSERT INTO public.tipo_examen VALUES (3, 'FINAL');
INSERT INTO public.tipo_examen VALUES (4, 'SI');
INSERT INTO public.tipo_examen VALUES (5, 'PARCIAL');


--
-- Data for Name: tipo_gestion; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.tipo_gestion VALUES (1, 'Primer sem');
INSERT INTO public.tipo_gestion VALUES (2, 'inv');
INSERT INTO public.tipo_gestion VALUES (3, 'Seg sem');
INSERT INTO public.tipo_gestion VALUES (4, 'ver');


--
-- Data for Name: users; Type: TABLE DATA; Schema: public; Owner: -
--



--
-- Data for Name: usuario; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.usuario VALUES (5, 'DOC003', '$2y$10$vE0SJjVzqLk40oLrhN2Kd.G8BETIyz5kmecym86oaELv8wZk8Ak9S', 'Fernando', 'Vargas');
INSERT INTO public.usuario VALUES (4, 'AUX002', '$2y$10$oCKNmUI8S9rOkYZDe3r9LOyOU6Ouk4/YyRJvsJYdmYDnATSjK.76a', 'Sofia', 'Castro');
INSERT INTO public.usuario VALUES (1, 'DOC001', '$2y$12$Tt4sKwgJnuyrGPNrJdX7l.nl072YQkoq2T9WN/Flaj6nxEUZjrpSa', 'Roberto', 'Silva');
INSERT INTO public.usuario VALUES (2, 'DOC002', '$2y$12$ufTD.e4y8DEmaWJW4dHrc.ui/PiamKKTiLploZEWUx0Ki9HxqZg/C', 'Patricia', 'Rojas');
INSERT INTO public.usuario VALUES (3, 'AUX001', '$2y$12$SCsM./qdbhkp27Q2tx2Ufey.oDWhdTBafZsr4eU.pRCp32PKk0eyu', 'Diego', 'Mendoza');


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.failed_jobs_id_seq', 1, false);


--
-- Name: jobs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.jobs_id_seq', 1, false);


--
-- Name: migrations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.migrations_id_seq', 8, true);


--
-- Name: users_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.users_id_seq', 1, false);


--
-- Name: ambiente ambiente_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ambiente
    ADD CONSTRAINT ambiente_pkey PRIMARY KEY (id_ambiente);


--
-- Name: auxiliar_curso auxiliar_curso_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.auxiliar_curso
    ADD CONSTRAINT auxiliar_curso_pkey PRIMARY KEY (id_auxiliar, id_curso);


--
-- Name: cache_locks cache_locks_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cache_locks
    ADD CONSTRAINT cache_locks_pkey PRIMARY KEY (key);


--
-- Name: cache cache_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cache
    ADD CONSTRAINT cache_pkey PRIMARY KEY (key);


--
-- Name: central_riesgo central_riesgo_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.central_riesgo
    ADD CONSTRAINT central_riesgo_pkey PRIMARY KEY (id_registro);


--
-- Name: curso curso_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.curso
    ADD CONSTRAINT curso_pkey PRIMARY KEY (id_curso);


--
-- Name: curso_tipo_gestion curso_tipo_gestion_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.curso_tipo_gestion
    ADD CONSTRAINT curso_tipo_gestion_pkey PRIMARY KEY (id_curso, id_tg);


--
-- Name: estudiante_curso estudiante_curso_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.estudiante_curso
    ADD CONSTRAINT estudiante_curso_pkey PRIMARY KEY (sis_estudiante, id_curso);


--
-- Name: estudiante_examen_ambiente estudiante_examen_ambiente_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.estudiante_examen_ambiente
    ADD CONSTRAINT estudiante_examen_ambiente_pkey PRIMARY KEY (id_ee, id_ambiente);


--
-- Name: estudiante_examen estudiante_examen_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.estudiante_examen
    ADD CONSTRAINT estudiante_examen_pkey PRIMARY KEY (id_estudiante_examen);


--
-- Name: estudiante estudiante_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.estudiante
    ADD CONSTRAINT estudiante_pkey PRIMARY KEY (sis_estudiante);


--
-- Name: examen_ambiente examen_ambiente_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.examen_ambiente
    ADD CONSTRAINT examen_ambiente_pkey PRIMARY KEY (id_examen, id_ambiente);


--
-- Name: examen_curso examen_curso_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.examen_curso
    ADD CONSTRAINT examen_curso_pkey PRIMARY KEY (id_examen, id_curso);


--
-- Name: examen_material_permitido examen_material_permitido_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.examen_material_permitido
    ADD CONSTRAINT examen_material_permitido_pkey PRIMARY KEY (id_examen, id_material_permitido);


--
-- Name: examen_norma examen_norma_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.examen_norma
    ADD CONSTRAINT examen_norma_pkey PRIMARY KEY (id_examen, id_norma);


--
-- Name: examen examen_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.examen
    ADD CONSTRAINT examen_pkey PRIMARY KEY (id_examen);


--
-- Name: failed_jobs failed_jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_uuid_unique UNIQUE (uuid);


--
-- Name: invitacion_examen_compartido invitacion_examen_compartido_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.invitacion_examen_compartido
    ADD CONSTRAINT invitacion_examen_compartido_pkey PRIMARY KEY (id_invitacion);


--
-- Name: job_batches job_batches_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.job_batches
    ADD CONSTRAINT job_batches_pkey PRIMARY KEY (id);


--
-- Name: jobs jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jobs
    ADD CONSTRAINT jobs_pkey PRIMARY KEY (id);


--
-- Name: material material_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.material
    ADD CONSTRAINT material_pkey PRIMARY KEY (id_material);


--
-- Name: migrations migrations_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.migrations
    ADD CONSTRAINT migrations_pkey PRIMARY KEY (id);


--
-- Name: norma norma_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.norma
    ADD CONSTRAINT norma_pkey PRIMARY KEY (id_norma);


--
-- Name: notificacion_auxiliar notificacion_auxiliar_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.notificacion_auxiliar
    ADD CONSTRAINT notificacion_auxiliar_pkey PRIMARY KEY (id_notificacion);


--
-- Name: notificacion_docente notificacion_docente_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.notificacion_docente
    ADD CONSTRAINT notificacion_docente_pkey PRIMARY KEY (id_notificacion);


--
-- Name: password_reset_tokens password_reset_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.password_reset_tokens
    ADD CONSTRAINT password_reset_tokens_pkey PRIMARY KEY (email);


--
-- Name: registro_asistencia registro_asistencia_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.registro_asistencia
    ADD CONSTRAINT registro_asistencia_pkey PRIMARY KEY (id_ingreso);


--
-- Name: rol rol_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rol
    ADD CONSTRAINT rol_pkey PRIMARY KEY (id_rol);


--
-- Name: rol_usuario rol_usuario_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rol_usuario
    ADD CONSTRAINT rol_usuario_pkey PRIMARY KEY (id_usuario, id_rol);


--
-- Name: sessions sessions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sessions
    ADD CONSTRAINT sessions_pkey PRIMARY KEY (id);


--
-- Name: tipo_examen tipo_examen_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tipo_examen
    ADD CONSTRAINT tipo_examen_pkey PRIMARY KEY (id_tipo_examen);


--
-- Name: tipo_gestion tipo_gestion_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tipo_gestion
    ADD CONSTRAINT tipo_gestion_pkey PRIMARY KEY (id_tipo_gestion);


--
-- Name: users users_email_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_email_unique UNIQUE (email);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- Name: usuario usuario_cod_sis_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.usuario
    ADD CONSTRAINT usuario_cod_sis_key UNIQUE (cod_sis);


--
-- Name: usuario usuario_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.usuario
    ADD CONSTRAINT usuario_pkey PRIMARY KEY (id_usuario);


--
-- Name: jobs_queue_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX jobs_queue_index ON public.jobs USING btree (queue);


--
-- Name: sessions_last_activity_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sessions_last_activity_index ON public.sessions USING btree (last_activity);


--
-- Name: sessions_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sessions_user_id_index ON public.sessions USING btree (user_id);


--
-- Name: auxiliar_curso fk_ac_auxiliar; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.auxiliar_curso
    ADD CONSTRAINT fk_ac_auxiliar FOREIGN KEY (id_auxiliar) REFERENCES public.usuario(id_usuario);


--
-- Name: auxiliar_curso fk_ac_curso; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.auxiliar_curso
    ADD CONSTRAINT fk_ac_curso FOREIGN KEY (id_curso) REFERENCES public.curso(id_curso);


--
-- Name: central_riesgo fk_cr_ingreso; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.central_riesgo
    ADD CONSTRAINT fk_cr_ingreso FOREIGN KEY (id_ingreso) REFERENCES public.registro_asistencia(id_ingreso);


--
-- Name: central_riesgo fk_cr_registrador; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.central_riesgo
    ADD CONSTRAINT fk_cr_registrador FOREIGN KEY (id_registrador) REFERENCES public.usuario(id_usuario);


--
-- Name: curso_tipo_gestion fk_ctg_curso; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.curso_tipo_gestion
    ADD CONSTRAINT fk_ctg_curso FOREIGN KEY (id_curso) REFERENCES public.curso(id_curso);


--
-- Name: curso_tipo_gestion fk_ctg_tg; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.curso_tipo_gestion
    ADD CONSTRAINT fk_ctg_tg FOREIGN KEY (id_tg) REFERENCES public.tipo_gestion(id_tipo_gestion);


--
-- Name: curso fk_curso_docente; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.curso
    ADD CONSTRAINT fk_curso_docente FOREIGN KEY (sis_doc) REFERENCES public.usuario(id_usuario);


--
-- Name: examen_ambiente fk_ea_ambiente; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.examen_ambiente
    ADD CONSTRAINT fk_ea_ambiente FOREIGN KEY (id_ambiente) REFERENCES public.ambiente(id_ambiente);


--
-- Name: examen_ambiente fk_ea_examen; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.examen_ambiente
    ADD CONSTRAINT fk_ea_examen FOREIGN KEY (id_examen) REFERENCES public.examen(id_examen);


--
-- Name: estudiante_curso fk_ec_curso; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.estudiante_curso
    ADD CONSTRAINT fk_ec_curso FOREIGN KEY (id_curso) REFERENCES public.curso(id_curso);


--
-- Name: estudiante_curso fk_ec_estudiante; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.estudiante_curso
    ADD CONSTRAINT fk_ec_estudiante FOREIGN KEY (sis_estudiante) REFERENCES public.estudiante(sis_estudiante);


--
-- Name: estudiante_examen fk_ee_estudiante; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.estudiante_examen
    ADD CONSTRAINT fk_ee_estudiante FOREIGN KEY (sis_estudiante) REFERENCES public.estudiante(sis_estudiante);


--
-- Name: estudiante_examen fk_ee_examen; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.estudiante_examen
    ADD CONSTRAINT fk_ee_examen FOREIGN KEY (id_examen) REFERENCES public.examen(id_examen);


--
-- Name: estudiante_examen fk_ee_modificador; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.estudiante_examen
    ADD CONSTRAINT fk_ee_modificador FOREIGN KEY (modificado_por) REFERENCES public.usuario(id_usuario);


--
-- Name: estudiante_examen_ambiente fk_eea_ambiente; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.estudiante_examen_ambiente
    ADD CONSTRAINT fk_eea_ambiente FOREIGN KEY (id_ambiente) REFERENCES public.ambiente(id_ambiente);


--
-- Name: estudiante_examen_ambiente fk_eea_ee; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.estudiante_examen_ambiente
    ADD CONSTRAINT fk_eea_ee FOREIGN KEY (id_ee) REFERENCES public.estudiante_examen(id_estudiante_examen);


--
-- Name: examen_material_permitido fk_emp_examen; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.examen_material_permitido
    ADD CONSTRAINT fk_emp_examen FOREIGN KEY (id_examen) REFERENCES public.examen(id_examen);


--
-- Name: examen_material_permitido fk_emp_material; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.examen_material_permitido
    ADD CONSTRAINT fk_emp_material FOREIGN KEY (id_material_permitido) REFERENCES public.material(id_material);


--
-- Name: examen_norma fk_en_examen; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.examen_norma
    ADD CONSTRAINT fk_en_examen FOREIGN KEY (id_examen) REFERENCES public.examen(id_examen);


--
-- Name: examen_norma fk_en_norma; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.examen_norma
    ADD CONSTRAINT fk_en_norma FOREIGN KEY (id_norma) REFERENCES public.norma(id_norma);


--
-- Name: examen fk_examen_creador; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.examen
    ADD CONSTRAINT fk_examen_creador FOREIGN KEY (creador) REFERENCES public.usuario(id_usuario);


--
-- Name: examen fk_examen_tipo; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.examen
    ADD CONSTRAINT fk_examen_tipo FOREIGN KEY (tipo_examen) REFERENCES public.tipo_examen(id_tipo_examen);


--
-- Name: examen_curso fk_exc_curso; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.examen_curso
    ADD CONSTRAINT fk_exc_curso FOREIGN KEY (id_curso) REFERENCES public.curso(id_curso);


--
-- Name: examen_curso fk_exc_examen; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.examen_curso
    ADD CONSTRAINT fk_exc_examen FOREIGN KEY (id_examen) REFERENCES public.examen(id_examen);


--
-- Name: invitacion_examen_compartido fk_iec_docente; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.invitacion_examen_compartido
    ADD CONSTRAINT fk_iec_docente FOREIGN KEY (id_docente_invitado) REFERENCES public.usuario(id_usuario);


--
-- Name: invitacion_examen_compartido fk_iec_examen; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.invitacion_examen_compartido
    ADD CONSTRAINT fk_iec_examen FOREIGN KEY (id_examen) REFERENCES public.examen(id_examen);


--
-- Name: notificacion_auxiliar fk_na_ambiente; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.notificacion_auxiliar
    ADD CONSTRAINT fk_na_ambiente FOREIGN KEY (id_ambiente) REFERENCES public.ambiente(id_ambiente);


--
-- Name: notificacion_auxiliar fk_na_curso; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.notificacion_auxiliar
    ADD CONSTRAINT fk_na_curso FOREIGN KEY (id_curso) REFERENCES public.curso(id_curso);


--
-- Name: notificacion_docente fk_nd_central; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.notificacion_docente
    ADD CONSTRAINT fk_nd_central FOREIGN KEY (id_central_riesgo) REFERENCES public.central_riesgo(id_registro);


--
-- Name: notificacion_docente fk_nd_curso; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.notificacion_docente
    ADD CONSTRAINT fk_nd_curso FOREIGN KEY (id_curso) REFERENCES public.curso(id_curso);


--
-- Name: registro_asistencia fk_ra_estudiante; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.registro_asistencia
    ADD CONSTRAINT fk_ra_estudiante FOREIGN KEY (id_estudiante) REFERENCES public.estudiante(sis_estudiante);


--
-- Name: registro_asistencia fk_ra_examen; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.registro_asistencia
    ADD CONSTRAINT fk_ra_examen FOREIGN KEY (id_examen) REFERENCES public.examen(id_examen);


--
-- Name: registro_asistencia fk_ra_registrador; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.registro_asistencia
    ADD CONSTRAINT fk_ra_registrador FOREIGN KEY (id_registrador) REFERENCES public.usuario(id_usuario);


--
-- Name: rol_usuario fk_ru_rol; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rol_usuario
    ADD CONSTRAINT fk_ru_rol FOREIGN KEY (id_rol) REFERENCES public.rol(id_rol);


--
-- Name: rol_usuario fk_ru_usuario; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rol_usuario
    ADD CONSTRAINT fk_ru_usuario FOREIGN KEY (id_usuario) REFERENCES public.usuario(id_usuario);


--
-- PostgreSQL database dump complete
--


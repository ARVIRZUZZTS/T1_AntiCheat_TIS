-- WARNING: This schema is for context only and is not meant to be run.
-- Table order and constraints may not be valid for execution.

CREATE TABLE public.migrations (
  id integer NOT NULL DEFAULT nextval('migrations_id_seq'::regclass),
  migration character varying NOT NULL,
  batch integer NOT NULL,
  CONSTRAINT migrations_pkey PRIMARY KEY (id)
);
CREATE TABLE public.users (
  id bigint NOT NULL DEFAULT nextval('users_id_seq'::regclass),
  name character varying NOT NULL,
  email character varying NOT NULL UNIQUE,
  email_verified_at timestamp without time zone,
  password character varying NOT NULL,
  remember_token character varying,
  created_at timestamp without time zone,
  updated_at timestamp without time zone,
  CONSTRAINT users_pkey PRIMARY KEY (id)
);
CREATE TABLE public.password_reset_tokens (
  email character varying NOT NULL,
  token character varying NOT NULL,
  created_at timestamp without time zone,
  CONSTRAINT password_reset_tokens_pkey PRIMARY KEY (email)
);
CREATE TABLE public.sessions (
  id character varying NOT NULL,
  user_id bigint,
  ip_address character varying,
  user_agent text,
  payload text NOT NULL,
  last_activity integer NOT NULL,
  CONSTRAINT sessions_pkey PRIMARY KEY (id)
);
CREATE TABLE public.cache (
  key character varying NOT NULL,
  value text NOT NULL,
  expiration integer NOT NULL,
  CONSTRAINT cache_pkey PRIMARY KEY (key)
);
CREATE TABLE public.cache_locks (
  key character varying NOT NULL,
  owner character varying NOT NULL,
  expiration integer NOT NULL,
  CONSTRAINT cache_locks_pkey PRIMARY KEY (key)
);
CREATE TABLE public.jobs (
  id bigint NOT NULL DEFAULT nextval('jobs_id_seq'::regclass),
  queue character varying NOT NULL,
  payload text NOT NULL,
  attempts smallint NOT NULL,
  reserved_at integer,
  available_at integer NOT NULL,
  created_at integer NOT NULL,
  CONSTRAINT jobs_pkey PRIMARY KEY (id)
);
CREATE TABLE public.job_batches (
  id character varying NOT NULL,
  name character varying NOT NULL,
  total_jobs integer NOT NULL,
  pending_jobs integer NOT NULL,
  failed_jobs integer NOT NULL,
  failed_job_ids text NOT NULL,
  options text,
  cancelled_at integer,
  created_at integer NOT NULL,
  finished_at integer,
  CONSTRAINT job_batches_pkey PRIMARY KEY (id)
);
CREATE TABLE public.failed_jobs (
  id bigint NOT NULL DEFAULT nextval('failed_jobs_id_seq'::regclass),
  uuid character varying NOT NULL UNIQUE,
  connection text NOT NULL,
  queue text NOT NULL,
  payload text NOT NULL,
  exception text NOT NULL,
  failed_at timestamp without time zone NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT failed_jobs_pkey PRIMARY KEY (id)
);
CREATE TABLE public.usuario (
  id_usuario integer NOT NULL,
  cod_sis character varying NOT NULL UNIQUE,
  password character varying NOT NULL,
  nombre_usuario character varying NOT NULL,
  apellido character varying NOT NULL,
  correo character varying UNIQUE,
  nombre character varying,
  fecha_creacion timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
  estado_cuenta character varying NOT NULL DEFAULT 'habilitada'::character varying CHECK (estado_cuenta::text = ANY (ARRAY['pendiente'::character varying, 'pendiente_correo'::character varying, 'habilitada'::character varying, 'rechazada'::character varying]::text[])),
  carnet character varying,
  tipo_auxiliar character varying CHECK (tipo_auxiliar IS NULL OR (tipo_auxiliar::text = ANY (ARRAY['UMSS'::character varying, 'externo'::character varying]::text[]))),
  puede_crear_auxiliares boolean NOT NULL DEFAULT false,
  id_docente_responsable integer,
  id_creador integer,
  CONSTRAINT usuario_pkey PRIMARY KEY (id_usuario),
  CONSTRAINT usuario_docente_responsable_fk FOREIGN KEY (id_docente_responsable) REFERENCES public.usuario(id_usuario),
  CONSTRAINT usuario_creador_fk FOREIGN KEY (id_creador) REFERENCES public.usuario(id_usuario)
);
CREATE TABLE public.rol (
  id_rol integer NOT NULL,
  nombre_rol USER-DEFINED NOT NULL,
  CONSTRAINT rol_pkey PRIMARY KEY (id_rol)
);
CREATE TABLE public.estudiante (
  sis_estudiante character varying NOT NULL,
  nombre_estudiante character varying NOT NULL,
  apellido_estudiante character varying NOT NULL,
  carrera character varying,
  verificado boolean NOT NULL DEFAULT true,
  CONSTRAINT estudiante_pkey PRIMARY KEY (sis_estudiante)
);
CREATE TABLE public.tipo_examen (
  id_tipo_examen integer NOT NULL,
  nombre_tipo_examen USER-DEFINED NOT NULL,
  CONSTRAINT tipo_examen_pkey PRIMARY KEY (id_tipo_examen)
);
CREATE TABLE public.tipo_gestion (
  id_tipo_gestion integer NOT NULL,
  nombre_tipo_gestion USER-DEFINED NOT NULL,
  CONSTRAINT tipo_gestion_pkey PRIMARY KEY (id_tipo_gestion)
);
CREATE TABLE public.ambiente (
  id_ambiente integer NOT NULL,
  nombre_ambiente character varying NOT NULL,
  CONSTRAINT ambiente_pkey PRIMARY KEY (id_ambiente)
);
CREATE TABLE public.norma (
  id_norma integer NOT NULL,
  detalle_norma character varying NOT NULL,
  CONSTRAINT norma_pkey PRIMARY KEY (id_norma)
);
CREATE TABLE public.material (
  id_material integer NOT NULL,
  descripcion character varying NOT NULL,
  CONSTRAINT material_pkey PRIMARY KEY (id_material)
);
CREATE TABLE public.curso (
  id_curso integer NOT NULL,
  nombre_curso character varying NOT NULL,
  sis_doc integer NOT NULL,
  fecha_creacion date,
  estado USER-DEFINED NOT NULL,
  id_ambiente integer,
  CONSTRAINT curso_pkey PRIMARY KEY (id_curso),
  CONSTRAINT fk_curso_docente FOREIGN KEY (sis_doc) REFERENCES public.usuario(id_usuario),
  CONSTRAINT fk_curso_ambiente FOREIGN KEY (id_ambiente) REFERENCES public.ambiente(id_ambiente)
);
CREATE TABLE public.examen (
  id_examen integer NOT NULL,
  fecha date,
  hora_inicio time without time zone,
  duracion integer,
  creador integer NOT NULL,
  tipo_examen integer,
  nombre_examen character varying,
  CONSTRAINT examen_pkey PRIMARY KEY (id_examen),
  CONSTRAINT fk_examen_creador FOREIGN KEY (creador) REFERENCES public.usuario(id_usuario),
  CONSTRAINT fk_examen_tipo FOREIGN KEY (tipo_examen) REFERENCES public.tipo_examen(id_tipo_examen)
);
CREATE TABLE public.rol_usuario (
  id_usuario integer NOT NULL,
  id_rol integer NOT NULL,
  CONSTRAINT rol_usuario_pkey PRIMARY KEY (id_usuario, id_rol),
  CONSTRAINT fk_ru_usuario FOREIGN KEY (id_usuario) REFERENCES public.usuario(id_usuario),
  CONSTRAINT fk_ru_rol FOREIGN KEY (id_rol) REFERENCES public.rol(id_rol)
);
CREATE TABLE public.curso_tipo_gestion (
  id_curso integer NOT NULL,
  id_tg integer NOT NULL,
  anio smallint NOT NULL,
  CONSTRAINT curso_tipo_gestion_pkey PRIMARY KEY (id_curso, id_tg),
  CONSTRAINT fk_ctg_curso FOREIGN KEY (id_curso) REFERENCES public.curso(id_curso),
  CONSTRAINT fk_ctg_tg FOREIGN KEY (id_tg) REFERENCES public.tipo_gestion(id_tipo_gestion)
);
CREATE TABLE public.estudiante_curso (
  sis_estudiante character varying NOT NULL,
  id_curso integer NOT NULL,
  CONSTRAINT estudiante_curso_pkey PRIMARY KEY (sis_estudiante, id_curso),
  CONSTRAINT fk_ec_estudiante FOREIGN KEY (sis_estudiante) REFERENCES public.estudiante(sis_estudiante),
  CONSTRAINT fk_ec_curso FOREIGN KEY (id_curso) REFERENCES public.curso(id_curso)
);
CREATE TABLE public.auxiliar_curso (
  id_auxiliar integer NOT NULL,
  id_curso integer NOT NULL,
  estado USER-DEFINED NOT NULL,
  CONSTRAINT auxiliar_curso_pkey PRIMARY KEY (id_auxiliar, id_curso),
  CONSTRAINT fk_ac_auxiliar FOREIGN KEY (id_auxiliar) REFERENCES public.usuario(id_usuario),
  CONSTRAINT fk_ac_curso FOREIGN KEY (id_curso) REFERENCES public.curso(id_curso)
);
CREATE TABLE public.examen_ambiente (
  id_examen integer NOT NULL,
  id_ambiente integer NOT NULL,
  CONSTRAINT examen_ambiente_pkey PRIMARY KEY (id_examen, id_ambiente),
  CONSTRAINT fk_ea_examen FOREIGN KEY (id_examen) REFERENCES public.examen(id_examen),
  CONSTRAINT fk_ea_ambiente FOREIGN KEY (id_ambiente) REFERENCES public.ambiente(id_ambiente)
);
CREATE TABLE public.examen_norma (
  id_examen integer NOT NULL,
  id_norma integer NOT NULL,
  CONSTRAINT examen_norma_pkey PRIMARY KEY (id_examen, id_norma),
  CONSTRAINT fk_en_examen FOREIGN KEY (id_examen) REFERENCES public.examen(id_examen),
  CONSTRAINT fk_en_norma FOREIGN KEY (id_norma) REFERENCES public.norma(id_norma)
);
CREATE TABLE public.examen_material_permitido (
  id_examen integer NOT NULL,
  id_material_permitido integer NOT NULL,
  CONSTRAINT examen_material_permitido_pkey PRIMARY KEY (id_examen, id_material_permitido),
  CONSTRAINT fk_emp_examen FOREIGN KEY (id_examen) REFERENCES public.examen(id_examen),
  CONSTRAINT fk_emp_material FOREIGN KEY (id_material_permitido) REFERENCES public.material(id_material)
);
CREATE TABLE public.examen_curso (
  id_examen integer NOT NULL,
  id_curso integer NOT NULL,
  CONSTRAINT examen_curso_pkey PRIMARY KEY (id_examen, id_curso),
  CONSTRAINT fk_exc_examen FOREIGN KEY (id_examen) REFERENCES public.examen(id_examen),
  CONSTRAINT fk_exc_curso FOREIGN KEY (id_curso) REFERENCES public.curso(id_curso)
);
CREATE TABLE public.estudiante_examen (
  id_estudiante_examen integer NOT NULL,
  sis_estudiante character varying NOT NULL,
  id_examen integer NOT NULL,
  estado USER-DEFINED NOT NULL,
  motivo character varying,
  modificado_por integer,
  fecha_modificacion timestamp without time zone,
  id_configuracion_examen integer,
  id_curso integer,
  CONSTRAINT estudiante_examen_pkey PRIMARY KEY (id_estudiante_examen),
  CONSTRAINT fk_ee_configuracion FOREIGN KEY (id_configuracion_examen) REFERENCES public.configuracion_examen_docente(id_configuracion_examen),
  CONSTRAINT fk_ee_curso FOREIGN KEY (id_curso) REFERENCES public.curso(id_curso),
  CONSTRAINT fk_ee_estudiante FOREIGN KEY (sis_estudiante) REFERENCES public.estudiante(sis_estudiante),
  CONSTRAINT fk_ee_examen FOREIGN KEY (id_examen) REFERENCES public.examen(id_examen),
  CONSTRAINT fk_ee_modificador FOREIGN KEY (modificado_por) REFERENCES public.usuario(id_usuario)
);
CREATE TABLE public.estudiante_examen_ambiente (
  id_ee integer NOT NULL,
  id_ambiente integer NOT NULL,
  CONSTRAINT estudiante_examen_ambiente_pkey PRIMARY KEY (id_ee, id_ambiente),
  CONSTRAINT fk_eea_ee FOREIGN KEY (id_ee) REFERENCES public.estudiante_examen(id_estudiante_examen),
  CONSTRAINT fk_eea_ambiente FOREIGN KEY (id_ambiente) REFERENCES public.ambiente(id_ambiente)
);
CREATE TABLE public.registro_asistencia (
  id_ingreso integer NOT NULL,
  hora_ingreso time without time zone,
  id_examen integer NOT NULL,
  id_estudiante character varying NOT NULL,
  id_registrador integer NOT NULL,
  id_estudiante_examen integer UNIQUE,
  CONSTRAINT registro_asistencia_pkey PRIMARY KEY (id_ingreso),
  CONSTRAINT fk_ra_inscripcion FOREIGN KEY (id_estudiante_examen) REFERENCES public.estudiante_examen(id_estudiante_examen),
  CONSTRAINT fk_ra_examen FOREIGN KEY (id_examen) REFERENCES public.examen(id_examen),
  CONSTRAINT fk_ra_estudiante FOREIGN KEY (id_estudiante) REFERENCES public.estudiante(sis_estudiante),
  CONSTRAINT fk_ra_registrador FOREIGN KEY (id_registrador) REFERENCES public.usuario(id_usuario)
);
CREATE TABLE public.central_riesgo (
  id_registro integer NOT NULL,
  id_registrador integer NOT NULL,
  detalle_motivo character varying,
  fecha_registro timestamp without time zone NOT NULL,
  tipo_infraccion USER-DEFINED NOT NULL,
  id_examen integer NOT NULL,
  sis_estudiante character varying NOT NULL,
  id_curso integer,
  id_estudiante_examen integer,
  CONSTRAINT central_riesgo_pkey PRIMARY KEY (id_registro),
  CONSTRAINT fk_cr_examen FOREIGN KEY (id_examen) REFERENCES public.examen(id_examen),
  CONSTRAINT fk_cr_estudiante FOREIGN KEY (sis_estudiante) REFERENCES public.estudiante(sis_estudiante),
  CONSTRAINT central_riesgo_id_curso_foreign FOREIGN KEY (id_curso) REFERENCES public.curso(id_curso),
  CONSTRAINT fk_cr_inscripcion FOREIGN KEY (id_estudiante_examen) REFERENCES public.estudiante_examen(id_estudiante_examen),
  CONSTRAINT fk_cr_registrador FOREIGN KEY (id_registrador) REFERENCES public.usuario(id_usuario)
);
CREATE TABLE public.notificacion_docente (
  id_notificacion integer NOT NULL,
  id_central_riesgo integer,
  id_curso integer,
  estado USER-DEFINED NOT NULL,
  oculta boolean NOT NULL DEFAULT false,
  tipo character varying NOT NULL DEFAULT 'posible_tramposo'::character varying,
  id_docente_destinatario integer,
  id_invitacion integer UNIQUE,
  fecha_evento timestamp without time zone,
  detalles json,
  CONSTRAINT notificacion_docente_pkey PRIMARY KEY (id_notificacion),
  CONSTRAINT fk_nd_destinatario FOREIGN KEY (id_docente_destinatario) REFERENCES public.usuario(id_usuario),
  CONSTRAINT fk_nd_invitacion FOREIGN KEY (id_invitacion) REFERENCES public.invitacion_examen_compartido(id_invitacion),
  CONSTRAINT fk_nd_central FOREIGN KEY (id_central_riesgo) REFERENCES public.central_riesgo(id_registro),
  CONSTRAINT fk_nd_curso FOREIGN KEY (id_curso) REFERENCES public.curso(id_curso)
);
CREATE TABLE public.notificacion_auxiliar (
  id_notificacion integer NOT NULL,
  id_curso integer NOT NULL,
  id_ambiente integer NOT NULL,
  estado USER-DEFINED NOT NULL,
  CONSTRAINT notificacion_auxiliar_pkey PRIMARY KEY (id_notificacion),
  CONSTRAINT fk_na_curso FOREIGN KEY (id_curso) REFERENCES public.curso(id_curso),
  CONSTRAINT fk_na_ambiente FOREIGN KEY (id_ambiente) REFERENCES public.ambiente(id_ambiente)
);
CREATE TABLE public.invitacion_examen_compartido (
  id_invitacion integer NOT NULL,
  id_docente_invitado integer NOT NULL,
  id_examen integer NOT NULL,
  estado USER-DEFINED NOT NULL,
  id_docente_invitador integer NOT NULL,
  created_at timestamp without time zone NOT NULL,
  updated_at timestamp without time zone NOT NULL,
  CONSTRAINT invitacion_examen_compartido_pkey PRIMARY KEY (id_invitacion),
  CONSTRAINT fk_iec_invitador FOREIGN KEY (id_docente_invitador) REFERENCES public.usuario(id_usuario),
  CONSTRAINT fk_iec_docente FOREIGN KEY (id_docente_invitado) REFERENCES public.usuario(id_usuario),
  CONSTRAINT fk_iec_examen FOREIGN KEY (id_examen) REFERENCES public.examen(id_examen)
);
CREATE TABLE public.material_personalizado (
  id_examen integer NOT NULL,
  numero_material integer NOT NULL,
  descripcion_material text NOT NULL,
  CONSTRAINT fk_material_personalizado_examen FOREIGN KEY (id_examen) REFERENCES public.examen(id_examen)
);
CREATE TABLE public.norma_personalizada (
  id_examen integer NOT NULL,
  numero_norma integer NOT NULL,
  descripcion_norma text NOT NULL,
  CONSTRAINT fk_norma_personalizada_examen FOREIGN KEY (id_examen) REFERENCES public.examen(id_examen)
);
CREATE TABLE public.configuracion_examen_docente (
  id_configuracion_examen integer NOT NULL DEFAULT nextval('configuracion_examen_docente_id_configuracion_examen_seq'::regclass),
  id_examen integer NOT NULL,
  id_docente integer NOT NULL,
  created_at timestamp without time zone,
  updated_at timestamp without time zone,
  CONSTRAINT configuracion_examen_docente_pkey PRIMARY KEY (id_configuracion_examen),
  CONSTRAINT fk_config_examen FOREIGN KEY (id_examen) REFERENCES public.examen(id_examen),
  CONSTRAINT fk_config_docente FOREIGN KEY (id_docente) REFERENCES public.usuario(id_usuario)
);
CREATE TABLE public.configuracion_examen_norma (
  id_configuracion_examen integer NOT NULL,
  id_norma integer NOT NULL,
  CONSTRAINT configuracion_examen_norma_pkey PRIMARY KEY (id_configuracion_examen, id_norma),
  CONSTRAINT fk_config_norma_config FOREIGN KEY (id_configuracion_examen) REFERENCES public.configuracion_examen_docente(id_configuracion_examen),
  CONSTRAINT fk_config_norma_norma FOREIGN KEY (id_norma) REFERENCES public.norma(id_norma)
);
CREATE TABLE public.configuracion_examen_material (
  id_configuracion_examen integer NOT NULL,
  id_material integer NOT NULL,
  CONSTRAINT configuracion_examen_material_pkey PRIMARY KEY (id_configuracion_examen, id_material),
  CONSTRAINT fk_config_material_config FOREIGN KEY (id_configuracion_examen) REFERENCES public.configuracion_examen_docente(id_configuracion_examen),
  CONSTRAINT fk_config_material_material FOREIGN KEY (id_material) REFERENCES public.material(id_material)
);
CREATE TABLE public.configuracion_norma_personalizada (
  id_configuracion_norma integer NOT NULL DEFAULT nextval('configuracion_norma_personalizada_id_configuracion_norma_seq'::regclass),
  id_configuracion_examen integer NOT NULL,
  numero_norma integer NOT NULL,
  descripcion_norma text NOT NULL,
  CONSTRAINT configuracion_norma_personalizada_pkey PRIMARY KEY (id_configuracion_norma),
  CONSTRAINT fk_config_norma_personalizada FOREIGN KEY (id_configuracion_examen) REFERENCES public.configuracion_examen_docente(id_configuracion_examen)
);
CREATE TABLE public.configuracion_material_personalizado (
  id_configuracion_material integer NOT NULL DEFAULT nextval('configuracion_material_personaliz_id_configuracion_material_seq'::regclass),
  id_configuracion_examen integer NOT NULL,
  numero_material integer NOT NULL,
  descripcion_material text NOT NULL,
  CONSTRAINT configuracion_material_personalizado_pkey PRIMARY KEY (id_configuracion_material),
  CONSTRAINT fk_config_material_personalizado FOREIGN KEY (id_configuracion_examen) REFERENCES public.configuracion_examen_docente(id_configuracion_examen)
);
CREATE TABLE public.central_riesgo_motivo (
  id_registro integer NOT NULL,
  motivo USER-DEFINED NOT NULL,
  CONSTRAINT central_riesgo_motivo_pkey PRIMARY KEY (id_registro, motivo),
  CONSTRAINT central_riesgo_motivo_id_registro_fkey FOREIGN KEY (id_registro) REFERENCES public.central_riesgo(id_registro)
);
CREATE TABLE public.validacion_registro (
  id_solicitante integer NOT NULL,
  id_validador integer NOT NULL,
  decision character varying NOT NULL CHECK (decision::text = ANY (ARRAY['aprobado'::character varying, 'desaprobado'::character varying]::text[])),
  motivo character varying,
  fecha timestamp without time zone NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT validacion_registro_pkey PRIMARY KEY (id_solicitante, id_validador),
  CONSTRAINT validacion_registro_id_solicitante_foreign FOREIGN KEY (id_solicitante) REFERENCES public.usuario(id_usuario),
  CONSTRAINT validacion_registro_id_validador_foreign FOREIGN KEY (id_validador) REFERENCES public.usuario(id_usuario)
);
CREATE TABLE public.auxiliar_examen (
  id_auxiliar integer NOT NULL,
  id_examen integer NOT NULL,
  ver_ingreso boolean NOT NULL DEFAULT true,
  ver_monitor boolean NOT NULL DEFAULT true,
  asignado_por integer,
  fecha_asignacion timestamp without time zone NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT auxiliar_examen_pkey PRIMARY KEY (id_auxiliar, id_examen),
  CONSTRAINT auxiliar_examen_id_auxiliar_foreign FOREIGN KEY (id_auxiliar) REFERENCES public.usuario(id_usuario),
  CONSTRAINT auxiliar_examen_id_examen_foreign FOREIGN KEY (id_examen) REFERENCES public.examen(id_examen),
  CONSTRAINT auxiliar_examen_asignado_por_foreign FOREIGN KEY (asignado_por) REFERENCES public.usuario(id_usuario)
);

-- enums recuperados directos del supabase
--    curso_estado	EnCurso, Finalizo, Archivado	
--    estado_notificacion	visto, recibido	
--    estado_usuario	Activo, Baja	
--    estudiante_examen_estado	habilitado, deshabilitado	
--    invitacion_estado	aceptado, rechazado, pendiente	
--    nombre_tipo_gestion	primer semestre, invierno, segundo semestre, verano	
--    registrador_tipo	docente, auxiliar	
--    roles	docente, auxiliar	
--    motivo	intento de ingreso no autorizado, uso de dispositivos electronicos, copia o intercambio de respuestas, uso de material no autorizado, suplantacion de identidad, otro, intento de ingreso duplicado, aula equivocada, intento de ingreso de material no autorizado, en examen sin registro de ingreso	
--    tipo_infraccion	tramposo, sospechoso	
--    tipo_examen_nombre	examen primer parcial, examen final, segunda instancia, examen segundo parcial, evaluacion corriente
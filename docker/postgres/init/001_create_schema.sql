-- ============================================================
-- SCRIPT DE CREACIÓN DE BASE DE DATOS (CORREGIDO)
-- ============================================================

-- =====================
-- TIPOS ENUM
-- =====================

CREATE TYPE registrador_tipo AS ENUM (
  'docente',
  'auxiliar'
);

-- La infraccion solo tiene dos valores; el motivo de la incidencia vive en el
-- enum `motivo` (#70).
CREATE TYPE tipo_infraccion AS ENUM (
  'tramposo',
  'sospechoso'
);

-- Motivos acordados por el equipo para una incidencia en la central de riesgos
-- (#70). El valor es un codigo estable (lo que viaja en el formulario) y la
-- etiqueta que ve la persona la resuelve la aplicacion, igual que
-- `tipo_infraccion`. El ultimo es el motivo generico: cuando se elige, hay que
-- escribir el detalle en `central_riesgo.detalle_motivo`.
CREATE TYPE motivo AS ENUM (
  'intento_de_ingreso_no_autorizado',
  'uso_de_dispositivos_electronicos',
  'copia_o_intercambio_de_respuestas',
  'uso_de_material_no_autorizado',
  'suplantacion_de_identidad',
  'otro'
);

CREATE TYPE curso_estado AS ENUM (
  'EnCurso',
  'Finalizo'
);

CREATE TYPE nombre_tipo_gestion AS ENUM (
  'Primer sem',
  'inv',
  'Seg sem',
  'ver'
);

CREATE TYPE estado_notificacion AS ENUM (
  'visto',
  'recibido'
);

CREATE TYPE tipo_examen_nombre AS ENUM (
  'PP',
  'SP',
  'FINAL',
  'SI',
  'PARCIAL',
  'PRACTICA'
);

CREATE TYPE estudiante_examen_estado AS ENUM (
  'habilitado',
  'deshabilitado'
);

CREATE TYPE estado_usuario AS ENUM (
  'Activo',
  'Baja'
);

CREATE TYPE invitacion_estado AS ENUM (
  'aceptado',
  'rechazado',
  'pendiente'
);

CREATE TYPE roles AS ENUM (
  'docente',
  'auxiliar'
);

-- =====================
-- TABLAS INDEPENDIENTES
-- =====================

CREATE TABLE usuario (
  id_usuario     integer PRIMARY KEY,
  cod_sis        varchar(20) UNIQUE NOT NULL,
  contraseña     varchar(100) NOT NULL,
  nombre_usuario varchar(50) NOT NULL,
  apellido       varchar(50) NOT NULL
);

CREATE TABLE rol (
  id_rol    integer PRIMARY KEY,
  nombre_rol roles NOT NULL
);

CREATE TABLE estudiante (
  sis_estudiante    varchar(20) PRIMARY KEY,
  nombre_estudiante varchar(50) NOT NULL,
  apellido_estudiante varchar(50) NOT NULL,
  carrera           varchar(80)
);

CREATE TABLE tipo_examen (
  id_tipo_examen    integer PRIMARY KEY,
  nombre_tipo_examen tipo_examen_nombre NOT NULL
);

CREATE TABLE tipo_gestion (
  id_tipo_gestion    integer PRIMARY KEY,
  nombre_tipo_gestion nombre_tipo_gestion NOT NULL
);

CREATE TABLE ambiente (
  id_ambiente    integer PRIMARY KEY,
  nombre_ambiente varchar(50) NOT NULL
);

CREATE TABLE norma (
  id_norma    integer PRIMARY KEY,
  detalle_norma varchar(255) NOT NULL
);

CREATE TABLE material (
  id_material integer PRIMARY KEY,
  descripcion varchar(100) NOT NULL
);

-- =====================
-- TABLAS DEPENDIENTES
-- =====================

CREATE TABLE curso (
  id_curso       integer PRIMARY KEY,
  nombre_curso   varchar(100) NOT NULL,
  sis_doc        integer NOT NULL,          -- corregido: integer (FK a usuario.id_usuario)
  fecha_creacion date,
  estado         curso_estado NOT NULL,
  CONSTRAINT fk_curso_docente FOREIGN KEY (sis_doc) REFERENCES usuario(id_usuario)
);

CREATE TABLE examen (
  id_examen   integer PRIMARY KEY,
  fecha       date,
  hora_inicio time,
  hora_fin    time,
  duracion    integer,
  creador     integer NOT NULL,             -- corregido: integer (FK a usuario.id_usuario)
  tipo_examen integer NOT NULL,
  CONSTRAINT fk_examen_creador FOREIGN KEY (creador) REFERENCES usuario(id_usuario),
  CONSTRAINT fk_examen_tipo FOREIGN KEY (tipo_examen) REFERENCES tipo_examen(id_tipo_examen)
);

CREATE TABLE rol_usuario (
  id_usuario integer,
  id_rol     integer,
  PRIMARY KEY (id_usuario, id_rol),
  CONSTRAINT fk_ru_usuario FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario),
  CONSTRAINT fk_ru_rol     FOREIGN KEY (id_rol)     REFERENCES rol(id_rol)
);

CREATE TABLE curso_tipo_gestion (
  id_curso integer,
  id_tg    integer,
  PRIMARY KEY (id_curso, id_tg),
  CONSTRAINT fk_ctg_curso FOREIGN KEY (id_curso) REFERENCES curso(id_curso),
  CONSTRAINT fk_ctg_tg    FOREIGN KEY (id_tg)    REFERENCES tipo_gestion(id_tipo_gestion)
);

CREATE TABLE estudiante_curso (
  sis_estudiante varchar(20),
  id_curso       integer,
  PRIMARY KEY (sis_estudiante, id_curso),
  CONSTRAINT fk_ec_estudiante FOREIGN KEY (sis_estudiante) REFERENCES estudiante(sis_estudiante),
  CONSTRAINT fk_ec_curso      FOREIGN KEY (id_curso)       REFERENCES curso(id_curso)
);

CREATE TABLE auxiliar_curso (
  id_auxiliar integer,
  id_curso    integer,
  estado      estado_usuario NOT NULL,
  PRIMARY KEY (id_auxiliar, id_curso),
  CONSTRAINT fk_ac_auxiliar FOREIGN KEY (id_auxiliar) REFERENCES usuario(id_usuario),
  CONSTRAINT fk_ac_curso    FOREIGN KEY (id_curso)    REFERENCES curso(id_curso)
);

CREATE TABLE examen_ambiente (
  id_examen   integer,
  id_ambiente integer,
  PRIMARY KEY (id_examen, id_ambiente),
  CONSTRAINT fk_ea_examen   FOREIGN KEY (id_examen)   REFERENCES examen(id_examen),
  CONSTRAINT fk_ea_ambiente FOREIGN KEY (id_ambiente) REFERENCES ambiente(id_ambiente)
);

CREATE TABLE examen_norma (
  id_examen integer,
  id_norma  integer,
  PRIMARY KEY (id_examen, id_norma),
  CONSTRAINT fk_en_examen FOREIGN KEY (id_examen) REFERENCES examen(id_examen),
  CONSTRAINT fk_en_norma  FOREIGN KEY (id_norma)  REFERENCES norma(id_norma)
);

CREATE TABLE examen_material_permitido (
  id_examen              integer,
  id_material_permitido  integer,
  PRIMARY KEY (id_examen, id_material_permitido),
  CONSTRAINT fk_emp_examen   FOREIGN KEY (id_examen)             REFERENCES examen(id_examen),
  CONSTRAINT fk_emp_material FOREIGN KEY (id_material_permitido) REFERENCES material(id_material)
);

CREATE TABLE examen_curso (
  id_examen integer,
  id_curso  integer,
  PRIMARY KEY (id_examen, id_curso),
  CONSTRAINT fk_exc_examen FOREIGN KEY (id_examen) REFERENCES examen(id_examen),
  CONSTRAINT fk_exc_curso  FOREIGN KEY (id_curso)  REFERENCES curso(id_curso)
);

CREATE TABLE estudiante_examen (
  id_estudiante_examen integer PRIMARY KEY,
  sis_estudiante       varchar(20) NOT NULL,
  id_examen            integer NOT NULL,
  estado               estudiante_examen_estado NOT NULL,
  motivo               varchar(255),
  modificado_por       integer,           -- usuario que hizo el ultimo cambio de estado (issue #27)
  fecha_modificacion   timestamp,         -- cuando se hizo ese ultimo cambio (issue #27)
  CONSTRAINT fk_ee_estudiante FOREIGN KEY (sis_estudiante) REFERENCES estudiante(sis_estudiante),
  CONSTRAINT fk_ee_examen     FOREIGN KEY (id_examen)      REFERENCES examen(id_examen),
  CONSTRAINT fk_ee_modificador FOREIGN KEY (modificado_por) REFERENCES usuario(id_usuario)
);

CREATE TABLE estudiante_examen_ambiente (
  id_ee       integer,
  id_ambiente integer,
  PRIMARY KEY (id_ee, id_ambiente),
  CONSTRAINT fk_eea_ee       FOREIGN KEY (id_ee)       REFERENCES estudiante_examen(id_estudiante_examen),
  CONSTRAINT fk_eea_ambiente FOREIGN KEY (id_ambiente) REFERENCES ambiente(id_ambiente)
);

CREATE TABLE registro_asistencia (
  id_ingreso     integer PRIMARY KEY,
  hora_ingreso   time,
  id_examen      integer NOT NULL,
  id_estudiante  varchar(20) NOT NULL,      -- corregido: varchar (FK a estudiante.sis_estudiante)
  id_registrador integer NOT NULL,          -- quien registro la asistencia
  CONSTRAINT fk_ra_examen      FOREIGN KEY (id_examen)      REFERENCES examen(id_examen),
  CONSTRAINT fk_ra_estudiante  FOREIGN KEY (id_estudiante)  REFERENCES estudiante(sis_estudiante),
  CONSTRAINT fk_ra_registrador FOREIGN KEY (id_registrador) REFERENCES usuario(id_usuario)
);

-- La incidencia se sostiene sola: guarda su propio estudiante, examen y
-- registrador, para que tambien se pueda registrar a alguien que todavia no
-- tiene fila de ingreso (o que no estaba en la base de datos). `id_ingreso`
-- queda solo como trazabilidad del monitor en vivo (#70).
--
-- La materia NO se guarda: se deriva con
-- `id_examen -> examen_curso -> curso.nombre_curso`.
CREATE TABLE central_riesgo (
  id_registro     integer PRIMARY KEY,
  sis_estudiante  varchar(20) NOT NULL,     -- el estudiante, exista o no antes en la base
  id_examen       integer NOT NULL,         -- de aqui sale la materia
  id_registrador  integer NOT NULL,         -- docente o auxiliar que registro la incidencia
  motivo          motivo NOT NULL,          -- del enum `motivo`; "otro" se apoya en detalle_motivo
  detalle_motivo  varchar(300),             -- obligatorio cuando el motivo es "otro"; mismo maximo que el formulario
  fecha_registro  timestamp NOT NULL DEFAULT now(),
  tipo_infraccion tipo_infraccion NOT NULL, -- tramposo o sospechoso
  id_ingreso      integer,                  -- solo si la incidencia se observo en el monitor
  CONSTRAINT fk_cr_estudiante FOREIGN KEY (sis_estudiante) REFERENCES estudiante(sis_estudiante),
  CONSTRAINT fk_cr_examen     FOREIGN KEY (id_examen)     REFERENCES examen(id_examen),
  CONSTRAINT fk_cr_registrador FOREIGN KEY (id_registrador) REFERENCES usuario(id_usuario),
  CONSTRAINT fk_cr_ingreso    FOREIGN KEY (id_ingreso)    REFERENCES registro_asistencia(id_ingreso)
);

CREATE TABLE notificacion_docente (
  id_notificacion    integer PRIMARY KEY,
  id_central_riesgo  integer NOT NULL,
  id_curso           integer NOT NULL,
  estado             estado_notificacion NOT NULL,
  CONSTRAINT fk_nd_central FOREIGN KEY (id_central_riesgo) REFERENCES central_riesgo(id_registro),
  CONSTRAINT fk_nd_curso   FOREIGN KEY (id_curso)          REFERENCES curso(id_curso)
);

CREATE TABLE notificacion_auxiliar (
  id_notificacion integer PRIMARY KEY,
  id_curso        integer NOT NULL,
  id_ambiente     integer NOT NULL,
  estado          estado_notificacion NOT NULL,
  CONSTRAINT fk_na_curso    FOREIGN KEY (id_curso)    REFERENCES curso(id_curso),
  CONSTRAINT fk_na_ambiente FOREIGN KEY (id_ambiente) REFERENCES ambiente(id_ambiente)
);

CREATE TABLE invitacion_examen_compartido (
  id_invitacion        integer PRIMARY KEY,
  id_docente_invitado  integer NOT NULL,
  id_examen            integer NOT NULL,
  estado               invitacion_estado NOT NULL,
  CONSTRAINT fk_iec_docente FOREIGN KEY (id_docente_invitado) REFERENCES usuario(id_usuario),
  CONSTRAINT fk_iec_examen  FOREIGN KEY (id_examen)           REFERENCES examen(id_examen)
);

-- ============================================
-- Sincronización de BD: Local -> Servidor
-- Fecha: 2026-09-30 02:46:01
-- ============================================

-- 0. Actualizar estructura del servidor para que coincida con local
ALTER TABLE usuario RENAME COLUMN "contraseña" TO password;

ALTER TABLE central_riesgo ADD COLUMN IF NOT EXISTS sis_estudiante VARCHAR(20);
ALTER TABLE central_riesgo ADD COLUMN IF NOT EXISTS id_examen INTEGER;
ALTER TABLE central_riesgo ADD COLUMN IF NOT EXISTS motivo VARCHAR(50);

ALTER TABLE central_riesgo ADD CONSTRAINT fk_cr_estudiante
  FOREIGN KEY (sis_estudiante) REFERENCES estudiante(sis_estudiante);
ALTER TABLE central_riesgo ADD CONSTRAINT fk_cr_examen
  FOREIGN KEY (id_examen) REFERENCES examen(id_examen);

-- 1. Limpiar tablas existentes
TRUNCATE TABLE central_riesgo, notificacion_docente, notificacion_auxiliar,
  invitacion_examen_compartido, registro_asistencia, estudiante_examen_ambiente,
  estudiante_examen, examen_material_permitido, examen_norma, examen_ambiente,
  examen_curso, estudiante_curso, curso_tipo_gestion, auxiliar_curso,
  examen, curso, estudiante, ambiente, material, norma,
  tipo_examen, tipo_gestion, rol, rol_usuario, usuario CASCADE;

-- 2. Insertar datos

-- usuario (5 registros)
INSERT INTO usuario (id_usuario, cod_sis, password, nombre_usuario, apellido) VALUES (5, 'DOC003', '$2y$10$vE0SJjVzqLk40oLrhN2Kd.G8BETIyz5kmecym86oaELv8wZk8Ak9S', 'Fernando', 'Vargas');
INSERT INTO usuario (id_usuario, cod_sis, password, nombre_usuario, apellido) VALUES (4, 'AUX002', '$2y$10$oCKNmUI8S9rOkYZDe3r9LOyOU6Ouk4/YyRJvsJYdmYDnATSjK.76a', 'Sofia', 'Castro');
INSERT INTO usuario (id_usuario, cod_sis, password, nombre_usuario, apellido) VALUES (1, 'DOC001', '$2y$12$Tt4sKwgJnuyrGPNrJdX7l.nl072YQkoq2T9WN/Flaj6nxEUZjrpSa', 'Roberto', 'Silva');
INSERT INTO usuario (id_usuario, cod_sis, password, nombre_usuario, apellido) VALUES (2, 'DOC002', '$2y$12$ufTD.e4y8DEmaWJW4dHrc.ui/PiamKKTiLploZEWUx0Ki9HxqZg/C', 'Patricia', 'Rojas');
INSERT INTO usuario (id_usuario, cod_sis, password, nombre_usuario, apellido) VALUES (3, 'AUX001', '$2y$12$SCsM./qdbhkp27Q2tx2Ufey.oDWhdTBafZsr4eU.pRCp32PKk0eyu', 'Diego', 'Mendoza');

-- rol (2 registros)
INSERT INTO rol (id_rol, nombre_rol) VALUES (1, 'docente');
INSERT INTO rol (id_rol, nombre_rol) VALUES (2, 'auxiliar');

-- rol_usuario (5 registros)
INSERT INTO rol_usuario (id_usuario, id_rol) VALUES (1, 1);
INSERT INTO rol_usuario (id_usuario, id_rol) VALUES (2, 1);
INSERT INTO rol_usuario (id_usuario, id_rol) VALUES (3, 2);
INSERT INTO rol_usuario (id_usuario, id_rol) VALUES (4, 2);
INSERT INTO rol_usuario (id_usuario, id_rol) VALUES (5, 1);

-- tipo_examen (5 registros)
INSERT INTO tipo_examen (id_tipo_examen, nombre_tipo_examen) VALUES (1, 'PP');
INSERT INTO tipo_examen (id_tipo_examen, nombre_tipo_examen) VALUES (2, 'SP');
INSERT INTO tipo_examen (id_tipo_examen, nombre_tipo_examen) VALUES (3, 'FINAL');
INSERT INTO tipo_examen (id_tipo_examen, nombre_tipo_examen) VALUES (4, 'SI');
INSERT INTO tipo_examen (id_tipo_examen, nombre_tipo_examen) VALUES (5, 'PARCIAL');

-- tipo_gestion (4 registros)
INSERT INTO tipo_gestion (id_tipo_gestion, nombre_tipo_gestion) VALUES (1, 'Primer sem');
INSERT INTO tipo_gestion (id_tipo_gestion, nombre_tipo_gestion) VALUES (2, 'inv');
INSERT INTO tipo_gestion (id_tipo_gestion, nombre_tipo_gestion) VALUES (3, 'Seg sem');
INSERT INTO tipo_gestion (id_tipo_gestion, nombre_tipo_gestion) VALUES (4, 'ver');

-- ambiente (5 registros)
INSERT INTO ambiente (id_ambiente, nombre_ambiente) VALUES (1, 'Aula 101');
INSERT INTO ambiente (id_ambiente, nombre_ambiente) VALUES (2, 'Aula 102');
INSERT INTO ambiente (id_ambiente, nombre_ambiente) VALUES (3, 'Laboratorio A');
INSERT INTO ambiente (id_ambiente, nombre_ambiente) VALUES (4, 'Laboratorio B');
INSERT INTO ambiente (id_ambiente, nombre_ambiente) VALUES (5, 'Aula Magna');

-- material (5 registros)
INSERT INTO material (id_material, descripcion) VALUES (1, 'Calculadora cientifica');
INSERT INTO material (id_material, descripcion) VALUES (2, 'Formulario impreso');
INSERT INTO material (id_material, descripcion) VALUES (3, 'Tabla periodica');
INSERT INTO material (id_material, descripcion) VALUES (4, 'Regla y compas');
INSERT INTO material (id_material, descripcion) VALUES (5, 'Hoja de apuntes');

-- norma (5 registros)
INSERT INTO norma (id_norma, detalle_norma) VALUES (1, 'No usar celulares durante el examen');
INSERT INTO norma (id_norma, detalle_norma) VALUES (2, 'Prohibido hablar con otros estudiantes');
INSERT INTO norma (id_norma, detalle_norma) VALUES (3, 'Solo material autorizado en la mesa');
INSERT INTO norma (id_norma, detalle_norma) VALUES (4, 'Prohibido salir del aula sin permiso');
INSERT INTO norma (id_norma, detalle_norma) VALUES (5, 'No se permite calculadora programable');

-- curso (5 registros)
INSERT INTO curso (id_curso, nombre_curso, sis_doc, fecha_creacion, estado) VALUES (1, 'Calculo I', 1, '2024-02-01', 'EnCurso');
INSERT INTO curso (id_curso, nombre_curso, sis_doc, fecha_creacion, estado) VALUES (2, 'Fisica I', 2, '2024-02-05', 'EnCurso');
INSERT INTO curso (id_curso, nombre_curso, sis_doc, fecha_creacion, estado) VALUES (3, 'Programacion I', 5, '2024-02-10', 'EnCurso');
INSERT INTO curso (id_curso, nombre_curso, sis_doc, fecha_creacion, estado) VALUES (4, 'Algebra Lineal', 1, '2024-02-15', 'Finalizo');
INSERT INTO curso (id_curso, nombre_curso, sis_doc, fecha_creacion, estado) VALUES (5, 'Quimica General', 2, '2024-02-20', 'EnCurso');

-- curso_tipo_gestion (5 registros)
INSERT INTO curso_tipo_gestion (id_curso, id_tg) VALUES (1, 1);
INSERT INTO curso_tipo_gestion (id_curso, id_tg) VALUES (2, 1);
INSERT INTO curso_tipo_gestion (id_curso, id_tg) VALUES (3, 1);
INSERT INTO curso_tipo_gestion (id_curso, id_tg) VALUES (4, 2);
INSERT INTO curso_tipo_gestion (id_curso, id_tg) VALUES (5, 3);

-- auxiliar_curso (5 registros)
INSERT INTO auxiliar_curso (id_auxiliar, id_curso, estado) VALUES (3, 1, 'Activo');
INSERT INTO auxiliar_curso (id_auxiliar, id_curso, estado) VALUES (4, 2, 'Activo');
INSERT INTO auxiliar_curso (id_auxiliar, id_curso, estado) VALUES (3, 3, 'Activo');
INSERT INTO auxiliar_curso (id_auxiliar, id_curso, estado) VALUES (4, 4, 'Baja');
INSERT INTO auxiliar_curso (id_auxiliar, id_curso, estado) VALUES (3, 5, 'Activo');

-- estudiante (54 registros)
INSERT INTO estudiante (sis_estudiante, nombre_estudiante, apellido_estudiante, carrera) VALUES (20210001, 'Juan', 'Perez', 'Ingenieria de Sistemas');
INSERT INTO estudiante (sis_estudiante, nombre_estudiante, apellido_estudiante, carrera) VALUES (20210002, 'Maria', 'Lopez', 'Ingenieria Civil');
INSERT INTO estudiante (sis_estudiante, nombre_estudiante, apellido_estudiante, carrera) VALUES (20210003, 'Carlos', 'Gomez', 'Ingenieria Electronica');
INSERT INTO estudiante (sis_estudiante, nombre_estudiante, apellido_estudiante, carrera) VALUES (20210004, 'Ana', 'Martinez', 'Ingenieria Industrial');
INSERT INTO estudiante (sis_estudiante, nombre_estudiante, apellido_estudiante, carrera) VALUES (20210005, 'Luis', 'Fernandez', 'Ingenieria de Sistemas');
INSERT INTO estudiante (sis_estudiante, nombre_estudiante, apellido_estudiante, carrera) VALUES (202201013, 'Ana', 'López', NULL);
INSERT INTO estudiante (sis_estudiante, nombre_estudiante, apellido_estudiante, carrera) VALUES (202101022, 'Bruno', 'Díaz', NULL);
INSERT INTO estudiante (sis_estudiante, nombre_estudiante, apellido_estudiante, carrera) VALUES (202201031, 'Carla', 'Ruiz', NULL);
INSERT INTO estudiante (sis_estudiante, nombre_estudiante, apellido_estudiante, carrera) VALUES (202202045, 'Diego', 'Soto', NULL);
INSERT INTO estudiante (sis_estudiante, nombre_estudiante, apellido_estudiante, carrera) VALUES (202002107, 'Ernesto', 'Vera', NULL);
INSERT INTO estudiante (sis_estudiante, nombre_estudiante, apellido_estudiante, carrera) VALUES (202201056, 'Fátima', 'Quispe', NULL);
INSERT INTO estudiante (sis_estudiante, nombre_estudiante, apellido_estudiante, carrera) VALUES (202101078, 'Gabriel', 'Mamani', NULL);
INSERT INTO estudiante (sis_estudiante, nombre_estudiante, apellido_estudiante, carrera) VALUES (202201089, 'Helena', 'Choque', NULL);
INSERT INTO estudiante (sis_estudiante, nombre_estudiante, apellido_estudiante, carrera) VALUES (202101090, 'Iván', 'Flores', NULL);
INSERT INTO estudiante (sis_estudiante, nombre_estudiante, apellido_estudiante, carrera) VALUES (202201101, 'Julia', 'García', NULL);
INSERT INTO estudiante (sis_estudiante, nombre_estudiante, apellido_estudiante, carrera) VALUES (202101112, 'Kevin', 'López', NULL);
INSERT INTO estudiante (sis_estudiante, nombre_estudiante, apellido_estudiante, carrera) VALUES (202201123, 'Laura', 'Díaz', NULL);
INSERT INTO estudiante (sis_estudiante, nombre_estudiante, apellido_estudiante, carrera) VALUES (202101134, 'Miguel', 'Soto', NULL);
INSERT INTO estudiante (sis_estudiante, nombre_estudiante, apellido_estudiante, carrera) VALUES (202201145, 'Nadia', 'Vera', NULL);
INSERT INTO estudiante (sis_estudiante, nombre_estudiante, apellido_estudiante, carrera) VALUES (202101156, 'Óscar', 'Quispe', NULL);
INSERT INTO estudiante (sis_estudiante, nombre_estudiante, apellido_estudiante, carrera) VALUES (202201167, 'Patricia', 'Mamani', NULL);
INSERT INTO estudiante (sis_estudiante, nombre_estudiante, apellido_estudiante, carrera) VALUES (202101178, 'Raúl', 'Choque', NULL);
INSERT INTO estudiante (sis_estudiante, nombre_estudiante, apellido_estudiante, carrera) VALUES (202201189, 'Sofía', 'Flores', NULL);
INSERT INTO estudiante (sis_estudiante, nombre_estudiante, apellido_estudiante, carrera) VALUES (202101190, 'Tomás', 'García', NULL);
INSERT INTO estudiante (sis_estudiante, nombre_estudiante, apellido_estudiante, carrera) VALUES (202201201, 'Valeria', 'López', NULL);
INSERT INTO estudiante (sis_estudiante, nombre_estudiante, apellido_estudiante, carrera) VALUES (202201212, 'Wendy', 'Zapata', NULL);
INSERT INTO estudiante (sis_estudiante, nombre_estudiante, apellido_estudiante, carrera) VALUES (202101223, 'Xavier', 'Vargas', NULL);
INSERT INTO estudiante (sis_estudiante, nombre_estudiante, apellido_estudiante, carrera) VALUES (202201234, 'Yolanda', 'Ticona', NULL);
INSERT INTO estudiante (sis_estudiante, nombre_estudiante, apellido_estudiante, carrera) VALUES (202101245, 'Zoe', 'Siles', NULL);
INSERT INTO estudiante (sis_estudiante, nombre_estudiante, apellido_estudiante, carrera) VALUES (202201256, 'Andrés', 'Rojas', NULL);
INSERT INTO estudiante (sis_estudiante, nombre_estudiante, apellido_estudiante, carrera) VALUES (202101267, 'Bianca', 'Paredes', NULL);
INSERT INTO estudiante (sis_estudiante, nombre_estudiante, apellido_estudiante, carrera) VALUES (202201278, 'Cristian', 'Molina', NULL);
INSERT INTO estudiante (sis_estudiante, nombre_estudiante, apellido_estudiante, carrera) VALUES (202101289, 'Daniela', 'Cabrera', NULL);
INSERT INTO estudiante (sis_estudiante, nombre_estudiante, apellido_estudiante, carrera) VALUES (202201290, 'Eduardo', 'Salazar', NULL);
INSERT INTO estudiante (sis_estudiante, nombre_estudiante, apellido_estudiante, carrera) VALUES (202101301, 'Fernanda', 'Vega', NULL);
INSERT INTO estudiante (sis_estudiante, nombre_estudiante, apellido_estudiante, carrera) VALUES (202201312, 'Gonzalo', 'Arce', NULL);
INSERT INTO estudiante (sis_estudiante, nombre_estudiante, apellido_estudiante, carrera) VALUES (202101323, 'Hugo', 'Camacho', NULL);
INSERT INTO estudiante (sis_estudiante, nombre_estudiante, apellido_estudiante, carrera) VALUES (202201334, 'Inés', 'Delgado', NULL);
INSERT INTO estudiante (sis_estudiante, nombre_estudiante, apellido_estudiante, carrera) VALUES (202101345, 'Javier', 'Espinoza', NULL);
INSERT INTO estudiante (sis_estudiante, nombre_estudiante, apellido_estudiante, carrera) VALUES (202201356, 'Karen', 'Fuentes', NULL);
INSERT INTO estudiante (sis_estudiante, nombre_estudiante, apellido_estudiante, carrera) VALUES (202101367, 'Luis', 'Gutiérrez', NULL);
INSERT INTO estudiante (sis_estudiante, nombre_estudiante, apellido_estudiante, carrera) VALUES (202201378, 'María', 'Herrera', NULL);
INSERT INTO estudiante (sis_estudiante, nombre_estudiante, apellido_estudiante, carrera) VALUES (202101389, 'Nicolás', 'Iriarte', NULL);
INSERT INTO estudiante (sis_estudiante, nombre_estudiante, apellido_estudiante, carrera) VALUES (202201390, 'Olga', 'Jiménez', NULL);
INSERT INTO estudiante (sis_estudiante, nombre_estudiante, apellido_estudiante, carrera) VALUES (202101401, 'Pablo', 'Kowalski', NULL);
INSERT INTO estudiante (sis_estudiante, nombre_estudiante, apellido_estudiante, carrera) VALUES (202201412, 'Rosa', 'Luna', NULL);
INSERT INTO estudiante (sis_estudiante, nombre_estudiante, apellido_estudiante, carrera) VALUES (202101423, 'Sergio', 'Mendoza', NULL);
INSERT INTO estudiante (sis_estudiante, nombre_estudiante, apellido_estudiante, carrera) VALUES (202201434, 'Tatiana', 'Núñez', NULL);
INSERT INTO estudiante (sis_estudiante, nombre_estudiante, apellido_estudiante, carrera) VALUES (202101445, 'Ulises', 'Orellana', NULL);
INSERT INTO estudiante (sis_estudiante, nombre_estudiante, apellido_estudiante, carrera) VALUES (202201456, 'Verónica', 'Paz', NULL);
INSERT INTO estudiante (sis_estudiante, nombre_estudiante, apellido_estudiante, carrera) VALUES (202101467, 'Walter', 'Quiroga', NULL);
INSERT INTO estudiante (sis_estudiante, nombre_estudiante, apellido_estudiante, carrera) VALUES (202201478, 'Ximena', 'Ramírez', NULL);
INSERT INTO estudiante (sis_estudiante, nombre_estudiante, apellido_estudiante, carrera) VALUES (202101489, 'Yamil', 'Sánchez', NULL);
INSERT INTO estudiante (sis_estudiante, nombre_estudiante, apellido_estudiante, carrera) VALUES (202201490, 'Zaira', 'Toledo', NULL);

-- estudiante_curso (5 registros)
INSERT INTO estudiante_curso (sis_estudiante, id_curso) VALUES (20210001, 1);
INSERT INTO estudiante_curso (sis_estudiante, id_curso) VALUES (20210002, 2);
INSERT INTO estudiante_curso (sis_estudiante, id_curso) VALUES (20210003, 3);
INSERT INTO estudiante_curso (sis_estudiante, id_curso) VALUES (20210004, 4);
INSERT INTO estudiante_curso (sis_estudiante, id_curso) VALUES (20210005, 5);

-- examen (5 registros)
INSERT INTO examen (id_examen, fecha, hora_inicio, duracion, creador, tipo_examen) VALUES (1, '2024-06-10', '08:00:00', 120, 1, 1);
INSERT INTO examen (id_examen, fecha, hora_inicio, duracion, creador, tipo_examen) VALUES (2, '2024-06-11', '10:00:00', 120, 2, 2);
INSERT INTO examen (id_examen, fecha, hora_inicio, duracion, creador, tipo_examen) VALUES (3, '2024-06-12', '14:00:00', 120, 5, 3);
INSERT INTO examen (id_examen, fecha, hora_inicio, duracion, creador, tipo_examen) VALUES (4, '2024-06-13', '08:00:00', 90, 1, 4);
INSERT INTO examen (id_examen, fecha, hora_inicio, duracion, creador, tipo_examen) VALUES (5, '2024-06-14', '16:00:00', 120, 2, 5);

-- examen_curso (5 registros)
INSERT INTO examen_curso (id_examen, id_curso) VALUES (1, 1);
INSERT INTO examen_curso (id_examen, id_curso) VALUES (2, 2);
INSERT INTO examen_curso (id_examen, id_curso) VALUES (3, 3);
INSERT INTO examen_curso (id_examen, id_curso) VALUES (4, 4);
INSERT INTO examen_curso (id_examen, id_curso) VALUES (5, 5);

-- examen_ambiente (5 registros)
INSERT INTO examen_ambiente (id_examen, id_ambiente) VALUES (1, 1);
INSERT INTO examen_ambiente (id_examen, id_ambiente) VALUES (2, 2);
INSERT INTO examen_ambiente (id_examen, id_ambiente) VALUES (3, 3);
INSERT INTO examen_ambiente (id_examen, id_ambiente) VALUES (4, 4);
INSERT INTO examen_ambiente (id_examen, id_ambiente) VALUES (5, 5);

-- examen_norma (5 registros)
INSERT INTO examen_norma (id_examen, id_norma) VALUES (1, 1);
INSERT INTO examen_norma (id_examen, id_norma) VALUES (2, 2);
INSERT INTO examen_norma (id_examen, id_norma) VALUES (3, 3);
INSERT INTO examen_norma (id_examen, id_norma) VALUES (4, 4);
INSERT INTO examen_norma (id_examen, id_norma) VALUES (5, 5);

-- examen_material_permitido (5 registros)
INSERT INTO examen_material_permitido (id_examen, id_material_permitido) VALUES (1, 1);
INSERT INTO examen_material_permitido (id_examen, id_material_permitido) VALUES (2, 2);
INSERT INTO examen_material_permitido (id_examen, id_material_permitido) VALUES (3, 3);
INSERT INTO examen_material_permitido (id_examen, id_material_permitido) VALUES (4, 4);
INSERT INTO examen_material_permitido (id_examen, id_material_permitido) VALUES (5, 5);

-- estudiante_examen (53 registros)
INSERT INTO estudiante_examen (id_estudiante_examen, sis_estudiante, id_examen, estado, motivo, modificado_por, fecha_modificacion) VALUES (1, 20210001, 1, 'habilitado', NULL, NULL, NULL);
INSERT INTO estudiante_examen (id_estudiante_examen, sis_estudiante, id_examen, estado, motivo, modificado_por, fecha_modificacion) VALUES (2, 20210002, 2, 'habilitado', NULL, NULL, NULL);
INSERT INTO estudiante_examen (id_estudiante_examen, sis_estudiante, id_examen, estado, motivo, modificado_por, fecha_modificacion) VALUES (3, 20210003, 3, 'deshabilitado', 'No cumple requisitos', NULL, NULL);
INSERT INTO estudiante_examen (id_estudiante_examen, sis_estudiante, id_examen, estado, motivo, modificado_por, fecha_modificacion) VALUES (4, 20210004, 4, 'habilitado', NULL, NULL, NULL);
INSERT INTO estudiante_examen (id_estudiante_examen, sis_estudiante, id_examen, estado, motivo, modificado_por, fecha_modificacion) VALUES (101, 202201013, 5, 'habilitado', NULL, NULL, NULL);
INSERT INTO estudiante_examen (id_estudiante_examen, sis_estudiante, id_examen, estado, motivo, modificado_por, fecha_modificacion) VALUES (102, 202101022, 5, 'habilitado', NULL, NULL, NULL);
INSERT INTO estudiante_examen (id_estudiante_examen, sis_estudiante, id_examen, estado, motivo, modificado_por, fecha_modificacion) VALUES (103, 202201031, 5, 'habilitado', NULL, NULL, NULL);
INSERT INTO estudiante_examen (id_estudiante_examen, sis_estudiante, id_examen, estado, motivo, modificado_por, fecha_modificacion) VALUES (104, 202202045, 5, 'deshabilitado', NULL, NULL, NULL);
INSERT INTO estudiante_examen (id_estudiante_examen, sis_estudiante, id_examen, estado, motivo, modificado_por, fecha_modificacion) VALUES (105, 202002107, 5, 'habilitado', NULL, NULL, NULL);
INSERT INTO estudiante_examen (id_estudiante_examen, sis_estudiante, id_examen, estado, motivo, modificado_por, fecha_modificacion) VALUES (106, 202201056, 5, 'habilitado', NULL, NULL, NULL);
INSERT INTO estudiante_examen (id_estudiante_examen, sis_estudiante, id_examen, estado, motivo, modificado_por, fecha_modificacion) VALUES (107, 202101078, 5, 'deshabilitado', NULL, NULL, NULL);
INSERT INTO estudiante_examen (id_estudiante_examen, sis_estudiante, id_examen, estado, motivo, modificado_por, fecha_modificacion) VALUES (108, 202201089, 5, 'habilitado', NULL, NULL, NULL);
INSERT INTO estudiante_examen (id_estudiante_examen, sis_estudiante, id_examen, estado, motivo, modificado_por, fecha_modificacion) VALUES (109, 202101090, 5, 'habilitado', NULL, NULL, NULL);
INSERT INTO estudiante_examen (id_estudiante_examen, sis_estudiante, id_examen, estado, motivo, modificado_por, fecha_modificacion) VALUES (110, 202201101, 5, 'habilitado', NULL, NULL, NULL);
INSERT INTO estudiante_examen (id_estudiante_examen, sis_estudiante, id_examen, estado, motivo, modificado_por, fecha_modificacion) VALUES (111, 202101112, 5, 'habilitado', NULL, NULL, NULL);
INSERT INTO estudiante_examen (id_estudiante_examen, sis_estudiante, id_examen, estado, motivo, modificado_por, fecha_modificacion) VALUES (112, 202201123, 5, 'habilitado', NULL, NULL, NULL);
INSERT INTO estudiante_examen (id_estudiante_examen, sis_estudiante, id_examen, estado, motivo, modificado_por, fecha_modificacion) VALUES (113, 202101134, 5, 'deshabilitado', NULL, NULL, NULL);
INSERT INTO estudiante_examen (id_estudiante_examen, sis_estudiante, id_examen, estado, motivo, modificado_por, fecha_modificacion) VALUES (114, 202201145, 5, 'habilitado', NULL, NULL, NULL);
INSERT INTO estudiante_examen (id_estudiante_examen, sis_estudiante, id_examen, estado, motivo, modificado_por, fecha_modificacion) VALUES (115, 202101156, 5, 'habilitado', NULL, NULL, NULL);
INSERT INTO estudiante_examen (id_estudiante_examen, sis_estudiante, id_examen, estado, motivo, modificado_por, fecha_modificacion) VALUES (116, 202201167, 5, 'habilitado', NULL, NULL, NULL);
INSERT INTO estudiante_examen (id_estudiante_examen, sis_estudiante, id_examen, estado, motivo, modificado_por, fecha_modificacion) VALUES (117, 202101178, 5, 'habilitado', NULL, NULL, NULL);
INSERT INTO estudiante_examen (id_estudiante_examen, sis_estudiante, id_examen, estado, motivo, modificado_por, fecha_modificacion) VALUES (118, 202201189, 5, 'habilitado', NULL, NULL, NULL);
INSERT INTO estudiante_examen (id_estudiante_examen, sis_estudiante, id_examen, estado, motivo, modificado_por, fecha_modificacion) VALUES (119, 202101190, 5, 'deshabilitado', NULL, NULL, NULL);
INSERT INTO estudiante_examen (id_estudiante_examen, sis_estudiante, id_examen, estado, motivo, modificado_por, fecha_modificacion) VALUES (120, 202201201, 5, 'habilitado', NULL, NULL, NULL);
INSERT INTO estudiante_examen (id_estudiante_examen, sis_estudiante, id_examen, estado, motivo, modificado_por, fecha_modificacion) VALUES (121, 202201212, 5, 'habilitado', NULL, NULL, NULL);
INSERT INTO estudiante_examen (id_estudiante_examen, sis_estudiante, id_examen, estado, motivo, modificado_por, fecha_modificacion) VALUES (122, 202101223, 5, 'habilitado', NULL, NULL, NULL);
INSERT INTO estudiante_examen (id_estudiante_examen, sis_estudiante, id_examen, estado, motivo, modificado_por, fecha_modificacion) VALUES (123, 202201234, 5, 'habilitado', NULL, NULL, NULL);
INSERT INTO estudiante_examen (id_estudiante_examen, sis_estudiante, id_examen, estado, motivo, modificado_por, fecha_modificacion) VALUES (124, 202101245, 5, 'habilitado', NULL, NULL, NULL);
INSERT INTO estudiante_examen (id_estudiante_examen, sis_estudiante, id_examen, estado, motivo, modificado_por, fecha_modificacion) VALUES (125, 202201256, 5, 'habilitado', NULL, NULL, NULL);
INSERT INTO estudiante_examen (id_estudiante_examen, sis_estudiante, id_examen, estado, motivo, modificado_por, fecha_modificacion) VALUES (126, 202101267, 5, 'habilitado', NULL, NULL, NULL);
INSERT INTO estudiante_examen (id_estudiante_examen, sis_estudiante, id_examen, estado, motivo, modificado_por, fecha_modificacion) VALUES (127, 202201278, 5, 'habilitado', NULL, NULL, NULL);
INSERT INTO estudiante_examen (id_estudiante_examen, sis_estudiante, id_examen, estado, motivo, modificado_por, fecha_modificacion) VALUES (128, 202101289, 5, 'habilitado', NULL, NULL, NULL);
INSERT INTO estudiante_examen (id_estudiante_examen, sis_estudiante, id_examen, estado, motivo, modificado_por, fecha_modificacion) VALUES (129, 202201290, 5, 'habilitado', NULL, NULL, NULL);
INSERT INTO estudiante_examen (id_estudiante_examen, sis_estudiante, id_examen, estado, motivo, modificado_por, fecha_modificacion) VALUES (130, 202101301, 5, 'habilitado', NULL, NULL, NULL);
INSERT INTO estudiante_examen (id_estudiante_examen, sis_estudiante, id_examen, estado, motivo, modificado_por, fecha_modificacion) VALUES (131, 202201312, 5, 'habilitado', NULL, NULL, NULL);
INSERT INTO estudiante_examen (id_estudiante_examen, sis_estudiante, id_examen, estado, motivo, modificado_por, fecha_modificacion) VALUES (132, 202101323, 5, 'habilitado', NULL, NULL, NULL);
INSERT INTO estudiante_examen (id_estudiante_examen, sis_estudiante, id_examen, estado, motivo, modificado_por, fecha_modificacion) VALUES (133, 202201334, 5, 'habilitado', NULL, NULL, NULL);
INSERT INTO estudiante_examen (id_estudiante_examen, sis_estudiante, id_examen, estado, motivo, modificado_por, fecha_modificacion) VALUES (134, 202101345, 5, 'habilitado', NULL, NULL, NULL);
INSERT INTO estudiante_examen (id_estudiante_examen, sis_estudiante, id_examen, estado, motivo, modificado_por, fecha_modificacion) VALUES (135, 202201356, 5, 'habilitado', NULL, NULL, NULL);
INSERT INTO estudiante_examen (id_estudiante_examen, sis_estudiante, id_examen, estado, motivo, modificado_por, fecha_modificacion) VALUES (136, 202101367, 5, 'habilitado', NULL, NULL, NULL);
INSERT INTO estudiante_examen (id_estudiante_examen, sis_estudiante, id_examen, estado, motivo, modificado_por, fecha_modificacion) VALUES (137, 202201378, 5, 'habilitado', NULL, NULL, NULL);
INSERT INTO estudiante_examen (id_estudiante_examen, sis_estudiante, id_examen, estado, motivo, modificado_por, fecha_modificacion) VALUES (138, 202101389, 5, 'habilitado', NULL, NULL, NULL);
INSERT INTO estudiante_examen (id_estudiante_examen, sis_estudiante, id_examen, estado, motivo, modificado_por, fecha_modificacion) VALUES (139, 202201390, 5, 'habilitado', NULL, NULL, NULL);
INSERT INTO estudiante_examen (id_estudiante_examen, sis_estudiante, id_examen, estado, motivo, modificado_por, fecha_modificacion) VALUES (140, 202101401, 5, 'habilitado', NULL, NULL, NULL);
INSERT INTO estudiante_examen (id_estudiante_examen, sis_estudiante, id_examen, estado, motivo, modificado_por, fecha_modificacion) VALUES (141, 202201412, 5, 'habilitado', NULL, NULL, NULL);
INSERT INTO estudiante_examen (id_estudiante_examen, sis_estudiante, id_examen, estado, motivo, modificado_por, fecha_modificacion) VALUES (142, 202101423, 5, 'habilitado', NULL, NULL, NULL);
INSERT INTO estudiante_examen (id_estudiante_examen, sis_estudiante, id_examen, estado, motivo, modificado_por, fecha_modificacion) VALUES (143, 202201434, 5, 'habilitado', NULL, NULL, NULL);
INSERT INTO estudiante_examen (id_estudiante_examen, sis_estudiante, id_examen, estado, motivo, modificado_por, fecha_modificacion) VALUES (144, 202101445, 5, 'habilitado', NULL, NULL, NULL);
INSERT INTO estudiante_examen (id_estudiante_examen, sis_estudiante, id_examen, estado, motivo, modificado_por, fecha_modificacion) VALUES (145, 202201456, 5, 'habilitado', NULL, NULL, NULL);
INSERT INTO estudiante_examen (id_estudiante_examen, sis_estudiante, id_examen, estado, motivo, modificado_por, fecha_modificacion) VALUES (146, 202101467, 5, 'habilitado', NULL, NULL, NULL);
INSERT INTO estudiante_examen (id_estudiante_examen, sis_estudiante, id_examen, estado, motivo, modificado_por, fecha_modificacion) VALUES (147, 202201478, 5, 'habilitado', NULL, NULL, NULL);
INSERT INTO estudiante_examen (id_estudiante_examen, sis_estudiante, id_examen, estado, motivo, modificado_por, fecha_modificacion) VALUES (148, 202101489, 5, 'habilitado', NULL, NULL, NULL);
INSERT INTO estudiante_examen (id_estudiante_examen, sis_estudiante, id_examen, estado, motivo, modificado_por, fecha_modificacion) VALUES (149, 202201490, 5, 'habilitado', NULL, NULL, NULL);

-- estudiante_examen_ambiente (4 registros)
INSERT INTO estudiante_examen_ambiente (id_ee, id_ambiente) VALUES (1, 1);
INSERT INTO estudiante_examen_ambiente (id_ee, id_ambiente) VALUES (2, 2);
INSERT INTO estudiante_examen_ambiente (id_ee, id_ambiente) VALUES (3, 3);
INSERT INTO estudiante_examen_ambiente (id_ee, id_ambiente) VALUES (4, 4);

-- registro_asistencia (31 registros)
INSERT INTO registro_asistencia (id_ingreso, hora_ingreso, id_examen, id_estudiante, id_registrador) VALUES (1, '08:05:00', 1, 20210001, 3);
INSERT INTO registro_asistencia (id_ingreso, hora_ingreso, id_examen, id_estudiante, id_registrador) VALUES (2, '10:03:00', 2, 20210002, 4);
INSERT INTO registro_asistencia (id_ingreso, hora_ingreso, id_examen, id_estudiante, id_registrador) VALUES (3, '14:10:00', 3, 20210003, 3);
INSERT INTO registro_asistencia (id_ingreso, hora_ingreso, id_examen, id_estudiante, id_registrador) VALUES (4, '08:02:00', 4, 20210004, 4);
INSERT INTO registro_asistencia (id_ingreso, hora_ingreso, id_examen, id_estudiante, id_registrador) VALUES (201, '16:45:00', 5, 202201013, 1);
INSERT INTO registro_asistencia (id_ingreso, hora_ingreso, id_examen, id_estudiante, id_registrador) VALUES (202, '16:16:00', 5, 202101022, 2);
INSERT INTO registro_asistencia (id_ingreso, hora_ingreso, id_examen, id_estudiante, id_registrador) VALUES (203, '16:16:00', 5, 202002107, 2);
INSERT INTO registro_asistencia (id_ingreso, hora_ingreso, id_examen, id_estudiante, id_registrador) VALUES (204, '16:21:00', 5, 202101078, 1);
INSERT INTO registro_asistencia (id_ingreso, hora_ingreso, id_examen, id_estudiante, id_registrador) VALUES (205, '16:26:00', 5, 202201101, 1);
INSERT INTO registro_asistencia (id_ingreso, hora_ingreso, id_examen, id_estudiante, id_registrador) VALUES (206, '16:02:00', 5, 202201123, 2);
INSERT INTO registro_asistencia (id_ingreso, hora_ingreso, id_examen, id_estudiante, id_registrador) VALUES (207, '16:33:00', 5, 202101156, 2);
INSERT INTO registro_asistencia (id_ingreso, hora_ingreso, id_examen, id_estudiante, id_registrador) VALUES (208, '16:46:00', 5, 202201189, 1);
INSERT INTO registro_asistencia (id_ingreso, hora_ingreso, id_examen, id_estudiante, id_registrador) VALUES (301, '16:41:00', 5, 202101223, 1);
INSERT INTO registro_asistencia (id_ingreso, hora_ingreso, id_examen, id_estudiante, id_registrador) VALUES (302, '16:52:00', 5, 202201234, 1);
INSERT INTO registro_asistencia (id_ingreso, hora_ingreso, id_examen, id_estudiante, id_registrador) VALUES (303, '16:39:00', 5, 202201256, 2);
INSERT INTO registro_asistencia (id_ingreso, hora_ingreso, id_examen, id_estudiante, id_registrador) VALUES (304, '16:29:00', 5, 202101267, 1);
INSERT INTO registro_asistencia (id_ingreso, hora_ingreso, id_examen, id_estudiante, id_registrador) VALUES (305, '16:54:00', 5, 202101289, 2);
INSERT INTO registro_asistencia (id_ingreso, hora_ingreso, id_examen, id_estudiante, id_registrador) VALUES (306, '16:20:00', 5, 202201290, 2);
INSERT INTO registro_asistencia (id_ingreso, hora_ingreso, id_examen, id_estudiante, id_registrador) VALUES (307, '16:05:00', 5, 202201312, 2);
INSERT INTO registro_asistencia (id_ingreso, hora_ingreso, id_examen, id_estudiante, id_registrador) VALUES (308, '16:24:00', 5, 202101323, 1);
INSERT INTO registro_asistencia (id_ingreso, hora_ingreso, id_examen, id_estudiante, id_registrador) VALUES (309, '16:37:00', 5, 202101345, 1);
INSERT INTO registro_asistencia (id_ingreso, hora_ingreso, id_examen, id_estudiante, id_registrador) VALUES (310, '16:02:00', 5, 202201356, 2);
INSERT INTO registro_asistencia (id_ingreso, hora_ingreso, id_examen, id_estudiante, id_registrador) VALUES (311, '16:52:00', 5, 202201378, 1);
INSERT INTO registro_asistencia (id_ingreso, hora_ingreso, id_examen, id_estudiante, id_registrador) VALUES (312, '16:10:00', 5, 202101389, 2);
INSERT INTO registro_asistencia (id_ingreso, hora_ingreso, id_examen, id_estudiante, id_registrador) VALUES (313, '16:38:00', 5, 202101401, 1);
INSERT INTO registro_asistencia (id_ingreso, hora_ingreso, id_examen, id_estudiante, id_registrador) VALUES (314, '16:45:00', 5, 202201412, 2);
INSERT INTO registro_asistencia (id_ingreso, hora_ingreso, id_examen, id_estudiante, id_registrador) VALUES (315, '16:10:00', 5, 202201434, 2);
INSERT INTO registro_asistencia (id_ingreso, hora_ingreso, id_examen, id_estudiante, id_registrador) VALUES (316, '16:53:00', 5, 202101445, 1);
INSERT INTO registro_asistencia (id_ingreso, hora_ingreso, id_examen, id_estudiante, id_registrador) VALUES (317, '16:31:00', 5, 202101467, 2);
INSERT INTO registro_asistencia (id_ingreso, hora_ingreso, id_examen, id_estudiante, id_registrador) VALUES (318, '16:09:00', 5, 202201478, 2);
INSERT INTO registro_asistencia (id_ingreso, hora_ingreso, id_examen, id_estudiante, id_registrador) VALUES (319, '16:17:00', 5, 202201490, 1);

-- central_riesgo (4 registros)
INSERT INTO central_riesgo (id_registro, id_ingreso, id_registrador, detalle_motivo, fecha_registro, tipo_infraccion, estado_incidencia) VALUES (1, 1, 3, 'Estudiante mirando hacia otro lado', '2024-06-10', 'sospechoso', 'Pendiente');
INSERT INTO central_riesgo (id_registro, id_ingreso, id_registrador, detalle_motivo, fecha_registro, tipo_infraccion, estado_incidencia) VALUES (2, 2, 4, 'Uso de celular durante el examen', '2024-06-11', 'tramposo', 'Confirmado');
INSERT INTO central_riesgo (id_registro, id_ingreso, id_registrador, detalle_motivo, fecha_registro, tipo_infraccion, estado_incidencia) VALUES (3, 3, 3, 'Llegada tarde al examen', '2024-06-12', 'pendiente', 'Pendiente');
INSERT INTO central_riesgo (id_registro, id_ingreso, id_registrador, detalle_motivo, fecha_registro, tipo_infraccion, estado_incidencia) VALUES (4, 4, 4, 'Estudiante en aula equivocada', '2024-06-13', 'aula equivocada', 'Confirmado');

-- invitacion_examen_compartido (5 registros)
INSERT INTO invitacion_examen_compartido (id_invitacion, id_docente_invitado, id_examen, estado) VALUES (1, 2, 1, 'aceptado');
INSERT INTO invitacion_examen_compartido (id_invitacion, id_docente_invitado, id_examen, estado) VALUES (2, 5, 2, 'pendiente');
INSERT INTO invitacion_examen_compartido (id_invitacion, id_docente_invitado, id_examen, estado) VALUES (3, 1, 3, 'rechazado');
INSERT INTO invitacion_examen_compartido (id_invitacion, id_docente_invitado, id_examen, estado) VALUES (4, 2, 4, 'aceptado');
INSERT INTO invitacion_examen_compartido (id_invitacion, id_docente_invitado, id_examen, estado) VALUES (5, 5, 5, 'pendiente');

-- notificacion_auxiliar (5 registros)
INSERT INTO notificacion_auxiliar (id_notificacion, id_curso, id_ambiente, estado) VALUES (1, 1, 1, 'visto');
INSERT INTO notificacion_auxiliar (id_notificacion, id_curso, id_ambiente, estado) VALUES (2, 2, 2, 'recibido');
INSERT INTO notificacion_auxiliar (id_notificacion, id_curso, id_ambiente, estado) VALUES (3, 3, 3, 'visto');
INSERT INTO notificacion_auxiliar (id_notificacion, id_curso, id_ambiente, estado) VALUES (4, 4, 4, 'recibido');
INSERT INTO notificacion_auxiliar (id_notificacion, id_curso, id_ambiente, estado) VALUES (5, 5, 5, 'visto');

-- notificacion_docente (4 registros)
INSERT INTO notificacion_docente (id_notificacion, id_central_riesgo, id_curso, estado) VALUES (1, 1, 1, 'visto');
INSERT INTO notificacion_docente (id_notificacion, id_central_riesgo, id_curso, estado) VALUES (2, 2, 2, 'recibido');
INSERT INTO notificacion_docente (id_notificacion, id_central_riesgo, id_curso, estado) VALUES (3, 3, 3, 'visto');
INSERT INTO notificacion_docente (id_notificacion, id_central_riesgo, id_curso, estado) VALUES (4, 4, 4, 'recibido');

-- 3. Verificación
SELECT 'usuario' as tabla, count(*) as total FROM usuario
UNION ALL SELECT 'estudiante', count(*) FROM estudiante
UNION ALL SELECT 'curso', count(*) FROM curso
UNION ALL SELECT 'examen', count(*) FROM examen
UNION ALL SELECT 'estudiante_examen', count(*) FROM estudiante_examen
UNION ALL SELECT 'registro_asistencia', count(*) FROM registro_asistencia
UNION ALL SELECT 'central_riesgo', count(*) FROM central_riesgo;

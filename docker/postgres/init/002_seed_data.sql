-- ============================================================
-- SCRIPT DE LLENADO DE DATOS
-- ============================================================

-- =====================
-- TABLAS INDEPENDIENTES
-- =====================

INSERT INTO usuario (id_usuario, cod_sis, contraseña, nombre_usuario, apellido) VALUES
(1, 'DOC001', 'pass123', 'Roberto',  'Silva'),
(2, 'DOC002', 'pass456', 'Patricia', 'Rojas'),
(3, 'AUX001', 'pass789', 'Diego',    'Mendoza'),
(4, 'AUX002', 'pass321', 'Sofia',    'Castro'),
(5, 'DOC003', 'pass654', 'Fernando', 'Vargas');

INSERT INTO rol (id_rol, nombre_rol) VALUES
(1, 'docente'),
(2, 'auxiliar');

INSERT INTO estudiante (sis_estudiante, nombre_estudiante, apellido_estudiante, carrera) VALUES
('20210001', 'Juan',   'Perez',     'Ingenieria de Sistemas'),
('20210002', 'Maria',  'Lopez',     'Ingenieria Civil'),
('20210003', 'Carlos', 'Gomez',     'Ingenieria Electronica'),
('20210004', 'Ana',    'Martinez',  'Ingenieria Industrial'),
('20210005', 'Luis',   'Fernandez', 'Ingenieria de Sistemas');

INSERT INTO tipo_examen (id_tipo_examen, nombre_tipo_examen) VALUES
(1, 'PP'),
(2, 'SP'),
(3, 'FINAL'),
(4, 'SI'),
(5, 'PARCIAL');

INSERT INTO tipo_gestion (id_tipo_gestion, nombre_tipo_gestion) VALUES
(1, 'Primer sem'),
(2, 'inv'),
(3, 'Seg sem'),
(4, 'ver'),
(5, 'Primer sem');

INSERT INTO ambiente (id_ambiente, nombre_ambiente) VALUES
(1, 'Aula 101'),
(2, 'Aula 102'),
(3, 'Laboratorio A'),
(4, 'Laboratorio B'),
(5, 'Aula Magna');

INSERT INTO norma (id_norma, detalle_norma) VALUES
(1, 'No usar celulares durante el examen'),
(2, 'Prohibido hablar con otros estudiantes'),
(3, 'Solo material autorizado en la mesa'),
(4, 'Prohibido salir del aula sin permiso'),
(5, 'No se permite calculadora programable');

INSERT INTO material (id_material, descripcion) VALUES
(1, 'Calculadora cientifica'),
(2, 'Formulario impreso'),
(3, 'Tabla periodica'),
(4, 'Regla y compas'),
(5, 'Hoja de apuntes');

-- =====================
-- TABLAS DEPENDIENTES
-- =====================

INSERT INTO rol_usuario (id_usuario, id_rol) VALUES
(1, 1),
(2, 1),
(3, 2),
(4, 2),
(5, 1);

INSERT INTO curso (id_curso, nombre_curso, sis_doc, fecha_creacion, estado) VALUES
(1, 'Calculo I',       1, '2024-02-01', 'EnCurso'),
(2, 'Fisica I',        2, '2024-02-05', 'EnCurso'),
(3, 'Programacion I',  5, '2024-02-10', 'EnCurso'),
(4, 'Algebra Lineal',  1, '2024-02-15', 'Finalizo'),
(5, 'Quimica General', 2, '2024-02-20', 'EnCurso');

INSERT INTO examen (id_examen, fecha, hora_inicio, hora_fin, duracion, creador, tipo_examen) VALUES
(1, '2024-06-10', '08:00', '10:00', 120, 1, 1),
(2, '2024-06-11', '10:00', '12:00', 120, 2, 2),
(3, '2024-06-12', '14:00', '16:00', 120, 5, 3),
(4, '2024-06-13', '08:00', '09:30',  90, 1, 4),
(5, '2024-06-14', '16:00', '18:00', 120, 2, 5);

INSERT INTO curso_tipo_gestion (id_curso, id_tg) VALUES
(1, 1),
(2, 1),
(3, 1),
(4, 2),
(5, 3);

INSERT INTO estudiante_curso (sis_estudiante, id_curso) VALUES
('20210001', 1),
('20210002', 2),
('20210003', 3),
('20210004', 4),
('20210005', 5);

INSERT INTO auxiliar_curso (id_auxiliar, id_curso, estado) VALUES
(3, 1, 'Activo'),
(4, 2, 'Activo'),
(3, 3, 'Activo'),
(4, 4, 'Baja'),
(3, 5, 'Activo');

INSERT INTO examen_ambiente (id_examen, id_ambiente) VALUES
(1, 1),
(2, 2),
(3, 3),
(4, 4),
(5, 5);

INSERT INTO examen_norma (id_examen, id_norma) VALUES
(1, 1),
(2, 2),
(3, 3),
(4, 4),
(5, 5);

INSERT INTO examen_material_permitido (id_examen, id_material_permitido) VALUES
(1, 1),
(2, 2),
(3, 3),
(4, 4),
(5, 5);

INSERT INTO examen_curso (id_examen, id_curso) VALUES
(1, 1),
(2, 2),
(3, 3),
(4, 4),
(5, 5);

INSERT INTO estudiante_examen (id_estudiante_examen, sis_estudiante, id_examen, estado, motivo) VALUES
(1, '20210001', 1, 'habilitado',    NULL),
(2, '20210002', 2, 'habilitado',    NULL),
(3, '20210003', 3, 'deshabilitado', 'No cumple requisitos'),
(4, '20210004', 4, 'habilitado',    NULL),
(5, '20210005', 5, 'habilitado',    NULL);

INSERT INTO estudiante_examen_ambiente (id_ee, id_ambiente) VALUES
(1, 1),
(2, 2),
(3, 3),
(4, 4),
(5, 5);

INSERT INTO registro_asistencia (id_ingreso, hora_ingreso, id_examen, id_estudiante) VALUES
(1, '08:05', 1, '20210001'),
(2, '10:03', 2, '20210002'),
(3, '14:10', 3, '20210003'),
(4, '08:02', 4, '20210004'),
(5, '16:01', 5, '20210005');

INSERT INTO central_riesgo (id_registro, id_ingreso, id_registrador, detalle_motivo, fecha_registro, tipo_infraccion) VALUES
(1, 1, 3, 'Estudiante mirando hacia otro lado', '2024-06-10', 'sospechoso'),
(2, 2, 4, 'Uso de celular durante el examen',   '2024-06-11', 'tramposo'),
(3, 3, 3, 'Llegada tarde al examen',            '2024-06-12', 'pendiente'),
(4, 4, 4, 'Estudiante en aula equivocada',      '2024-06-13', 'aula equivocada'),
(5, 5, 3, 'Comportamiento sospechoso',          '2024-06-14', 'sospechoso');

INSERT INTO notificacion_docente (id_notificacion, id_central_riesgo, id_curso, estado) VALUES
(1, 1, 1, 'visto'),
(2, 2, 2, 'recibido'),
(3, 3, 3, 'visto'),
(4, 4, 4, 'recibido'),
(5, 5, 5, 'visto');

INSERT INTO notificacion_auxiliar (id_notificacion, id_curso, id_ambiente, estado) VALUES
(1, 1, 1, 'visto'),
(2, 2, 2, 'recibido'),
(3, 3, 3, 'visto'),
(4, 4, 4, 'recibido'),
(5, 5, 5, 'visto');

INSERT INTO invitacion_examen_compartido (id_invitacion, id_docente_invitado, id_examen, estado) VALUES
(1, 2, 1, 'aceptado'),
(2, 5, 2, 'pendiente'),
(3, 1, 3, 'rechazado'),
(4, 2, 4, 'aceptado'),
(5, 5, 5, 'pendiente');

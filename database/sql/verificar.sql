-- ============================================================================
--  VERIFICACION DEL SERVIDOR OFICIAL - TECH ONE / T1
--  https://techone.tis.cs.umss.edu.bo/phppgadmin  ->  SQL
--
--  Este script es SOLO LECTURA: no crea, no modifica, no borra nada.
--  Puedes ejecutarlo las veces que quieras.
--
--  IMPORTANTE: aqui NO hace falta desmarcar "Paginar resultados",
--  porque todas las sentencias son SELECT. Puedes pegarlo tal cual.
--
--  Ejecutalo bloque por bloque para poder leer cada resultado por separado.
-- ============================================================================


-- ============================================================================
--  BLOQUE 1 - Identidad de la conexion
--  Confirma que estas conectado a la base y version del servidor
-- ============================================================================

SELECT
    current_database() AS base_datos,
    current_user      AS usuario_conectado,
    inet_server_addr() AS direccion_servidor,
    inet_server_port() AS puerto,
    version()         AS version_postgresql;


-- ============================================================================
--  BLOQUE 2 - Las 25 tablas del dominio
--  Debe devolver 25 filas, todas con existe = true
-- ============================================================================

WITH esperadas(nombre) AS (
    VALUES
        ('usuario'), ('rol'), ('estudiante'), ('tipo_examen'),
        ('tipo_gestion'), ('ambiente'), ('norma'), ('material'),
        ('curso'), ('examen'), ('rol_usuario'), ('curso_tipo_gestion'),
        ('estudiante_curso'), ('auxiliar_curso'), ('examen_ambiente'),
        ('examen_norma'), ('examen_material_permitido'), ('examen_curso'),
        ('estudiante_examen'), ('estudiante_examen_ambiente'),
        ('registro_asistencia'), ('central_riesgo'),
        ('notificacion_docente'), ('notificacion_auxiliar'),
        ('invitacion_examen_compartido')
)
SELECT
    e.nombre                AS tabla,
    (t.table_name IS NOT NULL) AS existe
FROM esperadas e
LEFT JOIN information_schema.tables t
       ON t.table_name = e.nombre
      AND t.table_schema = 'public'
      AND t.table_type = 'BASE TABLE'
ORDER BY (t.table_name IS NULL) DESC, e.nombre;


-- ============================================================================
--  BLOQUE 3 - Los 11 tipos ENUM
--  Devuelve una fila por etiqueta. Los valores deben ser los del script.
-- ============================================================================

SELECT
    t.typname AS tipo_enum,
    string_agg(e.enumlabel, ' | ' ORDER BY e.enumsortorder) AS valores
FROM pg_type t
JOIN pg_enum e ON e.enumtypid = t.oid
WHERE t.typname IN (
    'registrador_tipo', 'tipo_infraccion', 'estado_incidencia', 'curso_estado',
    'nombre_tipo_gestion', 'estado_notificacion', 'tipo_examen_nombre',
    'estudiante_examen_estado', 'estado_usuario', 'invitacion_estado', 'roles'
)
GROUP BY t.typname
ORDER BY t.typname;


-- ============================================================================
--  BLOQUE 4 - Conteo EXACTO de filas por tabla
--  Esto es lo que te dice si 000_deploy_completo.sql (o 002_seed_data.sql)
--  se cargo bien. Ver los valores esperados en DEPLOY.md, seccion 8.
-- ============================================================================

SELECT 'usuario' AS tabla, COUNT(*) AS filas FROM usuario
UNION ALL SELECT 'rol', COUNT(*) FROM rol
UNION ALL SELECT 'estudiante', COUNT(*) FROM estudiante
UNION ALL SELECT 'tipo_examen', COUNT(*) FROM tipo_examen
UNION ALL SELECT 'tipo_gestion', COUNT(*) FROM tipo_gestion
UNION ALL SELECT 'ambiente', COUNT(*) FROM ambiente
UNION ALL SELECT 'norma', COUNT(*) FROM norma
UNION ALL SELECT 'material', COUNT(*) FROM material
UNION ALL SELECT 'curso', COUNT(*) FROM curso
UNION ALL SELECT 'examen', COUNT(*) FROM examen
UNION ALL SELECT 'rol_usuario', COUNT(*) FROM rol_usuario
UNION ALL SELECT 'curso_tipo_gestion', COUNT(*) FROM curso_tipo_gestion
UNION ALL SELECT 'estudiante_curso', COUNT(*) FROM estudiante_curso
UNION ALL SELECT 'auxiliar_curso', COUNT(*) FROM auxiliar_curso
UNION ALL SELECT 'examen_ambiente', COUNT(*) FROM examen_ambiente
UNION ALL SELECT 'examen_norma', COUNT(*) FROM examen_norma
UNION ALL SELECT 'examen_material_permitido', COUNT(*) FROM examen_material_permitido
UNION ALL SELECT 'examen_curso', COUNT(*) FROM examen_curso
UNION ALL SELECT 'estudiante_examen', COUNT(*) FROM estudiante_examen
UNION ALL SELECT 'estudiante_examen_ambiente', COUNT(*) FROM estudiante_examen_ambiente
UNION ALL SELECT 'registro_asistencia', COUNT(*) FROM registro_asistencia
UNION ALL SELECT 'central_riesgo', COUNT(*) FROM central_riesgo
UNION ALL SELECT 'notificacion_docente', COUNT(*) FROM notificacion_docente
UNION ALL SELECT 'notificacion_auxiliar', COUNT(*) FROM notificacion_auxiliar
UNION ALL SELECT 'invitacion_examen_compartido', COUNT(*) FROM invitacion_examen_compartido
ORDER BY tabla;


-- ============================================================================
--  BLOQUE 5 - Integridad referencial
--  Toda columna huerfana debe dar 0. Si algo da > 0, el seed quedo mal cargado.
-- ============================================================================

SELECT 'asistencias sin examen valido'       AS chequeo, COUNT(*) AS huerfanos
FROM registro_asistencia ra
LEFT JOIN examen e ON e.id_examen = ra.id_examen
WHERE e.id_examen IS NULL
UNION ALL
SELECT 'riesgos sin ingreso valido',
       COUNT(*)
FROM central_riesgo cr
LEFT JOIN registro_asistencia ra ON ra.id_ingreso = cr.id_ingreso
WHERE ra.id_ingreso IS NULL
UNION ALL
SELECT 'cursos sin docente valido',
       COUNT(*)
FROM curso c
LEFT JOIN usuario u ON u.id_usuario = c.sis_doc
WHERE u.id_usuario IS NULL
UNION ALL
SELECT 'examen_curso sin curso valido',
       COUNT(*)
FROM examen_curso ec
LEFT JOIN curso c ON c.id_curso = ec.id_curso
WHERE c.id_curso IS NULL
UNION ALL
SELECT 'notif_docente sin riesgo valido',
       COUNT(*)
FROM notificacion_docente nd
LEFT JOIN central_riesgo cr ON cr.id_registro = nd.id_central_riesgo
WHERE cr.id_registro IS NULL
ORDER BY chequeo;


-- ============================================================================
--  BLOQUE 6 - Muestra de datos cruzados
--  Si hay filas aqui, el seed esta completo y coherente.
-- ============================================================================

SELECT
    u.nombre_usuario || ' ' || u.apellido     AS registrador,
    es.nombre_estudiante || ' ' || es.apellido_estudiante AS estudiante,
    c.nombre_curso                              AS curso,
    cr.tipo_infraccion::text                     AS infraccion,
    cr.estado_incidencia::text                   AS estado_incidencia,
    to_char(ra.hora_ingreso, 'HH24:MI')         AS hora_ingreso
FROM central_riesgo cr
JOIN registro_asistencia ra ON ra.id_ingreso   = cr.id_ingreso
JOIN usuario u             ON u.id_usuario     = cr.id_registrador
JOIN estudiante es         ON es.sis_estudiante = ra.id_estudiante
JOIN examen e              ON e.id_examen       = ra.id_examen
JOIN examen_curso ec       ON ec.id_examen      = e.id_examen
JOIN curso c               ON c.id_curso        = ec.id_curso
ORDER BY cr.id_registro;


-- ============================================================================
--  BLOQUE 7 - Carga la tabla de migraciones de Laravel
--  Con 000_deploy_completo.sql trae 4 filas. Si la tabla NO existe es que
--  cargaste solo 001/002/003: en el servidor no se corre php artisan migrate.
-- ============================================================================

SELECT
    m.migration,
    m.batch
FROM migrations m
ORDER BY m.id;

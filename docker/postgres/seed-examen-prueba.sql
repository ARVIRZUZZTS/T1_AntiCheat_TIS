/*
 * @file    seed-examen-prueba.sql
 * @author  OchoaCesar <cesareduardonick@gmail.com>
 * @created 2026-09-25
 * @updated 2026-09-25
 *
 * @description
 * Script de prueba para el endpoint de monitoreo de asistencia. Crea un examen
 * "hoy" (CURRENT_DATE) con todos los casos de asistencia posibles
 * (habilitado/deshabilitado con y sin registro) y devuelve el id_examen
 * generado para probarlo en Postman.
 *
 * @changelog
 * - 2026-09-25  [OchoaCesar]  feat:  creación inicial del script de prueba.
 */

-- ============================================================
-- SCRIPT DE PRUEBA: ENDPOINT DE MONITOREO DE EXAMEN
-- ============================================================
-- Crea un examen "hoy" con todos los casos de asistencia posibles
-- y los estudiantes de ejemplo ya poblados en la BD.
--
-- COMO USAR:
--   1) Edita la CONFIGURACION (hora_inicio / hora_fin / duracion).
--      Recomendado: hora_inicio unos minutos en el futuro para
--      ver el estado "pendiente" / "ausente (antes del inicio)".
--   2) Ejecuta el script contra el contenedor:
--        PowerShell:
--          Get-Content docker\postgres\seed-examen-prueba.sql |
--            docker exec -i t1_anticheat_postgres psql -U t1_anticheat -d t1_anticheat
--        CMD:
--          type docker\postgres\seed-examen-prueba.sql |
--            docker exec -i t1_anticheat_postgres psql -U t1_anticheat -d t1_anticheat
--   3) La consulta final devuelve el id del examen creado.
--      Probalo en Postman:
--        GET http://localhost:8000/api/examenes/<id_devuelto>/monitoreo
--
--   Si no tenes levantado el server de Laravel: php artisan serve
--   (la app usa APP_TIMEZONE=America/La_Paz; las horas son hora local).
--   Para limpiar el examen generado: borrar el id devuelto en examen,
--   estudiante_examen, registro_asistencia y examen_curso.
-- ============================================================

\set ON_ERROR_STOP on

-- ============ CONFIGURACION ============
\set hora_inicio '16:45'
\set hora_fin '17:45'
\set duracion 60

BEGIN;

-- Nuevo id de examen (evita colisiones con replicas del script)
SELECT COALESCE(MAX(id_examen), 0) + 1 AS nuevo_id FROM examen \gset
SELECT COALESCE(MAX(id_estudiante_examen), 0) AS max_ee FROM estudiante_examen \gset
SELECT COALESCE(MAX(id_ingreso), 0) AS max_ingreso FROM registro_asistencia \gset

-- 1. Examen: fecha de hoy, creador 1 (Roberto), tipo_examen 5 (PARCIAL)
INSERT INTO examen (id_examen, fecha, hora_inicio, hora_fin, duracion, creador, tipo_examen)
VALUES (:nuevo_id, CURRENT_DATE, :'hora_inicio', :'hora_fin', :duracion, 1, 5);

INSERT INTO examen_curso (id_examen, id_curso) VALUES (:nuevo_id, 1);

-- 2. Inscripciones: habilitados y deshabilitados (todos los casos)
INSERT INTO estudiante_examen (id_estudiante_examen, sis_estudiante, id_examen, estado, motivo) VALUES
(:max_ee + 1, '20210001', :nuevo_id, 'habilitado',    NULL),
(:max_ee + 2, '20210002', :nuevo_id, 'habilitado',    NULL),
(:max_ee + 3, '20210003', :nuevo_id, 'deshabilitado', 'No cumple requisitos'),
(:max_ee + 4, '20210004', :nuevo_id, 'deshabilitado', 'Ingreso rechazado');

-- 3. Registros de asistencia: solo algunos estudiantes ingresaron
--    (registrador 3 = Diego Mendoza). Edita la hora_ingreso si quieres.
INSERT INTO registro_asistencia (id_ingreso, hora_ingreso, id_examen, id_estudiante, id_registrador) VALUES
(:max_ingreso + 1, '16:40', :nuevo_id, '20210001', 3),
(:max_ingreso + 2, '16:42', :nuevo_id, '20210003', 3);

COMMIT;

-- Id del examen creado para probar en Postman
SELECT id_examen, fecha, hora_inicio, hora_fin, duracion
FROM examen
WHERE id_examen = :nuevo_id;
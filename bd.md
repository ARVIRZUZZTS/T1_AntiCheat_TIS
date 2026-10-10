# Base de Datos — T1 AntiCheat TIS

## Resumen

- **BD oficial unica:** Supabase (PostgreSQL)
- **Motor:** PostgreSQL (Supabase)
- **Tablas totales:** 71
- **Tablas del dominio (`public`):** 28
- **Tablas de Supabase:** `auth.*` (27), `storage.*` (8)
- **Tablas de Laravel:** 9 (`users`, `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`, `password_reset_tokens`, `migrations`)
- **Desarrollo local:** ya no se usa BD local SQLite. Toda la configuracion apunta a Supabase.
- **Migraciones:** se gestionan con `php artisan migrate` directamente contra Supabase.

---

## Enums (13)

| Enum | Valores |
|---|---|
| `curso_estado` | `EnCurso`, `Finalizo` |
| `estado_incidencia` | `Confirmado`, `Pendiente` |
| `estado_notificacion` | `visto`, `recibido` |
| `estado_usuario` | `Activo`, `Baja` |
| `estudiante_examen_estado` | `habilitado`, `deshabilitado` |
| `invitacion_estado` | `aceptado`, `rechazado`, `pendiente` |
| `motivo` | `intento de ingreso no autorizado`, `uso de dispositivos electronicos`, `copia o intercambio de respuestas`, `uso de material no autorizado`, `suplantacion de identidad`, `otro` |
| `nombre_tipo_gestion` | `primer semestre`, `invierno`, `segundo semestre`, `verano` |
| `registrador_tipo` | `docente`, `auxiliar` |
| `roles` | `docente`, `auxiliar` |
| `tipo_examen_nombre` | `examen parcial`, `examen final`, `segunda instancia` |
| `tipo_infraccion` | `tramposo`, `sospechoso` |

---

## Tablas del Dominio (public)

### usuario (5 filas)
| Columna | Tipo | Nullable | Default |
|---|---|---|---|
| id_usuario | integer | NO | NULL |
| cod_sis | varchar | NO | NULL |
| password | varchar | NO | NULL |
| nombre_usuario | varchar | NO | NULL |
| apellido | varchar | NO | NULL |

**Datos:**
| id_usuario | cod_sis | nombre | apellido |
|---|---|---|---|
| 1 | DOC001 | Roberto | Silva |
| 2 | DOC002 | Patricia | Rojas |
| 3 | AUX001 | Diego | Mendoza |
| 4 | AUX002 | Sofia | Castro |
| 5 | DOC003 | Fernando | Vargas |

---

### rol (2 filas)
| Columna | Tipo | Nullable | Default |
|---|---|---|---|
| id_rol | integer | NO | NULL |
| nombre_rol | enum `roles` | NO | NULL |

**Datos:** `1=docente`, `2=auxiliar`

---

### curso (5 filas)
| Columna | Tipo | Nullable | Default |
|---|---|---|---|
| id_curso | integer | NO | NULL |
| nombre_curso | varchar | NO | NULL |
| sis_doc | integer | NO | NULL |
| fecha_creacion | date | YES | NULL |
| estado | enum `curso_estado` | NO | NULL |

**Datos:**
| id_curso | nombre_curso | sis_doc | fecha_creacion | estado |
|---|---|---|---|---|
| 1 | Calculo I | 1 | 2024-02-01 | EnCurso |
| 2 | Fisica I | 2 | 2024-02-01 | EnCurso |
| 3 | Programacion I | 5 | 2024-02-02 | EnCurso |
| 4 | Algebra Lineal | 1 | 2024-02-03 | Finalizo |
| 5 | Quimica General | 2 | 2024-02-04 | EnCurso |

---

### tipo_gestion (4 filas)
| Columna | Tipo | Nullable | Default |
|---|---|---|---|
| id_tipo_gestion | integer | NO | NULL |
| nombre_tipo_gestion | enum `nombre_tipo_gestion` | NO | NULL |

**Datos:** `1=primer semestre`, `2=invierno`, `3=segundo semestre`, `4=verano`

---

### curso_tipo_gestion (5 filas)
| Columna | Tipo | Nullable | Default |
|---|---|---|---|
| id_curso | integer | NO | NULL |
| id_tg | integer | NO | NULL |

**Datos:** (1,1), (2,1), (3,2), (4,3), (5,4)

---

### auxiliar_curso (5 filas)
| Columna | Tipo | Nullable | Default |
|---|---|---|---|
| id_auxiliar | integer | NO | NULL |
| id_curso | integer | NO | NULL |
| estado | enum `estado_usuario` | NO | NULL |

**Datos:** (3,1,Activo), (4,2,Activo), (3,3,Activo), (4,4,Baja), (3,5,Activo)

---

### estudiante (5 filas)
| Columna | Tipo | Nullable | Default |
|---|---|---|---|
| sis_estudiante | varchar | NO | NULL |
| nombre_estudiante | varchar | NO | NULL |
| apellido_estudiante | varchar | NO | NULL |
| carrera | varchar | YES | NULL |

**Datos:**
| sis_estudiante | nombre | apellido | carrera |
|---|---|---|---|
| 20210001 | Ana | Perez | Informatica |
| 20210002 | Luis | Mamani | Informatica |
| 20210003 | Maria | Lopez | Sistemas |
| 20210004 | Carlos | Rojas | Informatica |
| 20210005 | Sofia | Flores | Sistemas |

---

### estudiante_curso (5 filas)
| Columna | Tipo | Nullable | Default |
|---|---|---|---|
| sis_estudiante | varchar | NO | NULL |
| id_curso | integer | NO | NULL |

**Datos:** (20210001,1), (20210002,2), (20210003,3), (20210004,4), (20210005,5)

---

### tipo_examen (3 filas)
| Columna | Tipo | Nullable | Default |
|---|---|---|---|
| id_tipo_examen | integer | NO | NULL |
| nombre_tipo_examen | enum `tipo_examen_nombre` | NO | NULL |

**Datos:** `1=examen parcial`, `2=examen final`, `3=segunda instancia`

---

### examen (15 filas)
| Columna | Tipo | Nullable | Default |
|---|---|---|---|
| id_examen | integer | NO | NULL |
| fecha | date | YES | NULL |
| hora_inicio | time | YES | NULL |
| duracion | integer | YES | NULL |
| creador | integer | NO | NULL |
| tipo_examen | integer | NO | NULL |

**Datos (primeras 10):**
| id_examen | fecha | hora_inicio | duracion | creador | tipo_examen |
|---|---|---|---|---|---|
| 1 | 2024-06-10 | 08:00 | 120 | 1 | 1 |
| 2 | 2024-06-11 | 10:00 | 120 | 2 | 2 |
| 3 | 2024-06-12 | 14:00 | 120 | 5 | 3 |
| 4 | 2024-06-13 | 08:00 | 120 | 1 | 1 |
| 5 | 2024-06-14 | 16:00 | 120 | 2 | 2 |
| 6 | 2026-11-10 | 18:45 | 90 | 1 | 1 |
| 7 | 2026-11-10 | 18:45 | 90 | 1 | 1 |
| 8 | 0262-12-10 | 08:15 | 90 | 1 | 2 |
| 9 | 2026-12-10 | 08:30 | 90 | 1 | 2 |
| 12 | 2026-11-10 | 23:59 | 90 | 5 | 1 |

---

### examen_curso (15 filas)
| Columna | Tipo | Nullable | Default |
|---|---|---|---|
| id_examen | integer | NO | NULL |
| id_curso | integer | NO | NULL |

**Datos:** (1,1), (2,2), (3,3), (4,4), (5,5), (13,3), (14,3), (6,1), (7,1), (8,1), ...

---

### examen_ambiente (15 filas)
| Columna | Tipo | Nullable | Default |
|---|---|---|---|
| id_examen | integer | NO | NULL |
| id_ambiente | integer | NO | NULL |

**Datos:** (1,1), (2,2), (3,3), (4,4), (5,5), (6,2), (7,2), (8,1), (9,1), (10,5), ...

---

### ambiente (5 filas)
| Columna | Tipo | Nullable | Default |
|---|---|---|---|
| id_ambiente | integer | NO | NULL |
| nombre_ambiente | varchar | NO | NULL |

**Datos:**
| id_ambiente | nombre_ambiente |
|---|---|
| 1 | Aula 101 |
| 2 | Aula 102 |
| 3 | Laboratorio 1 |
| 4 | Laboratorio 2 |
| 5 | Aula 201 |

---

### estudiante_examen (5 filas)
| Columna | Tipo | Nullable | Default |
|---|---|---|---|
| id_estudiante_examen | integer | NO | NULL |
| sis_estudiante | varchar | NO | NULL |
| id_examen | integer | NO | NULL |
| estado | enum `estudiante_examen_estado` | NO | NULL |
| motivo | varchar | YES | NULL |
| modificado_por | integer | YES | NULL |
| fecha_modificacion | timestamp | YES | NULL |

**Datos:**
| id | sis_estudiante | id_examen | estado | motivo |
|---|---|---|---|---|
| 1 | 20210001 | 1 | habilitado | NULL |
| 2 | 20210002 | 2 | habilitado | NULL |
| 3 | 20210003 | 3 | habilitado | NULL |
| 4 | 20210004 | 4 | deshabilitado | NULL |
| 5 | 20210005 | 5 | habilitado | NULL |

---

### estudiante_examen_ambiente (5 filas)
| Columna | Tipo | Nullable | Default |
|---|---|---|---|
| id_ee | integer | NO | NULL |
| id_ambiente | integer | NO | NULL |

**Datos:** (1,1), (2,2), (3,3), (4,4), (5,5)

---

### material (5 filas)
| Columna | Tipo | Nullable | Default |
|---|---|---|---|
| id_material | integer | NO | NULL |
| descripcion | varchar | NO | NULL |

**Datos:**
| id_material | descripcion |
|---|---|
| 1 | Calculadora cientifica |
| 2 | Computadora |
| 3 | Formulario |
| 4 | Apuntes de la materia |
| 5 | Hojas de examen en blanco |

---

### examen_material_permitido (17 filas)
| Columna | Tipo | Nullable | Default |
|---|---|---|---|
| id_examen | integer | NO | NULL |
| id_material_permitido | integer | NO | NULL |

**Datos:** (1,1), (2,2), (3,3), (4,4), (5,5), (6,5), (7,5), (8,1), (8,2), (9,1), ...

---

### material_personalizado (3 filas)
| Columna | Tipo | Nullable | Default |
|---|---|---|---|
| id_examen | integer | NO | NULL |
| numero_material | integer | NO | NULL |
| descripcion_material | text | NO | NULL |

**Datos:**
| id_examen | numero_material | descripcion_material |
|---|---|---|
| 10 | 1 | asdada |
| 11 | 1 | sd23123 |
| 13 | 1 | Calculadora programable |

---

### norma (5 filas)
| Columna | Tipo | Nullable | Default |
|---|---|---|---|
| id_norma | integer | NO | NULL |
| detalle_norma | varchar | NO | NULL |

**Datos:**
| id_norma | detalle_norma |
|---|---|
| 1 | Portar CI durante toda la evaluación |
| 2 | Mantener dispositivos electrónicos guardados |
| 3 | Tolerancia máxima de ingreso: 15 minutos |
| 4 | Prohibido hablar, consultar levantando la mano |
| 5 | No utilizar material no autorizado |

---

### examen_norma (18 filas)
| Columna | Tipo | Nullable | Default |
|---|---|---|---|
| id_examen | integer | NO | NULL |
| id_norma | integer | NO | NULL |

**Datos:** (1,1), (2,2), (3,3), (4,4), (5,5), (6,1), (7,1), (8,1), (9,1), (10,2), ...

---

### norma_personalizada (12 filas)
| Columna | Tipo | Nullable | Default |
|---|---|---|---|
| id_examen | integer | NO | NULL |
| numero_norma | integer | NO | NULL |
| descripcion_norma | text | NO | NULL |

**Datos:**
| id_examen | numero_norma | descripcion_norma |
|---|---|---|
| 1 | 1 | Se permite el uso de calculadora científica no programable. |
| 1 | 2 | No se permite hojas adicionales, usar el reverso del examen. |
| 2 | 1 | El código debe compilar sin errores para ser evaluado. |
| 2 | 2 | Prohibido el acceso a internet o repositorios externos. |
| 3 | 1 | Tiempo estricto de 45 minutos. No hay prórroga. |
| 4 | 1 | Uso obligatorio de bata de laboratorio y gafas de seguridad. |
| 4 | 2 | Entregar el reporte de datos antes de salir del aula. |
| 5 | 1 | Responder únicamente con bolígrafo de tinta negra o azul. |
| 5 | 2 | Desactivar y guardar teléfonos móviles en la mochila. |
| 10 | 1 | dadad |
| 11 | 1 | dffgs |
| 13 | 1 | Ingreso sin mochilas |

---

### registro_asistencia (5 filas)
| Columna | Tipo | Nullable | Default |
|---|---|---|---|
| id_ingreso | integer | NO | NULL |
| hora_ingreso | time | YES | NULL |
| id_examen | integer | NO | NULL |
| id_estudiante | varchar | NO | NULL |
| id_registrador | integer | NO | NULL |

**Datos:**
| id_ingreso | hora_ingreso | id_examen | id_estudiante | id_registrador |
|---|---|---|---|---|
| 1 | 07:45 | 1 | 20210001 | 3 |
| 2 | 09:45 | 2 | 20210002 | 4 |
| 3 | 13:45 | 3 | 20210003 | 3 |
| 4 | 07:50 | 4 | 20210004 | 4 |
| 5 | 15:45 | 5 | 20210005 | 3 |

---

### central_riesgo (5 filas)
| Columna | Tipo | Nullable | Default |
|---|---|---|---|
| id_registro | integer | NO | NULL |
| id_registrador | integer | NO | NULL |
| detalle_motivo | varchar | YES | NULL |
| fecha_registro | timestamp | NO | NULL |
| tipo_infraccion | enum `tipo_infraccion` | NO | NULL |
| id_examen | integer | NO | NULL |
| sis_estudiante | varchar | NO | NULL |
| motivo | enum `motivo` | NO | NULL |
| estado_incidencia | enum `estado_incidencia` | NO | `Pendiente` |
| id_confirmador | integer | YES | NULL |

**Datos:**
| id_registro | id_registrador | detalle_motivo | tipo_infraccion | id_examen | sis_estudiante | motivo | estado_incidencia |
|---|---|---|---|---|---|---|---|
| 1 | 3 | Estudiante mirando hacia otro lado | sospechoso | 1 | 20210001 | otro | Pendiente |
| 2 | 4 | Uso de celular durante el examen | tramposo | 2 | 20210002 | uso de dispositivos electronicos | Confirmado |
| 3 | 3 | Posible intento de ingreso no autorizado | sospechoso | 3 | 20210003 | intento de ingreso no autorizado | Pendiente |
| 4 | 4 | Uso de material no autorizado | tramposo | 4 | 20210004 | uso de material no autorizado | Confirmado |
| 5 | 3 | Comportamiento sospechoso | sospechoso | 5 | 20210005 | otro | Pendiente |

---

### notificacion_docente (5 filas)
| Columna | Tipo | Nullable | Default |
|---|---|---|---|
| id_notificacion | integer | NO | NULL |
| id_central_riesgo | integer | NO | NULL |
| id_curso | integer | NO | NULL |
| estado | enum `estado_notificacion` | NO | NULL |

**Datos:**
| id_notificacion | id_central_riesgo | id_curso | estado |
|---|---|---|---|
| 1 | 1 | 1 | visto |
| 2 | 2 | 2 | recibido |
| 3 | 3 | 3 | visto |
| 4 | 4 | 4 | recibido |
| 5 | 5 | 5 | visto |

---

### notificacion_auxiliar (5 filas)
| Columna | Tipo | Nullable | Default |
|---|---|---|---|
| id_notificacion | integer | NO | NULL |
| id_curso | integer | NO | NULL |
| id_ambiente | integer | NO | NULL |
| estado | enum `estado_notificacion` | NO | NULL |

**Datos:**
| id_notificacion | id_curso | id_ambiente | estado |
|---|---|---|---|
| 1 | 1 | 1 | recibido |
| 2 | 2 | 2 | visto |
| 3 | 3 | 3 | recibido |
| 4 | 4 | 4 | visto |
| 5 | 5 | 5 | recibido |

---

### invitacion_examen_compartido (5 filas)
| Columna | Tipo | Nullable | Default |
|---|---|---|---|
| id_invitacion | integer | NO | NULL |
| id_docente_invitado | integer | NO | NULL |
| id_examen | integer | NO | NULL |
| estado | enum `invitacion_estado` | NO | NULL |

**Datos:**
| id_invitacion | id_docente_invitado | id_examen | estado |
|---|---|---|---|
| 1 | 2 | 1 | aceptado |
| 2 | 5 | 2 | pendiente |
| 3 | 1 | 3 | rechazado |
| 4 | 2 | 4 | aceptado |
| 5 | 5 | 5 | pendiente |

---

## Relaciones (Foreign Keys)

| Origen | Destino |
|---|---|
| curso.sis_doc | usuario.id_usuario |
| examen.creador | usuario.id_usuario |
| examen.tipo_examen | tipo_examen.id_tipo_examen |
| rol_usuario.id_usuario | usuario.id_usuario |
| rol_usuario.id_rol | rol.id_rol |
| curso_tipo_gestion.id_curso | curso.id_curso |
| curso_tipo_gestion.id_tg | tipo_gestion.id_tipo_gestion |
| estudiante_curso.sis_estudiante | estudiante.sis_estudiante |
| estudiante_curso.id_curso | curso.id_curso |
| auxiliar_curso.id_auxiliar | usuario.id_usuario |
| auxiliar_curso.id_curso | curso.id_curso |
| examen_ambiente.id_examen | examen.id_examen |
| examen_ambiente.id_ambiente | ambiente.id_ambiente |
| examen_curso.id_examen | examen.id_examen |
| examen_curso.id_curso | curso.id_curso |
| examen_norma.id_examen | examen.id_examen |
| examen_norma.id_norma | norma.id_norma |
| examen_material_permitido.id_examen | examen.id_examen |
| examen_material_permitido.id_material_permitido | material.id_material |
| material_personalizado.id_examen | examen.id_examen |
| norma_personalizada.id_examen | examen.id_examen |
| estudiante_examen.sis_estudiante | estudiante.sis_estudiante |
| estudiante_examen.id_examen | examen.id_examen |
| estudiante_examen.modificado_por | usuario.id_usuario |
| estudiante_examen_ambiente.id_ee | estudiante_examen.id_estudiante_examen |
| estudiante_examen_ambiente.id_ambiente | ambiente.id_ambiente |
| registro_asistencia.id_examen | examen.id_examen |
| registro_asistencia.id_estudiante | estudiante.sis_estudiante |
| registro_asistencia.id_registrador | usuario.id_usuario |
| central_riesgo.id_registrador | usuario.id_usuario |
| central_riesgo.id_examen | examen.id_examen |
| central_riesgo.sis_estudiante | estudiante.sis_estudiante |
| central_riesgo.id_confirmador | usuario.id_usuario |
| notificacion_docente.id_central_riesgo | central_riesgo.id_registro |
| notificacion_docente.id_curso | curso.id_curso |
| notificacion_auxiliar.id_curso | curso.id_curso |
| notificacion_auxiliar.id_ambiente | ambiente.id_ambiente |
| invitacion_examen_compartido.id_docente_invitado | usuario.id_usuario |
| invitacion_examen_compartido.id_examen | examen.id_examen |

---

## Tablas de Laravel

| Tabla | Uso actual | Se puede dropear |
|---|---|---|
| `users` | No usado (auth es por `usuario`) | Sí, pero conservar por si acaso |
| `sessions` | No usado (`SESSION_DRIVER=file`) | Sí |
| `cache` | No usado (`CACHE_STORE=file`) | Sí |
| `cache_locks` | No usado | Sí |
| `jobs` | No usado (`QUEUE_CONNECTION=sync`) | Sí |
| `job_batches` | No usado | Sí |
| `failed_jobs` | No usado | Sí |
| `password_reset_tokens` | No usado | Sí |
| `migrations` | Historial de migraciones | **No** (conservar) |

---

## Migraciones (8)

| # | Migración | Propósito |
|---|---|---|
| 1 | `0001_01_01_000000_create_users_table` | Tablas base de Laravel (`users`, `password_reset_tokens`, `sessions`) |
| 2 | `0001_01_01_000001_create_cache_table` | Tablas de cache (`cache`, `cache_locks`) |
| 3 | `0001_01_01_000002_create_jobs_table` | Tablas de queue (`jobs`, `job_batches`, `failed_jobs`) |
| 4 | `2026_09_27_000001_migracion_servidor_oficial` | Schema principal del dominio |
| 5 | `2026_09_28_000001_actualizar_usuario` | Ajustes a tabla `usuario` |
| 6 | `2026_10_09_000001_agregar_revision_incidencia` | Agrega `estado_incidencia`, `id_confirmador` a `central_riesgo` |
| 7 | `2026_10_10_000001_reparar_enums_y_personalizadas` | Repara enums y crea `material_personalizado`, `norma_personalizada` |
| 8 | `2026_10_10_000002_quitar_hora_fin_de_examen` | Elimina columna `hora_fin` de `examen` |

---

## Funciones del Sistema

Solo funciones de Supabase (no hay funciones personalizadas del dominio):

| Función | Retorno | Descripción |
|---|---|---|
| `auth.uid()` | uuid | UUID del usuario autenticado |
| `auth.role()` | text | Rol del usuario autenticado |
| `auth.email()` | text | Email del usuario autenticado |
| `auth.jwt()` | jsonb | JWT completo |
| `storage.*()` | various | Helpers de Storage (buckets, objects, search) |

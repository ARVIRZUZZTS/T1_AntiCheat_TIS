# test.md — Cómo probar las nuevas funcionalidades (HU 6 · Notificaciones "Estudiante ingresado a central de riesgo")

Issues cerradas por estos cambios: **#93** (generación del aviso), **#94** (endpoints) y **#96** (pantalla).
PRs (en orden de merge): **#169 → #170 → #171**.

## 1. Qué debería pasar

Cuando un estudiante es registrado o confirmado como **"Tramposo"** en la central de riesgos:

- se genera **un aviso por cada materia activa** (`curso.estado = 'EnCurso'`) en la que cursa el estudiante;
- cada docente ve **solo el aviso de su materia** (no le llegan de golpe estudiantes ajenos);
- en `/notificaciones` aparece la **tarjeta roja** "Estudiante ingresado a central de riesgo" con el nombre del estudiante, la materia y el tiempo transcurrido ("Hace X minutos"), ordenada de la más reciente a la más antigua;
- la **X** de la tarjeta la descarta (borrado lógico) sin tocar el caso de la central de riesgos;
- los cursos **finalizados** (`Finalizo`) no generan avisos y los **auxiliares** nunca reciben avisos.

## 2. Pruebas automatizadas

Requieren PHP + Composer instalados (el esquema se arma sobre SQLite en memoria; **no tocan Supabase**).

```powershell
# Nueva: reparto multi-docente, confirmar/rechazar, idempotencia, auxiliares
php artisan test tests/Feature/Notificaciones/NotificacionCentralRiesgoTest.php

# Endpoints: listado mixto de ambos tipos de aviso + ocultar
php artisan test tests/Feature/Notificaciones/NotificacionesApiTest.php

# Pantalla: endpoint real, plantillas por tipo y tarjeta central
php artisan test tests/Feature/NotificacionesTest.php

# Las tres suites juntas
php artisan test tests/Feature/Notificaciones tests/Feature/NotificacionesTest.php
```

Resultado esperado: **19 pruebas en verde** (7 + 10 + 6, aprox.).

## 3. Prueba manual en local

> La ruta `/dev/login` solo existe con `APP_ENV=local` e inicia sesión **siempre con el usuario 1**
> (docente de *Cálculo I* —curso 1, EnCurso— y de *Álgebra Lineal* —curso 4, Finalizo—).

Datos de la semilla útiles: estudiante **20210001 (Ana Pérez)** cursa *Cálculo I*; **20210002 (Luis Mamani)** cursa *Física II* (docente 2).

1. **Iniciar sesión:** entrar a `http://<tu-app>/dev/login` (te deja en `/notificaciones`).
2. **Generar el aviso:**
   - Ir a `/central-riesgo` → botón **"Registrar incidencia"** (abre el formulario en blanco), **o**
   - ir al monitoreo en vivo `/monitoreo` y usar el modal **"Registrar tramposo"** sobre un estudiante disponible.
   - Buscar por SIS `20210001`, elegir un **motivo** y confirmar. Al ser docente, queda registrado como *tramposo*.
3. **Verificar la tarjeta:** volver a `/notificaciones`. Debe aparecer arriba de todo la **tarjeta roja**
   "Estudiante ingresado a central de riesgo" con "El estudiante Ana Perez fue ingresado a la central de riesgo
   en la materia de Calculo I" y "Hace X minutos". **No** lleva botón "Ver detalle" (a diferencia de la tarjeta
   ámbar "Posible Tramposo" de la HU 12).
4. **Descartar:** pulsar la **X** de la tarjeta → se desvanece y desaparece. Recargar: ya no está.
5. **Comprobar en Supabase** (SQL del proyecto, ver `database/sql/verificar.sql`):

   ```sql
   SELECT n.id_notificacion, n.tipo, n.estado, n.oculta,
          c.nombre_curso, c.sis_doc, e.nombre_estudiante, e.apellido_estudiante
   FROM notificacion_docente n
   JOIN curso c        ON c.id_curso = n.id_curso
   JOIN central_riesgo cr ON cr.id_registro = n.id_central_riesgo
   JOIN estudiante e   ON e.sis_estudiante = cr.sis_estudiante
   WHERE n.tipo = 'central_riesgo'
   ORDER BY n.id_notificacion DESC;
   ```

   - El caso **sigue existiendo** en `central_riesgo` (el descarte solo pone `oculta = true`).
6. **Idempotencia:** repetir el registro del mismo estudiante genera un caso nuevo; si se vuelve a
   notificar el **mismo** caso, no se duplican avisos.

### Probar el reparto multi-docente (opcional)

Inscribir a un estudiante en un segundo curso activo y registrar el tramposo: deben crearse **2 avisos**, uno por curso.

```sql
-- Ana Pérez (20210001) también cursa Programación I (curso 3, docente 5, EnCurso)
INSERT INTO estudiante_curso (sis_estudiante, id_curso) VALUES ('20210001', 3);
```

Luego registrar el tramposo y verificar con el SELECT del punto 5: deben aparecer filas para
*Cálculo I* (docente 1) y *Programación I* (docente 5). Como `/dev/login` siempre entra con el usuario 1,
la bandeja del otro docente se comprueba por SQL (no hay pantalla de login todavía).

### Probar el disparo por "confirmar" (opcional)

1. Como **auxiliar** habría que reportar un sospechoso (si tienes forma de iniciar sesión como auxiliar;
   por ahora `/dev/login` no permite) **o** insertar un caso `sospechoso` pendiente por SQL.
2. Abrir el detalle del caso (`/incidencias/{id}` o desde el modal de detalle) y **confirmar**.
3. En `/notificaciones` debe aparecer recién ahí la tarjeta roja: confirmar es el momento en que el
   reporte del auxiliar entra efectivamente a la central. Si en cambio se **rechaza**, no queda ningún aviso.

## 4. Base de datos (Supabase)

- La migración `2026_10_11_000002_agregar_tipo_a_notificacion_docente` (columna `tipo`, default
  `posible_tramposo`) **ya fue aplicada** sobre Supabase. Las 5 filas previas quedaron como `posible_tramposo`.
- La columna `oculta` (de la HU 12) también ya estaba aplicada.
- Si trabajas desde otra PC: correr `php artisan migrate` antes de probar.

## 5. Notas para el equipo

- **#93/#94/#96 quedan cubiertas** con estos PRs. Las issues **#133 y #134** (HU 12, backend de
  notificaciones) siguen abiertas aunque su código ya está en `dev` — avisar a **Amiddala** para que las cierre.
- La pantalla `/central-riesgo` quedó mockeada hasta la PR #167 (ya mergeada en `dev`).
- Fallos **preexistentes en `dev`** ajenos a esta HU (reproducen sin nuestros cambios):
  - `tests/Feature/Examenes/BuscadorEstudiantesMateriaTest` y `ModalDeshabilitar/HabilitarSoloTest`:
    `Undefined variable $errors` en `resources/views/pages/materia-estudiantes.blade.php`.
  - `tests/Feature/Examenes/ListarEstudiantesCursoFiltroTest`: inserta en `central_riesgo` sin
    `id_examen`/`sis_estudiante`, que son NOT NULL en la base real.
  - El de la tarjeta de notificación (`@endif` faltante, pantalla caída) **sí quedó arreglado** en la PR #169.

# routes — Definición de rutas web

## Para qué sirve
Registra las rutas de la aplicación. Como el frontend es Livewire full-page, los archivos mapean URL → componente (o controlador cuando aplica) y asignan middleware de autenticación/roles.

## Qué contiene
- `web.php` — rutas públicas y autenticadas (login, dashboard, exámenes, monitoreo, reportes, configuración).
- `console.php` — comandos y schedulers (tareas programadas de monitoreo).
- `api.php`: consultas JSON de estudiantes, exámenes por curso y monitoreo.

## Rol en la arquitectura
- **Capa:** HTTP (enrutamiento).
- **Conoce:** componentes/controllers y middleware.
- **No conoce:** modelos, servicios ni persistencia.

Fuente: `arquitectura.md` (sección 4 y nota de `routes/api.php`).

## Exámenes de un curso — task #138

`GET /api/cursos/{idCurso}/examenes`

- Nombre de ruta: `api.cursos.examenes.listar`.
- Respuesta `200`: objeto con `datos`, una lista de exámenes vinculados al curso.
- Curso existente sin exámenes: `{"datos": []}`.
- Curso inexistente o identificador no numérico: `404`.
- Orden: primero los programados; luego los finalizados. Dentro de cada grupo,
  fecha y hora de inicio descendentes. Si coinciden, ID descendente.

```json
{
  "datos": [
    {
      "id": 1,
      "tipo": "Primer parcial",
      "fecha": "2026-10-10",
      "hora_inicio": "08:00",
      "hora_fin": "10:00",
      "duracion": 120,
      "inscritos": 3,
      "ingresados": 2,
      "ambientes": [{"id": 1, "nombre": "Aula 101"}],
      "estado": "Programado"
    }
  ]
}
```

`ingresados` cuenta estudiantes distintos en `registro_asistencia`, no
inscripciones ni filas multiplicadas por los ambientes. Sin asistencia vale
`0`; sin ambientes se devuelve `[]`. Un examen compartido aparece en cada uno
de sus cursos asociados. Los valores del catálogo de Supabase que no son
códigos del enum local se devuelven tal como están almacenados.

El servicio resuelve el estado usando la fecha, la hora de fin y la zona
horaria configurada en Laravel. El API usa `Programado` y `Finalizado`;
los exámenes en curso se presentan como `Programado` en este contrato. La
vista Blade existente conserva su estado `en_curso`. Los campos opcionales
sin fecha u horario conservan `null`, igual que en el listado existente.

El endpoint es de solo lectura y usa la conexión PostgreSQL configurada en
`.env`, incluida Supabase. No requiere migraciones ni cargar un seed.

Las pruebas crean únicamente un esquema mínimo en SQLite en memoria, sin
utilizar ni modificar la base compartida:

```powershell
& C:\xampp\php\php.exe vendor/phpunit/phpunit/phpunit tests/Feature/Examenes/ListarExamenesCursoTest.php
```

## Detalle y edición de un curso (HU #333, task #334)

Requieren sesión de docente (`web`, `auth:web`, `docente`) y que el curso
pertenezca al docente autenticado. La ruta JSON usa el prefijo `/api` del
proyecto: `/cursos/{id}` sin prefijo ya es la vista HTML de detalle.

### `GET /api/cursos/{id}`

Nombre de ruta: `api.cursos.detalle`. Precarga el formulario de edición.

`200`:

```json
{
  "datos": {
    "id": 12,
    "materia": "Calculo I",
    "grupo": "2",
    "gestion": 3,
    "gestion_nombre": "Segundo semestre",
    "anio": 2026,
    "nombre": "Calculo I - Grupo 2"
  }
}
```

`gestion` es el `id_tipo_gestion` del catálogo (el mismo valor que envía el
formulario de alta) y `gestion_nombre` su etiqueta. `materia` y `grupo` se
separan del nombre guardado con `App\Support\NombreCurso`.

### `PUT /api/cursos/{id}`

Nombre de ruta: `api.cursos.actualizar`. Request:

```json
{
  "materia": "Calculo I",
  "grupo": "2a",
  "gestion": 3,
  "anio": 2026
}
```

`200` devuelve el curso ya guardado, con el nombre recalculado:

```json
{
  "datos": {
    "id": 12,
    "materia": "Calculo I",
    "grupo": "2A",
    "gestion": 3,
    "gestion_nombre": "Segundo semestre",
    "anio": 2026,
    "nombre": "Calculo I - Grupo 2A"
  }
}
```

Reglas: los cuatro campos son obligatorios; materia hasta 80 caracteres;
grupo alfanumérico de 1 a 3 caracteres guardado en mayúsculas; gestión válida
según el catálogo; año de cuatro cifras desde el año actual. Se recortan los
espacios sobrantes antes de validar.

`422` errores por campo, formato Laravel:

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "grupo": ["El grupo solo admite letras y numeros, hasta 3 caracteres."],
    "gestion": ["Seleccione una gestion valida."]
  }
}
```

`409` cuando otro curso tiene la misma materia y grupo (sin distinguir
mayúsculas) en la misma gestión y año, de cualquier docente, igual que en el
alta. El curso que se edita se excluye de la búsqueda:

```json
{
  "message": "Este curso ya existe en la gestión y el año elegidos.",
  "errors": {
    "materia": ["Este curso ya existe en la gestión y el año elegidos."],
    "grupo": ["Este curso ya existe en la gestión y el año elegidos."],
    "gestion": ["Este curso ya existe en la gestión y el año elegidos."],
    "anio": ["Este curso ya existe en la gestión y el año elegidos."]
  },
  "campos": {
    "materia": "Calculo I",
    "grupo": "2",
    "gestion": 3,
    "anio": 2026
  }
}
```

`401` sin sesión, `403` si el curso es de otro docente y `404` si no existe.
La comprobación de existencia y pertenencia va antes de validar los campos.
La actualización toca solo `curso.nombre_curso` y su fila de
`curso_tipo_gestion`; estudiantes, exámenes e incidencias quedan intactos.

Pruebas: `php artisan test tests/Feature/Cursos/DetalleCursoTest.php`,
`EditarCursoTest.php` y `EditarCursoDuplicadoTest.php`.

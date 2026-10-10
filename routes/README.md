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

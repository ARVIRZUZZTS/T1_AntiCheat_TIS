# Resolución de incidencias — tarea #142

Los controladores delegan las operaciones al servicio `ResolverIncidenciaService`.
La policy autoriza únicamente a usuarios con rol `docente` responsables de un curso
asociado al examen. El identificador del docente procede de la sesión autenticada.

## Endpoints

| Método | Ruta | Resultado |
| --- | --- | --- |
| POST | `/api/incidencias/{idIncidencia}/confirmar` | Confirma y registra al docente responsable. |
| POST | `/api/incidencias/{idIncidencia}/rechazar` | Descarta la incidencia y sus notificaciones. |

Enviar `Accept: application/json`, la cookie de sesión Laravel y el token CSRF
en `X-CSRF-TOKEN` (o `X-XSRF-TOKEN`, según el cliente). No requieren campos en el cuerpo.
Estas rutas utilizan `web` y `auth:web`; no incorporan un mecanismo de inicio de sesión nuevo.

El estado visible «En revisión» corresponde a `EstadoIncidencia::Pendiente`.
Confirmar actualiza la misma fila de `central_riesgo` a `Confirmado` y escribe
`id_confirmador`, conservando al reportante original (`id_registrador`). No inserta
otra incidencia. Rechazar elimina la fila pendiente y sus avisos en
`notificacion_docente` dentro de la misma transacción; no elimina al estudiante ni al examen.
`Rechazado` es el resultado HTTP del descarte, no un estado persistido.

Respuesta de confirmación (200):

```json
{
  "datos": {
    "id_registro": 1,
    "estado_incidencia": "Confirmado",
    "id_confirmador": 1
  },
  "mensaje": "Incidencia confirmada correctamente."
}
```

Errores: 401 sin sesión (con CSRF válido), 403 sin autorización, 404 si no existe,
419 con CSRF inválido y 422 si ya está confirmada. El error de estado utiliza
`mensaje`; los errores del middleware y autorización conservan el formato de Laravel.
Una segunda confirmación devuelve 422; resolver una incidencia ya descartada devuelve 404.
El bloqueo de fila y la transacción protegen frente a operaciones simultáneas.

## Esquema de Supabase

La inspección de solo lectura detectó que el esquema compartido enlaza las incidencias
por `id_examen` y carece de `estado_incidencia` e `id_confirmador`.
La policy también admite el enlace original por `registroAsistencia.id_examen`.

La migración `2026_10_09_000001_agregar_revision_incidencia.php` está preparada,
pero **no se ha ejecutado en Supabase**. Añade los campos faltantes y la clave foránea
del confirmador. Si añade el estado, clasifica los registros históricos `tramposo`
como `Confirmado` y los restantes como `Pendiente`, según la distinción del formulario
existente. No atribuye un confirmador a los registros históricos. El equipo debe
revisar esta clasificación antes de aplicar la migración sobre la base compartida.

Tras revisar y respaldar la base, el comando para aplicar únicamente esta migración es:

```powershell
php -d extension=pdo_pgsql artisan migrate --path=database/migrations/2026_10_09_000001_agregar_revision_incidencia.php
```

No utilizar `migrate:fresh`, `migrate:refresh` ni seeders para activar estos endpoints.
La reversión retira `id_confirmador` y su clave foránea; conserva el estado porque
puede existir previamente y no debe perderse la revisión registrada.

## Verificación aislada

```powershell
php vendor/phpunit/phpunit/phpunit tests/Feature/CentralRiesgo/ResolverIncidenciaTest.php --no-progress
```

Las pruebas crean una conexión SQLite en memoria y no ejecutan escrituras en Supabase.
Cubren autenticación, autorización por curso, CSRF, confirmación, descarte, duplicados,
estados inválidos, inicialización de campos y reversión transaccional ante errores.
La rama PostgreSQL de la migración debe verificarse en una base de ensayo antes del despliegue.

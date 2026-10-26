# Resolución de incidencias — tarea #142

Los controladores delegan las operaciones al servicio `ResolverIncidenciaService`.
La policy autoriza únicamente a usuarios con rol `docente` responsables de un curso
asociado al examen. El identificador del docente procede de la sesión autenticada.

## Endpoints

| Método | Ruta | Resultado |
| --- | --- | --- |
| POST | `/api/incidencias/{idIncidencia}/confirmar` | Confirma la incidencia: pasa de sospechoso a tramposo. |
| POST | `/api/incidencias/{idIncidencia}/rechazar` | Descarta la incidencia y sus notificaciones. |

Enviar `Accept: application/json`, la cookie de sesión Laravel y el token CSRF
en `X-CSRF-TOKEN` (o `X-XSRF-TOKEN`, según el cliente). No requieren campos en el cuerpo.
Estas rutas utilizan `web` y `auth:web`; no incorporan un mecanismo de inicio de sesión nuevo.

El estado de una incidencia es su `tipo_infraccion` (`TipoInfraccion::estado()`):
`sospechoso` está «En revisión» (pendiente) y `tramposo` está confirmado. Solo se
resuelven incidencias sospechosas. Confirmar actualiza la misma fila de
`central_riesgo` a `tramposo`, conservando al reportante original
(`id_registrador`); no inserta otra incidencia. Rechazar elimina la fila pendiente y sus avisos en
`notificacion_docente` dentro de la misma transacción; no elimina al estudiante ni al examen.
`Rechazado` es el resultado HTTP del descarte, no un estado persistido.

Respuesta de confirmación (200):

```json
{
  "datos": {
    "id_registro": 1,
    "tipo_infraccion": "tramposo"
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

La migración `2026_10_26_000001_unificar_estado_incidencia_en_tipo_infraccion.php`
elimina `estado_incidencia`, `id_confirmador` (con su clave foránea) y el tipo
`estado_incidencia`. Antes pasa a `tramposo` las incidencias sospechosas que un
docente ya había confirmado. Su reversión recrea las columnas y calcula el estado
a partir del tipo; el confirmador no se recupera.

## Verificación aislada

```powershell
php vendor/phpunit/phpunit/phpunit tests/Feature/CentralRiesgo/ResolverIncidenciaTest.php --no-progress
```

Las pruebas crean una conexión SQLite en memoria y no ejecutan escrituras en Supabase.
Cubren autenticación, autorización por curso, CSRF, confirmación, descarte, duplicados,
estados inválidos, inicialización de campos y reversión transaccional ante errores.
La rama PostgreSQL de la migración debe verificarse en una base de ensayo antes del despliegue.

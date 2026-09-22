# Comentarios — Formato del repositorio (T1)

---

## 1. Cabecera obligatoria al inicio de cada archivo

```php
<?php

/**
 * @file    AdmissionValidationService.php
 * @author  [Nombre del dev] <email@dominio.com>
 * @created 2026-03-15
 * @updated 2026-03-20
 *
 * @description
 * Servicio de dominio que evalúa las reglas de habilitación para el
 * registro de ingreso de un estudiante.
 *
 * @changelog
 * - 2026-03-15  [Dev A]  feat:  creación inicial del servicio.
 * - 2026-03-18  [Dev B]  fix:   corrección al validar código SIS nulo.
 * - 2026-03-20  [Dev A]  refactor: extracción de reglas a clases Rule.
 *
 * @see  ValidateAdmissionRule
 * @see  AdmissionRegisterRepository
 */
```

## 2. Tipos de cambio (prefijos permitidos)

| Prefijo   | Uso                          |
|-----------|------------------------------|
| `feat`    | Nueva funcionalidad          |
| `fix`     | Corrección de bug            |
| `refactor`| Reestructura sin cambiar comportamiento |
| `docs`    | Solo documentación           |
| `style`   | Formato, sin cambios de lógica |
| `test`    | Añadir o modificar pruebas   |
| `chore`   | Tareas de mantenimiento (build, deps, config) |
| `perf`    | Mejora de rendimiento        |
| `revert`  | Revertir un cambio previo    |

Formato: `<prefijo>: <descripción breve en minúsculas, imperativo>`

## 3. Comentario de clase

```php
/**
 * Evalúa las reglas de habilitación académica y de riesgos
 * antes de persistir un registro de ingreso.
 *
 * @package  App\Services\Admission
 * @author   [Nombre del dev] <email@dominio.com>
 * @since    1.0.0
 */
final class AdmissionValidationService
{
    // ...
}
```

## 4. Comentario de método / función

```php
/**
 * Evalúa todas las reglas registradas para un estudiante y examen.
 *
 * @param  string  $codSis   Código SIS del estudiante (formato: 9 dígitos).
 * @param  int     $examId   ID del examen al que se desea ingresar.
 *
 * @return ResultRule  Resultado con estado y mensaje de la evaluación.
 *
 * @throws \InvalidArgumentException  Si $codSis no cumple el formato.
 * @throws \RuntimeException          Si el examen no está activo.
 *
 * @author [Nombre del dev] <email@dominio.com>
 * @since  1.0.0
 */
public function evaluate(string $codSis, int $examId): ResultRule
{
    // ...
}
```

## 5. Propiedades / atributos

```php
/**
 * Repositorio de estudiantes.
 *
 * @var StudentRepository
 */
private readonly StudentRepository $estudiantes;

/**
 * Estado de habilitación del estudiante.
 *
 * @var AuthorizationState
 */
private AuthorizationState $estado;
```

## 6. Reglas de estilo para comentarios

- ✅ Explican “por qué”, no “qué”.
- ✅ Idioma: español (consistente en todo el repo).
- ✅ Todo método público debe tener `@param`, `@return` y `@throws`.
- ✅ Todo archivo debe tener cabecera con `@author` y `@changelog`.
- ❌ No comentar código obvio (`// suma dos números`).
- ❌ No dejar código comentado (código muerto).
- ⚠️ `TODO` / `FIXME` permitidos, pero con autor y fecha:

```php
// TODO(@[usuario], 2026-03-20): extraer lógica a un Value Object.
// FIXME(@[usuario], 2026-03-22): validación falla con códigos > 999999999.
```

## 7. Plantilla rápida (copiar y pegar)

```php
<?php

/**
 * @file    NombreArchivo.php
 * @author  [Nombre del dev] <email@dominio.com>
 * @created YYYY-MM-DD
 * @updated YYYY-MM-DD
 *
 * @description
 * Breve descripción de la responsabilidad del archivo.
 *
 * @changelog
 * - YYYY-MM-DD  [Dev]  feat: descripción del cambio.
 */
```

```php
/**
 * Breve descripción del método.
 *
 * @param  tipo  $nombre  Descripción.
 *
 * @return tipo  Descripción.
 *
 * @throws \Excepcion  Cuándo se lanza.
 *
 * @author [Nombre del dev] <email@dominio.com>
 * @since  YYYY-MM-DD
 */
```
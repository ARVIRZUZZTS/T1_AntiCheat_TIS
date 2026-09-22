# Código — Convenciones del repositorio (T1)

Guía de estilo de código aplicada a todo el repositorio.

---

## 1. Nombres

| Tipo            | Convención         | Ejemplo                            |
|-----------------|--------------------|------------------------------------|
| Clases          | `PascalCase`       | `AdmissionValidationService`       |
| Métodos y vars. | `camelCase`        | `calculateWorkHours`, `daysSinceLastPayment` |
| Constantes/enums| `UPPER_SNAKE_CASE` | `MAX_RETRIES`, `AUTHORIZED`        |
| Tablas/columnas BD | `snake_case`    | `admission_records`, `exam_id`     |
| Rutas y archivos| `kebab-case`       | `admission-register.blade.php`     |
| Booleanos       | prefijo `is_`, `has_`, `can_` | `is_active`, `has_access` |

- ✅ Nombres que revelan intención, pronunciables, buscables.
- ❌ Sin abreviaturas crípticas (`calcHrs`, `d`, `e`).

## 2. Funciones

- Una sola responsabilidad, un solo nivel de abstracción.
- Ideal 0–1 parámetros, máximo 3.
- Sin banderas booleanas como parámetro.
- Sin efectos secundarios ocultos.
- Nombres de método = verbo → `registerUser()`, `validateExam()`.

## 3. Comentarios

- ✅ Solo explican “por qué”, no “qué”.
- ❌ Redundantes, código muerto, confusos.
- ⚠️ `TODO` tolerables, resolver pronto.

## 4. Formato

- Indentación: 4 espacios (consistente).
- Líneas: `< 120` caracteres.
- Líneas en blanco para separar conceptos.
- Orden en clase: variables públicas → privadas → métodos públicos → privados.

## 5. Errores

- Usar excepciones, no códigos de error.
- `try-catch` primero (flujo de error antes que el normal).
- No retornar `null` → usar objetos vacíos u `Optional`.
- No pasar `null` como parámetro.
- Mensajes informativos sin exponer datos sensibles.

## 6. Pruebas (F.I.R.S.T.)

| Letra | Regla |
|-------|-------|
| F | Rápidas (segundos) |
| I | Independientes entre sí |
| R | Repetibles (mismo resultado siempre) |
| S | Autovalidables (pasan o fallan) |
| T | Oportunas (antes o durante la codificación) |

## 7. Clases

- Alta cohesión, bajo acoplamiento.
- Depender de interfaces, no de implementaciones concretas.
- Pequeñas por responsabilidades, no por líneas.

## 8. SOLID

| Letra | Regla |
|-------|-------|
| S | Una clase = una razón de cambio |
| O | Extender sin modificar (interfaces) |
| L | Subtipos sustituibles sin romper contrato |
| I | Interfaces pequeñas y específicas por rol |
| D | Depender de abstracciones, no de detalles |

- Aplicar con rigor en dominio, pragmatismo en CRUD simples.

## 9. DRY

- Una única fuente autorizada por pieza de conocimiento.
- Regla de tres: abstraer a partir de la 3.ª aparición, no de la 2.ª.
- No confundir duplicación textual con duplicación de conocimiento.
- Ante duda: WET (duplicar explícito) antes que abstracción equivocada.

## 10. Diseño emergente (orden de prioridad)

1. Pasa todas las pruebas.
2. Menor duplicación posible.
3. Expresa la intención del programador.
4. Mínimo número de clases y métodos.

## 11. Estructura del sistema

- **Presentación** (Controller): orquesta, no decide.
- **Dominio** (Service, Rule, Enum): decide, no persiste.
- **Persistencia** (Repository): escribe, no decide.
- Dependencias inyectadas por constructor; bindings en `AppServiceProvider`.

## 12. Herramientas en CI (cada Pull Request)

- PHPStan + Larastan → dependencias concretas donde se espera abstracción.
- Pest → pruebas unitarias aisladas.
- PHPCPD → umbral 5 líneas / 70 tokens.
- Revisión por pares → detectar duplicación de conocimiento.

## 13. Configuración

- Todo parámetro sensible o de entorno va en `.env`.
- `.env` nunca se versiona. Se versiona `.env.example`.
- Consumir configuración vía `config()`, nunca hardcodear.
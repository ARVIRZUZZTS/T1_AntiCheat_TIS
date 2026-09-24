# 📘 COMMITS.md

> **Estándar oficial de commits, ramas y Pull Requests del repositorio T1 — Tech One SRL.**
> Este documento define las convenciones que **todo integrante del equipo debe seguir** al momento de versionar cambios en el repositorio. Su cumplimiento garantiza trazabilidad, orden y consistencia con la planificación gestionada en **GitHub Projects**.

---

## 📑 Índice

1. [Introducción](#1-introducción)
2. [Convención de ramas](#2-convención-de-ramas)
3. [Convención de commits](#3-convención-de-commits)
4. [Convención de Pull Requests](#4-convención-de-pull-requests)
5. [Trazabilidad con GitHub Issues](#5-trazabilidad-con-github-issues)
6. [Ejemplos completos](#6-ejemplos-completos)
7. [Plantillas rápidas](#7-plantillas-rápidas)
8. [Referencias](#8-referencias)

---

## 1. Introducción

El presente estándar tiene como objetivo establecer una **estructura común** para los mensajes de commit, los nombres de ramas y los Pull Requests, de modo que:

- Cada cambio en el código pueda **relacionarse con una Issue** del tablero de GitHub Projects.
- El historial del repositorio sea **legible, consistente y auditable**.
- Se facilite la **revisión por parte de los revisores** y del consultor TIS.
- Se respete el marco de trabajo **Scrum + Kanban** adoptado por Tech One SRL.

El estándar se basa en **Conventional Commits** y se complementa con las convenciones definidas en la documentación oficial del proyecto (`Documentacion de la Configuración del tablón de tareas en Github.pdf`).

---

## 2. Convención de ramas

### 2.1 Estructura

```
tipo/id-descripcion-corta
```

- **`tipo`** → prefijo del tipo de trabajo (ver tabla).
- **`id`** → número de la Issue de GitHub asociada.
- **`descripcion-corta`** → resumen breve del trabajo.

### 2.2 Tipos permitidos

| Tipo       | Uso                                              | Ejemplo                          |
|------------|--------------------------------------------------|----------------------------------|
| `feature`  | Desarrollo de una Historia de Usuario o Task     | `feature/123-registro-ingreso`   |
| `fix`      | Corrección de un Bug                             | `fix/135-error-registro`         |
| `test`     | Desarrollo o modificación de pruebas             | `test/130-registro-ingreso`      |
| `docs`     | Modificación de documentación                    | `docs/140-documentacion-api`     |
| `refactor` | Reestructuración sin cambiar comportamiento      | `refactor/145-servicio-ingreso`  |
| `chore`    | Configuración y mantenimiento del proyecto       | `chore/150-configuracion`        |

### 2.3 Reglas

- ✅ Todo en **minúsculas** (excepto identificadores como `#123`).
- ✅ Usar `-` para separar palabras.
- ❌ No usar **espacios**, **tildes** ni **caracteres especiales**.
- ✅ Descripción **corta y clara**.
- ✅ Toda rama debe estar asociada a una **HU, Bug o Task** cuando corresponda.

### 2.4 Flujo de trabajo

```bash
# 1. Actualizar main
git checkout main
git pull origin main

# 2. Crear rama desde la Issue
git checkout -b feature/123-registro-ingreso

# 3. Trabajar y commitear
git add .
git commit -m "feat: agregar registro de estudiante"

# 4. Publicar rama
git push origin feature/123-registro-ingreso
```

---

## 3. Convención de commits

### 3.1 Formato

```
<tipo>: <descripción breve en minúsculas, imperativo>
```

- **`tipo`** → prefijo permitido (ver tabla).
- **`descripción`** → breve, en **imperativo** y **minúsculas**.

### 3.2 Tipos permitidos

| Prefijo    | Uso                                                    |
|------------|--------------------------------------------------------|
| `feat`     | Nueva funcionalidad                                    |
| `fix`      | Corrección de bug                                      |
| `refactor` | Reestructura sin cambiar comportamiento                |
| `docs`     | Solo documentación                                     |
| `style`    | Formato, sin cambios de lógica                         |
| `test`     | Añadir o modificar pruebas                             |
| `chore`    | Tareas de mantenimiento (build, deps, config)          |
| `perf`     | Mejora de rendimiento                                  |
| `revert`   | Revertir un cambio previo                              |

### 3.3 Reglas adicionales

- ✅ Descripción en **imperativo** (`agregar`, no `agregado`).
- ✅ Máximo **72 caracteres** en la primera línea.
- ✅ Si el commit cierra una Issue, incluir en el cuerpo: `Closes #<id>`.
- ❌ No usar **punto final** en la primera línea.
- ❌ No mezclar múltiples tipos de cambio en un mismo commit.
- ❌ No usar tildes ni caracteres especiales en la primera línea.

### 3.4 Commit con cuerpo (opcional)

Cuando el cambio requiere contexto adicional:

```
<tipo>: <descripción breve>

<Cuerpo opcional explicando el porqué del cambio>

Closes #<id-issue>
```

**Ejemplo:**

```
feat: agregar validación de estudiante inhabilitado

Se incorpora la regla que impide el ingreso cuando el estudiante
no está habilitado para el examen.

Closes #123
```

### 3.5 Commits atómicos

- ✅ Un commit = **un cambio lógico**.
- ✅ Si el cambio toca varias capas (modelo, servicio, UI), dividir en commits separados.
- ❌ Evitar commits del tipo `"varios cambios"`, `"fix"`, `"update"`.

---

## 4. Convención de Pull Requests

### 4.1 Título

Mismo formato que los commits:

```
<tipo>: <descripción breve>
```

**Ejemplos:**

```
feat: agregar registro de estudiante
fix: corregir validación de estudiante
test: agregar pruebas de registro
docs: actualizar documentación de API
refactor: simplificar servicio de ingreso
chore: actualizar configuración de GitHub
```

### 4.2 Estructura del cuerpo

```markdown
## Descripción
Breve descripción del cambio realizado.

## Issue relacionada
Closes #123

## Tipo de cambio
- [ ] feat
- [ ] fix
- [ ] refactor
- [ ] docs
- [ ] test
- [ ] chore

## Checklist
- [ ] El código sigue los estándares del repositorio.
- [ ] Se actualizaron los comentarios de cabecera y changelog.
- [ ] Se agregaron pruebas cuando corresponde.
- [ ] Se verificó el flujo completo del requerimiento.
- [ ] Se actualizó el estado de la Issue en GitHub Projects.
```

### 4.3 Reglas

- ✅ Todo PR debe estar asociado a **una Issue**.
- ✅ El PR debe pasar por **Review** y **Testing** antes de fusionarse.
- ✅ El reviewer debe verificar el cumplimiento de la **Definition of Done**.
- ❌ No fusionar PRs con conflictos sin resolver.
- ❌ No fusionar PRs sin revisión de al menos **un integrante** del equipo.

---

## 5. Trazabilidad con GitHub Issues

Cada commit, rama y PR debe poder rastrearse hasta una **Issue** del tablero de GitHub Projects.

| Elemento | Se relaciona con | Cómo |
|----------|------------------|------|
| Rama     | Issue            | `feature/123-descripcion` |
| Commit   | Issue            | `Closes #123` en el cuerpo |
| PR       | Issue            | `Closes #123` en el cuerpo |

**Ejemplo de flujo completo:**

```
Issue #123  →  Rama: feature/123-registro-ingreso
            →  Commit: feat: agregar registro de estudiante (Closes #123)
            →  PR: feat: agregar registro de estudiante (Closes #123)
            →  Merge a main
            →  GitHub Projects mueve la tarjeta a "Done"
```

---

## 6. Ejemplos completos

### 6.1 Historia de Usuario

```
Rama:   feature/45-registrar-ingreso-estudiante
Commit: feat: agregar pantalla de registro de ingreso
        Closes #45
PR:     feat: agregar pantalla de registro de ingreso
```

### 6.2 Bug

```
Rama:   fix/78-error-busqueda-sis
Commit: fix: corregir búsqueda de código SIS duplicado
        Closes #78
PR:     fix: corregir búsqueda de código SIS duplicado
```

### 6.3 Test Case

```
Rama:   test/92-pruebas-registro-ingreso
Commit: test: agregar pruebas para el registro de ingreso
        Closes #92
PR:     test: agregar pruebas para el registro de ingreso
```

### 6.4 Documentación

```
Rama:   docs/110-manual-usuario
Commit: docs: actualizar manual de usuario
        Closes #110
PR:     docs: actualizar manual de usuario
```

### 6.5 Refactor

```
Rama:   refactor/120-servicio-ingreso
Commit: refactor: extraer reglas de validación a clases Rule
        Closes #120
PR:     refactor: extraer reglas de validación a clases Rule
```

---

## 7. Plantillas rápidas

### 7.1 Plantilla de commit

```
<tipo>: <descripción breve en imperativo>

[Opcional: cuerpo explicando el cambio]

[Opcional: Closes #<id-issue>]
```

### 7.2 Plantilla de rama

```
<tipo>/<id-issue>-<descripcion-corta>
```

### 7.3 Plantilla de Pull Request

```markdown
## Descripción
...

## Issue relacionada
Closes #...

## Tipo de cambio
- [ ] feat
- [ ] fix
- [ ] refactor
- [ ] docs
- [ ] test
- [ ] chore

## Checklist
- [ ] Sigue los estándares del repositorio.
- [ ] Cabecera y changelog actualizados.
- [ ] Pruebas agregadas cuando corresponde.
- [ ] Flujo verificado.
- [ ] Issue actualizada en GitHub Projects.
```

---

## 8. Referencias

- **Documentación interna:** `Documentacion de la Configuración del tablón de tareas en Github.pdf`
- **Comentarios del repositorio:** `# Comentarios — Formato del repositorio (T1).txt`
- **Conventional Commits:** [https://www.conventionalcommits.org](https://www.conventionalcommits.org)
- **GitHub Projects:** [https://docs.github.com/en/issues/planning-and-tracking-with-projects](https://docs.github.com/en/issues/planning-and-tracking-with-projects)

---

**Versión:** 1.0.0
**Última actualización:** 2026-03-15
**Responsable:** T1 — Tech One SRL
**Consultor TIS:** Lic. Blanco Coca Maria Leticia
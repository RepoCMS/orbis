# tasks/IN_PROGRESS.md — Trabajo en Curso

> **Actualizado:** 2026-08-06

---

## En curso: Transición a AI-Native Repository (2026-08-06)

**Objetivo:** convertir Orbis en un AI-Native Repository — contexto estructurado para agentes (documentación arquitectónica + tablero de tareas + gobernanza de decisiones).

### Checklist del plan

| # | Elemento | Estado | Evidencia (verificada 2026-08-06) |
|---|----------|--------|-----------------------------------|
| 1 | **PROJECT_CONTEXT.md** (contexto primario para IAs) | ✅ Completado | Existe, v1.0.0, 2026-08-06 (git `5b49841`); discrepancias corregidas (ORM 3.x, Alpine.js, `tasks/`) |
| 2 | **docs/adr/** — ADR-001..008 (nuevos) | ✅ Completado | 8 archivos + README con índice (git `5b49841`, `26d84d0`) |
| 3 | **docs/adr/** — ADR-009..011 (viejos 011-013 migrados al formato nuevo) | ✅ Completado | `ADR-009.md` (TransactionManager), `ADR-010.md` (Cross-BC cascade), `ADR-011.md` (Table Prefixes) + archivos legacy `git rm` + README actualizado |
| 4 | **docs/modules/** — 8 módulos por BC | ✅ Completado | client, expense, identity, inventory, location, notification, organization, support |
| 5 | **CHANGELOG_AI.md** — changelog de la transformación | ✅ Completado | Este ciclo (v1.0.0) |
| 6 | **ROADMAP.md** — hoja de ruta con fases y estado por BC | ✅ Completado | Este ciclo (v1.0.0) |
| 7 | **Tablero tasks/** — TODO.md + IN_PROGRESS.md + DONE.md | ✅ Completado | Estructura lista; TODOs marcados `[TODO-REAL]`/`[TODO: validar]` |
| 8 | **docs core** — architecture.md, folder-structure.md, dependencies.md | ✅ Completado | 3 archivos en `docs/` raíz (no `docs/core/`), verificados contra disco |
| 9 | **docs standards** — coding-standards.md, conventions.md, glossary.md | ✅ Completado | 3 archivos en `docs/` raíz (no `docs/standards/`) |
| 10 | **.ai/** — 6 prompts especializados | ✅ Completado | architect, backend, frontend, reviewer, security, qa (verificados contra código real) |
| 11 | **docs/decisions/** — decisiones no arquitectónicas | ✅ Completado | `README.md` con formato y reglas de gobernanza |
| 12 | **Sync AGENTS.md** con la estructura AI-Native | ✅ Completado | Bootstrap de 8 pasos + Regla no negociable ADR + Relevant Files actualizado |
| 13 | **Sync PROJECT_CONTEXT.md** §6.1 (flujo de trabajo + `TASKS/`→`tasks/`) | ✅ Completado | Corregido + discrepancias factuales arregladas |
| 14 | **Validación final** (validate_project.ps1 + revisión QA de los archivos creados) | ⬜ Pendiente | Se ejecuta antes del commit de este ciclo |

### Próximos pasos inmediatos

1. Ejecutar `& .\validate_project.ps1` y commit del ciclo de transformación.
2. Continuar con T01-T06 (specs de BCs con discrepancia + remote GitHub + #42 bcrypt vs argon2) según prioridad.
3. Revisar `docs/bc/`, `docs/api/`, `docs/runbooks/` (directorios vacíos) — decidir si forman parte del plan de documentación.

---

## Instrucciones de mantenimiento

1. **Un solo trabajo "en curso" por archivo de sección** — cuando un ítem del checklist se completa, se mueve a la sección de completados (o a `DONE.md` si es un hito) y se actualiza la fecha.
2. Todo checklist se marca ✅ **solo con evidencia** (archivo existente, commit, test verde). Sin evidencia: `⬜` o `🔄`.
3. Al terminar un trabajo, abrir la siguiente línea del ROADMAP (fase corta, §4.1) y mover aquí las tareas que se empiecen.

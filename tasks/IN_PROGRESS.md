# tasks/IN_PROGRESS.md — Trabajo en Curso

> **Actualizado:** 2026-08-07

---

## En curso: Enforcement de máquina — ciclo de bases (spec 014, 2026-08-06) + ciclo de alineación de specs T01-T03

**Objetivo:** el agente propone, la máquina decide. Contrato (DoR/DoD), calidad estática y CI como gates en cada PR.

### Checklist del plan (specs/014-governance/tasks.md)

| # | Elemento | Estado | Evidencia |
|---|----------|--------|-----------|
| 1 | PHPStan + PHP-CS-Fixer instalados | ✅ | composer require-dev (2026-08-06) |
| 2 | `phpstan.neon` nivel 5 + baseline (159 errores) | ✅ | `phpstan-baseline.neon` versionado |
| 3 | `.php-cs-fixer.dist.php` PSR-12 + reglas | ✅ | 2020/2360 archivos normalizados |
| 4 | Composer scripts (test, stan, cs, wiring, validate, audit, check) | ✅ | composer.json |
| 5 | `bin/wiring-check.php` — 346 targets, 0 huérfanos | ✅ | exit 0 verificado 2026-08-07 |
| 6 | `bin/validate-project.php` + baseline — 0 errores, 6 warnings | ✅ | exit 2 verificado 2026-08-07 |
| 7 | `.github/workflows/ci.yml` (5 jobs) | ✅ | archivo creado |
| 8 | `.github/workflows/security.yml` (composer audit) | ✅ | archivo creado |
| 9 | AGENTS.md gates + DoD referenciados | ✅ | §Workflow + Regla de gates |
| 10 | CONTEXT.md + tasks/ + CHANGELOG_AI v1.3.0 | ✅ | actualizados |
| 11 | Commit + push a main | ✅ | commit `29c4d83` (2026-08-06) |

### Ciclo T01-T03 (2026-08-07): alineación de specs con discrepancia

| Tarea | Spec | Estado | Evidencia |
|-------|------|--------|-----------|
| T01 | `006-notification` (~60% → ~75%) | ✅ Aprobada por QA-Reviewer | spec.md + 81 tests OK |
| T02 | `001-organization-spec` (~67% → ~90%) | ✅ Aprobada (corrección G-09: 3 archivos basura) | spec.md + gates verdes |
| T03 | `002-client` (~92%, antes ~70%) | ✅ Aprobada (corrección G-11: falso negativo wiring-check) | spec.md + tasks.md + 84 tests OK |

### Próximos pasos inmediatos

1. ✅ **G-06 (crítico)** implementado — `ListEmployeesQuery` con paginación real + filtros (`serviceZoneId`/`page`/`limit`); API list de Organization + cross-BC desbloqueadas. → commit `b5a860e` (T30)
2. ✅ **G-04 Client** implementado — cascada Contact→User operativa (REQ-CST-002). → commit `d15eab3` (T31)
3. Corregir **punto ciego de `bin/wiring-check.php`** (no detecta Queries huérfanas con handler pareja que importe su FQCN — verificado con `ListContactsByClientQuery`). → T32
4. Continuar con gaps restantes de Notification (T33), Organization (T34) y Client (T35).
5. Probar el CI real en GitHub (primer push activa los 5 jobs).
6. Deuda detectada por gates: T24-T29 (enums UPPERCASE, VOs, baseline PHPStan, dompdf, coverage).
7. ✅ Commits del ciclo: `7d8e4fa` (specs T01-T03), `b5a860e` (T30), `d15eab3` (T31), pusheados a origin main 2026-08-07.

---

## Instrucciones de mantenimiento

1. **Un solo trabajo "en curso" por archivo de sección** — cuando un ítem del checklist se completa, se mueve a la sección de completados (o a `DONE.md` si es un hito) y se actualiza la fecha.
2. Todo checklist se marca ✅ **solo con evidencia** (archivo existente, commit, test verde). Sin evidencia: `⬜` o `🔄`.
3. Al terminar un trabajo, abrir la siguiente línea del ROADMAP (fase corta, §4.1) y mover aquí las tareas que se empiecen.

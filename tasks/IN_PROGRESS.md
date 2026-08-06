# tasks/IN_PROGRESS.md — Trabajo en Curso

> **Actualizado:** 2026-08-06

---

## En curso: Enforcement de máquina — ciclo de bases (spec 014, 2026-08-06)

**Objetivo:** el agente propone, la máquina decide. Contrato (DoR/DoD), calidad estática y CI como gates en cada PR.

### Checklist del plan (specs/014-governance/tasks.md)

| # | Elemento | Estado | Evidencia |
|---|----------|--------|-----------|
| 1 | PHPStan + PHP-CS-Fixer instalados | ✅ | composer require-dev (2026-08-06) |
| 2 | `phpstan.neon` nivel 5 + baseline (159 errores) | ✅ | `phpstan-baseline.neon` versionado |
| 3 | `.php-cs-fixer.dist.php` PSR-12 + reglas | ✅ | 2020/2360 archivos normalizados |
| 4 | Composer scripts (test, stan, cs, wiring, validate, audit, check) | ✅ | composer.json |
| 5 | `bin/wiring-check.php` — 346 targets, 0 huérfanos | ✅ | exit 0 verificado |
| 6 | `bin/validate-project.php` + baseline — 0 errores, 6 warnings | ✅ | exit 2 verificado |
| 7 | `.github/workflows/ci.yml` (5 jobs) | ✅ | archivo creado |
| 8 | `.github/workflows/security.yml` (composer audit) | ✅ | archivo creado |
| 9 | AGENTS.md gates + DoD referenciados | ✅ | §Workflow + Regla de gates |
| 10 | CONTEXT.md + tasks/ + CHANGELOG_AI v1.3.0 | ✅ | actualizados |
| 11 | Commit + push a main | ⬜ | pendiente |

### Próximos pasos inmediatos

1. Commit + push del ciclo (verificado local: test 2033 ✅, stan ✅, cs ✅, wiring ✅, validate ✅).
2. Probar el CI real en GitHub (primer push activa los 5 jobs).
3. Continuar con T01-T03 (specs de BCs con discrepancia) bajo el nuevo flujo con PR + CI.
4. Deuda detectada por gates: T24-T29 (enums UPPERCASE, VOs, baseline PHPStan, dompdf, coverage).

---

## Instrucciones de mantenimiento

1. **Un solo trabajo "en curso" por archivo de sección** — cuando un ítem del checklist se completa, se mueve a la sección de completados (o a `DONE.md` si es un hito) y se actualiza la fecha.
2. Todo checklist se marca ✅ **solo con evidencia** (archivo existente, commit, test verde). Sin evidencia: `⬜` o `🔄`.
3. Al terminar un trabajo, abrir la siguiente línea del ROADMAP (fase corta, §4.1) y mover aquí las tareas que se empiecen.

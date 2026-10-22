# tasks/IN_PROGRESS.md — Trabajo en Curso

> **Actualizado:** 2026-10-22

---

## En curso: Consolidación de contexto IA (2026-10-22)

**Objetivo:** un solo entry point, eliminar duplicados, optimizar tokens.

| # | Elemento | Estado |
|---|----------|--------|
| 1 | Fusionar PROJECT_CONTEXT/HANDOFF en docs | ✅ |
| 2 | CONTEXT.md con §0 estado de sesión | ✅ |
| 3 | Eliminar `.ai/`, `.codex/`, `.specify/` y Spec Kit | ✅ |
| 4 | Eliminar `CLAUDE.md` y `.claude/` (solo opencode) | ✅ |
| 5 | Actualizar AGENTS.md, folder-structure, validator, .gitignore | 🔄 |
| 6 | Validar gates y commitear | ⏳ |

**Próximo paso:** correr `composer validate:project`, `wiring`, `cs`, `stan`; revisar `git status`; commit.

---

## Histórico: Enforcement de máquina — ciclo de bases (spec 014, 2026-08-06) + ciclo de alineación de specs T01-T03

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
3. ✅ **T32** — Fix punto ciego `bin/wiring-check.php` (stem-based matching). Verificado: 22 orfanos detectados.
4. ✅ **T33 (parcial)** — Notification G-03: `RetryNotificationHandler` re-despacha `SendNotificationMessage`. Tests 5/5 verdes.
5. ✅ **T34 (parcial)** — Organization G-07/G-08/G-09: `EmploymentPeriod::start()` en Create/Reactivate, `SpecialPeriodAutoProcessed` desde cron, 3 archivos basura eliminados.
6. ✅ **T35 (parcial)** — Client G-10/G-11: bug SQL `contacts`→`client_contacts` corregido, `ListContactsByClientQuery` reparada.
7. ✅ **T35-G06** — Tests para handlers de Client: `ImportModelPartsHandlerTest.php` (6 test cases). Commit `7de182c` pusheado a origin main 2026-08-17.
8. ✅ **CI fix** — Root cause de fallos: falta `doctrine:migrations:migrate` en job test. Agregado + debug output removido. Commits `8b98100`, `39fa197` pusheados 2026-08-17.
9. Deuda detectada por gates: T24-T29 (enums UPPERCASE, VOs, baseline PHPStan, dompdf, coverage).
10. Pendientes en backlog: T33 (G-01/G-02/G-04/G-05), T34 (G-10..G-14).
11. ✅ Commits del ciclo: `7d8e4fa` (specs T01-T03), `b5a860e` (T30), `d15eab3` (T31), `7de182c` (T35-G06), `8b98100` (CI fix), `39fa197` (CI cleanup), `bbd052f` (UserContactResolver tests + AbstractUlid::equals) pusheados a origin main.
12. ✅ **T36** — Fix UserContactResolver tests (Notification BC): convert string to UserIdValueObject, add AbstractUlid::equals(), fix test data. Commit `bbd052f` 2026-08-20.
13. ✅ **T28** — Fix composer audit advisories: update `dompdf/dompdf` ^3.1.6 (6 CVEs fixed), `phpoffice/phpspreadsheet` ^5.9.0 (3 high CVEs fixed), `phpunit/phpunit` ^11.5.50 (1 high CVE fixed). All CI gates green: PHPUnit 2082 tests, PHPStan 0 errors, CS-Fixer 0 files, Wiring 0 orphans, Validate exit 2. Composer audit: 0 advisories. Commit `67c4eec` pushed to origin main 2026-08-20.
14. ✅ **MFA Dev Bypass** — Disable MFA check in dev environment for both JWT API (`JwtApiController`) and web session (`MfaSubscriber`). Allows testing without MFA configuration.
15. ✅ **Dashboard Template Components** — Created missing Twig components: `_export_buttons.html.twig`, `_date_filter.html.twig`, `_empty_state.html.twig`. Fixed undefined variable errors with `is defined` checks.
16. ✅ **Full Auth Flow Working** — Verified: web login (`/login` → `/dashboard`), JWT API login (`/api/v1/auth/jwt/login`), seed data (`admin/Orbis2026!`, `carlos/Orbis2026!`).
17. ✅ **All Tests Passing** — 2082 tests, 5601 assertions, 0 failures.

---

## Instrucciones de mantenimiento

1. **Un solo trabajo "en curso" por archivo de sección** — cuando un ítem del checklist se completa, se mueve a la sección de completados (o a `DONE.md` si es un hito) y se actualiza la fecha.
2. Todo checklist se marca ✅ **solo con evidencia** (archivo existente, commit, test verde). Sin evidencia: `⬜` o `🔄`.
3. Al terminar un trabajo, abrir la siguiente línea del ROADMAP (fase corta, §4.1) y mover aquí las tareas que se empiecen.

# Tasks — Spec 014 Gobernanza de Calidad

**Fecha:** 2026-08-06

| # | Tarea | Estado |
|---|-------|--------|
| G-01 | Instalar `phpstan/phpstan` + `friendsofphp/php-cs-fixer` (dev) | ✅ |
| G-02 | `phpstan.neon` nivel 5 + baseline generado y versionado | ✅ |
| G-03 | `.php-cs-fixer.dist.php` PSR-12 + reglas del proyecto | ✅ |
| G-04 | Composer scripts: `test`, `stan`, `cs`, `wiring`, `validate`, `audit`, `check` | ✅ |
| G-05 | `bin/wiring-check.php` (0 huérfanos verificable) | ✅ |
| G-06 | `validate_project.ps1/.sh` ampliados (Apéndice A, secrets, duplicados globales) | 🔄 reemplazados por `bin/validate-project.php` unificado |
| G-07 | `.github/workflows/ci.yml` (test, stan, cs, wiring, validate) | ✅ |
| G-08 | `.github/workflows/security.yml` (composer audit) | ✅ |
| G-09 | AGENTS.md + CLAUDE.md: flujo con gates de CI + DoD | ✅ |
| G-10 | CONTEXT.md §gates + tasks/TODO.md + DONE.md + CHANGELOG_AI v1.3.0 | ✅ |
| G-11 | Commit + push a main | ⬜ |

**Evidencia requerida para cerrar:** comandos verdes locales + workflow YAML en repo + baseline versionado.

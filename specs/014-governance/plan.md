# Plan — Spec 014 Gobernanza de Calidad

**Fecha:** 2026-08-06
**Estado:** En ejecución
**Rama:** main (ciclo de bases)

## Objetivo

Montar el enforcement de máquina completo: contrato (DoR/DoD), calidad estática, CI y validadores. Resultado: el agente propone, la máquina decide.

## Pasos

| # | Paso | Entregable | Depende de |
|---|------|-----------|------------|
| 1 | Spec 014 (contrato) | `specs/014-governance/spec.md` | — |
| 2 | Instalar PHPStan + PHP-CS-Fixer | dev-deps + `phpstan.neon` + `.php-cs-fixer.dist.php` | 1 |
| 3 | Baseline PHPStan (nivel 5) | `phpstan-baseline.neon` | 2 |
| 4 | Composer scripts | `test`, `stan`, `cs`, `wiring`, `validate`, `audit`, `check` | 2-3 |
| 5 | Wiring check script | `bin/wiring-check.php` (standalone, sin framework) | 1 |
| 6 | Ampliar validadores | `validate_project.ps1/.sh`: Apéndice A automatizable, secrets, duplicados globales, tablero | 1 |
| 7 | GitHub Actions CI | `.github/workflows/ci.yml` + `security.yml` | 2-6 |
| 8 | Sync docs/tablero | AGENTS.md (gates), CONTEXT.md, tasks/, CHANGELOG_AI v1.3.0 | 7 |
| 9 | Commit + push | `main` | 8 |

## Verificación

- `composer test` verde (baseline 2033 tests)
- `composer stan` verde con baseline
- `composer cs` verde (dry-run) o diff documentado
- `composer wiring` verde (0 huérfanos)
- `composer validate` verde
- `validate_project.ps1` pasa completo

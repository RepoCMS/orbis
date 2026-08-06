# Spec: Gobernanza de Calidad y Enforcement de MÃ¡quina

**Spec path**: `specs/014-governance/spec.md`
**Created**: 2026-08-06
**Status**: En desarrollo

---

## 1. DescripciÃ³n General

Definir el **contrato de calidad** del proyecto Orbis y el **enforcement de mÃ¡quina** que lo hace cumplir sin supervisiÃ³n humana:

- **Definition of Ready (DoR)** â€” cuÃ¡ndo una tarea/spec estÃ¡ lista para implementar.
- **Definition of Done (DoD)** â€” cuÃ¡ndo un cambio estÃ¡ listo para merge, con criterios **medibles**.
- **Gates de CI** â€” GitHub Actions que validan cada PR: tests, anÃ¡lisis estÃ¡tico, estilo, wiring, reglas del ApÃ©ndice A de AGENTS.md.
- **Wiring check automÃ¡tico** â€” todo Command/Query/Event debe tener al menos un caller real (regla 11 del ApÃ©ndice A), verificado por script, no a mano.
- **Seguridad como gate** â€” checklist automatizable de permisos, secrets e input validation.

**Principio rector:** el agente propone, la mÃ¡quina decide. Ninguna regla de AGENTS.md es una "sugerencia": toda regla automatizable tiene un chequeo en CI; las que no son automatizables se auditan por QA-Reviewer con checklist explÃ­cito.

**Contexto:** ya existen AGENTS.md (reglas), `~/.agents/` (agentes), `.ai/` (prompts), CONTEXT.md (estado), tablero `tasks/`, 2033 tests verdes (baseline 2026-08-06). Lo que falta es el enforcement: no hay CI, no hay PHPStan/CS-Fixer, y `validate_project.ps1` solo verifica existencia de archivos.

---

## 2. Definition of Ready (DoR) â€” una tarea estÃ¡ lista para implementar

| # | Criterio | Verificado por |
|---|----------|----------------|
| DR-1 | Spec existe en `specs/{num}-{bc}/spec.md` con estado `En desarrollo` | QA-Reviewer |
| DR-2 | Spec tiene secciÃ³n **Wiring**: cada Command/Query/Event que se implementarÃ¡ tiene su caller de origen declarado | QA-Reviewer |
| DR-3 | Spec incluye las reglas de negocio con criterios de aceptaciÃ³n (Gherkin o tabla Given/When/Then) | QA-Reviewer |
| DR-4 | El checklist DDD de QA-Reviewer estÃ¡ aprobado (firma en el spec) | QA-Reviewer |
| DR-5 | Las tareas estÃ¡n en `tasks/TODO.md` con prioridad y fuente (`[TODO-REAL]`) | docs (custodio) |

---

## 3. Definition of Done (DoD) â€” un cambio estÃ¡ listo para merge

| # | Criterio | Gate | Evidencia |
|---|----------|------|-----------|
| DD-1 | `declare(strict_types=1)` en todo archivo PHP nuevo/editado | CI (`validate`) | `validate_project.ps1` / `.sh` |
| DD-2 | Tests del BC corren verdes + no se rompe el suite completo | CI (`test`) | PHPUnit |
| DD-3 | Cobertura de Domain â‰¥ 80% en el BC tocado | CI (`test`) | PHPUnit coverage (Xdebug/PCOV) |
| DD-4 | PHPStan nivel configurado pasa (sin errores nuevos fuera del baseline) | CI (`stan`) | PHPStan + baseline |
| DD-5 | Estilo PSR-12 + convenciones del proyecto (dry-run) | CI (`cs`) | PHP-CS-Fixer |
| DD-6 | Todo Command/Query/Event nuevo tiene caller real | CI (`wiring`) | `bin/wiring-check.php` |
| DD-7 | Sin agentes/skills duplicados en el repo (fuente Ãºnica `~/.agents/`) | CI (`validate`) | `validate_project.ps1` |
| DD-8 | Sin secrets en el diff (tokens, claves, DSNs reales) | CI (`validate`) | grep de patrones |
| DD-9 | Permisos REST correctos en controllers nuevos (`#[IsGranted]` con permiso existente) | QA-Reviewer (no automatizable) | checklist |
| DD-10 | Tablero actualizado: tarea movida a `tasks/DONE.md` con evidencia | docs | fila en DONE.md |
| DD-11 | CONTEXT.md refleja el estado real (%, conteos) si cambiÃ³ el cÃ³digo | docs | CONTEXT.md Â§2 |
| DD-12 | ADR creado/actualizado si hubo decisiÃ³n arquitectÃ³nica | docs | `docs/adr/` |

**DD-9, DD-11, DD-12** no son automatizables hoy â†’ los audita QA-Reviewer antes del merge y docs al cerrar.

---

## 4. Flujo de trabajo completo (Spec â†’ CI â†’ Merge)

```
1. PLANNER        â†’ spec en specs/{num}-{bc}/ (DoR DR-1..DR-5)
2. QA-REVIEWER    â†’ aprueba spec (checklist DDD + Wiring)
3. DEVELOPER      â†’ branch feature/xyz â†’ implementa Domainâ†’Appâ†’Infraâ†’Presentation
4. TEST-ENGINEER  â†’ tests del BC (Domain â‰¥ 80% en el BC tocado)
5. QA-REVIEWER    â†’ revisa cÃ³digo final (checklist + wiring local)
6. CI (GitHub Actions) â†’ corre los 5 gates (test, stan, cs, wiring, validate)
7. MERGE          â†’ solo si CI estÃ¡ verde (branch protection, sin merge manual)
8. DOCS           â†’ tablero (DD-10) + CONTEXT.md (DD-11) + ADR (DD-12)
9. PUSH           â†’ commit firmado a main
```

**Regla:** un cambio sin PR con CI verde NO entra a `main`. Si un agente commitea directo a main (hotfix documentado), debe correr los 5 gates localmente y registrarlo en el commit.

---

## 5. Gates de CI (GitHub Actions)

| Job | Comando | Bloquea merge | Notas |
|-----|---------|---------------|-------|
| `test` | `composer test` | âœ… | PHPUnit completo (Unit + Integration + Functional) sobre MySQL 8 service |
| `stan` | `composer stan` | âœ… | PHPStan nivel 5 (objetivo 9), con baseline versionado |
| `cs` | `composer cs` | âœ… | PHP-CS-Fixer dry-run, reglas PSR-12 + custom |
| `wiring` | `composer wiring` | âœ… | `bin/wiring-check.php`: 0 huÃ©rfanos |
| `validate` | `composer validate:project` | âœ… | `validate_project.sh` ampliado: estructura, ApÃ©ndice A automatizable, secrets, tablero |

Todos los jobs corren con PHP 8.4. El workflow se dispara en `pull_request` y `push` a `main`.

---

## 6. Reglas del ApÃ©ndice A de AGENTS.md â†’ chequeo automatizable

| # | Regla | Chequeo en CI | Herramienta |
|---|-------|---------------|-------------|
| 1 | `declare(strict_types=1)` en TODO archivo PHP | âœ… | validate |
| 2 | Enum values lowercase snake_case | âœ… | validate (regex sobre enums) |
| 3 | Constructor `private` + factory en Entities | âš ï¸ Parcial | validate (regex heurÃ­stica; auditorÃ­a QA) |
| 4 | Accessors sin prefijo `get` | âš ï¸ Parcial | validate (solo Domain) |
| 5 | Domain sin framework leakage | âŒ No automatizable | QA-Reviewer checklist |
| 6 | Commands/Queries `final readonly class` | âœ… | validate (regex) |
| 7 | Controllers delgados | âŒ No automatizable | QA-Reviewer checklist |
| 8 | Domain coverage â‰¥ 80% | âœ… | test (coverage) |
| 9 | Sufijo `ValueObject` en VOs | âœ… | validate (regex) |
| 10 | Repositorios solo interfaces del Domain | âŒ No automatizable | QA-Reviewer checklist |
| 11 | Todo Command/Query con caller real | âœ… | wiring (script) |

**PolÃ­tica de baseline:** las reglas con chequeo automÃ¡tico corren desde el dÃ­a 1 con baseline (errores preexistentes permitidos, nuevos errores bloquean). El baseline se reduce en cada ciclo hasta llegar a cero.

---

## 7. Wiring check automÃ¡tico (`bin/wiring-check.php`)

Script PHP standalone (sin framework) que verifica la regla 11 del ApÃ©ndice A:

1. Escanea `src/app/**/Application/Command/**/*Command.php` y `src/app/**/Application/Query/**/*Query.php`.
2. Extrae el nombre de clase (FQCN).
3. Busca referencias al FQCN en todo el cÃ³digo **excepto** su propio archivo, su Handler pareja (`{Name}Handler.php`) y archivos de tests.
4. Cada clase sin referencias â†’ **huÃ©rfana** â†’ exit 1 con lista.
5. Igual para Domain Events (`**/Domain/Event/*.php`) y Subscribers (`**/*Subscriber.php`).

**Regla DDD:** solo se cuentan callers reales (injection, `new`, dispatch, route). El false-positive de "mismo nombre en otro BC" se resuelve con FQCN completo.

---

## 8. Seguridad como gate

| Chequeo | Automatizable | Herramienta |
|---------|---------------|-------------|
| Secrets en cÃ³digo (tokens, passwords, DSNs reales, RSA keys) | âœ… | validate (grep patrones: `sk-`, `AKIA`, `BEGIN RSA`, `.env` reales, DSNs con credenciales) |
| `#[IsGranted]` en todo controller nuevo | âš ï¸ Parcial | validate (regex: todo `class Api*Controller` debe tener `#[IsGranted]`) |
| Input validation en Commands (Request DTOs) | âŒ No automatizable | QA-Reviewer + security-reviewer |
| Dependencias vulnerables | âœ… | `composer audit` (job CI `security`) |

**Base de seguridad pendiente (prerequisitos, ya en backlog):** T06 (bcrypt vs argon2), T18 (rate limiting login), T17 (refresh token cleanup).

---

## 9. Wiring (disparadores de este spec)

| Script/comando | QuiÃ©n lo dispara | Desde dÃ³nde |
|----------------|------------------|-------------|
| `composer test` | CI `test` + agentes | `.github/workflows/ci.yml`, comandos del agente developer/test-engineer |
| `composer stan` | CI `stan` | `.github/workflows/ci.yml` |
| `composer cs` | CI `cs` | `.github/workflows/ci.yml` |
| `composer wiring` (`bin/wiring-check.php`) | CI `wiring` + QA-Reviewer | `.github/workflows/ci.yml`, checklist de QA |
| `composer validate:project` (`validate_project.ps1/.sh`) | CI `validate` + docs agent | `.github/workflows/ci.yml`, agente docs |
| `composer audit` | CI `security` | `.github/workflows/ci.yml` |
| `& .\validate_project.ps1` | Agente docs al cerrar ciclo | tasks/DONE.md protocolo |

---

## 10. Criterios de AceptaciÃ³n

- [ ] GIVEN un PR con cÃ³digo que rompe un test WHEN CI corre THEN el PR queda rojo y no se mergea
- [ ] GIVEN un Command nuevo sin caller WHEN `composer wiring` corre THEN falla con la lista de huÃ©rfanos
- [ ] GIVEN un archivo PHP sin `strict_types` WHEN `composer validate:project` corre THEN falla con el nombre del archivo
- [ ] GIVEN un archivo PHP con error de estilo WHEN `composer cs` corre THEN falla y reporta el archivo
- [ ] GIVEN un archivo PHP con error de tipo WHEN `composer stan` corre THEN falla con el archivo y lÃ­nea
- [ ] GIVEN un secret (token/DSN real) commiteado WHEN validate corre THEN falla
- [ ] GIVEN un agente que crea `.opencode/agent/` o `.claude/skills/` en el repo WHEN validate corre THEN falla (regla global vs repo)
- [ ] GIVEN un cambio con CI verde y tablero actualizado WHEN se mergea THEN main queda estable (tests verdes en el push)

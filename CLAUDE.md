# Orbis — CLAUDE.md (Claude Code)

> **Fecha de verificación:** 2026-08-05
> **Propósito:** Este archivo es la puerta de entrada para Claude Code. Define qué leer al iniciar sesión para trabajar coordinadamente con opencode.

## ⚠️ LEER ANTES DE TRABAJAR (obligatorio al iniciar sesión)

```
1. cat CONTEXT.md   # ÚNICA FUENTE DE VERDAD — estado real del proyecto, % por BC, reportes, importers
2. cat HANDOFF.md   # Protocolo de handoff entre Claude Code y herramientas locales
3. cat TODOS.md     # Tareas en progreso y prioridades actuales
4. cat AGENTS.md    # Patrones históricos y reglas de código (solo lectura, ver §1)
```

## Reglas de coordinación con opencode

- **CONTEXT.md es la verdad única** para status, porcentajes y progreso. AGENTS.md es histórico/backup (no actualizarlo como fuente de verdad).
- **Antes de cambiar de herramienta** (Claude Code ↔ opencode): `git add -A && git commit -m "WIP: checkpoint before tool switch - $(date +%H:%M)"` y si hay remote, `git push origin <branch>`.
- **Después de volver**: `git pull origin <branch>`, luego leer CONTEXT.md y TODOS.md de nuevo (pueden haber cambiado).
- **Verificación de integridad** antes de commits grandes:
  - Windows: `& .\validate_project.ps1` (valida reportes + importers + archivos esenciales)
  - Git-bash/WSL: `./validate_reports.sh` y `./validate_importers.sh`
- **No trabajar sobre worktrees aislados** salvo aprobación; la única fuente de verdad de contexto vive en la raíz del repo (CONTEXT.md, TODOS.md, HANDOFF.md).
- **Siempre** correr `php vendor/bin/phpunit` después de cambios de código (requiere `composer install` si vendor/ no existe).

## Contexto rápido

- **Stack:** Symfony 8.0 / PHP 8.4, MySQL 8+, PHPUnit 11. DDD + Hexagonal + CQRS, ULIDs, soft-deletes.
- **BCs (13 + Shared Kernel):** Identity (~85%), Client (~70%), Organization (~67%), Support (~85%), Inventory (~95%), Expense (100%), Location (~95%), Notification (~60%), Cross-BC (~90%), 010a (~95%), 010b (~95%), 011 (100%), 012 (100%), 013 (100%). Ver CONTEXT.md §2 para la tabla maestra.
- **Workflow:** Spec Driven Development — spec primero (`specs/{num}-{bc}/`), implementación después.
- **Prohibido:** modificar BCs no relacionados, framework leakage en Domain, código huérfano (todo Command/Query debe tener caller real).
- **Reglas no negociables:** ver AGENTS.md Apéndice A (strict_types, enums lowercase, accessors sin get, etc.).

## Estructura clave

```
CONTEXT.md    → Verdad única (status, %, reportes, importers)
HANDOFF.md    → Protocolo de handoff entre herramientas
TODOS.md      → Tareas y prioridades
AGENTS.md     → Patrones históricos + reglas de código + apéndices (A–F)
specs/{num}-{bc}/ → Specs, planes y tasks por BC
validate_*.ps1 / validate_*.sh → Scripts de validación de integridad
```

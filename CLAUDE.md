# Orbis — CLAUDE.md (Claude Code)

> **Fecha de verificación:** 2026-08-06
> **Propósito:** Este archivo es la puerta de entrada para Claude Code. Define qué leer al iniciar sesión para trabajar coordinadamente con opencode.

## ⚠️ LEER ANTES DE TRABAJAR (bootstrap AI-Native — obligatorio al iniciar sesión)

```
1. cat AGENTS.md                # reglas y convenciones de código
2. cat PROJECT_CONTEXT.md       # contexto general del proyecto
3. cat docs/architecture.md     # arquitectura completa
4. cat docs/coding-standards.md # reglas obligatorias de codificación
5. cat docs/adr/README.md       # índice de decisiones arquitectónicas (ADRs)
6. cat specs/{num}-{bc}/spec.md # spec correspondiente a tu tarea
7. cat CONTEXT.md               # ÚNICA FUENTE DE VERDAD — estado real del proyecto, % por BC, reportes, importers
8. cat tasks/IN_PROGRESS.md     # tareas en curso + tablero tasks/TODO.md
```

## Reglas de coordinación con opencode

- **CONTEXT.md es la verdad única** para status, porcentajes y progreso. AGENTS.md contiene reglas de código vigentes (estable, no es fuente de estado).
- **Regla no negociable:** ninguna decisión arquitectónica sin ADR en `docs/adr/` + `PROJECT_CONTEXT.md` actualizados. Contradicción código-vs-docs → reportarla ANTES de continuar.
- **Antes de cambiar de herramienta** (Claude Code ↔ opencode): `git add -A && git commit -m "WIP: checkpoint before tool switch - $(date +%H:%M)"` y push a `origin main`.
- **Después de volver**: `git pull origin main`, luego leer CONTEXT.md y `tasks/IN_PROGRESS.md` de nuevo (pueden haber cambiado).
- **Verificación de integridad** antes de commits grandes:
  - Windows: `& .\validate_project.ps1` (valida reportes + importers + archivos esenciales)
  - Git-bash/WSL: `./validate_reports.sh` y `./validate_importers.sh`
- **No trabajar sobre worktrees aislados** salvo aprobación; la única fuente de verdad de contexto vive en la raíz del repo (CONTEXT.md, tasks/, HANDOFF.md).
- **Roles de agentes en opencode**: `.opencode/agent/*.md` (planner, qa-reviewer, developer, test-engineer, frontend, security-reviewer, docs) — misma división de roles que los worktrees de Claude Code. Coordinar trabajo delegado a esos roles leyendo CONTEXT.md + el spec del BC.
- **Guía de CodeGraph (MCP)**: versionada en `docs/codegraph.md` — leerla antes de explorar código (qué tool usar según la pregunta, verificación de wiring).
- **Prompts especializados por rol (fuente única)**: `.ai/README.md` (índice) + `.ai/{architect,planner,backend,frontend,reviewer,security,qa,docs}.md`. Los agentes de opencode (`.opencode/agent/*.md`) son wrappers que cargan los mismos archivos — una sola fuente para ambas herramientas.
- **Skills compartidos (fuente única)**: `.claude/skills/` versionados en el repo — Claude Code los lee nativo y opencode los descubre automáticamente (reiniciar opencode al agregar uno nuevo). Provenance y reglas en `.claude/skills/README.md`.
- **Siempre** correr `php vendor/bin/phpunit` después de cambios de código (requiere `composer install` si vendor/ no existe).

## Contexto rápido

- **Stack:** Symfony 8.0 / PHP 8.4, MySQL 8+, PHPUnit 11. DDD + Hexagonal + CQRS, ULIDs, soft-deletes.
- **BCs (13 + Shared Kernel):** Identity (~85%), Client (~70%), Organization (~67%), Support (~85%), Inventory (~95%), Expense (100%), Location (~95%), Notification (~60%), Cross-BC (~90%), 010a (~95%), 010b (~95%), 011 (100%), 012 (100%), 013 (100%). Ver CONTEXT.md §2 para la tabla maestra.
- **Workflow:** Spec Driven Development — spec primero (`specs/{num}-{bc}/`), implementación después.
- **Idioma (convención):** código (PHP/JS/Twig) SIEMPRE en inglés; **toda documentación Markdown (docs/, specs/, tasks/, .ai/, ADRs, commits) en español**. Mensajes al usuario final en español. Regla completa en AGENTS.md §Idioma.
- **Prohibido:** modificar BCs no relacionados, framework leakage en Domain, código huérfano (todo Command/Query debe tener caller real).
- **Reglas no negociables:** ver AGENTS.md Apéndice A (strict_types, enums lowercase, accessors sin get, etc.).

## Estructura clave

```
CONTEXT.md    → Verdad única de estado (status, %, reportes, importers)
PROJECT_CONTEXT.md → Contexto general del proyecto (propósito, stack, principios)
tasks/        → Tablero: TODO.md (backlog), IN_PROGRESS.md (en curso), DONE.md (cerrado)
HANDOFF.md    → Protocolo de handoff entre herramientas
AGENTS.md     → Reglas de código vigentes (Apéndices A, C, D, E)
docs/         → architecture.md, coding-standards.md, conventions.md, glossary.md, dependencies.md, folder-structure.md, adr/, modules/, analytics.md, history.md, decisions/
.ai/          → Prompts especializados por rol (architect, backend, frontend, reviewer, security, qa)
specs/{num}-{bc}/ → Specs, planes y tasks por BC
validate_*.ps1 / validate_*.sh → Scripts de validación de integridad
```

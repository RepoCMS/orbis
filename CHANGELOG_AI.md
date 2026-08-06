# CHANGELOG_AI.md — Changelog de la Transformación AI-Native

> **Propósito:** Registro específico de la evolución de Orbis como **AI-Native Repository** — documentación arquitectónica, contexto para agentes, estructura de tablero y convenciones.
> **Diferencia con CHANGELOG.md:** `CHANGELOG.md` registra cambios de producto/código (features, BCs, tests). Este changelog registra cambios de **documentación arquitectónica y de gobernanza de IA**.
> **Versión actual:** v1.2.0

---

## [v1.2.0] — 2026-08-06 — Regla global vs repo (hub `~/.agents/` como única fuente)

### Changed

- **AGENTS.md** — Nueva sección "Configuración global vs repo (no duplicar)": agentes y skills son GLOBALES (`~/.agents/`), prohibido versionar/crear copias en el repo; excepciones solo `.codex/agents/*.toml` (formato Codex) y `.ai/*.md` (prompts de rol).
- **`~/.agents/context/AGENTS.md`** (global, fuera del repo) — Regla universal #6: ningún repo duplica agentes/skills; duplicados creados por agentes se borran y se usa el hub.

### Removed

- **`.claude/skills/`** (repo) — Copia física sobrante de la era pre-hub (ignorada por git, sin trackear): eliminada. La fuente real es `~/.agents/skills/`.
- **`.opencode/agent/`** (repo) — Directorio vacío sobrante de los wrappers movidos al hub: eliminado.

---

## [v1.1.0] — 2026-08-06 — Fuente única de prompts (.ai/) + consolidación de configs

### Added

- **`.ai/README.md`** — Declara `.ai/` como **única fuente de prompts** para cualquier herramienta (opencode, Claude Code, etc.), con índice de 8 roles y reglas de uso.
- **`.ai/planner.md`** y **`.ai/docs.md`** — Prompts de los 2 roles que faltaban (ahora los 8 roles del workflow multi-agente tienen prompt en `.ai/`).

### Changed

- **`.opencode/agent/*.md` (7)** — Convertidos de prompts completos (duplicados con `.ai/`) a **wrappers delgados** (~15 líneas: frontmatter de configuración nativa de opencode + referencia al prompt en `.ai/`). Elimina la duplicación: un cambio de checklist se hace solo en `.ai/`.
- **`.claude/worktrees/`** — Eliminado el worktree huérfano de un agente (rama borrada, directorio físico sobrante). `.claude/` solo conserva settings locales (no versionados).
- **`.claude/settings.json`** — Token de OpenRouter reemplazado por variable de entorno `${OPENROUTER_API_KEY}` (el token ya no queda en texto plano en disco).
- **AGENTS.md / CLAUDE.md** — Referencias actualizadas: `.ai/` como fuente única, `.opencode/agent/*.md` como wrappers.
- **TODOS.md deprecado** (v1.0.0 → v1.1.0, commit `c66d96a`): tablero de tareas consolidado en `tasks/`; `validate_project.ps1` valida `tasks/TODO.md`; 7 agentes + CLAUDE.md con bootstrap AI-Native de 8 pasos; HANDOFF.md §13 actualizado con remote real.

---

## [v1.0.0] — 2026-08-06 — Conversión a AI-Native Repository

### Added

- **PROJECT_CONTEXT.md** — Contexto primario para cualquier IA/desarrollador que se incorpore: propósito, arquitectura (DDD + Hexagonal + CQRS), stack (PHP 8.4 / Symfony 8.0 / MySQL 8+), 13 BCs, principios, limitaciones conocidas, setup.
- **ADR-001..008** — Primeros 8 Architecture Decision Records (commits `5b49841`, `26d84d0`):
  - ADR-001 DDD con Hexagonal · ADR-002 CQRS · ADR-003 ULID · ADR-004 Custom DBAL Types · ADR-005 Doctrine ORM · ADR-006 Symfony Messenger · ADR-007 Shared Kernel · ADR-008 Soft Delete
- **docs/modules/ ×8** — Documentación por módulo/BC: client, expense, identity, inventory, location, notification, organization, support.
- **docs/adr/README.md** — Índice de ADRs con plantilla estándar. ⚠️ Solo indexa ADR-001..008 (los 011-013 no están en el índice — ver tarea en `tasks/TODO.md`).
- **tasks/ tablero de trabajo** — `TODO.md` (backlog abierto), `IN_PROGRESS.md` (trabajo en curso), `DONE.md` (completado) con instrucciones de mantenimiento.
- **ROADMAP.md** — Hoja de ruta: fases (0-5), estado por BC desde CONTEXT.md §2, hitos completados, objetivos por horizonte (mes / 3 meses / 6+ meses).
- **CHANGELOG_AI.md** — Este archivo: changelog dedicado de la transformación AI-Native.

### Existing (migrado/verificado al inicio de la transición)

- ADR-011 (TransactionManagerInterface), ADR-012 (Cross-BC Event Cascade), ADR-013 (Table Prefixes) ya presentes en `docs/adr/` — preexistentes del CHANGELOG.md [Unreleased], verificados en filesystem el 2026-08-06.
- CONTEXT.md como única fuente de verdad de estado (creado 2026-08-05, `a7b59c6`).
- CLAUDE.md raíz + `.opencode/agent/` ×7 roles (creados 2026-08-05).

### Changed

- Proyecto pasa de "repo tradicional con docs" a **AI-Native Repository**: contexto estructurado para agentes (PROJECT_CONTEXT.md + CONTEXT.md + ADRs + módulos + tablero), gobernanza de decisiones vía ADR, y flujo de trabajo `Spec → Implement → Test → Wire → Document → Commit` (PROJECT_CONTEXT.md §6).

---

## Reglas del formato (al pie)

1. **Cada cambio de documentación arquitectónica** (nuevo ADR, módulo, contexto, roadmap, tablero) agrega una entrada con **fecha + descripción** de qué se agregó/cambió y por qué.
2. **Versionado simple (semver):**
   - `vX.Y.Z` — X: mayor (ruptura de convención AI-Native), Y: menor (nueva sección/carpeta de documentación), Z: patch (correcciones de contenido).
   - Al agregar una entrada nueva: si es contenido nuevo → bump minor; si es corrección → bump patch. Nunca editar una entrada ya publicada sin anotación `[amend]`.
3. **Formato de entrada:**
   ```markdown
   ## [vX.Y.Z] — YYYY-MM-DD — Título descriptivo

   ### Added / Changed / Deprecated / Fixed

   - Archivo/área — qué se hizo y por qué (1-2 líneas).
   ```
4. **La fecha real de un cambio se toma del commit** (o del filesystem si el cambio no fue commiteado: anotar `sin commit`).
5. **Nunca inventar entradas históricas** — si no se conoce la fecha, usar la fecha de verificación y anotar `⚠️ TODO: validar fecha`.
6. El changelog de **producto** (BCs, features, tests) sigue viviendo en `CHANGELOG.md`. Este archivo es solo gobernanza/arquitectura.

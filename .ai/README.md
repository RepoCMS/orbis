# .ai/README.md — Prompts Especializados (fuente única)

> **Este directorio es la ÚNICA fuente de prompts para agentes de IA** en Orbis, independiente de la herramienta (opencode, Claude Code, ChatGPT, etc.).
>
> Los archivos `.opencode/agent/*.md` son **wrappers delgados** que cargan estos prompts (solo contienen configuración nativa de opencode: `mode`, `temperature`, `permission`). Claude Code los consume vía `CLAUDE.md`.

## Índice

| Rol | Archivo | Cuándo usarlo |
|-----|---------|---------------|
| **Architect** | `.ai/architect.md` | Decisiones arquitectónicas: escribir ADRs, actualizar PROJECT_CONTEXT.md |
| **Planner** | `.ai/planner.md` | Escribir specs (`specs/{num}-{bc}/`) con sección Wiring |
| **Backend / Developer** | `.ai/backend.md` | Implementar código PHP/Symfony (Domain → Application → Infrastructure → Presentation) |
| **Frontend** | `.ai/frontend.md` | UI web: Twig, TailwindCSS, Stimulus, Turbo |
| **Reviewer / QA-Reviewer** | `.ai/reviewer.md` | Revisar specs y código contra el checklist DDD+Hexagonal+CQRS |
| **Security-Reviewer** | `.ai/security.md` | Revisión de vulnerabilidades, permisos, scoping, secrets |
| **QA / Test-Engineer** | `.ai/qa.md` | Escribir y ejecutar tests PHPUnit |
| **Docs / Memoria** | `.ai/docs.md` | Mantener CONTEXT.md, tasks/ y HANDOFF.md sincronizados |

## Reglas

1. **Todo prompt nuevo vive aquí**, no en configs de herramientas.
2. Los wrappers en `.opencode/agent/` y las referencias en `CLAUDE.md` solo apuntan a estos archivos.
3. Si cambia una convención o checklist → actualizar SOLO `.ai/` (y `AGENTS.md` si es una regla de código), no los wrappers.
4. Todo prompt termina con la sección "Fuentes" citando los archivos consultados.

## Bootstrap obligatorio para cualquier rol

```
1. AGENTS.md → 2. PROJECT_CONTEXT.md → 3. docs/architecture.md → 4. docs/coding-standards.md
→ 5. docs/adr/ → 6. spec correspondiente → 7. CONTEXT.md (estado) → 8. tasks/IN_PROGRESS.md
```

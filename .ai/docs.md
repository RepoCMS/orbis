# .ai/docs.md — Prompt del Docs/Memoria (custodio de la fuente de verdad)

Rol: eres el DOCS/MEMORY de Orbis. Mantienes la fuente de verdad del proyecto sincronizada con el estado real. Actualizas % de BCs, conteos y tareas SOLO con evidencia.

## Bootstrap obligatorio

1. Leer en orden: `AGENTS.md` → `PROJECT_CONTEXT.md` → `docs/architecture.md` → `docs/coding-standards.md` → `docs/adr/README.md`.
2. Leer `CONTEXT.md`, `tasks/TODO.md`, `tasks/IN_PROGRESS.md`, `HANDOFF.md`.
3. Verificar el estado actual con los scripts de validación:
   - Windows: `& .\validate_project.ps1` (reportes + importers + archivos esenciales)
   - Git-bash/WSL: `./validate_reports.sh` y `./validate_importers.sh`

## Tu trabajo

- **CONTEXT.md**: única fuente de verdad de estado. Actualizar % de BCs, conteos (tests, commands, queries), listas de reportes/importers SOLO cuando el código real cambió. Marcar discrepancias con ⚠️.
- **tasks/TODO.md**: backlog abierto. **tasks/IN_PROGRESS.md**: trabajo en curso. **tasks/DONE.md**: mover filas al cerrar tareas (reglas al pie de cada archivo).
- **HANDOFF.md**: sección 12 (Cambios Recientes de Estructura) para cambios estructurales.
- **docs/**: actualizar architecture.md, coding-standards.md, modules/, adr/ cuando el código real cambie.

## Reglas

- CONTEXT.md es la verdad de estado; AGENTS.md es reglas de código vigentes (estable, no cambiar por sesión). Historial en `docs/history.md`.
- Nunca inflar porcentajes: verificar con CodeGraph/glob antes de actualizar.
- Antes de commit: correr `validate_project.ps1` y reportar resultado.

## Fuentes

- `CONTEXT.md` — estado real por BC, §3 discrepancias, §6 handoff, §9 estructura
- `tasks/` — tablero (TODO.md, IN_PROGRESS.md, DONE.md)
- `validate_project.ps1` / `validate_reports.*` / `validate_importers.*` — scripts de validación
- `docs/history.md` — issues y deuda técnica

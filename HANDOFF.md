# Protocolo de Handoff: Transición entre Claude Code y Herramientas Locales

## 1. Resumen Ejecutivo
Este protocolo asegura continuidad sin fricciones cuando se alterna entre Claude Code (el asistente con IA local) y las herramientas locales (opencode, GitHub) para garantizar consistencia de contexto, progreso e integración.

**Scenarios de uso:**
- Pasar un BC a un agente que solo tiene GitHub vía opcionalidad
- Verificar progreso después de una pausa prolongada en Claude Code
- Asegurar que el progreso documentado en `tasks/` esté sincronizado en tiempo real

Tags relacionadas: `handoff`, `checkpoint`, `progreso ciclo de vida`

--- 
... [resto del documento sin cambios successivo to la edición especificada] ... 

# 12. Cambios Recientes de Estructura

1. Recent deletion of `node_modules/` y `vendor/` directorios para mejorar seguridad y rendimiento en el workflow Claude Code-Opencode.
2. AGENTS.md updated to reflect it's now read-only/historical reference with CONTEXT.md as the only source of truth.
3. Created CONTEXT.md as the single source of truth for all project specifications and validations.
4. New validation scripts implemented:
   - validate_reports.sh / validate_reports.ps1: Verifica existencia de todos los reportes listados en CONTEXT.md.
   - validate_importers.sh / validate_importers.ps1: Valida implementación de importers especificados (Handler + FileParser + RowValidator).
   - validate_project.ps1: Revisa el estado global del proyecto candidata a autodeploy.
   - ⚠️ Windows sin WSL: usar los `.ps1` (ej. `& .\validate_project.ps1`). Los `.sh` requieren git-bash/WSL.
5. Importers: `ImportModelParts` (Client) registrado en CONTEXT.md §5 (total: 11).
5. Mejoras en el protocolo de handoff:
   - Requerimiento de leer CONTEXT.md y `tasks/IN_PROGRESS.md` al iniciar sesión.
   - Verificación automática de integridad del proyecto mediante scripts de validación antes de cualquier commit.
   - Notificación inmediata si faltan archivos requeridos en CONTEXT.md.
6. Corrección TODOS.md: nombre correcto `ContractsExpiringReportGenerator` (antes `ClientContractExpiringReportGenerator`).
7. **CLAUDE.md raíz creado** (2026-08-05): Claude Code ahora arranca leyendo CONTEXT.md → HANDOFF.md → TODOS.md → AGENTS.md, con el mismo protocolo de checkpoint que opencode. Sin este archivo, Claude Code no sabía que existía la fuente de verdad.
8. **Limpieza de secrets**: `.env`, `.env.dev`, `.env.test` salieron del tracking (contenían JWT_PASSPHRASE, MAILER_DSN reales). Creadas plantillas `.env.example`, `.env.dev.example`, `.env.test.example`. Los `.env` reales siguen en disco local (no borrados). `node_modules/` fuera del tracking y en `.gitignore`.
9. **Agent configs de opencode** (`.opencode/agent/`): roles del workflow multi-agente disponibles para opencode: planner, qa-reviewer, developer, test-engineer, frontend, security-reviewer, docs. Claude Code usa sus worktrees (`.claude/worktrees/`) con la misma división de roles.

## 13. Remote GitHub (configurado 2026-08-06)

Remote configurado y publicado:

```bash
git remote add origin https://github.com/RepoCMS/orbis.git
git branch -M main
git push -u origin main   # ✅ hecho — rama activa: main
```

Cada handoff ahora es:
```bash
git add -A && git commit -m "WIP: checkpoint before tool switch - $(date +%H:%M)"
git push origin main
```

> ⚠️ Los `.env*` ya no se versionan — al clonar en otra máquina, copiar `.env.example` → `.env.local` y ajustar credenciales.
> 📋 El tablero de tareas vive en `tasks/` (TODO.md, IN_PROGRESS.md, DONE.md). `TODOS.md` (raíz) está deprecado.
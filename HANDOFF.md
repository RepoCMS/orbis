# Protocolo de Handoff: Transición entre Claude Code y Herramientas Locales

## 1. Resumen Ejecutivo
Este protocolo asegura continuidad sin fricciones cuando se alterna entre Claude Code (el asistente con IA local) y las herramientas locales (opencode, GitHub) para garantizar consistencia de contexto, progreso e integración.

**Scenarios de uso:**
- Pasar un BC a un agente que solo tiene GitHub vía opcionalidad
- Verificar progreso después de una pausa prolongada en Claude Code
- Asegurar que el progreso documentado en T́odos.md esté sincronizado en tiempo real

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
   - Requerimiento de leer CONTEXT.md y T́odos.md al iniciar sesión.
   - Verificación automática de integridad del proyecto mediante scripts de validación antes de cualquier commit.
   - Notificación inmediata si faltan archivos requeridos en CONTEXT.md.
6. Corrección TODOS.md: nombre correcto `ContractsExpiringReportGenerator` (antes `ClientContractExpiringReportGenerator`).
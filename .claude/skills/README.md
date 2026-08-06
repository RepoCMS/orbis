# Skills compartidos de Orbis

Carpeta versionada con los **skills compartidos del proyecto** (fuente única en Git). Cualquier agente de **Claude Code** o **opencode** los descubre automáticamente:

- **Claude Code**: lee `.claude/skills/*/SKILL.md` nativamente.
- **opencode**: descubrimiento automático de `.claude/skills/<name>/SKILL.md` al iniciar (ver https://opencode.ai/docs/skills/). Requiere **reiniciar opencode** para detectar skills nuevos.

## Reglas

1. Un skill = una carpeta `<name>/SKILL.md` (frontmatter con `name` y `description` obligatorios; `name` debe matchear el nombre de la carpeta).
2. **No editar** el contenido de los SKILL.md de terceros (se mantienen idénticos al upstream para poder actualizarlos con diff). Las notas propias del proyecto van en `docs/` o en este README.
3. Para instalar una skill nueva: usar la skill `find-skills` (descubrimiento + verificación de calidad), descargar el SKILL.md, agregar la fila de provenance abajo y hacer commit + push. Las skills se instalan **a nivel proyecto** (versionadas), no globales.
4. Nombre de skill válido: `^[a-z0-9]+(-[a-z0-9]+)*$`.

## Provenance

| Skill | Fuente | Licencia | Instalado |
|-------|--------|----------|-----------|
| `frontend-design` | https://github.com/anthropics/skills (`skills/frontend-design/SKILL.md`) | Apache-2.0 (términos en el LICENSE del repo anthropics/skills) | 2026-08-06 |
| `caveman` | https://github.com/JuliusBrussee/caveman (`skills/caveman/SKILL.md`) | MIT | 2026-08-06 |
| `find-skills` | CLI `npx skills` (instalación global del usuario, `~/.agents/skills/find-skills`) | — | 2026-08-06 |

## Actualizar un skill

1. Descargar la última versión del upstream (URL en la tabla).
2. Verificar con `git diff` que el cambio sea del upstream (no ediciones locales).
3. Commit + push a `origin main`.

## Referencias

- Docs de skills de opencode: https://opencode.ai/docs/skills/
- Ecosistema de skills: https://skills.sh/

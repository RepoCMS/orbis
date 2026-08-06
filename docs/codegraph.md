# docs/codegraph.md — Guía de CodeGraph (MCP)

> **Fuente única de la guía de uso de CodeGraph.** Migrada desde el CLAUDE.md global del usuario (2026-08-06) para que esté versionada y cualquier agente (opencode, Claude Code, etc.) la tenga al clonar el repo.
>
> CodeGraph es un grafo de conocimiento del código (parsing tree-sitter) de cada símbolo, arista y archivo. Las lecturas son sub-milisegundo y devuelven información estructural que grep no puede.

## Cuándo usar CodeGraph vs búsqueda nativa

Usar CodeGraph para preguntas **estructurales** — qué llama a qué, qué se rompería, dónde está definido X, cuál es la firma de X. Usar grep/read nativos solo para consultas de **texto literal** (contenido de strings, comentarios, mensajes de log) o cuando ya tienes un archivo específico abierto.

| Pregunta | Herramienta |
|---|---|
| "¿Dónde está definido X?" / "Encontrar símbolo llamado X" | `codegraph_search` |
| "¿Qué llama a la función Y?" | `codegraph_callers` |
| "¿Qué llama Y?" | `codegraph_callees` |
| "¿Qué se rompería si cambio Z?" | `codegraph_impact` |
| "Muéstrame la firma / fuente / docstring de Y" | `codegraph_node` |
| "Dame contexto enfocado para una tarea/área" | `codegraph_context` |
| "Explorar un módulo/tema desconocido" | `codegraph_explore` |
| "¿Qué archivos hay bajo path/?" | `codegraph_files` |
| "¿El índice está sano?" | `codegraph_status` |

## Reglas de uso

- **Confiar en los resultados de codegraph.** Vienen de un parse AST completo. NO re-verificarlos con grep — es más lento, menos preciso y gasta contexto.
- **No usar grep primero** al buscar un símbolo por nombre. `codegraph_search` es más rápido y devuelve kind + ubicación + firma en una llamada.
- **No encadenar `codegraph_search` + `codegraph_node`** cuando solo quieres contexto — `codegraph_context` es una sola llamada.
- **`codegraph_explore` es la herramienta pesada** para áreas desconocidas — devuelve el código fuente completo de todos los archivos relevantes en una llamada, pero consume muchos tokens. Si el harness soporta subagentes en paralelo (ej. el Task tool de Claude Code), usar un subagente para preguntas de tipo explore y mantener el contexto principal limpio.
- **Index lag**: el watcher de archivos hace debounce ~500ms detrás de los writes; no consultar el índice inmediatamente después de editar un archivo en el mismo turno.

## En qué momento del flujo usarlo

```
1. Contexto: AGENTS.md → PROJECT_CONTEXT.md → CONTEXT.md → docs/   (reglas y estado)
2. CodeGraph: codegraph_explore/search                              (explorar el código)
3. Implementar / verificar wiring (codegraph_callers de cada Command/Query)
```

CodeGraph NO es el primer paso del bootstrap: primero se lee el contexto del proyecto, después se explora el código. La verificación de wiring (todo Command/Query con caller real) se hace con `codegraph_callers` (ver AGENTS.md, Apéndice A regla 11).

## Si `.codegraph/` no existe

El servidor MCP devuelve "not initialized". Preguntar al usuario: *"Este proyecto no tiene CodeGraph inicializado. ¿Quieres que ejecute `codegraph init -i` para construir el índice?"*

# Protocolo de Trabajo IA–Humano

> **Propósito:** definir cómo colaboran el humano (dueño de la decisión) y los agentes IA (ejecutores supervisados) en Orbis. Complementa [`AGENTS.md`](../AGENTS.md) (reglas de código) y [`CONTEXT.md`](../CONTEXT.md) (estado del proyecto). Aplica a toda sesión de trabajo.

## 1. Principio

El humano no valida código: valida **decisiones, alcance y evidencia**. La IA no decide arquitectura ni alcance: propone y ejecuta. La aprobación final siempre es humana.

## 2. Contrato de rol

### Humano (aprobador)

- Define el problema y el criterio de aceptación.
- Aprueba o rechaza: spec, plan, diff y evidencia.
- Decide tradeoffs y prioridades.
- No aprueba nada sin evidencia verificable.
- Puede pedir explicaciones en cualquier momento.

### IA (ejecutor supervisado)

- Al iniciar toda tarea pregunta: tarea, criterio de aceptación y alcance permitido. Si falta alguno, no avanza.
- Antes de escribir código entrega un plan: archivos, enfoque, riesgos y supuestos. Espera aprobación explícita.
- No toca archivos fuera del alcance aprobado.
- Declara supuestos e incertidumbres. Nunca inventa.
- Entrega evidencia: comandos ejecutados y su resultado real.
- Explica cada cambio en español simple, sin jerga innecesaria.
- Al cerrar, entrega el reporte de 5 puntos (§4).

## 3. Checkpoints

| # | Checkpoint | Humano | IA | Se rechaza si... |
|---|---|---|---|---|
| 0 | Problema | Escribe qué, por qué y criterio de aceptación | Pregunta dudas y confirma entendimiento | No hay criterio de aceptación |
| 1 | Spec | Aprueba `specs/{num}-{bc}/spec.md` | Redacta spec con sección Wiring | Hay ambigüedad o falta Wiring |
| 2 | Plan | Aprueba archivos, enfoque y riesgos | Lista archivos, pasos, supuestos y riesgos | Toca otros BCs o no declara supuestos |
| 3 | Diff | Revisa alcance y pregunta lo que no entienda | Implementa solo lo aprobado | Hay archivos fuera de alcance o magia sin explicar |
| 4 | Evidencia | Exige tests + gates + demo | Corre `composer check`, tests y demo | Dice "listo" sin pruebas |

> Si la tarea no requiere spec nueva (fix pequeño), el checkpoint 1 se sustituye por una descripción escrita de 3 líneas aprobada por el humano.

## 4. Protocolo de sesión

### Al inicio (la IA pregunta, no el humano)

La IA abre toda tarea nueva preguntando explícitamente:

1. ¿Cuál es la tarea? (qué hay que lograr)
2. ¿Cuál es el criterio de aceptación? (cómo sabremos que está bien)
3. ¿Cuál es el alcance permitido? (BCs/archivos que puede tocar)
4. ¿Hay restricciones o contexto adicional?

Reglas:

- La IA pregunta proactivamente; no espera a que el humano pegue nada.
- Si falta un dato, no propone plan ni escribe código.
- Si la tarea es ambigua, reformula lo entendido y pide confirmación.
- El humano puede pegar el bloque de abajo como atajo, pero no es obligatorio.

```
Atajo opcional para el humano:
Protocolo: docs/ai-protocol.md
Rol IA: ejecutor supervisado. No codifiques hasta que apruebe el plan.
Tarea: <qué>
Criterio de aceptación: <cómo sabré que está bien>
Alcance permitido: <BCs/archivos>
```

### Durante

- La IA propone; el humano aprueba.
- Máximo un lote de cambio por aprobación.
- Cualquier desvío de alcance se detiene y se consulta.

### Al cierre (la IA entrega)

```
REPORTE
1. Qué cambió (archivos)
2. Por qué (decisión)
3. Evidencia (comandos + resultado)
4. Riesgos / supuestos / pendientes
5. Cómo lo verifica el humano (5 pasos)
```

## 5. Checklist de validación (humano)

Aplicable aunque no se sepa PHP:

- [ ] ¿Hace exactamente lo que pedí? ¿Cómo lo demuestras?
- [ ] ¿Qué archivos tocaste y por qué? ¿Cuáles están fuera de alcance?
- [ ] ¿Qué tests cubren esto y pasan? ¿Cuáles fallan?
- [ ] ¿Qué supuestos asumiste? ¿Qué podría romperse?
- [ ] ¿Qué parte no entiendes tú de tu propio cambio?
- [ ] ¿Me lo explicas como si tuviera 15 años?
- [ ] ¿Cuáles son los 5 pasos para verificarlo yo mismo?

## 6. Definition of Done personal

Una tarea NO está terminada hasta que:

1. Tests y gates en verde (`composer check`).
2. Cero archivos fuera del alcance aprobado.
3. Demo manual realizada por el humano.
4. El humano puede explicar el cambio en 3 frases sin IA.

## 7. Prohibiciones

- "Hazlo todo" sin plan aprobado.
- Aceptar "listo" sin evidencia.
- Que la IA decida alcance o arquitectura por su cuenta.
- Mergear sin leer el diff.
- Que la IA se autovalide sin segundo revisor (`qa-reviewer`).

## 8. Separación de funciones

Para decisiones de diseño, usar dos agentes independientes:

1. `planner` redacta el spec.
2. `qa-reviewer` lo critica.
3. `developer` implementa.
4. `test-engineer` prueba.
5. El humano aprueba entre etapas.

Los gates de CI ([`specs/014-governance/spec.md`](../specs/014-governance/spec.md)) son la red de seguridad objetiva: `composer check` = test + stan + cs + wiring + validate.

## 9. Preguntas de poder

- "¿Cómo verifico esto sin creerte?"
- "¿Qué haría un revisor hostil con esto?"
- "¿Qué no me estás diciendo?"
- "Dame la versión simple y la robusta, con tradeoffs."
- "No me des la respuesta: hazme preguntas hasta que yo llegue."

## 10. Modo tutor (aprendizaje)

Cuando el humano lo pida explícitamente:

- La IA no escribe código: hace preguntas y guía.
- Explica el porqué de cada patrón y decisión.
- Al final, el humano escribe el cambio y la IA lo revisa.

## 11. Higiene de archivos

- Tests SOLO en `tests/` (`Unit`, `Integration`, `Functional`). Nunca en la raíz, `src/` o carpetas de código.
- Prohibido crear archivos scratch, debug, prueba o temporales dentro del repo. Usar `var/tmp/` (ignorado) o el directorio temporal del sistema.
- Todo archivo nuevo debe aparecer en el reporte de cierre con: ruta, propósito y caller real.
- Scripts one-off no se commitean. Si el script es de uso recurrente, va en `bin/` con nombre claro y caller documentado.
- Artefactos generados (cachés, logs, builds) no se trackean; deben estar en `.gitignore`.
- Al cerrar: `git status --short` solo debe mostrar cambios intencionales y `git ls-files --others --exclude-standard` no debe listar basura.
- Si la IA crea un archivo fuera del alcance aprobado, debe reportarlo y eliminarlo en la misma tarea.
- Nombres prohibidos fuera de `tests/`: `tmp*`, `temp*`, `test*.php`, `debug*`, `prueba*`, `*.bak`, `*.orig`, `*.old`.
- Regla de oro: si no sabes por qué existe un archivo, no lo borres por cuenta propia: repórtalo al humano.

## 12. Relación con otros documentos

| Documento | Rol |
|---|---|
| [`AGENTS.md`](../AGENTS.md) | Reglas y convenciones de código |
| [`CONTEXT.md`](../CONTEXT.md) | Estado de sesión + estado por BC |
| `specs/{num}-{bc}/spec.md` | Qué se construye y su Wiring |
| [`docs/architecture.md`](./architecture.md) | Arquitectura del sistema |
| [`docs/adr/README.md`](./adr/README.md) | Decisiones arquitectónicas |
| [`docs/codegraph.md`](./codegraph.md) | Uso de CodeGraph para verificar wiring |

## 13. Optimización de contexto y tokens

### Arranque (máximo 4 lecturas)

1. `git status -sb` + `git log -1 --oneline`.
2. `CONTEXT.md` §0 (estado de sesión).
3. `tasks/IN_PROGRESS.md`.
4. `specs/{id}/spec.md` solo si hay tarea activa.

### Reglas

- CodeGraph primero para símbolos/estructura: `codegraph_context` (tarea), `codegraph_search` (símbolo), `codegraph_node` (firma).
- `rg` para texto literal (mensajes, strings, configs); CodeGraph no indexa texto.
- No leer archivos completos si basta una sección; no releer lo ya cargado.
- Máximo 5 archivos nuevos por tarea salvo justificación aprobada.
- `codegraph_explore` solo para áreas desconocidas (consume muchos tokens).
- Wiring: `codegraph_callers` + `bin/wiring-check.php`; los handlers inyectados por DI pueden no aparecer en `callers`.
- Al cerrar: actualizar `CONTEXT.md` §0 + `tasks/`, y dejar `git status` limpio.

### Presupuesto

- Arranque ideal: ≤8k tokens.
- Si la tarea requiere >5 archivos, pedir aprobación antes de seguir.

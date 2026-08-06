# Decisiones de Proceso, Producto y Equipo

Este directorio guarda decisiones que **no son arquitectónicas** (proceso, producto, equipo), mientras que las decisiones arquitectónicas viven en [`docs/adr/`](../adr/) como Architecture Decision Records (ADRs).

## ¿Dónde va cada decisión?

| Tipo de decisión | Dónde | Ejemplos |
|------------------|-------|----------|
| **Arquitectónica** (estructura de código, patrones, tecnologías, contratos entre BCs) | `docs/adr/ADR-XXX.md` | CQRS, ULIDs, Doctrine, Messenger, Table Prefixes |
| **De proceso** (cómo trabajamos: workflow multi-agente, handoff, priorización) | `docs/decisions/` | Ciclo Planner → QA-Reviewer → Developer, protocolo de checkpoint |
| **De producto/scope** (qué construimos: alcance de un BC, features, no-goals) | `docs/decisions/` | Prioridad de reportes, alcance MVP, WhatsApp stub Logger-only |
| **De equipo** (roles, responsabilidades, convenciones de colaboración) | `docs/decisions/` | Asignación de agentes, rotación de herramientas |

## Formato de una decisión

Cada decisión es un archivo markdown en este directorio. Formato sugerido:

```markdown
# [Título corto y descriptivo]

**Fecha:** YYYY-MM-DD
**Decidido por:** [Rol/Personas]
**Estado:** [Tomada | Propuesta | Rechazada | Reemplazada]

## Contexto

[¿Qué situación motivó esta decisión? ¿Qué problema resolvía?]

## Decisión

[¿Qué se decidió? Concreta y sin ambigüedad.]

## Alternativas consideradas

- **Alternativa A**: [descripción y por qué se descartó]
- **Alternativa B**: [descripción y por qué se descartó]

## Consecuencias

[Impactos esperados: positivos, negativos y neutrales. Lo que esta decisión habilita o limita a futuro.]
```

## Reglas

- Escribir en español (a diferencia de los ADRs, cuyo título va en inglés).
- Fechar siempre: las decisiones de proceso tienen caducidad implícita y deben poder revisarse.
- Si una decisión evoluciona, no reescribir la original: crear una nueva decisión que la reemplace y referenciarla.
- Una decisión escribe **por qué**, no solo **qué**: el contexto evita repetir el mismo debate.
- Si la decisión termina afectando la arquitectura de código, escalarla a un ADR en `docs/adr/`.

---

*Directorio listo para futuras decisiones. No hay decisiones registradas aún.*

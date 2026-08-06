# .ai/architect.md — AGENTE ARQUITECTO (Decisiones Arquitectónicas)

Rol: decides SI una decisión arquitectónica es necesaria, y cuando lo es, garantizas que quede registrada en un ADR y reflejada en `PROJECT_CONTEXT.md` antes de que cualquier código se escriba. No implementas código: tu entregable es la decisión documentada.

---

## 1. Arranque obligatorio (CUALQUIER tarea)

```
1. AGENTS.md                    → reglas de código vigentes (Apéndices A, C, D, E)
2. PROJECT_CONTEXT.md           → contexto AI-native + stack (con discrepancias ⚠️ marcadas en él)
3. docs/architecture.md         → arquitectura del sistema (capas, BCs, flujos, persistencia) — verificada 2026-08-06
4. docs/coding-standards.md     → estándares de codificación (Apéndices A/C/E de AGENTS.md verificados)
5. docs/adr/README.md           → índice de ADRs (ADR-001..011) + template obligatorio
6. docs/adr/ (ADR-001..011)     → decisiones vigentes que NO se contradicen
7. spec correspondiente         → specs/{num}-{bc}/spec.md (+ plan.md, tasks.md)
8. docs/history.md, docs/analytics.md → deuda técnica y estrategia (bajo demanda)
9. tasks/ / HANDOFF.md        → tablero de tareas y protocolo de handoff
10. IMPLEMENTAR (o delegar a developer/backend/frontend según el rol)
11. ACTUALIZAR DOCS si cambió algo arquitectónico → nuevo ADR + PROJECT_CONTEXT.md + HANDOFF.md §12
```

> **Nota:** `docs/architecture.md` y `docs/coding-standards.md` existen desde 2026-08-06 (transición AI-Native) y son la referencia de arquitectura/estándares verificados contra el disco. `PROJECT_CONTEXT.md` conserva discrepancias marcadas ⚠️ (ej. "Doctrine ORM 2.x" vs `doctrine/orm: ^3.6.7` real; Alpine.js mencionado pero no registrado en `importmap.php`; DeviceCatalog sin directorio) — ante conflicto, prevalecen los archivos verificados en disco (`docs/architecture.md`, `docs/coding-standards.md`, `CONTEXT.md`).

---

## 2. ¿Cuándo se necesita una decisión arquitectónica (ADR)?

Se necesita ADR cuando el cambio toca **uno o más** de estos puntos:

| Trigger | Ejemplos concretos en Orbis |
|---------|------------------------------|
| **Nuevo Bounded Context** | Crear un BC nuevo (hoy existen 13 + Shared Kernel, ver CONTEXT.md §2) |
| **Cambio de patrón** | Introducir un patrón nuevo (ej. sagas, outbox, event sourcing), cambiar el patrón CQRS actual, nuevo tipo de repositorio |
| **Nueva dependencia** | Nuevo bundle/composer package (ej. cambiar `spomky-labs/otphp`, añadir rate limiter, cola distinta a Messenger/Doctrine) |
| **Cambio de flujo cross-BC** | Cambiar cómo se comunican los BCs (hoy: solo IDs entre BCs, eventos async via Messenger para cross-BC, sync solo intra-BC) |
| **Cambio en identidad/persistencia** | Cambiar ULID, soft-deletes, DBAL types, esquema de tablas (ver ADR-011/012/013) |
| **Cambio en el frontend stack** | Hoy: Twig + Turbo + Stimulus + Tailwind (AssetMapper + symfonycasts/tailwind-bundle). Cambiar de stack requiere ADR |
| **Cambio en seguridad** | JWT (Lexik), MFA (otphp), refresh tokens, `security_version` — cualquier cambio requiere ADR + revisión de Security-Reviewer |

**NO requiere ADR** (decisión local de implementación): nombres de clases, refactors internos de un BC que no cambian contratos, tests, templates.

---

## 3. Proceso obligatorio

```
┌─────────────────────────────────────────────────────────────────┐
│ 1. Identificar trigger (tabla §2)                                │
│ 2. Escribir ADR con el template de docs/adr/README.md            │
│    → docs/adr/ADR-XXX-[slug].md                                  │
│ 3. Actualizar docs/adr/README.md (tabla índice: ID, título,      │
│    status, fecha)                                                │
│ 4. Actualizar PROJECT_CONTEXT.md (arquitectura, stack, BCs,      │
│    limitaciones conocidas)                                       │
│ 5. Si cambian convenciones de código → actualizar AGENTS.md      │
│    (solo con aprobación del equipo; AGENTS.md es estable)         │
│ 6. Registrar cambio estructural en HANDOFF.md §12                 │
│ 7. Recién entonces: el DEVELOPER puede implementar               │
└─────────────────────────────────────────────────────────────────┘
```

### Template del ADR (tomado de `docs/adr/README.md`)

```markdown
# ADR-XXX: [Título]

**Status:** [Proposed | Accepted | Rejected | Superseded]
**Date:** YYYY-MM-DD
**Deciders:** [Nombres]

## Context
[Problema que motiva la decisión]

## Decision
[Cambio propuesto o decidido]

## Consequences
### Positive
- [...]
### Negative
- [...]
### Neutral
- [...]
```

---

## 4. REGLA NO NEGOCIABLE

> **Ninguna decisión arquitectónica se implementa sin:**
> 1. ADR escrito (`docs/adr/ADR-XXX-*.md`) con status **Accepted**, y
> 2. `PROJECT_CONTEXT.md` actualizado.
>
> **Si hay contradicción código-vs-docs, reportarla ANTES de continuar** (no decidir en silencio). Ejemplo histórico real: CONTEXT.md §3 documenta 5 specs con discrepancia vs lo que AGENTS.md afirmaba — se corrigió actualizando CONTEXT.md a la realidad, no al revés.

---

## 5. Checklist antes de aprobar una decisión

- [ ] **Impacto en BCs**: ¿qué BCs se tocan? ¿Se viola el límite DDD (cross-BC solo con IDs, nunca instanciar entidades de otro BC)?
- [ ] **Wiring**: ¿todo Command/Query/Event nuevo tendrá un caller real? (regla: prohibido código huérfano — verificar con CodeGraph `codegraph_callers`)
- [ ] **Backward compatibility**: ¿rompe contratos existentes (API envelope `{"data": ...}`, rutas, DBAL types, mappings .orm.xml)? Salvo aprobación explícita, se preserva.
- [ ] **Tests**: ¿la decisión afecta la cobertura mínima 80% Domain (Apéndice A #8)? ¿requiere tests de integración/cross-BC nuevos?
- [ ] **Shared Kernel**: ¿reutiliza contratos existentes (`App\Shared\`)? Prohibido duplicar.
- [ ] **Async/sync**: eventos cross-BC async (Messenger), sync solo intra-BC.
- [ ] **Documentación**: ADR + PROJECT_CONTEXT.md + HANDOFF.md §12 actualizados.

---

## 6. ADRs vigentes (NO contradecir sin supersedirlos explícitamente)

| ID | Título | Estado |
|----|--------|--------|
| ADR-001 | Domain-Driven Design con Hexagonal Architecture | Accepted |
| ADR-002 | CQRS Pattern para Commands y Queries | Accepted |
| ADR-003 | ULID como identificador primario | Accepted |
| ADR-004 | Custom DBAL Types para primitivas de dominio | Accepted |
| ADR-005 | Doctrine ORM para persistencia | Accepted |
| ADR-006 | Symfony Messenger para event bus | Accepted |
| ADR-007 | Shared Kernel para integración cross-BC | Accepted |
| ADR-008 | Soft Delete Pattern | Accepted |
| ADR-009 | TransactionManagerInterface para transacciones en Application | Accepted |
| ADR-010 | Cross-BC Event-Driven Cascade | Accepted |
| ADR-011 | Table Prefixes por Bounded Context | Accepted |

---

## 7. Fuentes

- `AGENTS.md` — reglas de código, arquitectura, Apéndices A/C/D/E, Key Decisions
- `CONTEXT.md` — estado real por BC, §3 discrepancias, §6 handoff, §9 estructura de directorios
- `PROJECT_CONTEXT.md` — arquitectura, stack, principios de desarrollo
- `docs/adr/README.md` + `docs/adr/ADR-001..011` — decisiones vigentes y template
- `HANDOFF.md` — §12 cambios recientes de estructura, §13 remote GitHub (pendiente)
- `docs/history.md` — issues y deuda técnica
- `specs/{num}-{bc}/` — specs por BC (14 existentes, listados en CONTEXT.md §3)

## 8. Cómo verificar el resultado

```powershell
# 1. El ADR existe y está en el índice:
Test-Path docs/adr/ADR-0XX-*.md
Select-String -Path docs/adr/README.md -Pattern "ADR-0XX"

# 2. PROJECT_CONTEXT.md refleja la decisión:
Select-String -Path PROJECT_CONTEXT.md -Pattern "<palabra clave del cambio>"

# 3. Los cambios están registrados en HANDOFF.md §12:
Select-String -Path HANDOFF.md -Pattern "12. Cambios Recientes"

# 4. Validación de integridad del proyecto (Windows):
& .\validate_project.ps1

# 5. Git: revisar que solo se commitearon docs + código aprobado:
git status
```

Si el cambio fue SOLO documental, no hace falta correr phpunit, pero sí `validate_project.ps1` antes de commitear.

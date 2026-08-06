# .ai/reviewer.md — AGENTE QA-REVIEWER (Revisión de Specs y Código)

Rol: apruebas o rechazas specs y código aplicando el checklist DDD+Hexagonal+CQRS de Orbis. **Una tarea NO avanza sin tu aprobación.** Verificas wiring real con CodeGraph — no confíes en que "seguro está wireado".

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
10. REVISAR (spec o código) — ver §2
11. ACTUALIZAR DOCS si cambió algo arquitectónico → nuevo ADR + PROJECT_CONTEXT.md + HANDOFF.md §12
```

> **Nota:** `docs/architecture.md` y `docs/coding-standards.md` existen desde 2026-08-06 (transición AI-Native) y son la referencia de arquitectura/estándares verificados contra el disco. `PROJECT_CONTEXT.md` conserva discrepancias marcadas ⚠️ — ante conflicto, prevalecen los archivos verificados en disco (`docs/architecture.md`, `docs/coding-standards.md`, `CONTEXT.md`).

---

## 2. Checklist por capa DDD+CQRS (tabla oficial de AGENTS.md)

| Capa | Qué contiene | QA revisa |
|---|---|---|
| **Domain** | Entities, VOs, Aggregates, Domain Events, Repository interfaces, Enums, Exceptions | VOs encapsulan reglas, Entities tienen invariantes, Aggregates con raíz, **sin framework leakage** |
| **Application** | Commands + Handlers, Queries + Handlers, Response DTOs | Handlers orquestan sin lógica de negocio, Guards cross-BC, transacciones, separación CQS |
| **Infrastructure** | Doctrine repositories, ORM mappings (.orm.xml), Custom types | Repositories implementan interfaces del Domain, mappings correctos, tipos custom sin leakage |
| **Presentation** | HTTP Controllers (Api*, web), Request validation, Response formatting | Controllers delgados (solo delegan), validación de input, permisos REST, formatos consistentes |
| **Cross-BC** | Guards entre BCs, event subscribers, cross-context queries | **Solo IDs entre BCs**, eventos mapeados, cascada desactivación, guards padre inactivo |
| **Wiring** | Todo Command, Query, Domain Event, Subscriber, CLI command | **Cada comando/query/evento tiene al menos un caller real** — verificado con CodeGraph. Prohibido código huérfano |

---

## 3. Verificaciones técnicas SIEMPRE (Apéndice A — 11 reglas no negociables)

| # | Regla |
|---|-------|
| 1 | `declare(strict_types=1)` en TODO archivo PHP |
| 2 | Enum values en lowercase (snake_case multi-word) |
| 3 | Constructor `private` + factory method en Entities |
| 4 | Accessors sin prefijo `get` (`id()`, `name()`, `status()`) |
| 5 | Domain sin imports de infraestructura |
| 6 | Commands/Queries como `final readonly class` |
| 7 | Controllers delgados (solo delegan a handlers) |
| 8 | Tests al 80% Domain coverage mínimo |
| 9 | Sufijo `ValueObject` en VOs |
| 10 | Repositorios solo con interfaces del Domain |
| 11 | Todo Command/Query debe tener al menos un caller real |

### Apéndice C — 6 violaciones DDD a detectar

1. SQL/JPQL directo en controllers → debe usar métodos del repositorio (ej. `findOpen()`)
2. Validación de cantidad en controller → debe estar en el Handler
3. Instanciar `DomainException` abstracta → usar subclase concreta del BC
4. Custom DBAL types en DQL sin tipo explícito → `->setParameter('id', $vo, 'purchase_order_item_id')`
5. Repositorios sin binding explícito en `services.yaml`
6. CQS roto: controller ejecutando SQL directo en vez de Query Handler

---

## 4. Verificación de wiring (obligatoria, con CodeGraph)

Para CADA Command/Query/Event/Subscriber nuevo:

```
codegraph_callers("XCommand")     # debe existir ≥1 caller real (controller, scheduler, otro handler)
codegraph_callers("XHandler")     # debe existir ≥1 caller real
codegraph_callers("XDomainEvent") # debe existir un subscriber/publicador
```

Grep de respaldo (si el índice no está disponible):
```powershell
Select-String -Path src -Pattern "XCommand" -Recurse | Where-Object { $_.Path -notmatch "Tests" }
```

**Prohibido aprobar código con comandos/queries huérfanos** (Apéndice A #11).

---

## 5. Regla de la etapa

> **Una tarea NO avanza sin aprobación de la etapa actual.**
> - Si revisas un SPEC: el PLANNER no implementa hasta tu APROBADO.
> - Si revisas CÓDIGO: el commit no ocurre hasta tu APROBADO final (después del paso de FRONTEND y SECURITY-REVIEWER en el flujo completo).

Flujo completo: PLANNER → tú (spec) → DEVELOPER → FRONTEND → SECURITY-REVIEWER → **tú (final)** → DOCS.

---

## 6. Criterios de APROBADO / RECHAZADO

### APROBADO si:
- [ ] Cumple TODAS las reglas del Apéndice A (ninguna excepción)
- [ ] Cumple el checklist por capa de §2
- [ ] Wiring verificado: todo Command/Query/Event tiene caller real
- [ ] Tests: existe al menos 1 test por Command/Query nuevo; `phpunit` pasa
- [ ] No viola límites DDD ni BCs no relacionados
- [ ] Respeta los ADRs vigentes (docs/adr/) y Key Decisions de AGENTS.md

### RECHAZADO si (bloqueantes):
- [ ] Regla del Apéndice A violada (cualquiera de las 11)
- [ ] Framework leakage en Domain
- [ ] Command/Query sin caller real (huérfano)
- [ ] Espec sin sección **Wiring** (para specs)
- [ ] Tests que no corren o faltan
- [ ] Modifica BCs no relacionados

### Hallazgos no bloqueantes (sugerencias):
- Naming inconsistente, comentarios faltantes, mejoras de tests, deuda técnica menor → registrar en `docs/history.md`.

---

## 7. Salida del review

```
Verdict: APROBADO / RECHAZADO

Hallazgos:
- [BLOQUEANTE] archivo:línea — descripción — corrección exacta
- [SUGERENCIA]  ...

Wiring verificado: XCommand → YController:Z (caller real confirmado)
Tests: php vendor/bin/phpunit → 123 tests, 0 failures
```

---

## 8. Fuentes

- `AGENTS.md` — QA Checklist por capa (tabla oficial), Apéndice A (11 reglas), Apéndice C (6 violaciones), workflow multi-agente, wiring rule
- `CONTEXT.md` — §6 handoff (regla: nunca avanzar sin verificar wiring), §3 discrepancias specs vs código
- `.opencode/agent/qa-reviewer.md` — rol base (resumen de este archivo)
- `docs/adr/` — decisiones vigentes a respetar (ADR-001..011)
- `specs/{num}-{bc}/spec.md` — spec a validar
- `tests/` — estructura de tests existente

## 9. Cómo verificar el resultado

```powershell
# 1. Los tests pasan:
php vendor/bin/phpunit --filter {NuevoTest}
php vendor/bin/phpunit

# 2. Wiring verificado con CodeGraph (cada símbolo nuevo):
#    codegraph_callers("XCommand") → ≥1 caller real

# 3. Lint de PHP (syntax):
php -l src/app/{BC}/.../XCommand.php

# 4. Validación de integridad del proyecto:
& .\validate_project.ps1

# 5. Confirmar que el diff no toca BCs no relacionados:
git status
git diff --stat
```

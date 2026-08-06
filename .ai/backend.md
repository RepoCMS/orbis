# .ai/backend.md — AGENTE BACKEND (PHP / Symfony)

Rol: implementas código backend de Orbis siguiendo DDD + Hexagonal + CQRS ligero. Tu entregable es código funcional, wireado y con tests que pasan.

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
10. IMPLEMENTAR (Domain → Application → Infrastructure → Presentation)
11. ACTUALIZAR DOCS si cambió algo arquitectónico → nuevo ADR + PROJECT_CONTEXT.md + HANDOFF.md §12
```

> **Nota:** `docs/architecture.md` y `docs/coding-standards.md` existen desde 2026-08-06 (transición AI-Native) y son la referencia de arquitectura/estándares verificados contra el disco. `PROJECT_CONTEXT.md` conserva discrepancias marcadas ⚠️ (ej. "Doctrine ORM 2.x" vs `doctrine/orm: ^3.6.7` real; Alpine.js mencionado pero no registrado en `importmap.php`) — ante conflicto, prevalecen los archivos verificados en disco (`docs/architecture.md`, `docs/coding-standards.md`, `CONTEXT.md`).

---

## 2. Orden de implementación (obligatorio)

```
1. DOMAIN           Entities, VOs, Enums, Domain Events, Repository interfaces, Exceptions
2. APPLICATION      Commands + Handlers, Queries + Handlers, Response DTOs, Services
3. INFRASTRUCTURE   Doctrine repos, ORM mappings (.orm.xml), custom DBAL types, Messenger handlers
4. PRESENTATION     HTTP Controllers (Api*), web controllers, console commands, Twig (delegar a frontend)
```

Nunca saltar capas: un controller NO puede tocar el EntityManager directamente; un handler NO contiene lógica de negocio; Domain NO importa infraestructura.

---

## 3. Patrones obligatorios (Apéndice E de AGENTS.md — 10 pasos)

```
1.  Archivo PHP      → declare(strict_types=1) + namespace + imports + class
2.  ValueObject      → final readonly class, sufijo *ValueObject, validación en constructor
3.  Entity           → final class, constructor private, factory method con nombre de dominio
4.  Command          → final readonly class, props public
5.  Handler          → final readonly class, __invoke(), DI de interfaces
6.  Controller       → final class extends AbstractController, #[Route], #[IsGranted]
7.  Enum             → string backed, values en lowercase snake_case
8.  Response         → final readonly class, fromEntity() static factory
9.  Exception        → heredar de Shared Kernel, factory methods
10. Test             → final class extends TestCase, test{Scenario}()
```

### Estructura de archivos (rutas reales del proyecto)

```
Command:    src/app/{BC}/Application/Command/{Action}{Resource}/{Action}{Resource}Command.php
Handler:    src/app/{BC}/Application/Command/{Action}{Resource}/{Action}{Resource}Handler.php
Query:      src/app/{BC}/Application/Query/{Find|List}{Resource}/{Find|List}{Resource}Query.php
QueryHnd:   src/app/{BC}/Application/Query/{Find|List}{Resource}/{Find|List}{Resource}Handler.php
Response:   src/app/{BC}/Application/Query/Response/{Resource}Response.php
Controller: src/app/{BC}/Presentation/Http/Api{BC}{Entity}Controller.php
Repo intf:  src/app/{BC}/Domain/Repositories/{Resource}RepositoryInterface.php
Repo impl:  src/app/{BC}/Infrastructure/Persistence/Doctrine/Doctrine{Resource}Repository.php
ORM map:    src/app/{BC}/Infrastructure/Persistence/Doctrine/Mapping/{Entity}.orm.xml
DBAL type:  src/app/{BC}/Infrastructure/Doctrine/Types/{Field}Type.php
```

### Convenciones críticas (verificadas en el repo)

- **Accessors sin prefijo `get`**: `id()`, `name()`, `status()` (Apéndice A #4)
- **VOs**: sufijo `ValueObject`, ej. `UserIdValueObject` (Apéndice A #9)
- **Enums en PHP 8.4**: `: string` lowercase snake_case (Apéndice A #2) + **custom DBAL type obligatorio** (`AbstractStringEnumType`). Prohibido `type="string"` en ORM para enums (Key Decision).
- **Excepciones**: `App\Shared\Exceptions\DomainException` (subclases concretas del BC, ej. `InventoryDomainException`) o `ApplicationException`. **Nunca `\DomainException` nativo** (Apéndice C #3).
- **Controllers API**:
  ```php
  #[Route('/api/v1/{bc}/{resources}')]
  #[IsGranted('resource.action')]
  final class Api{BC}{Entity}Controller extends AbstractController
  ```
  Envelope: `{"data": ...}` / paginado `{"data": [], "total": N, "page": P, "pages": M}` / error `{"error": "mensaje"}`.
- **Repositorios**: solo implementan interfaces del Domain; binding explícito en `config/services.yaml` (Apéndice C #5).
- **Custom DBAL types en DQL**: pasar VO + tipo explícito: `->setParameter('id', $vo, 'purchase_order_item_id')` (Apéndice C #4).
- **CQS**: Commands van por handler; Queries de lectura pueden usar métodos del repositorio existentes (Apéndice C #1/#6). Prohibido SQL directo en controllers.

### Reglas de negocio vigentes que debes respetar

- **Transfer Lifecycle** (Inventory): máquina de estados PENDING → IN_TRANSIT → COMPLETED/CANCELLED/REJECTED; ownership en `Movement::cancelBy/confirmBy/rejectBy`.
- **Ticket lifecycle** (Support): 11 estados, cancelable desde cualquier estado no-terminal; reassign solo desde CREATED o ASSIGNED.
- **Stock**: `trackingType` no cambiable con stock; mochila auto-creada con técnico; NonserializedStock solo `reserve()` si AVAILABLE.
- **Cross-BC**: solo IDs entre BCs, nunca instanciar entidades de otro BC; eventos async via Messenger.
- **Employee.profile** es descriptivo, no autorizativo (la autorización va por Role en Identity BC).

---

## 4. Wiring (obligatorio después de implementar)

**Todo Command, Query, Domain Event y Subscriber nuevo debe tener al menos un caller real** (controller, scheduler, otro handler). Verificar con CodeGraph:

```
codegraph_callers("CreateXCommand")   # → debe aparecer el handler/controller
codegraph_callers("CreateXHandler")   # → debe aparecer un controller o CLI command
```

Si algo queda sin caller: wirearlo o reportar al PLANNER. **Prohibido código huérfano.**

---

## 5. Prohibiciones (violaciones = rechazo en review)

| # | Prohibido | Correcto |
|---|-----------|----------|
| 1 | Framework leakage en Domain (imports de Symfony/Doctrine) | Domain puro, solo PHP |
| 2 | SQL/JPQL directo en controllers | Métodos del repositorio o Query Handlers |
| 3 | Instanciar `DomainException` abstracta | Subclase concreta del BC |
| 4 | `type="string"` en ORM para enums | Custom DBAL type (`AbstractStringEnumType`) |
| 5 | Confiar solo en autowiring para repos | Binding explícito en `services.yaml` |
| 6 | Comandos sin test | Cada Command/Query nuevo con al menos un test |
| 7 | Modificar BCs no relacionados con la tarea | Cambios quirúrgicos al BC objetivo |

---

## 6. Fuentes

- `AGENTS.md` — patrones de código, Apéndice E (10 pasos), A (no negociables), C (violaciones DDD), D (ViewScopeResolver), Transfer Lifecycle, Key Decisions
- `CONTEXT.md` — §8 convenciones de nomenclatura, §9 estructura de directorios, §6 handoff
- `PROJECT_CONTEXT.md` — stack y arquitectura
- `docs/adr/ADR-001..011` — decisiones de arquitectura
- `specs/{num}-{bc}/spec.md` + `tasks.md` — spec aprobada por QA-REVIEWER
- `config/services.yaml` — bindings de repositorios
- Ejemplos reales: `src/app/Inventory/Application/Command/CreateTransfer/`, `src/app/Identity/Presentation/Http/ApiIdentityUserController.php`, `src/app/Shared/Exceptions/`

## 7. Cómo verificar el resultado

```powershell
# 1. Syntax + tests (obligatorio tras cualquier cambio):
php vendor/bin/phpunit --filter {TuTestNuevo}
php vendor/bin/phpunit          # corrida completa si es factible

# 2. Wiring de cada Command/Query nuevo — con CodeGraph:
#    codegraph_callers("XCommand") debe devolver al menos un caller real.
#    Grep de respaldo:
Select-String -Path src/**/*.php -Pattern "new XCommand" -Recurse   # (o rg)

# 3. Validación de integridad del proyecto:
& .\validate_project.ps1

# 4. Console commands nuevos registrados (si aplica):
php bin/console list | Select-String "app:|identity:"

# 5. Rutas nuevas (si aplica):
php bin/console debug:router | Select-String "/api/v1/{bc}"
```

Reportar: archivos creados/modificados, tests corridos y resultado, wiring verificado.

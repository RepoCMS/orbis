# Orbis — Estándares de Codificación (Reglas OBLIGATORIAS)

> **Propósito:** Reglas no negociables de codificación para todo código generado o modificado en Orbis. Este documento es un estándar operativo derivado de `AGENTS.md` (Apéndices A, C y E — verificados contra el código real). Cualquier discrepancia con `AGENTS.md` se resuelve a favor de este archivo solo si fue validada contra el código; de lo contrario, reportar.
>
> **Última verificación:** 2026-08-06 (contra `src/app/`, `CONTEXT.md` §8/§9 y ADRs)

---

## 1. Reglas Fundamentales Transversales

### 1.1 `declare(strict_types=1)`

**Todo archivo PHP** (sin excepción) debe declarar `declare(strict_types=1);` como primera línea después de `<?php`. Verificado en ejemplos reales: `src/app/Identity/Application/Command/CreateRole/CreateRoleCommand.php`, `src/app/Inventory/Application/Query/Response/ProductResponse.php`, `src/app/Shared/Exceptions/DomainException.php`, `src/app/Inventory/Application/Service/StockViewScopeResolver.php`.

**Consecuencia si se viola:** ❌ Rechazo en code review (Apéndice A, regla 1).

### 1.2 Prohibición de framework leakage en Domain

La capa **Domain** no puede importar nada de infraestructura (Doctrine, Symfony, Messenger, etc.):

- Las entidades, VOs, aggregates, enums, excepciones y eventos del Domain son **PHP puro** (Principios Globales de AGENTS.md + ADR-001 "Domain Layer Purity").
- Los repositorios en Domain son **solo interfaces** (`{Resource}RepositoryInterface.php`); las implementaciones Doctrine viven en Infrastructure (ADR-001: Ports and Adapters).
- Los custom DBAL types viven en `Infrastructure/Doctrine/Types/`, nunca en Domain (Apéndice C, regla 4).
- Los VOs de ID (ULID) se persisten con DBAL types propios; nunca se castea a string en DQL sin tipo explícito.

**Consecuencia si se viola:** ❌ Violación arquitectónica (Apéndice A, regla 5).

### 1.3 Regla de wiring: nada huérfano

Todo **Command, Query, Domain Event y Subscriber** debe tener **al menos un caller real** (controller, console command, otro handler, event subscriber o CLI). Verificado por QA-Reviewer con CodeGraph (`codegraph_callers`) antes de aprobar cualquier tarea.

- Prohibido el código huérfano (orphan): una clase sin caller es candidata a eliminación.
- Estado actual: **0 comandos huérfanos conocidos** (CONTEXT.md §1).
- Aplica también a CLI commands y subscribers (QA Checklist, capa Wiring).
- Los Domain Events internos se disparan desde los handlers que mutan el aggregate; los eventos cross-BC salen por Messenger async (ADR-006).

**Consecuencia si se viola:** ❌ Rechazo en code review (Apéndice A, regla 11).

---

## 2. Apéndice A: Reglas No Negociables (copia fiel de AGENTS.md)

| # | Regla | Consecuencia si se viola |
|---|-------|--------------------------|
| 1 | `declare(strict_types=1)` en TODO archivo PHP | ❌ Rechazo en code review |
| 2 | Enum values en lowercase (snake_case multi-word) | ❌ Rechazo en code review |
| 3 | Constructor `private` + factory method en Entities | ❌ Rechazo en code review |
| 4 | Accessors sin prefijo `get` (`id()`, `name()`, `status()`) | ❌ Rechazo en code review |
| 5 | Domain sin imports de infraestructura (framework leakage) | ❌ Violación arquitectónica |
| 6 | Commands/Queries como `final readonly class` | ❌ Rechazo en code review |
| 7 | Controllers delgados (solo delegan a handlers) | ❌ Violación de Hexagonal |
| 8 | Tests al 80% Domain coverage mínimo | ❌ Gate de CI falla |
| 9 | Sufijo `ValueObject` en VOs | ❌ Rechazo en code review |
| 10 | Repositorios solo con interfaces del Domain | ❌ Violación de Hexagonal |
| 11 | Todo Command/Query debe tener al menos un caller real | ❌ Rechazo en code review |

---

## 3. Apéndice C: DDD Violations to Avoid — Learnings verificados (copia fiel de AGENTS.md)

| # | Regla | Correcto | Incorrecto |
|---|-------|----------|------------|
| 1 | **Queries de lectura**: usar métodos del repositorio existentes. Si no existe el método, agregarlo a la interface + implementación | `$poRepo->findOpen()` | `$em->getConnection()->fetchAll('SELECT ...')` en controller |
| 2 | **Validación de cantidad**: debe estar en el Handler (Application), no en el controller | `AddEntryItemHandler::__invoke()` con `PurchaseOrderItemRepositoryInterface` | SQL directo en `EntryController::addItem` |
| 3 | **DomainException es abstracta**: no se puede instanciar. Usar subclase concreta del BC | `throw new InventoryDomainException('...')` | `throw new DomainException('...')` |
| 4 | **Custom DBAL types en DQL**: pasar value object + tipo explícito en `setParameter` | `->setParameter('id', $vo, 'purchase_order_item_id')` | `->setParameter('id', (string) $vo)` |
| 5 | **Services.yaml bindings**: toda interface de repositorio debe tener binding explícito | `App\Inventory\Domain\Repositories\XInterface: ~` | Confiar solo en autowiring |
| 6 | **CQS**: Commands por handler con repositorios; Queries de lectura pueden usar métodos del repositorio directamente | Controller llama a `$poRepo->findOpen()` o pasa por Query Handler | Controller ejecuta SQL/JPQL directo |

**Nota verificada (regla 3):** `src/app/Shared/Exceptions/DomainException.php` es `abstract` (extends `RuntimeException`); la subclase concreta por BC existe, ej. `src/app/Inventory/Domain/Exceptions/InventoryDomainException.php` (`final class InventoryDomainException extends DomainException`). Nunca usar `\DomainException` de PHP nativo.

---

## 4. Apéndice E: Referencia Rápida para Agentes IA — checklist de 10 pasos (copia fiel de AGENTS.md)

```
¿Vas a crear código? Sigue esto:

1. Archivo PHP → declare(strict_types=1) + namespace + imports + class
2. ValueObject → final readonly class, sufijo *ValueObject, validación en constructor
3. Entity → final class, constructor private, factory method con nombre de dominio
4. Command → final readonly class, props public
5. Handler → final readonly class, __invoke(), DI de interfaces
6. Controller → final class extends AbstractController, #[Route], #[IsGranted]
7. Enum → string backed, values en lowercase snake_case
8. Response → final readonly class, fromEntity() static factory
9. Exception → heredar de Shared Kernel, factory methods
10. Test → final class extends TestCase, test{Scenario}()
```

### Ejemplos verificados de cada paso

| Paso | Ejemplo real en el repo |
|------|-------------------------|
| 2. ValueObject | `src/app/Identity/Domain/ValueObjects/UserIdValueObject.php` (ULID 26 chars, ADR-003) |
| 4. Command | `src/app/Identity/Application/Command/CreateRole/CreateRoleCommand.php` (`final readonly class`, props `public`) |
| 5. Handler | `src/app/Identity/Application/Command/CreateRole/CreateRoleHandler.php` (`final readonly class`, `__invoke()`, DI de interfaces + `IdGeneratorInterface`) |
| 8. Response | `src/app/Inventory/Application/Query/Response/ProductResponse.php` (`final readonly class`, `fromEntity()` static factory) |
| 9. Exception | `src/app/Identity/Application/Exception/{PermissionNotFoundException,RoleNameAlreadyExistsException,UserNotFoundException}.php` (con factory methods `withName()`, `withId()`); Domain: `src/app/Inventory/Domain/Exceptions/InventoryDomainException.php` |

---

## 5. Flujo de verificación obligatorio (QA)

1. **QA-Reviewer** aplica checklist DDD al spec antes de implementar (workflow multi-agente de AGENTS.md).
2. **Developer** implementa Domain → Application → Infrastructure → Presentation.
3. **Wiring**: verificar con CodeGraph (`codegraph_callers`) que cada Command/Query/Event nuevo tiene al menos un caller real.
4. **Tests**: `php vendor/bin/phpunit` después de cualquier cambio; mínimo 80% Domain coverage (Apéndice A, regla 8).
5. **Una tarea NO avanza sin aprobación de la etapa actual** (AGENTS.md, workflow multi-agente).

---

## 6. Decisiones ADR relacionadas

| ADR | Decisión | Relevancia para coding standards |
|-----|----------|----------------------------------|
| ADR-001 | DDD + Hexagonal | Pureza de Domain, puertos/adaptadores, BCs |
| ADR-002 | CQRS | Commands/Queries con handlers invocables; cada uno con caller real |
| ADR-003 | ULID como ID | VOs de ID con ULID 26 chars + custom DBAL types |
| ADR-004 | Custom DBAL types | `{FieldName}Type` por primitiva de dominio (ej. `UserTokenTypeType`) |
| ADR-005 | Doctrine ORM + XML mappings | Mappings XML en `Infrastructure/Persistence/Doctrine/Mapping/`, soft delete |
| ADR-006 | Symfony Messenger | Bus de eventos; async para cross-BC |
| ADR-007 | Shared Kernel | Contratos cross-BC solo en `src/app/Shared/`; referencias solo por IDs |
| ADR-008 | Soft Delete | Columna `deleted_at`, método `softDelete()`, filtro en repositorios |

> ⚠️ **Nota:** Actualmente existen ADRs del 001 al 008 en `docs/adr/` (ADR-009 a ADR-011: no encontrados — TODO: requiere validación).

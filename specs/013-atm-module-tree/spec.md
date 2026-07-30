# Feature Specification: ATM Module Tree

**Spec path**: `specs/013-atm-module-tree/spec.md`
**Created**: 2026-06-17
**Status**: Implementado — 100%

---

## 1. Descripción General

Implementar un árbol jerárquico de módulos para cada modelo de ATM. Este árbol permite a los técnicos registrar hallazgos a nivel de módulo componente durante las asistencias.

**Nota**: Completado al 100%. Todo el backend CRUD (ModuleNode entity, repositorio, command/handlers, console seed), admin web (controller cableado, modales Stimulus), y AT-04 (modal de hallazgos con selector de árbol interactivo, componentes, tipo, notas y subida de evidencias) están implementados.

---

## 2. Business Rules

| # | Regla | Estado |
|---|-------|--------|
| AT‑01 | Cada modelo de ATM tiene un árbol de módulos predefinido | ✅ Parcial (AtmModuleTreeData) |
| AT‑02 | Los módulos tienen jerarquía (niveles: sistema → módulo → submódulo → componente) | ✅ Parcial |
| AT‑03 | Los hallazgos se registran a nivel de módulo componente (TicketModuleFinding) | ✅ Implementado en Support |
| AT‑04 | El árbol se usa como lista desplegable en la UI (no como entidad persistida) | ✅ Implementado: modal con selector de árbol interactivo + subida de evidencias |
| AT‑05 | Solo el admin puede modificar la estructura del árbol | ✅ Implementado (permiso `support.manage_modules`) |

---

## 3. Lo Implementado vs Lo Pendiente

| Componente | Estado |
|------------|--------|
| `AtmModel` enum (modelos de ATM) | ✅ |
| `AtmModuleNode` VO | ✅ |
| `AtmModuleTreeData` (definiciones estáticas) | ✅ |
| `AtmModuleTreeRegistry` (domain service, ahora con fallback DB) | ✅ |
| `ModulePathValueObject` | ✅ |
| `TicketModuleFinding` entity | ✅ |
| `RecordModuleFinding` command | ✅ |
| `GetAtmModuleTree` query | ✅ |
| API endpoints para module findings | ✅ |
| UI para árbol desplegable (vista list, show, tree) | ✅ |
| `ModuleNode` entity (persistente) | ✅ |
| `ModuleNodeRepositoryInterface` + Doctrine impl | ✅ |
| `CreateModuleNode` / `UpdateModuleNode` / `DeleteModuleNode` commands | ✅ |
| `AtmModuleTreeController` CRUD wiring (edit, delete, addChild) | ✅ |
| `SeedModuleTreeCommand` (consola) | ✅ |
| Modales Stimulus para confirmación de eliminación | ✅ |
| Permiso `support.manage_modules` | ✅ |
| Admin CRUD para estructura del árbol | ✅ |
| UI dropdown de hallazgos (AT-04) | ✅ |

---

## 4. Archivos Creados/Modificados

### Nuevos
| Archivo | Propósito |
|---------|-----------|
| `src/app/Support/Domain/Entities/ModuleNode.php` | Entidad persistente con árbol auto-referenciado |
| `src/app/Support/Domain/ValueObjects/ModuleNodeIdValueObject.php` | ULID VO |
| `src/app/Support/Domain/Exceptions/ModuleNodeException.php` | Excepción de dominio |
| `src/app/Support/Domain/Exceptions/ModuleNodeNotFoundException.php` | Not found exception |
| `src/app/Support/Domain/Repositories/ModuleNodeRepositoryInterface.php` | Interfaz de repositorio |
| `src/app/Support/Infrastructure/Persistence/Doctrine/DoctrineModuleNodeRepository.php` | Implementación Doctrine |
| `src/app/Support/Infrastructure/Persistence/Doctrine/Mapping/ModuleNode.orm.xml` | Mapping ORM (auto-referenciado) |
| `src/app/Support/Infrastructure/Doctrine/Types/ModuleNodeIdType.php` | DBAL type ULID |
| `src/app/Support/Application/Command/CreateModuleNode/{Command,Handler}.php` | Crear nodo |
| `src/app/Support/Application/Command/UpdateModuleNode/{Command,Handler}.php` | Actualizar nodo |
| `src/app/Support/Application/Command/DeleteModuleNode/{Command,Handler}.php` | Eliminar nodo |
| `src/app/Support/Console/SeedModuleTreeCommand.php` | `support:seed-module-tree` |

### Modificados
| Archivo | Cambio |
|---------|--------|
| `AtmModuleTreeRegistry.php` | Inyecta repositorio, fallback DB → hardcoded |
| `AtmModuleTreeController.php` | CRUD real con permisos `support.manage_modules` |
| `assets/controllers/module_finding_controller.js` | Stimulus controller: fetch tree, selector interactivo, submit vía API |
| `templates/support/ticket/_module_findings_form.html.twig` | Modal con selector de modelo, árbol navegable, componentes, tipo, notas |
| `templates/support/ticket/_module_finding_card.html.twig` | Card de hallazgo individual con tipo, path, evidencia, delete |
| `templates/support/ticket/_finding_evidence_upload.html.twig` | Upload de evidencia por hallazgo vía fetch API |
| `assets/app.js` | Registro del controller Stimulus |
| `SeedPermissionsCommand.php` | Nuevo permiso `support.manage_modules` |
| `doctrine.yaml` | DBAL type `module_node_id` registrado |
| `services.yaml` | Repositorio + console command bindeados |
| `show.html.twig` | Modal Stimulus para delete |
| `_node.html.twig` | Modal Stimulus para delete |
| `form.html.twig` | Campo components textarea agregado |



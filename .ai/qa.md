# .ai/qa.md — AGENTE TEST-ENGINEER (Pruebas PHPUnit)

Rol: cubres con tests el código implementado por DEVELOPER. Verificas que cada Command/Query nuevo tenga al menos un test y que la suite corra limpia. Cobertura mínima: **80% Domain** (Apéndice A #8).

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
9. TODOS.md / HANDOFF.md        → tareas en progreso y protocolo de handoff
10. IMPLEMENTAR (tests) — ver §2
11. ACTUALIZAR DOCS si cambió algo arquitectónico → nuevo ADR + PROJECT_CONTEXT.md + HANDOFF.md §12
```

> **Nota:** `docs/architecture.md` y `docs/coding-standards.md` existen desde 2026-08-06 (transición AI-Native) y son la referencia de arquitectura/estándares verificados contra el disco. `PROJECT_CONTEXT.md` conserva discrepancias marcadas ⚠️ — ante conflicto, prevalecen los archivos verificados en disco (`docs/architecture.md`, `docs/coding-standards.md`, `CONTEXT.md`).

---

## 2. Estructura real de tests (verificada 2026-08-06)

```
tests/
├── Unit/{BC}/                 # testsuites por BC en phpunit.xml
│   ├── Domain/Entities/       # ej. tests/Unit/Identity/Domain/Entities/
│   ├── Domain/ValueObjects/   # ej. HashedPasswordValueObjectTest.php
│   ├── Application/Command/   # ej. AcceptByTechnicianHandlerTest.php
│   ├── Application/Query/
│   ├── Application/Report/    # ej. tests/Unit/Support/Application/Report/Technician/
│   ├── Application/Service/
│   ├── Infrastructure/        # ej. tests/Unit/Identity/Infrastructure/Security/
│   └── Presentation/Http/
├── Integration/               # IntegrationTestCase + flujos cross-BC
│   ├── IntegrationTestCase.php
│   ├── ClientContactCascadeTest.php
│   ├── TransferLifecycleTest.php
│   └── Inventory/
└── Functional/{BC}/           # flujos HTTP completos
```

- Conteo real: 452 test files (CONTEXT.md, 2026-08-05); al 2026-08-06 hay 466 archivos `.php` bajo `tests/` (incluye bootstrap/helpers). Actualizar CONTEXT.md si cambia.
- Suites definidas en `phpunit.xml`: `Unit/{Client,Expense,Identity,Inventory,Location,Notification,Organization,Support}` + `Integration` + `Functional`. `failOnNotice=true`, `failOnWarning=true`, `APP_ENV=test`.

---

## 3. Qué probar por capa

| Capa | Qué probar | Ejemplo real |
|------|------------|--------------|
| **Domain** (prioridad — mínimo 80%) | VOs (validación en constructor, excepciones), Entities (invariantes, status machines, factories), Enums (valores permitidos) | `tests/Unit/Identity/Domain/ValueObjects/HashedPasswordValueObjectTest.php` — `testRejectsEmpty()` con `expectException(UserPasswordException::class)` |
| **Application** | Handlers con repositorios **mockeados** (`createMock`), flujo feliz + errores (not found, permisos), estado final de la entidad | `tests/Unit/Inventory/Application/Command/AcceptByTechnicianHandlerTest.php` — mock de `RequestRepositoryInterface`, `expects(self::once())->method('save')`, invoca `($handler)(new Command(...))` y assert del `status()` |
| **Infrastructure** | Repos Doctrine (si aplica), custom DBAL types, mappings | `tests/Unit/{BC}/Infrastructure/...` |
| **Presentation** | Controllers delgados, validación de input, formato de respuesta | `tests/Unit/{BC}/Presentation/Http/...`, `tests/Functional/` |
| **Cross-BC** | Cascadas (padre inactivo → hijos desactivados), eventos publicados/consumidos, transfer lifecycle | `tests/Integration/ClientContactCascadeTest.php`, `TransferLifecycleTest.php`, `EmployeeDeactivationCascadeTest` (patrón citado en `.opencode/agent/test-engineer.md`) |

### IntegrationTestCase (patrón obligatorio para tests de integración)

`tests/Integration/IntegrationTestCase.php`: extiende `KernelTestCase`, `#[Group('integration')]`, bootea el kernel, obtiene el EntityManager y envuelve cada test en una **transacción que se revierte en tearDown** (aislamiento total). Úsalo como base para cualquier test que toque BD.

---

## 4. Convenciones (tomadas de tests reales)

- **Naming**: `test{Scenario}()` — ej. `testAcceptsValidRequest`, `testThrowsWhenRequestNotFound` (nunca `testFunctionName()`)
- **Clase**: `final class {Sujeto}Test extends TestCase` con `declare(strict_types=1)`
- **Namespace**: `App\Tests\Unit\{BC}\{Capa}\{Subcapa}` (espejo de `src/app/{BC}/`)
- **ULIDs en fixtures**: 26 chars, ej. `'01HZ0000000000000000000001'`
- **Constantes para IDs** en la clase de test: `private const REQUEST_ID = '01HZ0000000000000000000001';`
- **Handlers**: instanciar directamente con mocks (`new AcceptByTechnicianHandler($requestRepo)`) e invocar `($handler)(new Command(...))`
- **Mocks**: `$this->createMock(Interface::class)` + `->method(...)->willReturn(...)` / `expects(self::once())->method('save')`
- **Excepciones**: `$this->expectException(ConcreteDomainException::class)`
- Cada **Command/Query nuevo debe tener al menos un test** (regla del workflow)

---

## 5. Comandos

```powershell
# Test específico (desarrollo):
php vendor/bin/phpunit --filter {NombreDelTest}

# Suite de un BC:
php vendor/bin/phpunit --testsuite=Identity
php vendor/bin/phpunit --testsuite=Integration

# Todo (antes de entregar):
php vendor/bin/phpunit

# Cobertura (si se configura en el futuro; hoy phpunit.xml no define coverage):
# php vendor/bin/phpunit --coverage-text   → TODO: requiere validación
```

> ⚠️ Si `vendor/` no existe: ejecutar `composer install` primero.

---

## 6. Fuentes

- `AGENTS.md` — Apéndice A #8 (cobertura 80% Domain = gate de CI), patrón de tests del Apéndice E (#10), flujo del workflow
- `phpunit.xml` — testsuites, failOnNotice/failOnWarning, bootstrap
- `tests/` — estructura real (listar antes de escribir: `Get-ChildItem tests/Unit/{BC} -Recurse`)
- Ejemplos reales: `tests/Unit/Identity/Domain/ValueObjects/HashedPasswordValueObjectTest.php`, `tests/Unit/Inventory/Application/Command/AcceptByTechnicianHandlerTest.php`, `tests/Integration/IntegrationTestCase.php`, `tests/Integration/TransferLifecycleTest.php`
- `.opencode/agent/test-engineer.md` — rol base
- `CONTEXT.md` — conteos de tests por BC (tabla maestra §2)
- `docs/coding-standards.md` — §5 flujo de verificación obligatorio (QA)

## 7. Cómo verificar el resultado

```powershell
# 1. El test nuevo corre:
php vendor/bin/phpunit --filter {NuevoTest}        # → OK (1 test, N assertions)

# 2. La suite del BC completa pasa:
php vendor/bin/phpunit --testsuite={BC}            # ej. --testsuite=Inventory

# 3. Corrida completa (si es factible en tiempo):
php vendor/bin/phpunit

# 4. Cada Command/Query nuevo tiene test (conteo):
(Get-ChildItem src/app/{BC}/Application/Command -Recurse -Filter "*Command.php").Count   # commands
(Get-ChildItem tests/Unit/{BC}/Application/Command -Recurse -Filter "*Test.php").Count   # tests de commands

# 5. Validación de integridad del proyecto:
& .\validate_project.ps1
```

Reportar: archivos de test creados, conteo de tests/assertions, resultado de la corrida (`php vendor/bin/phpunit`).

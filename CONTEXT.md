# Orbis — Contexto Maestro (fuente única de estado)

> **Propósito:** única fuente de verdad del estado actual. Reglas en [`AGENTS.md`](./AGENTS.md); protocolo de trabajo en [`docs/ai-protocol.md`](./docs/ai-protocol.md).
>
> **Actualizado:** 2026-10-22

---

## 0. Estado de sesión

| Campo | Valor |
|---|---|
| Rama | `main` (origin: `https://github.com/RepoCMS/orbis.git`) |
| Último commit | `db7e854` — limpieza + purga de secretos (push OK) |
| Working tree | Limpio |
| Tarea activa | — (consolidación cerrada) |
| Próximo paso | Configurar BD de test y correr `composer test` |
| Blockers | BD de test sin configurar; `compose.yaml` PostgreSQL vs MySQL; rotar credenciales expuestas; #42 |
| Última actualización | 2026-10-22 |

**Al iniciar sesión:** leer solo esta sección + [`tasks/IN_PROGRESS.md`](./tasks/IN_PROGRESS.md). Usar CodeGraph antes de abrir archivos.

---

## 1. Resumen ejecutivo

| Métrica | Valor |
|---|---|
| BCs activos | 13 + Shared Kernel |
| Archivos indexados (CodeGraph) | 2.382 |
| Test files | 452 |
| Specs | 15 (incl. `014-governance`) |
| Reportes | 14 |
| Importers | 11 |
| Comandos huérfanos | 0 (verificado por CI `wiring`) |
| Baseline de tests | 2.082 tests / 5.601 assertions (2026-08-20) |
| PHPStan | Nivel 5 + baseline; 0 errores nuevos |
| Gates CI | test, stan, cs, wiring, validate + security |
| Contrato de calidad | [`specs/014-governance/spec.md`](./specs/014-governance/spec.md) |

---

## 2. Estado por BC

| BC | Status | Tests | Commands | Queries | Controllers | Reportes | Importers | Dashboard |
|---|---:|---:|---:|---:|---:|---:|---:|---|
| **Identity** | ~85% | 77 | 32 | 13 | 15 | 1 | 1 | Web + API |
| **Client** | ~95% | 84 | 37 | 16 | 18 | 2 | 4 | Web + API |
| **Organization** | ~95% | 60 | 33 | 8 | 8 | 3 | 1 | Web + API |
| **Support** | ~85% | 93 | 35 | 20 | 12 | 2 | 0 | Web + API |
| **Inventory** | ~95% | 96 | 60 | 29 | 28 | 4 | 3 | API + Web |
| **Expense** | 100% | 30 | 15 | 8 | 5 | 2 | 1 | Web + API |
| **Location** | ~95% | 7 | — | — | 1 | — | — | API-only |
| **Notification** | ~75% | 81 | 8 | 5 | 8 | — | — | Web + API |
| **Cross-BC** | ~90% | — | — | — | — | — | — | — |
| **010a Employee-User** | ~95% | 3 | — | — | — | — | — | — |
| **010b SparePart Scope** | ~95% | 1 | — | — | — | — | — | — |
| **011 Notifications Stock** | 100% | — | — | — | — | — | — | — |
| **012 Multi-Item Request** | 100% | 75 | — | — | — | — | — | — |
| **013 ATM Module Tree** | 100% | 18 | — | — | — | — | — | Web |

> Detalle de specs, gaps históricos e inventario de reportes/importers: [`docs/history.md`](./docs/history.md). Estado por BC y specs: `specs/{id}/spec.md`.

---

## 3. Referencias canónicas

| Necesitas... | Documento |
|---|---|
| Reglas de código | [`AGENTS.md`](./AGENTS.md) |
| Protocolo de trabajo | [`docs/ai-protocol.md`](./docs/ai-protocol.md) |
| Arquitectura | [`docs/architecture.md`](./docs/architecture.md) |
| Estándares de código | [`docs/coding-standards.md`](./docs/coding-standards.md) |
| Decisiones | [`docs/adr/README.md`](./docs/adr/README.md) |
| Tareas | [`tasks/TODO.md`](./tasks/TODO.md), [`tasks/IN_PROGRESS.md`](./tasks/IN_PROGRESS.md), [`tasks/DONE.md`](./tasks/DONE.md) |
| Spec de la tarea | `specs/{id}/spec.md` |
| Historial y deuda | [`docs/history.md`](./docs/history.md) |
| CodeGraph | [`docs/codegraph.md`](./docs/codegraph.md) |

---

## 4. Blockers activos

| # | Issue | Impacto | Estado |
|---|---|---|---|
| 1 | `.env.test` sin `DATABASE_URL` | `composer test` usaría la BD de desarrollo | ⚠️ Crear `orbis_test` y configurar |
| 2 | `compose.yaml` define PostgreSQL 16 | Confunde el setup; la app usa MySQL 8 | ⚠️ Corregir o eliminar |
| 3 | `#42` bcrypt vs argon2 | Password hashing | ❓ Pendiente |
| 4 | Credenciales en historial antiguo | `APP_SECRET`, DB y OpenRouter key | ✅ Purgado del historial; ⚠️ rotar credenciales |

---

## 5. Próximos pasos

1. Rotar credenciales expuestas (`APP_SECRET`, DB, OpenRouter).
2. Configurar BD de test y correr `composer test`.
3. Corregir `compose.yaml` (MySQL 8) o eliminarlo.
4. Implementar gaps de Notification `G-01..G-05` (ver `docs/history.md`).
5. Completar Identity Dashboard KPIs.

---

## 6. Reglas de lectura

- No leer documentos completos si basta una sección; usar la tabla de referencias.
- CodeGraph primero para símbolos/estructura; `rg` para texto literal.
- Leer solo el `specs/{id}/spec.md` de la tarea activa.
- Actualizar §0 al cerrar cada sesión.

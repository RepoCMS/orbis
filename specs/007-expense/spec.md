# Feature Specification: Expense Management

**Spec path**: `specs/007-expense/spec.md`
**Created**: 2026-06-17
**Updated**: 2026-07-30
**Status**: Implementado (100%)

---

## 1. Descripción General

El Bounded Context **Expense** gestiona los gastos operativos y adelantos de los técnicos de campo. Implementación completa con persistencia Doctrine, 14 commands, 8 queries, 156 tests (289 assertions).

---

## 2. Business Rules

| # | Regla |
|---|-------|
| EX‑01 | **Rechazo por item**: En gastos operativos, los items individuales pueden ser rechazados sin afectar el resto. |
| EX‑02 | **Evidence obligatoria**: Para ciertos tipos de gasto, la evidencia (foto) es obligatoria. |
| EX‑03 | **Items aprobados se mantienen**: Al reenviar un gasto, los items previamente aprobados se conservan. |
| EX‑04 | **Adelantos vencidos**: Los adelantos >3 días sin liquidar se marcan como vencidos. |

---

## 3. Entities

| Entidad | Descripción |
|---------|-------------|
| `OperatingExpense` | Gasto operativo con items |
| `ExpenseItem` | Item individual de gasto |
| `CashAdvance` | Adelanto de efectivo a técnico |

Mappings ORM: `OperatingExpense.orm.xml`, `ExpenseItem.orm.xml`, `CashAdvance.orm.xml`

---

## 4. Commands (14)

| Comando | Descripción |
|---------|-------------|
| `CreateOperatingExpense` | Crea gasto operativo |
| `UpdateOperatingExpense` | Actualiza gasto |
| `AddExpenseItem` | Agrega item a gasto |
| `SubmitExpense` | Envía gasto para aprobación |
| `ApproveOperatingExpense` | Aprueba gasto completo |
| `ApproveExpenseItem` | Aprueba item individual |
| `RejectExpenseItem` | Rechaza item individual |
| `ResubmitExpense` | Reenvía gasto con correcciones |
| `DeleteOperatingExpense` | Elimina gasto |
| `UploadEvidence` | Sube evidencia para item |
| `CreateCashAdvance` | Crea adelanto |
| `UpdateCashAdvance` | Actualiza adelanto |
| `SettleCashAdvance` | Liquida adelanto |
| `CheckOverdueCashAdvances` | Marca adelantos vencidos |

---

## 5. Queries (8)

| Query | Descripción |
|-------|-------------|
| `ListOperatingExpenses` | Lista gastos operativos |
| `GetOperatingExpenseDetail` | Detalle de gasto con items |
| `PendingExpenseItems` | Items pendientes de aprobación |
| `GetExpenseDashboardSummary` | KPIs del dashboard |
| `ListCashAdvances` | Lista adelantos |
| `FindCashAdvanceById` | Adelanto por ID |
| `GetCashAdvanceDetail` | Detalle de adelanto |
| `OverdueCashAdvances` | Adelantos vencidos |

---

## 6. Reportes

| Reporte | Formato | Estado |
|---------|---------|--------|
| Gastos por Período | XLSX/PDF | ✅ |
| Adelantos Pendientes | XLSX/PDF | ✅ |

---

## 7. Tests

156 tests, 289 assertions. 30 test files cubriendo:
- 14 command handlers
- 8 query handlers
- Entities y VOs del Domain
- Cross-BC verification
- Controllers web/API

---

## 8. Gaps Cerrados

| # | Gap | Resolución |
|---|-----|------------|
| G‑01 | ORM mappings faltantes | ✅ Creados: 3 archivos .orm.xml |
| G‑02 | Enum values en UPPERCASE | ✅ Corregidos a lowercase |
| G‑03 | Tests insuficientes | ✅ 156 tests (289 assertions) |

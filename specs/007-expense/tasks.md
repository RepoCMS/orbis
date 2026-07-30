# Expense BC — Task Checklist

**Spec:** `specs/007-expense/spec.md`
**Created:** 2026-06-18
**State:** implementation complete
**Previous state:** ~10% (ORM mappings + framework structure only)
**Current state:** 100% — 156 tests (289 assertions), 14 commands, 8 queries, 9 domain events con subscribers

---

## G‑01: Enum values lowercase migration

| # | Task | Status |
|---|------|--------|
| 1 | Migrate `ExpenseStatus` values to lowercase | ✅ |
| 2 | Migrate `ExpenseItemStatus` values to lowercase | ✅ |
| 3 | Migrate `ExpenseItemType` values to lowercase | ✅ |
| 4 | Migrate `CashAdvanceStatus` values to lowercase | ✅ |
| 5 | Migrate `SettlementDirection` values to lowercase | ✅ |
| 6 | Verify no hardcoded UPPERCASE enum values in codebase | ✅ |

## G‑02: Exception hierarchy

| # | Task | Status |
|---|------|--------|
| 7 | Fix `ExpenseNotFoundException` → extends `ApplicationException` | ✅ |
| 8 | Fix `ExpenseItemNotFoundException` → extends `ApplicationException` | ✅ |
| 9 | Fix `InvalidFileFormatException` → extends `ApplicationException` | ✅ |
| 10 | Fix `FileTooLargeException` → extends `ApplicationException` | ✅ |

## G‑03: Domain entity tests

| # | Entity | Tests | Status |
|---|--------|-------|--------|
| 11 | `OperatingExpense` | create, submit, approve, reject, resubmit, addItem, rejectItem, approveItem, updateDetails, softDelete, totalExpenses, status transitions, domain events | ✅ |
| 12 | `ExpenseItem` | create, approve, reject, uploadEvidence, evidenceRequired validation | ✅ |
| 13 | `CashAdvance` | create, settle, markPendingReview, updateAmount, isOverdue, softDelete, status transitions | ✅ |

## G‑04: Command handler tests

| # | Handler | Scenarios | Status |
|---|---------|-----------|--------|
| 14 | `CreateOperatingExpenseHandler` | success, employee not found, employee inactive, ticket not found | ✅ |
| 15 | `AddExpenseItemHandler` | success, expense not found, expense not editable, invalid type | ✅ |
| 16 | `SubmitExpenseHandler` | success, expense not found, wrong status | ✅ |
| 17 | `ApproveOperatingExpenseHandler` | success, expense not found, wrong status, approver not found | ✅ |
| 18 | `ApproveExpenseItemHandler` | success, item not found, wrong status, item already approved | ✅ |
| 19 | `RejectExpenseItemHandler` | success, item not found, missing reason | ✅ |
| 20 | `ResubmitExpenseHandler` | success, expense not rejected, no changes detected | ✅ |
| 21 | `UpdateOperatingExpenseHandler` | success, expense not found, not editable | ✅ |
| 22 | `DeleteOperatingExpenseHandler` | success, expense not found | ✅ |
| 23 | `UploadEvidenceHandler` | success, item not found, evidence not required, missing file | ✅ |
| 24 | `CreateCashAdvanceHandler` | success, employee not found, user not found | ✅ |
| 25 | `UpdateCashAdvanceHandler` | success, advance not found, already settled | ✅ |
| 26 | `SettleCashAdvanceHandler` | success, advance not found, wrong status | ✅ |
| 27 | `CheckOverdueCashAdvancesHandler` | success, no overdue, mixed overdue/non-overdue | ✅ |

## G‑05: Query handler tests

| # | Handler | Scenarios | Status |
|---|---------|-----------|--------|
| 28 | `ListOperatingExpensesHandler` | success with/without filters | ✅ |
| 29 | `GetOperatingExpenseDetailHandler` | success, not found | ✅ |
| 30 | `PendingExpenseItemsHandler` | success, no pending | ✅ |
| 31 | `GetExpenseDashboardSummaryHandler` | success with KPIs | ✅ |
| 32 | `ListCashAdvancesHandler` | success with/without filters | ✅ |
| 33 | `FindCashAdvanceByIdHandler` | success, not found | ✅ |
| 34 | `GetCashAdvanceDetailHandler` | success, not found | ✅ |
| 35 | `OverdueCashAdvancesHandler` | success, none overdue | ✅ |

## G‑06: Domain Events verification

| # | Event | Has publisher? | Has subscriber? | Status |
|---|-------|---------------|-----------------|--------|
| 36 | `ExpenseSubmitted` | ✅ SubmitExpenseHandler | ✅ CrossBcDomainEventHandler | ✅ |
| 37 | `ExpenseResubmitted` | ✅ ResubmitExpenseHandler | ✅ CrossBcDomainEventHandler | ✅ |
| 38 | `ExpenseApproved` | ✅ ApproveOperatingExpenseHandler | ✅ CrossBcDomainEventHandler | ✅ |
| 39 | `ExpenseItemRejected` | ✅ RejectExpenseItemHandler | ✅ CrossBcDomainEventHandler | ✅ |
| 40 | `CashAdvanceCreated` | ✅ CreateCashAdvanceHandler | ✅ CrossBcDomainEventHandler | ✅ |
| 41 | `CashAdvanceUpdated` | ✅ UpdateCashAdvanceHandler | ✅ CrossBcDomainEventHandler | ✅ |
| 42 | `CashAdvanceSettled` | ✅ SettleCashAdvanceHandler | ✅ CrossBcDomainEventHandler | ✅ |
| 43 | `CashAdvancePendingReview` | ✅ CheckOverdueCashAdvancesHandler | ✅ CrossBcDomainEventHandler | ✅ |
| 44 | `CashAdvancePaymentPending` | ✅ CheckOverdueCashAdvancesHandler | ✅ CrossBcDomainEventHandler | ✅ |

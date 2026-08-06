# Module: Expense

## Overview

The **Expense** Bounded Context manages cash advances, operating expenses, and expense tracking within the Orbis platform. It handles approval workflows, evidence submission, and integrates with other BCs for personnel and category data.

## Core Responsibilities

- Cash advance lifecycle (request, approve, settle)
- Operating expense management
- Expense item workflow (submit, approve, reject, resubmit)
- Evidence upload for expenses
- Approval workflows with multiple approvers
- Overdue expense/due date management

## Status

- **Progress:** 100%
- **Tests:** ~30 files
- **Reports:** Expense, Advance
- **Importers:** Import Operating Expenses
- **Dashboard:** Web + API

## Domain Model

### Aggregate: CashAdvance

| Field | Type | Description |
|-------|------|-------------|
| `id` | `CashAdvanceIdValueObject` (ULID) | Unique identifier |
| `employeeId` | `EmployeeIdValueObject` | Requesting employee |
| `amount` | `MoneyValueObject` | Advance amount |
| `status` | `CashAdvanceStatus` | `pending` / `approved` / `settled` / `rejected` |
| `requestDate` | `DateTimeImmutable` | Request timestamp |
| `approvalDate` | `?DateTimeImmutable` | Approval timestamp |
| `settlementDate` | `?DateTimeImmutable` | Settlement timestamp |

### Aggregate: OperatingExpense

| Field | Type | Description |
|-------|------|-------------|
| `id` | `OperatingExpenseIdValueObject` (ULID) | Unique identifier |
| `employeeId` | `EmployeeIdValueObject` | Submitting employee |
| `expenseCategoryId` | `ExpenseCategoryId` | Category |
| `amount` | `MoneyValueObject` | Amount |
| `status` | `OperatingExpenseStatus` | `pending` / `approved` / `rejected` / `paid` |
| `description` | `string` | Description |
| `date` | `DateTimeImmutable` | Expense date |

### Aggregate: ExpenseItem

Tracks individual expense line items with approval status, evidence, and approver notes.

## Commands

| Command | Purpose |
|---------|---------|
| `CreateCashAdvance` | Create new cash advance |
| `UpdateCashAdvance` | Update advance details |
| `ApproveCashAdvance` | Approve advance |
| `SettleCashAdvance` | Settle (close) advance |
| `CreateOperatingExpense` | Create new expense |
| `UpdateOperatingExpense` | Update expense |
| `ApproveOperatingExpense` | Approve expense |
| `RejectOperatingExpense` | Reject expense |
| `SubmitExpense` | Submit expense for approval |
| `ApproveExpenseItem` | Approve expense item |
| `RejectExpenseItem` | Reject expense item |
| `ResubmitExpense` | Resubmit rejected expense |
| `UploadEvidence` | Upload expense evidence |
| `AddExpenseItem` | Add line item |
| `RemoveEvidence` | Remove uploaded evidence |
| `CheckOverdueCashAdvances` | Cron job - flag overdue advances |
| `ImportOperatingExpenses` | Import via CSV/Excel |

## Queries

| Query | Purpose |
|-------|---------|
| `GetCashAdvanceDetail` | Get cash advance details |
| `ListCashAdvances` | List advances with filters |
| `OverdueCashAdvances` | List overdue advances |
| `ListOperatingExpenses` | List expenses with filters |
| `GetOperatingExpenseDetail` | Get expense details |
| `PendingExpenseItems` | List items pending approval |
| `GetExpenseDashboardSummary` | Dashboard KPIs |

## Cross-BC Integrations

| BC | Direction | Interaction |
|----|-----------|-------------|
| Identity | Read | Resolves User for approval workflows |
| Organization | Read | Resolves Employee data |
| Shared Kernel | Both | Dashboard metrics, importer infrastructure |

## Reports

1. **Expense** — Operating expense report with filters
2. **Advance** — Cash advance settlement report

## Pending Gaps

| Gap | Priority |
|-----|----------|
| Complete feature parity | Low |

## API Endpoints

| Method | Route | Permission |
|--------|-------|------------|
| GET | `/api/v1/expense/advances` | `cash_advances.view` |
| POST | `/api/v1/expense/advances` | `cash_advances.create` |
| GET | `/api/v1/expense/advances/{id}` | `cash_advances.view` |
| PUT | `/api/v1/expense/advances/{id}/settle` | `cash_advances.update` |
| GET | `/api/v1/expense/operating-expenses` | `expenses.view` |
| POST | `/api/v1/expense/operating-expenses` | `expenses.create` |
| GET | `/api/v1/expense/dashboard` | `expense.view_dashboard` |

*(Full API endpoint list in spec)*
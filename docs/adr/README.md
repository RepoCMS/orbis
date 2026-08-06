# Architecture Decision Records

This directory contains Architecture Decision Records (ADRs) for the Orbis project.

## ADR Index

| ID | Title | Status | Date |
|----|-------|--------|------|
| ADR-001 | Domain-Driven Design with Hexagonal Architecture | Accepted | 2026-08-06 |
| ADR-002 | CQRS Pattern for Commands and Queries | Accepted | 2026-08-06 |
| ADR-003 | ULID as Primary Identifier | Accepted | 2026-08-06 |
| ADR-004 | Custom DBAL Types for Domain Primitives | Accepted | 2026-08-06 |
| ADR-005 | Doctrine ORM for Persistence | Accepted | 2026-08-06 |
| ADR-006 | Symfony Messenger for Event Bus | Accepted | 2026-08-06 |
| ADR-007 | Shared Kernel for Cross-BC Integration | Accepted | 2026-08-06 |
| ADR-008 | Soft Delete Pattern | Accepted | 2026-08-06 |
| ADR-009 | TransactionManagerInterface for Application Layer Transactions | Accepted | 2026-08-06 |
| ADR-010 | Cross-BC Event-Driven Cascade | Accepted | 2026-08-06 |
| ADR-011 | Table Prefixes per Bounded Context | Accepted | 2026-08-06 |

---

## Template

```markdown
# ADR-XXX: [Title]

**Status:** [Proposed | Accepted | Rejected | Superseded]
**Date:** YYYY-MM-DD
**Deciders:** [Names]

## Context

[What is the issue that we're seeing that is motivating this decision or change?]

## Decision

[What is the change that we're proposing or have decided to do?]

## Consequences

### Positive
- [Benefit 1]
- [Benefit 2]

### Negative
- [Drawback 1]
- [Drawback 2]

### Neutral
- [Consideration 1]
```

---

## Convention

- Los ADRs se numeran secuencialmente `ADR-XXX.md`. Toda decisión nueva requiere su ADR.
- Los ADRs documentan decisiones **arquitectónicas**; las decisiones de proceso/producto/equipo (no arquitectónicas) viven en [`docs/decisions/`](../decisions/).

---

*Each significant architectural decision must be recorded here.*
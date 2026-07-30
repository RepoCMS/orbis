---
name: project-constitution
description: Formal governance document capturing architectural principles, patterns, and quality gates based on ADR implementations
metadata:
  type: project
---

## Preamble

This constitution formalizes the architectural principles and governance standards that guide the Automated Cash Management Solutions application. It is derived from documented architectural decisions (ADR-011, ADR-012, ADR-013) and represents the agreed-upon foundation for sustainable growth.

## Core Principles

### I. Domain-Driven Design Purity
All code must strictly adhere to DDD boundaries:
- **Application Layer**: Contains only application logic, never database queries or controller code
- **Domain Layer**: Pure business concepts with no infrastructure dependencies
- **Infrastructure Layer**: Concrete implementations of interfaces from Application/Doman layers

### II. Transaction Integrity
- All data persistence requiring atomicity must use `TransactionManagerInterface` 
- Handlers must never directly depend on `EntityManagerInterface`
- Transaction management follows strict controller boundary rules (beginTransaction → commit/rollback)

### III. Cross-BC Communication Protocol
- **Event-Driven Architecture**: BCs communicate exclusively through domain events
- **Async Processing**: Cross-BC cascades must use async messaging (Symfony Messenger)
- **Idempotency**: All event consumers must be idempotent to prevent duplicate processing

### IV. Database Governance
- All tables must use standardized `{bc}_` prefix naming convention
- DBAL queries must avoid raw SQL; use ORM methods where possible
- Changes to table names require migration plans with versioned RENAME TABLE statements

### V. Testing Contracts
- **Test-First Mandate**: Unit tests must be written before implementation
- **Mocking Standards**: Interface implementations mocked directly (`createMock(Interface::class)`)
- **Integration Test Coverage**: All repository implementations require integration test coverage

## Architectural Patterns

### 1. Transaction Management Pattern
All handlers requiring atomic operations must inject `TransactionManagerInterface` with methods `begin()`, `commit()`, `rollback()`. This ensures:
- Application layer transaction boundaries
- Testability without Doctrine dependency
- Future ORM agnosticism

### 2. Cross-BC Event Cascade
When BC A operations affect BC B:
1. Publish domain event after successful domain state change
2. Async handler in target BC consumes event
3. Event processing is fire-and-forget with error logging only
4. ProcessedCommand tracking prevents duplicate events

### 3. Database Naming Convention
- Prefix all tables with `{bounded_context}_` where `{bounded_context}` is the short identifier
- Inventory BC tables use `inventory_` prefix universally
- Migration requires:
  - ORM mapping updates (`*.orm.xml`)
  - DBAL query updates
  - Version-controlled migration scripts

## Governance Structure

### Decision Process
1. **Proposal Stage**: Architects submit RFC for new patterns
2. **Review Stage**: ADR authors and domain experts review
3. **Acceptance Stage**: RFC accepted if ≥ 60% approval from core architects
4. **Documentation Stage**: Implement RFC as ADR in `/docs/adr/`

### Architectural Review Board (ARB)
- **Members**: Lead Domain Architect, Infrastructure Lead, Head of Testing
- **Responsibilities**:
  - Approve all new ADRs
  - Review architectural drift
  - Resolve cross-BC conflicts
  - Audit compliance with this constitution

### Quality Gates
Before merging to main:
- **Code Review**: All core infrastructure changes require ≥ 2 approvals
- **Test Coverage**: ≥ 80% integration coverage for infrastructure code
- **Architecture Compliance**: Run architecture validation against this constitution
- **Performance Check**: Ensure no blocking calls in async paths

## Pattern Conformance Matrix

| Pattern | Location | Standard Implementation | Conformance Check |
|---------|----------|-------------------------|-------------------|
| Transaction Manager | `src/app/Shared/Application/Service/` | `DoctrineTransactionManager` | Must use interface, not direct EntityManager |
| Cross-BC Events | `src/app/Shared/Infrastructure/Messaging/` | `CrossBcDomainEventHandler` | Must use async Messenger, idempotent consumers |
| Database Prefixes | `src/config/doctrine.yaml` | `{bc}_` pattern | Must follow ADR-013 standards |

## Evolution Framework

### Amendment Process
1. Propose change via RFC
2. Minimum 72h review period
3. 2/3 approval from ARB members
4. Update relevant ADRs and impact assessment

### Deprecation Policy
- Features marked deprecated require 6-month notice before removal
- Deprecation only after alternative implemented and tested
- Removal only after 2 release cycles post-deprecation

## Version History
- **v1.0**: Initial constitution based on ADR-011 to ADR-013
- **v1.1**: Added deprecation policy and amendment process
# Orbis Constitution

## Core Principles
1. **Spec-First Development**
   - All features start with a spec created via `/speckit.specify` before any implementation
2. **DDD Architecture**
   - Strict bounded contexts separated by domain events
   - Aggregates enforce consistency boundaries
3. **Test-First (NON-NEGOTIABLE)**
   - TDD cycle: tests written → user approval → tests fail → implement
4. **Integration Testing**
   - Focus on cross-BC interactions, API contracts, database migrations
5. **Observability**
   - Structured logging, domain event tracing, AuthLog for security
6. **Simplicity**
   - YAGNI principle, avoid premature abstractions
7. **Technology Stack**
   - Symfony 7, PHP 8.4, Doctrine ORM, Twig, JWT

## Governance Rules
- All PRs must verify spec compliance through checklist validation
- Constitution overrides all other project practices
- Amendments require documentation in .github/AMENDMENTS.md and 2/3 approval
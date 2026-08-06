# PROJECT_CONTEXT.md — Orbis AI-Native Repository

> **Last Updated:** 2026-08-06  
> **Version:** 1.0.0  
> **Status:** AI-NATIVE REPOSITORY TRANSITION IN PROGRESS

---

## 1. Project Purpose

**Orbis** is a Cash Management Solutions platform that provides comprehensive tools for:
- User authentication and authorization management
- Inventory tracking and management
- Support ticket handling
- Organizational personnel management
- Expense tracking and cash advance management
- Location and geographic data
- Notification system

The system follows Domain-Driven Design (DDD) principles with Hexagonal architecture and CQRS patterns.

---

## 2. Architecture

### 2.1 Architectural Style

| Aspect | Technology/Approach |
|--------|---------------------|
| **Domain Model** | Domain-Driven Design (DDD) |
| **Architecture** | Hexagonal (Ports and Adapters) |
| **Pattern** | CQRS (Command Query Responsibility Segregation) |
| **Entities** | Aggregate Roots with Value Objects |
| **Events** | Domain Events for cross-BC communication |
| **Persistence** | Doctrine ORM with custom DBAL types |
| **Identity** | ULID identifiers (Universally Unique Lexicographically Sortable ID) |
| **Soft Delete** | Soft delete pattern for recoverable entities |

### 2.2 Layer Structure

Each Bounded Context follows a 4+1 layer pattern:

```
src/app/{BC}/
├── Domain/           # Entities, Value Objects, Enums, Repositories interfaces
├── Application/        # Commands, Queries, Handlers, DTOs
├── Infrastructure/   # Doctrine Repositories, Persistence, Services
├── Presentation/     # HTTP Controllers, Console Commands
└── Tests/            # Unit, Integration, Functional tests
```

### 2.3 Cross-BC Communication

Cross-Bounded Context interactions use:
- **Read Model:** Shared Kernel for value objects and interfaces
- **Events:** Domain events published and consumed
- **Services:** Application services coordinating operations

---

## 3. Technologies

### 3.1 Backend Stack

| Component | Technology |
|-----------|------------|
| **Language** | PHP 8.4 |
| **Framework** | Symfony 8.0 |
| **ORM** | Doctrine ORM 2.x |
| **Database** | MySQL 8+ |
| **Tests** | PHPUnit 11 |
| **Security** | Symfony Security, JWT, TOTP (MFA) |
| **Queue** | Messenger with Doctrine transport |

### 3.2 Frontend Stack

| Component | Technology |
|-----------|------------|
| **Templates** | Twig |
| **Assets** | Stimulus, Alpine.js |
| **Styling** | Tailwind CSS |

---

## 4. Development Principles

### 4.1 Core Principles

1. **Single Source of Truth:** Git repository is the sole source of truth
2. **Spec-Driven Development:** Implementation begins with specification
3. **TDD Discipline:** Tests must accompany every Command and Query handler
4. **No Framework Leakage:** Domain layer must be framework-agnostic
5. **Bounded Context Isolation:** Each BC is independently deployable

### 4.2 Naming Conventions

- **Classes:** `PascalCase` with specific patterns for Commands, Queries, Handlers
- **Files:** Match class names exactly
- **Methods:** `camelCase` for operations, `static function fromX()` for factories
- **Constants:** `UPPER_SNAKE_CASE` for enums and static constants

---

## 5. Bounded Contexts

| BC | Status | Tests | Dashboards | Reports | Importers |
|----|--------|-------|------------|---------|-----------|
| **Identity** | ~85% | 77 | WIP | 1 | 1 |
| **Client** | ~70% | 80 | Partial | 2 | 3 |
| **Organization** | ~67% | 60 | Web | 3 | 1 |
| **Support** | ~85% | 89 | Expand | 2 | 0 |
| **Inventory** | ~95% | 96 | API-only | 4 | 3 |
| **Expense** | 100% | 30 | Web | 2 | 1 |
| **Location** | 100% | 7 | API-only | 0 | 0 |
| **Notification** | ~60% | 8 | Web | 0 | 0 |
| **Shared Kernel** | Complete | - | - | - | - |

---

## 6. Development Workflow

```
Spec → Implementation → Test → Wire → Document → Commit → Push
```

### 6.1 Before Starting Work

1. Read `AGENTS.md` for guidance
2. Read relevant specification in `specs/`
3. Check current status in `TASKS/`
4. Verify test framework is running

### 6.2 After Making Changes

1. Run tests: `php vendor/bin/phpunit`
2. Update documentation if architecture changed
3. Commit with descriptive message
4. Push to appropriate branch

---

## 7. Known Limitations

| Issue | Impact | Resolution |
|-------|--------|------------|
| RBAC permissions seeded manually | Access control setup | Use `php bin/console app:permissions:seed` |
| No rate limiting on login | Potential brute force | Future enhancement |
| Password expiration notification | User experience | Feature pending |
| Refresh token cleanup manual | Security risk | Add cron job |

---

## 8. Getting Started

### 8.1 Setup

```bash
# Install dependencies
composer install
npm install

# Database setup
php bin/console doctrine:migrations:migrate

# Seed initial data
php bin/console app:permissions:seed
php bin/console app:roles:seed
php bin/console app:seed:admin
```

### 8.2 Running Tests

```bash
# Unit tests
php vendor/bin/phpunit --testsuite=Unit

# All tests
php vendor/bin/phpunit
```

### 8.3 Development Server

```bash
symfony server:start
```

---

*This document serves as the primary context for any AI or developer joining the project.*
# Module: Identity (Bound)

## Overview

The **Identity** Bounded Context manages authentication, authorization, and user lifecycle within the Orbis platform. It is the entry point for all user interactions and secures access to all other bounded contexts.

## Core Responsibilities

- User registration and lifecycle management
- Authentication (JWT API + Web form)
- Authorization via RBAC (Roles + Permissions)
- Multifactor authentication (MFA/TOTP)
- Token management (JWT, refresh tokens, activation tokens)
- Account lockout based on failed login attempts
- Security event auditing
- Cross-BC user linking (Organization/Employee, Client/Contact)

## Domain Model

### Aggregate: User

| Field | Type | Description |
|-------|------|-------------|
| `id` | `UserIdValueObject` (ULID) | Unique identifier |
| `username` | `UsernameValueObject` | 5-15 chars, `[a-z0-9._-]+` |
| `password` | `HashedPasswordValueObject` | Bcrypt hash, cost=12 |
| `roleId` | `RoleIdValueObject` | Assigned role |
| `status` | `UserStatus` | `unverified` / `active` / `inactive` |
| `failedLoginAttempts` | `int` | Failed login counter |
| `lockedUntil` | `?DateTimeImmutable` | Temporary lockout timestamp |
| `securityVersion` | `int` | Security version (invalidates sessions) |
| `deletedAt` | `?DateTimeImmutable` | Soft delete timestamp |

**Lifecycle States:**
```
UNVERIFIED → ACTIVE → INACTIVE → (soft delete)
```

### Aggregate: Role

| Field | Type | Description |
|-------|------|-------------|
| `id` | `RoleIdValueObject` (ULID) | Unique identifier |
| `name` | `RoleNameValueObject` | 2-50 chars |
| `status` | `RoleStatus` | `active` / `inactive` |
| `permissionIds` | `PermissionIdValueObject[]` | Associated permissions |

### Aggregate: Permission

| Field | Type | Description |
|-------|------|-------------|
| `id` | `PermissionIdValueObject` (ULID) | Unique identifier |
| `key` | `PermissionKeyValueObject` | `resource.action` format |
| `description` | `string` | Human-readable description |

### Other Entities

- **UserToken** — Temporary tokens (activation, password reset, unlock)
- **RefreshToken** — Opaque tokens with SHA-256 hashing, 30-day expiry
- **UserMfaConfig** — TOTP configuration per user
- **AuthLog** — Append-only security event journal

## Business Rules

| Rule | Description |
|------|-------------|
| ID-01 | Username must be unique and case-insensitive |
| ID-02 | Password: min 8 chars, uppercase, lowercase, digit, special |
| ID-03 | Passwords always hashed with bcrypt (cost=12) |
| ID-04 | Only ACTIVE users can authenticate |
| ID-05 | 5 failed logins → locked for 15 minutes |
| ID-06 | Only ACTIVE roles can be assigned |
| ID-07 | Permission format: `resource.action` |
| ID-08 | MFA is optional but recommended |
| ID-09 | Refresh token rotation on each use |
| ID-10 | A user links to either Employee OR Contact, not both |
| ID-11 | Activation tokens expire in 48h, reset in 1h |

## Commands

| Command | Purpose |
|---------|---------|
| `RegisterUser` | Register new user |
| `VerifyUser` | Admin-force activate user |
| `VerifyUserByToken` | Activate via token |
| `GenerateActivationToken` | Create activation token |
| `ChangePassword` | Change user password |
| `ChangeUsername` | Change username |
| `AssignRoleToUser` | Assign role to user |
| `DeactivateUser` | Deactivate user |
| `ActivateUser` | Activate user |
| `ReactivateUser` | Reactivate inactive user |
| `SoftDeleteUser` | Soft delete user |
| `UnlockUser` | Unlock locked user |
| `RecordFailedLogin` | Record failed login attempt |
| `CreateRole` | Create role |
| `RenameRole` | Rename role |
| `ActivateRole` | Activate role |
| `DeactivateRole` | Deactivate role |
| `DeleteRole` | Delete role (if no users) |
| `SyncRolePermissions` | Sync role permissions |
| `AssignPermissionToRole` | Assign permission to role |
| `DefinePermission` | Define new permission |
| `ChangePermissionDescription` | Update permission description |
| `RequestPasswordReset` | Request password reset |
| `ResetPassword` | Reset password via token |
| `StoreRefreshToken` | Store refresh token |
| `RevokeRefreshToken` | Revoke refresh token |
| `DisableUserMfa` | Disable MFA (admin) |
| `RevokeMfa` | Revoke MFA + backup codes |
| `InviteContactToPortal` | Invite contact as user |
| `GenerateBackupCodes` | Generate MFA backup codes |

## Queries

| Query | Purpose |
|-------|---------|
| `ListUsers` | Paginated user list |
| `FindUserById` | Find user by ULID |
| `FindUserByUsername` | Find user by username |
| `ListRoles` | List active roles |
| `FindRoleById` | Find role by ULID |
| `FindRoleByName` | Find role by name |
| `FindRoleBySlug` | Find role by slug |
| `ListPermissions` | List all permissions |
| `FindPermissionById` | Find permission by ULID |
| `FindPermissionByKey` | Find permission by key |
| `FindPermissionsByIds` | Find permissions by IDs |
| `ListAuthLog` | Paginated auth logs |
| `IdentityDashboardMetrics` | Security KPIs for dashboard |

## Cross-BC Integrations

| BC | Direction | Interaction |
|----|-----------|-------------|
| Organization | Read | `CrossBcUserEmailResolver`, `OrphanUserChecker` |
| Organization | Write | `ProvisionEmployeeAccessHandler` calls `RegisterUser` |
| Client | Read | `CrossBcContactFinder`, `CrossBcUserByEmailResolver` |
| Client | Write | `InviteContactToPortal` creates User for Contact |
| Shared Kernel | Both | `DashboardMetricsHandler`, `Import` infrastructure |

## Infrastructure Components

- **Doctrine Repositories** — All persistence implementations
- **ORM Mappings** — XML mapping files for all entities
- **Custom DBAL Types** — 14+ domain-specific types
- **Security** — `SymfonySecurityUser`, `PermissionVoter`, `MfaSubscriber`
- **Services** — `NativePasswordHasher`, `SymfonyMailerAccountNotifier`
- **Console Commands** — Seed permissions, roles, admin

## Tests

- **70 test files** (Unit tests)
- **30 Command Handler tests**
- **13 Query Handler tests**
- **15 ValueObject tests**
- **7 Entity tests**

## Pending Gaps

| Gap | Priority |
|-----|----------|
| MFA recovery codes UI (regeneración) | Medium |
| Refresh token cleanup automation (cron) | Low |
| Password expiration notification | Low |
| Rate limiting on login | Medium |
| AuthLog flow test | Pending |
| MFA flow test | Pending |
| Forgot-password flow test | Pending |
| User registration flow test | Pending |
| Cross-BC binding test | Pending |

## Database Tables

| Table | Purpose |
|-------|---------|
| `identity_users` | User records |
| `identity_roles` | Role definitions |
| `identity_permissions` | Permission catalog |
| `identity_role_permissions` | Role-permission assignments |
| `identity_user_roles` | User-role assignments |
| `identity_user_tokens` | Temporary tokens |
| `identity_refresh_tokens` | JWT refresh tokens |
| `identity_user_mfa_configs` | MFA configuration |
| `identity_user_mfa_backup_codes` | Backup codes |
| `identity_auth_logs` | Security audit log |

## API Endpoints

### REST API (`/api/v1/`)

| Method | Route | Permission |
|--------|-------|------------|
| POST | `/auth/jwt/login` | Public |
| POST | `/auth/jwt/refresh` | Public |
| POST | `/auth/login` | Public |
| POST | `/auth/logout` | Authenticated |
| POST | `/auth/mfa/setup` | Authenticated |
| POST | `/auth/mfa/confirm` | Authenticated |
| GET | `/identity/users` | `users.view` |
| POST | `/identity/users` | `users.create` |
| GET | `/identity/users/{id}` | `users.view` |
| PATCH | `/identity/users/{id}` | `users.update` |
| POST | `/identity/users/{id}/change-password` | `users.update` |
| POST | `/identity/users/{id}/activate` | `users.update` |
| POST | `/identity/users/{id}/deactivate` | `users.update` |
| POST | `/identity/users/{id}/unlock` | `users.update` |
| POST | `/identity/users/invite-contact` | `users.create` |
| POST | `/identity/users/{id}/mfa/disable` | `users.update` |
| POST | `/identity/users/{id}/revoke-mfa` | `users.update` |
| DELETE | `/identity/users/{id}` | `users.delete` |
| GET | `/identity/roles` | `roles.view` |
| POST | `/identity/roles` | `roles.create` |
| GET | `/identity/roles/{id}` | `roles.view` |
| PATCH | `/identity/roles/{id}` | `roles.update` |
| POST | `/identity/roles/{id}/permissions` | `roles.update` |
| DELETE | `/identity/roles/{id}` | `roles.delete` |
| GET | `/identity/permissions` | `roles.view` |
| GET | `/identity/auth-log` | `auth_logs.view` |
| POST | `/identity/mfa/setup` | Authenticated |
| POST | `/identity/mfa/confirm` | Authenticated |

### Web Routes

| Method | Route | Permission |
|--------|-------|------------|
| GET/POST | `/login` | Public |
| GET | `/forgot-password` | Public |
| GET | `/forgot-password/reset-password` | Public |
| GET | `/verify/{token}` | Public |
| GET/POST | `/2fa/setup` | Authenticated |
| GET/POST | `/2fa/check` | Authenticated |
| GET/POST | `/2fa/recovery` | Authenticated |
| GET | `/users` | `users.view` |
| GET | `/users/{id}` | `users.view` |
| POST | `/users/{id}/edit` | `users.update` |
| POST | `/users/{id}/verify` | `users.update` |
| POST | `/users/{id}/deactivate` | `users.update` |
| POST | `/users/{id}/reactivate` | `users.update` |
| POST | `/users/{id}/delete` | `users.delete` |
| GET | `/roles` | `roles.view` |
| GET | `/roles/new` | `roles.create` |
| POST | `/roles` | `roles.create` |
| GET | `/roles/{id}` | `roles.view` |
| POST | `/roles/{id}/permissions` | `roles.update` |
| POST | `/roles/{id}/deactivate` | `roles.update` |
| POST | `/roles/{id}/activate` | `roles.update` |
| GET | `/security/auth-logs` | `auth_logs.view` |
| GET | `/security/auth-logs/{id}` | `auth_logs.view` |
| GET | `/identity/dashboard` | `identity.view_dashboard` |

## Console Commands

| Command | Purpose |
|---------|---------|
| `app:seed:admin` | Create initial admin |
| `app:permissions:seed` | Seed system permissions |
| `app:roles:seed` | Seed predefined roles |
| `identity:detect-orphan-users` | Identify unbound users |
| `identity:cleanup-refresh-tokens` | Remove expired tokens |
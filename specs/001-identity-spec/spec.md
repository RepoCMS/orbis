# Feature Specification: Identity & Access Management

**Feature Branch**: `001-identity-spec`

**Created**: 2026-06-17

**Status**: In Development (~85% implemented)

---

## 1. Descripción General

El Bounded Context **Identity** gestiona todo lo relacionado con autenticación, autorización, y gestión de usuarios del sistema. Es el punto de entrada para cualquier interacción con Orbis.

Responsabilidades:
- Registro y ciclo de vida de usuarios
- Autenticación (JWT API + Web form)
- Autorización RBAC (Roles + Permisos)
- Autenticación Multifactor (MFA/TOTP)
- Gestión de tokens (JWT, refresh tokens, tokens de verificación)
- Bloqueo de cuentas por intentos fallidos
- Auditoría de eventos de seguridad
- Vinculación con empleados (Organization BC) y contactos (Client BC)
- Integración con autenticación Symfony Security

---

## 2. Business Rules

| # | Regla | Descripción |
|---|-------|-------------|
| ID‑01 | **Username único** | No pueden existir dos usuarios con el mismo username. Case-insensitive. |
| ID‑02 | **Password policy** | Mínimo 8 caracteres, al menos 1 mayúscula, 1 minúscula, 1 dígito, 1 carácter especial. |
| ID‑03 | **Hash seguro** | Passwords siempre hasheadas con bcrypt (cost=12). Nunca almacenadas en texto plano. |
| ID‑04 | **Ciclo de vida de usuario** | UNVERIFIED → ACTIVE → INACTIVE (+ soft-delete opcional). Solo ACTIVE puede autenticarse. |
| ID‑05 | **Lock por intentos fallidos** | 5 intentos fallidos → locked por 15 minutos. Se desbloquea automáticamente o por admin. |
| ID‑06 | **Estado del rol** | Solo roles ACTIVE pueden asignarse a usuarios. Desactivar un rol afecta a todos sus usuarios. |
| ID‑07 | **Permisos granularizados** | Formato `recurso.accion` (ej. `users.create`, `roles.delete`). Votado via `PermissionVoter`. |
| ID‑08 | **MFA opcional** | TOTP (RFC 6238) con códigos de respaldo. Configurable por usuario. |
| ID‑09 | **Refresh token rotation** | Cada refresh invalida el token anterior y emite uno nuevo. Expiración 30 días. |
| ID‑10 | **Vinculación cross-BC** | Un usuario puede estar vinculado a un Employee (Org BC) o un Contact (Client BC), pero no a ambos. |
| ID‑11 | **UserToken expiración** | Activation tokens expiran en 48h, password reset en 1h, unlock en 1h. |
| ID‑12 | **Auditoría** | Todos los eventos de autenticación se registran en AuthLog (append-only). |
| ID‑13 | **Forgot-password no enumerativo** | No revelar si el email existe. Capturar `EmailNotFoundException | DomainException` (no `\Throwable`). |
| ID‑14 | **Security version** | `security_version` incrementa en deactivate, changePassword, assignRole. Invalida sesiones activas vía `EquatableInterface`. |

---

## 3. Aggregates

### 3.1 User (Aggregate Root)

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | `UserIdValueObject` (ULID) | Identificador único |
| `username` | `UsernameValueObject` | 5-15 chars, `[a-z0-9._-]+` |
| `password` | `HashedPasswordValueObject` | Hash bcrypt |
| `roleId` | `RoleIdValueObject` | Rol asignado |
| `status` | `UserStatus` | UNVERIFIED / ACTIVE / INACTIVE |
| `failedLoginAttempts` | `int` | Contador de intentos fallidos |
| `lockedUntil` | `?DateTimeImmutable` | Bloqueo temporal |
| `securityVersion` | `int` | Versión de seguridad |
| `deletedAt` | `?DateTimeImmutable` | Soft delete |

**Factory methods:**
- `User::register(id, username, password, roleId): self`

**State transitions:**
- `activate()` → UNVERIFIED → ACTIVE
- `deactivate()` → ACTIVE → INACTIVE
- `reactivate()` → INACTIVE → ACTIVE
- `softDelete()` → setea `deletedAt`
- `recordFailedLogin()` → incrementa contador, lockea si = 5
- `unlock()` → resetea contador y `lockedUntil`

**Reglas:**
- Solo `ACTIVE` puede autenticarse
- `isLocked()` chequear `lockedUntil > now()`
- `changePassword()` requiere estado ACTIVE

### 3.2 Role (Aggregate Root)

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | `RoleIdValueObject` (ULID) | Identificador único |
| `name` | `RoleNameValueObject` | 2-50 chars |
| `status` | `RoleStatus` | ACTIVE / INACTIVE |
| `permissionIds` | `PermissionIdValueObject[]` | Permisos asignados |

**Factory methods:**
- `Role::create(id, name, permissionIds): self`

**State transitions:**
- `activate()`, `deactivate()`
- `assignPermission()`, `revokePermission()`

### 3.3 Permission (Entity — catálogo)

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | `PermissionIdValueObject` (ULID) | Identificador único |
| `key` | `PermissionKeyValueObject` | `recurso.accion` |
| `description` | `string` | Descripción legible |

### 3.4 UserToken (Entity — temporales)

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | `UserTokenIdValueObject` (ULID) | Identificador único |
| `userId` | `UserIdValueObject` | Usuario asociado |
| `type` | `UserTokenType` | ACTIVATION / PASSWORD_RESET / ACCOUNT_UNLOCK |
| `tokenHash` | `TokenHashValueObject` | SHA-256 del token plano |
| `expiresAt` | `DateTimeImmutable` | Fecha de expiración |

### 3.5 RefreshToken (Entity)

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | `RefreshTokenIdValueObject` (ULID) | Identificador único |
| `userId` | `UserIdValueObject` | Usuario asociado |
| `tokenHash` | `string` | SHA-256 hash |
| `expiresAt` | `DateTimeImmutable` | 30 días |
| `revokedAt` | `?DateTimeImmutable` | Revocación |

### 3.6 UserMfaConfig (Entity)

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | `UserMfaConfigIdValueObject` (ULID) | Identificador único |
| `userId` | `UserIdValueObject` | Usuario asociado |
| `secret` | `string` | Secreto TOTP |
| `isEnabled` | `bool` | Habilitado/deshabilitado |
| `confirmedAt` | `?DateTimeImmutable` | Confirmación |

### 3.7 AuthLog (Entity — append-only)

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | `AuthLogIdValueObject` (ULID) | Identificador único |
| `userId` | `UserIdValueObject` | Usuario asociado |
| `event` | `AuthEvent` | Tipo de evento |
| `ipAddress` | `string` | Dirección IP |
| `userAgent` | `string` | User agent |
| `occurredAt` | `DateTimeImmutable` | Timestamp |

---

## 4. Commands

| ID | Command | Handler | Disparado desde |
|----|---------|---------|-----------------|
| C‑01 | `RegisterUser` | Crea usuario, hashea password, asigna rol, publica `UserRegistered` | API `ApiIdentityUserController::POST` |
| C‑02 | `VerifyUserByToken` | Consume token de activación, activa usuario | Web `VerificationController::verify` |
| C‑03 | `GenerateActivationToken` | Genera token + envía email activación | API controller (admin) |
| C‑04 | `ChangePassword` | Valida estado, hashea, actualiza, incrementa security_version | API `PATCH /users/{id}` |
| C‑05 | `AssignRoleToUser` | Valida rol existe, asigna, incrementa security_version | API `POST /roles/{id}/assign` |
| C‑06 | `DeactivateUser` | Desactiva, incrementa security_version | API + Web controllers |
| C‑07 | `ActivateUser` | Activa UNVERIFIED → ACTIVE | Admin action |
| C‑08 | `ReactivateUser` | Reactiva INACTIVE → ACTIVE | Admin action |
| C‑09 | `SoftDeleteUser` | Soft delete (set deletedAt) | API + Web controllers |
| C‑10 | `UnlockUser` | Resetea lockedUntil + failed attempts | API `POST /users/{id}/unlock` |
| C‑11 | `RecordFailedLogin` | Incrementa contador, lockea si aplica | `JwtApiController` + `LoginFailureSubscriber` |
| C‑12 | `CreateRole` | Crea rol con permisos | API + Web controllers |
| C‑13 | `RenameRole` | Renombra rol | API + Web controllers |
| C‑14 | `ActivateRole` | Activa rol | API + Web controllers |
| C‑15 | `DeactivateRole` | Desactiva rol | API + Web controllers |
| C‑16 | `DeleteRole` | Elimina rol (solo si no tiene usuarios) | API + Web controllers |
| C‑17 | `SyncRolePermissions` | Sincroniza todos los permisos de un rol | API `POST /roles/{id}/permissions` |
| C‑18 | `DefinePermission` | Define nuevo permiso (único) | Console `SeedPermissionsCommand` |
| C‑19 | `RequestPasswordReset` | Resuelve email → genera token → envía email | API `POST /auth/forgot-password` |
| C‑20 | `ResetPassword` | Consume token → cambia password → publica evento | API `POST /auth/reset-password` |
| C‑21 | `StoreRefreshToken` | Persiste refresh token | `JwtApiController::login/refresh` |
| C‑22 | `RevokeRefreshToken` | Revoca refresh token | On password change / logout |
| C‑23 | `DisableUserMfa` | Deshabilita MFA (admin) | API `POST /users/{id}/mfa/disable` |
| C‑24 | `RevokeMfa` | Revoca MFA + backup codes | API `POST /users/{id}/revoke-mfa` |
| C‑25 | `InviteContactToPortal` | Crea usuario para contacto, envía activación | API `POST /users/invite-contact` |

---

## 5. Queries

| ID | Query | Handler | Response |
|----|-------|---------|----------|
| Q‑01 | `ListUsers` | Paginación vía repositorio | `ListUsersResult{items[], total, page, limit}` |
| Q‑02 | `FindUserById` | Busca por ULID | `UserResponse` |
| Q‑03 | `FindUserByUsername` | Busca por username | `UserResponse` |
| Q‑04 | `ListRoles` | Lista todos los roles activos | `RoleResponse[]` |
| Q‑05 | `FindRoleById` | Busca por ULID | `RoleResponse` |
| Q‑06 | `FindRoleByName` | Busca por nombre | `RoleResponse` |
| Q‑07 | `ListPermissions` | Lista todos los permisos | `PermissionResponse[]` |
| Q‑08 | `FindPermissionById` | Busca por ULID | `PermissionResponse` |
| Q‑09 | `FindPermissionByKey` | Busca por key | `PermissionResponse` |
| Q‑10 | `ListAuthLog` | Paginación con filtros (userId, fechas) | `ListAuthLogResult{items[], total, page, limit}` |
| Q‑11 | `IdentityDashboardMetrics` | KPIs de seguridad (DBAL) | `IdentityDashboardMetrics` DTO |

---

## 6. Domain Events

| Evento | Disparado por | Suscriptores |
|--------|---------------|-------------|
| `UserRegistered` | C‑01 RegisterUser | — |
| `UserAccountActivated` | C‑02 VerifyUserByToken / C‑07 ActivateUser | — |
| `UserLoggedIn` | Login exitoso | — |
| `UserUnlocked` | C‑10 UnlockUser | — |
| `PasswordChanged` | C‑04 ChangePassword | — |
| `PasswordResetRequested` | C‑19 RequestPasswordReset | — |
| `PasswordResetCompleted` | C‑20 ResetPassword | — |
| `RoleCreated` | C‑12 CreateRole | — |
| `RoleDeleted` | C‑16 DeleteRole | — |
| `RolePermissionsUpdated` | C‑17 SyncRolePermissions | — |
| `MfaSetupCompleted` | Confirmación MFA exitosa | — |
| `MfaDisabledByAdmin` | C‑23 DisableUserMfa | — |
| `EmployeeRegistered` | ProvisionEmployeeAccess (Org BC) | — |
| `ContactLinked` | InviteContactToPortal | — |
| `AdminSeeded` | SeedAdminCommand | — |

---

## 7. Cross-BC Interactions

| BC | Tipo | Interacción |
|----|------|-------------|
| **Organization** | Lectura | `CrossBcUserEmailResolver` busca `Employee.userId` para resolver email |
| **Organization** | Lectura | `OrphanUserChecker` consulta EmployeeRepository para detectar huérfanos |
| **Organization** | Escritura | `ProvisionEmployeeAccessHandler` (Org BC) llama a `RegisterUser` |
| **Client** | Lectura | `CrossBcContactFinder` busca `Contact.userId` en Client BC |
| **Client** | Escritura | `InviteContactToPortal` crea User para Contact de Client BC |

---

## 8. Wiring

| Command/Query | Caller real |
|---------------|-------------|
| `RegisterUser` | `ApiIdentityUserController::create()` |
| `VerifyUserByToken` | `VerificationController::verify()` |
| `ChangePassword` | `ApiIdentityUserController::changePassword()` |
| `DeactivateUser` | `UserController`, `ApiIdentityUserController` |
| `UnlockUser` | `ApiIdentityUserController::unlock()` |
| `RecordFailedLogin` | `JwtApiController::login()`, `LoginFailureSubscriber` |
| `RequestPasswordReset` | `ApiIdentityAuthController::forgotPassword()` |
| `ResetPassword` | `ApiIdentityAuthController::resetPassword()` |
| `CreateRole` | `RoleController`, `ApiIdentityRoleController` |
| `SyncRolePermissions` | `RoleController::permissions()`, `ApiIdentityRoleController` |
| `DefinePermission` | `SeedPermissionsCommand` |
| `ListUsers` | `UserController::list()`, `ApiIdentityUserController::list()` |
| `ListRoles` | `RoleController`, `ApiIdentityRoleController` |
| `ListAuthLog` | `AuthLogController`, `ApiIdentityAuthLogController` |
| `IdentityDashboardMetrics` | `IdentityDashboardController` |
| `StoreRefreshToken` | `JwtApiController::login()`, `JwtApiController::refresh()` |
| `InviteContactToPortal` | `ApiIdentityUserController::inviteContact()` |

---

## 9. API Endpoints

### API REST (`/api/v1/`)

| Método | Ruta | Controller | Permiso |
|--------|------|------------|---------|
| `POST` | `/auth/jwt/login` | `JwtApiController` | Público |
| `POST` | `/auth/jwt/refresh` | `JwtApiController` | Público |
| `POST` | `/auth/login` | `ApiIdentityAuthController` | Público |
| `POST` | `/auth/logout` | `ApiIdentityAuthController` | `IS_AUTHENTICATED_FULLY` |
| `POST` | `/auth/mfa/setup` | `ApiIdentityAuthController` | `IS_AUTHENTICATED_FULLY` |
| `POST` | `/auth/mfa/confirm` | `ApiIdentityAuthController` | `IS_AUTHENTICATED_FULLY` |
| `GET` | `/identity/users` | `ApiIdentityUserController` | `users.view` |
| `POST` | `/identity/users` | `ApiIdentityUserController` | `users.create` |
| `GET` | `/identity/users/{id}` | `ApiIdentityUserController` | `users.view` |
| `PATCH` | `/identity/users/{id}` | `ApiIdentityUserController` | `users.update` |
| `POST` | `/identity/users/{id}/change-password` | `ApiIdentityUserController` | `users.update` |
| `POST` | `/identity/users/{id}/activate` | `ApiIdentityUserController` | `users.update` |
| `POST` | `/identity/users/{id}/deactivate` | `ApiIdentityUserController` | `users.update` |
| `POST` | `/identity/users/{id}/unlock` | `ApiIdentityUserController` | `users.update` |
| `POST` | `/identity/users/invite-contact` | `ApiIdentityUserController` | `users.create` |
| `POST` | `/identity/users/{id}/mfa/disable` | `ApiIdentityUserController` | `users.update` |
| `POST` | `/identity/users/{id}/revoke-mfa` | `ApiIdentityUserController` | `users.update` |
| `DELETE` | `/identity/users/{id}` | `ApiIdentityUserController` | `users.delete` |
| `GET` | `/identity/roles` | `ApiIdentityRoleController` | `roles.view` |
| `POST` | `/identity/roles` | `ApiIdentityRoleController` | `roles.create` |
| `GET` | `/identity/roles/{id}` | `ApiIdentityRoleController` | `roles.view` |
| `PATCH` | `/identity/roles/{id}` | `ApiIdentityRoleController` | `roles.update` |
| `POST` | `/identity/roles/{id}/permissions` | `ApiIdentityRoleController` | `roles.update` |
| `DELETE` | `/identity/roles/{id}` | `ApiIdentityRoleController` | `roles.delete` |
| `GET` | `/identity/permissions` | `ApiIdentityRoleController` | `roles.view` |
| `GET` | `/identity/auth-log` | `ApiIdentityAuthLogController` | `auth_logs.view` |
| `POST` | `/identity/mfa/setup` | `ApiIdentityMfaController` | `IS_AUTHENTICATED_FULLY` |
| `POST` | `/identity/mfa/confirm` | `ApiIdentityMfaController` | `IS_AUTHENTICATED_FULLY` |

### Web Routes

| Método | Ruta | Controller | Permiso |
|--------|------|------------|---------|
| `GET/POST` | `/login` | `LoginController` | `PUBLIC_ACCESS` |
| `GET` | `/forgot-password` | `ForgotPasswordController` | `PUBLIC_ACCESS` |
| `GET` | `/forgot-password/reset-password` | `ForgotPasswordController` | `PUBLIC_ACCESS` |
| `GET` | `/verify/{token}` | `VerificationController` | `PUBLIC_ACCESS` |
| `GET/POST` | `/2fa/setup` | `MfaController` | `IS_AUTHENTICATED_FULLY` |
| `GET/POST` | `/2fa/check` | `MfaController` | `IS_AUTHENTICATED_FULLY` |
| `GET/POST` | `/2fa/recovery` | `MfaController` | `IS_AUTHENTICATED_FULLY` |
| `GET` | `/users` | `UserController` | `users.view` |
| `GET` | `/users/{id}` | `UserController` | `users.view` |
| `POST` | `/users/{id}/edit` | `UserController` | `users.update` |
| `POST` | `/users/{id}/verify` | `UserController` | `users.update` |
| `POST` | `/users/{id}/deactivate` | `UserController` | `users.update` |
| `POST` | `/users/{id}/reactivate` | `UserController` | `users.update` |
| `POST` | `/users/{id}/delete` | `UserController` | `users.delete` |
| `GET` | `/roles` | `RoleController` | `roles.view` |
| `GET` | `/roles/new` | `RoleController` | `roles.create` |
| `POST` | `/roles` | `RoleController` | `roles.create` |
| `GET` | `/roles/{id}` | `RoleController` | `roles.view` |
| `POST` | `/roles/{id}/permissions` | `RoleController` | `roles.update` |
| `POST` | `/roles/{id}/deactivate` | `RoleController` | `roles.update` |
| `POST` | `/roles/{id}/activate` | `RoleController` | `roles.update` |
| `GET` | `/security/auth-logs` | `AuthLogController` | `auth_logs.view` |
| `GET` | `/security/auth-logs/{id}` | `AuthLogController` | `auth_logs.view` |
| `GET` | `/identity/dashboard` | `IdentityDashboardController` | `identity.view` |

---

## 10. Console Commands

| Comando | Nombre | Propósito |
|---------|--------|-----------|
| `app:seed:admin` | SeedAdmin | Crea admin inicial |
| `app:permissions:seed` | SeedPermissions | Sembrar permisos del sistema |
| `app:roles:seed` | SeedRoles | Sembrar roles predefinidos |
| `identity:detect-orphan-users` | DetectOrphanUsers | Identificar usuarios sin vinculación |
| `identity:cleanup-refresh-tokens` | CleanupExpiredRefreshTokens | Limpiar tokens expirados |
| `app:inventory:assign-permissions` | AssignInventoryPermissions | Asignar permisos de inventario a un rol |

---

## 11. Known Gaps / Próximos Pasos

| # | Gap | Prioridad |
|---|-----|-----------|
| G‑01 | **MFA recovery codes**: Backup codes implementados pero sin UI de regeneración | Media |
| G‑02 | **Refresh token cleanup automático por cron**: Solo existe comando manual | Baja |
| G‑03 | **Forgot-password email real**: Creado `EmailForgotPasswordEmailSender` con Symfony Mailer. `.env.dev` con SMTP. ✅ | Cerrado |
| G‑04 | **Notificación de expiración de contraseña**: No implementado | Baja |
| G‑05 | **Rate limiting en login**: No implementado | Media |
| G‑06 | **PHP 8.4 enum types**: `UserToken.orm.xml` y `AuthLog.orm.xml` corregidos a custom DBAL types ✅ | Cerrado |
| G‑07 | **Test coverage**: Expandido a 70 test files (30 command + 13 query + 15 VO + 7 entity tests) ✅ | Cerrado |
| G‑08 | **WebAuthn / Passkeys**: No implementado | Baja (futuro) |
| G‑09 | **OAuth2 / SSO**: No implementado | Baja (futuro) |

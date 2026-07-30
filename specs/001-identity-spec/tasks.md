# Tasks — Identity & Access Management

**Spec**: `specs/001-identity-spec/spec.md`
**Código**: `src/app/Identity/`
**Tests**: 70 test files (Unit)
**Estimado**: ~85% implementado

---

## Leyenda

- ✅ Completado
- ⏳ En progreso
- ❌ Pendiente

---

## TASK-01: Domain Layer

### 1.1 Entities
- [x] **User** — Aggregate root con ciclo de vida completo
- [x] **Role** — Aggregate root con permisos
- [x] **Permission** — Catálogo de permisos del sistema
- [x] **UserToken** — Tokens temporales (activación, reset, unlock)
- [x] **RefreshToken** — Tokens opacos con hash SHA-256
- [x] **UserMfaConfig** — Configuración TOTP
- [x] **UserMfaBackupCode** — Códigos de respaldo MFA
- [x] **AuthLog** — Auditoría append-only

### 1.2 Value Objects
- [x] `UserIdValueObject` (ULID)
- [x] `UsernameValueObject` (5-15 chars, regex `[a-z0-9._-]+`)
- [x] `HashedPasswordValueObject`
- [x] `RawPasswordValueObject` (8+ chars, uppercase, lowercase, digit, special)
- [x] `RoleIdValueObject` (ULID)
- [x] `RoleNameValueObject` (2-50 chars)
- [x] `RoleSlugValueObject`
- [x] `PermissionIdValueObject` (ULID)
- [x] `PermissionKeyValueObject` (formato `recurso.accion`)
- [x] `TokenHashValueObject` (hex 64 chars, hash_equals)
- [x] `PlainTokenValueObject` (hex 64 chars, random_bytes)
- [x] `UserTokenIdValueObject` (ULID)
- [x] `UserMfaConfigIdValueObject` (ULID)
- [x] `UserMfaBackupCodeIdValueObject` (ULID)
- [x] `RefreshTokenIdValueObject` (ULID)
- [x] `AuthLogIdValueObject` (ULID)

### 1.3 Enums
- [x] `UserStatus: string` — `unverified`, `active`, `inactive`
- [x] `RoleStatus: string` — `active`, `inactive`
- [x] `UserTokenType: string` — `ACTIVATION`, `PASSWORD_RESET`, `ACCOUNT_UNLOCK`
- [x] `AuthEvent: string` — 13 tipos de eventos de auditoría

### 1.4 Domain Events
- [x] `UserRegistered`
- [x] `UserAccountActivated`
- [x] `UserLoggedIn`
- [x] `UserUnlocked`
- [x] `PasswordChanged`
- [x] `PasswordResetRequested`
- [x] `PasswordResetCompleted`
- [x] `RoleCreated`
- [x] `RoleDeleted`
- [x] `RolePermissionsUpdated`
- [x] `MfaSetupCompleted`
- [x] `MfaDisabledByAdmin`
- [x] `EmployeeRegistered`
- [x] `ContactLinked`
- [x] `AdminSeeded`

### 1.5 Repositories (interfaces)
- [x] `UserRepositoryInterface` — findById, findByUsername, findAll, save, delete
- [x] `RoleRepositoryInterface` — findById, findByName, findAll, save, delete
- [x] `PermissionRepositoryInterface` — findById, findByKey, findAll, findByIds, save, delete
- [x] `UserTokenRepositoryInterface` — findById, findByHash, findByUserAndType, save, delete, deleteByUserAndType, deleteExpired
- [x] `RefreshTokenRepositoryInterface` — save, findByHash, findByUserId, deleteExpired
- [x] `UserMfaConfigRepositoryInterface` — findByUserId, save, deleteByUserId
- [x] `UserMfaBackupCodeRepositoryInterface` — findUnusedByUser, findById, save, markAsUsed, countUnusedByUser
- [x] `AuthLogRepositoryInterface` — append, findByUserId, countByUserId

### 1.6 Domain Services (interfaces)
- [x] `PasswordHasherInterface` — hash, verify
- [x] `ForgotPasswordEmailSenderInterface` — sendPasswordResetEmail
- [x] `AccountNotifierInterface` — sendActivationEmail
- [x] `ContactFinderInterface` — findById (cross-BC)
- [x] `UserEmailResolverInterface` — resolveEmail (cross-BC)
- [x] `UserByEmailResolverInterface` — resolveByEmail (cross-BC)
- [x] `UserBindingCheckerInterface` — ensureNotBound (cross-BC)
- [x] `OrphanUserCheckerInterface` — ensureIsBound, isBound, findAllOrphanUserIds

### 1.7 Exceptions
- [x] `UserIdException`, `UserUsernameException`, `UserPasswordException`, `UserStatusException`
- [x] `UserAlreadyBoundException`, `UserOrphanException`
- [x] `RoleIdException`, `RoleNameException`, `RoleStatusException`
- [x] `PermissionIdException`, `PermissionKeyException`
- [x] `TokenException`, `RefreshTokenException`
- [x] `AuthLogException`

---

## TASK-02: Application Layer

### 2.1 Commands
- [x] `RegisterUser` + Handler
- [x] `VerifyUser` + Handler (admin direct activation)
- [x] `VerifyUserByToken` + Handler
- [x] `GenerateActivationToken` + Handler
- [x] `ChangePassword` + Handler
- [x] `ChangeUsername` + Handler
- [x] `AssignRoleToUser` + Handler
- [x] `DeactivateUser` + Handler
- [x] `ActivateUser` + Handler
- [x] `ReactivateUser` + Handler
- [x] `SoftDeleteUser` + Handler
- [x] `UnlockUser` + Handler
- [x] `RecordFailedLogin` + Handler
- [x] `CreateRole` + Handler
- [x] `RenameRole` + Handler
- [x] `ActivateRole` + Handler
- [x] `DeactivateRole` + Handler
- [x] `DeleteRole` + Handler
- [x] `SyncRolePermissions` + Handler
- [x] `AssignPermissionToRole` + Handler
- [x] `DefinePermission` + Handler
- [x] `ChangePermissionDescription` + Handler
- [x] `RequestPasswordReset` + Handler
- [x] `ResetPassword` + Handler
- [x] `StoreRefreshToken` + Handler
- [x] `RevokeRefreshToken` + Handler
- [x] `DisableUserMfa` + Handler
- [x] `RevokeMfa` + Handler
- [x] `InviteContactToPortal` + Handler

### 2.2 Queries
- [x] `ListUsers` + Handler
- [x] `FindUserById` + Handler
- [x] `FindUserByUsername` + Handler
- [x] `ListRoles` + Handler
- [x] `FindRoleById` + Handler
- [x] `FindRoleByName` + Handler
- [x] `FindRoleBySlug` + Handler
- [x] `ListPermissions` + Handler
- [x] `FindPermissionById` + Handler
- [x] `FindPermissionByKey` + Handler
- [x] `FindPermissionsByIds` + Handler
- [x] `ListAuthLog` + Handler
- [x] `IdentityDashboardMetrics` + Handler

### 2.3 Response DTOs
- [x] `UserResponse` (fromEntity)
- [x] `RoleResponse` (fromEntity)
- [x] `PermissionResponse` (fromEntity)
- [x] `AuthLogResponse` (fromEntity)
- [x] `ApiTokenResponse`
- [x] `ListUsersResult`
- [x] `ListAuthLogResult`

### 2.4 Application Exceptions
- [x] `UserNotFoundException`
- [x] `UsernameAlreadyExistsException`
- [x] `RoleNotFoundException`
- [x] `RoleNameAlreadyExistsException`
- [x] `PermissionNotFoundException`
- [x] `PermissionKeyAlreadyExistsException`
- [x] `EmailNotFoundException`
- [x] `ContactNotFoundException`
- [x] `ContactAlreadyHasAccessException`

---

## TASK-03: Infrastructure Layer

### 3.1 Doctrine Repositories
- [x] `DoctrineUserRepository`
- [x] `DoctrineRoleRepository` (incl. role_permissions pivot manual)
- [x] `DoctrinePermissionRepository`
- [x] `DoctrineUserTokenRepository`
- [x] `DoctrineRefreshTokenRepository`
- [x] `DoctrineUserMfaConfigRepository`
- [x] `DoctrineUserMfaBackupCodeRepository`
- [x] `DoctrineAuthLogRepository`

### 3.2 ORM Mappings (XML)
- [x] `User.orm.xml` — table `identity_users`
- [x] `Role.orm.xml` — table `identity_roles`
- [x] `Permission.orm.xml` — table `identity_permissions`
- [x] `UserToken.orm.xml` — table `identity_user_tokens`
- [x] `UserMfaConfig.orm.xml` — table `identity_user_mfa_configs`
- [x] `AuthLog.orm.xml` — table `identity_auth_logs`

### 3.3 Custom DBAL Types
- [x] `UserIdType`, `UsernameType`, `HashedPasswordType`
- [x] `RoleIdType`, `RoleNameType`, `RoleSlugType`
- [x] `RoleStatusType`, `UserStatusType`
- [x] `PermissionIdType`, `PermissionKeyType`
- [x] `TokenHashType`, `UserTokenIdType`
- [x] `UserTokenTypeType` (custom string enum — PHP 8.4 compat)
- [x] `UserMfaConfigIdType`, `UserMfaBackupCodeIdType`
- [x] `AuthLogIdType`, `AuthEventType` (custom string enum — PHP 8.4 compat)
- [x] `RefreshTokenIdType`

### 3.4 Security
- [x] `SymfonySecurityUser` — Adapter User domain → Symfony UserInterface
- [x] `SymfonyUserProvider` — UserProviderInterface con chequeo de locked/deleted/status
- [x] `PermissionVoter` — Voter basado en atributos con `.`
- [x] `TotpService` — RFC 6238 TOTP con QR code
- [x] `MfaSubscriber` — Redirección a MFA si no configurado
- [x] `LoginSuccessSubscriber` — Registra login exitoso
- [x] `LoginFailureSubscriber` — Registra login fallido → RecordFailedLogin
- [x] `LandingRouteResolver` — Post-login redirect

### 3.5 Services
- [x] `NativePasswordHasher` — bcrypt cost=12
- [x] `LoggerForgotPasswordEmailSender` — stub (solo log)
- [x] `SymfonyMailerAccountNotifier` — Symfony Mailer + Twig
- [x] `CrossBcUserEmailResolver` — busca en Org + Client BCs
- [x] `CrossBcUserByEmailResolver` — busca en Org + Client BCs
- [x] `CrossBcContactFinder` — busca en Client BC
- [x] `UserBindingChecker` — cross-reference Org + Client
- [x] `OrphanUserChecker` — detecta usuarios sin binding

### 3.6 Console Commands
- [x] `SeedPermissionsCommand` (53+ permisos)
- [x] `SeedRolesCommand` (predefined roles)
- [x] `SeedAdminCommand` (admin inicial)
- [x] `DetectOrphanUsersCommand`
- [x] `CleanupExpiredRefreshTokensCommand`
- [x] `AssignInventoryPermissionsCommand`

---

## TASK-04: Presentation Layer

### 4.1 API Controllers
- [x] `JwtApiController` — `POST /api/v1/auth/jwt/login`, `POST /api/v1/auth/jwt/refresh`
- [x] `ApiIdentityAuthController` — `POST /auth/login|logout|mfa/*|forgot-password|reset-password`
- [x] `ApiIdentityUserController` — CRUD users + acciones
- [x] `ApiIdentityRoleController` — CRUD roles + permissions
- [x] `ApiIdentityMfaController` — `POST /identity/mfa/setup|confirm`
- [x] `ApiIdentityAuthLogController` — `GET /identity/auth-log`

### 4.2 Web Controllers
- [x] `LoginController` — login form
- [x] `ForgotPasswordController` — forgot/reset forms
- [x] `UserController` — CRUD web users
- [x] `RoleController` — CRUD web roles + permissions
- [x] `VerificationController` — token activation
- [x] `MfaController` — `/2fa/setup|check|recovery`
- [x] `AuthLogController` — auth logs list/show
- [x] `IdentityDashboardController` — dashboard

### 4.3 Templates
- [x] `login.html.twig`
- [x] `user/list.html.twig`, `user/show.html.twig`, `user/form.html.twig`
- [x] `role/list.html.twig`, `role/show.html.twig`, `role/form.html.twig`
- [x] `security/login.html.twig`, `security/2fa_check.html.twig`, `security/2fa_setup.html.twig`
- [x] Forgot-password form template

---

## TASK-05: Tests

### 5.1 Unit Tests
- [ ] `UserTest` — ✅ exists
- [ ] `RoleTest` — ✅ exists
- [ ] `PermissionTest` — ✅ exists
- [ ] `JwtApiControllerTest` — ✅ exists
- [ ] `SymfonyUserProviderTest` — ✅ exists
- [ ] `DoctrineRefreshTokenRepositoryTest` — ✅ exists
- [x] Todos los VOs — ✅ 15 ValueObject tests (ULID validation)
- [x] Todos los Handlers — ✅ 30 command + 13 query handler tests
- [x] AuthLog flow test — ❌ Pendiente
- [x] MFA flow test — ❌ Pendiente
- [x] Forgot-password flow test — ❌ Pendiente

### 5.2 Integration Tests
- [x] User registration → activation flow — ❌ Pendiente
- [x] Role → permission sync — ❌ Pendiente
- [x] JWT token lifecycle — ❌ Pendiente
- [x] Cross-BC binding (Employee + Contact) — ❌ Pendiente

---

## TASK-06: Known Gaps

| # | Gap | Estado |
|---|-----|--------|
| G‑01 | MFA recovery codes UI (regeneración) | ❌ Pendiente |
| G‑02 | Refresh token cleanup automático (cron) | ❌ Pendiente |
| G‑03 | Forgot-password email real (no stub) | ✅ EmailForgotPasswordEmailSender con Symfony Mailer. `.env.dev` con SMTP. Wireado en services.yaml. |
| G‑04 | Notificación expiración de contraseña | ❌ No implementado |
| G‑05 | Rate limiting en login | ❌ No implementado |
| G‑06 | PHP 8.4: UserToken.orm.xml usa `type="string"` para UserTokenType | ✅ Cambiado a `type="user_token_type"` |
| G‑07 | PHP 8.4: AuthLog.orm.xml usa `type="string"` para AuthEvent | ✅ Cambiado a `type="auth_event"` |
| G‑08 | Tests coverage insuficiente (solo 6 test files) | ✅ 70 test files (30 command + 13 query + 15 VO + 7 entity + 2 infra + 1 presentation + 1 integration) |

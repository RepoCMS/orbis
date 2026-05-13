# BC Identity — Especificación de Dominio

**Propósito:** Autenticación, autorización y auditoría de seguridad. No contiene datos personales — cada BC es dueño de los suyos.

**Estado:** 🟡 Funcional (MFA parcial — `UserMfaBackupCode` pendiente)

**Dependencias cross-BC:** Ninguna (los demás BCs dependen de Identity, no al revés)

---

## Entidades y Aggregates

### Aggregate Root — `User`

`User` representa únicamente las credenciales de acceso de una persona al sistema. No tiene nombre, email ni datos personales.

| Campo | Tipo | Descripción |
|---|---|---|
| id | ULID | Identificador único |
| username | string | Nombre de usuario único en el sistema |
| passwordHash | string | Contraseña hasheada (bcrypt cost 12) |
| roleId | ULID | **Un único rol** asignado (referencia a `Role`) |
| status | enum | `UNVERIFIED` / `ACTIVE` / `INACTIVE` |
| failedLoginAttempts | int | Contador de intentos fallidos consecutivos (default 0) |
| lockedUntil | datetime? | Bloqueado hasta esta fecha. `null` = no bloqueado |
| deletedAt | datetime? | Soft delete |

> `createdAt` / `updatedAt` gestionados por Doctrine (TimestampsSubscriber). El dominio no los expone porque ninguna regla de negocio los requiere.

#### Value Objects de User

| Value Object | Reglas |
|---|---|
| `UsernameValueObject` | Minúsculas, 5–15 caracteres, patrón `/^[a-z0-9._-]+$/`, único en el sistema |
| `RawPasswordValueObject` | ≥8 caracteres, ≥1 mayúscula, ≥1 minúscula, ≥1 dígito, ≥1 carácter especial (`@$!%*?&._-`) |
| `PasswordHashValueObject` | Valor hasheado con bcrypt, no reversible |

#### Generación automática de username

> ⚠️ **Responsabilidad del Application Layer** — Identity BC **no conoce** nombres ni apellidos. La generación ocurre en el handler cross-BC (`CreateEmployeeWithUserHandler` / `InviteContactToPortalHandler`) que recibe los datos del BC correspondiente.

Regla de negocio (ejecutada por `UsernameGeneratorService` en Application Layer):

1. Base: `{inicial_nombre}.{apellido}` en minúsculas, sin acentos, sin espacios (ej: `j.perez`)
2. Si ya existe → añadir sufijo numérico: `j.perez1`, `j.perez2`, ...
3. Unicidad verificada llamando a `UserRepositoryInterface::existsByUsername()`
4. El username resultante se pasa como parámetro al comando `CreateUser`

Identity Domain solo valida que el username recibido cumpla el patrón de `UsernameValueObject` — **no lo genera**.

#### Estados del User

```
UNVERIFIED ──── verify(token) ────► ACTIVE ──── deactivate() ────► INACTIVE
                                      ▲                                  │
                                      └──────── reactivate() ────────────┘
```

| Estado | Descripción |
|---|---|
| `UNVERIFIED` | Estado inicial. No puede autenticarse. |
| `ACTIVE` | Cuenta activa. Puede autenticarse si no está bloqueado. |
| `INACTIVE` | Desactivado. No puede autenticarse. |

> El bloqueo por intentos fallidos **no es un estado** — es un campo temporal (`lockedUntil`). Un `ACTIVE` con `lockedUntil > now()` no puede autenticarse. El bloqueo dura 15 minutos; tras expirar, el sistema envía contraseña temporal + link de cambio por email.

#### Comportamientos del User

| Método | Guard (precondición) | Efecto |
|---|---|---|
| `activate()` | status = UNVERIFIED | status → ACTIVE |
| `deactivate()` | status = ACTIVE | status → INACTIVE |
| `reactivate()` | status = INACTIVE | status → ACTIVE |
| `changePassword(raw)` | status = ACTIVE | passwordHash actualizado, failedAttempts = 0 |
| `changeUsername(username)` | status = ACTIVE, username único | username actualizado |
| `assignRole(roleId)` | — | roleId actualizado |
| `recordFailedLogin()` | — | failedAttempts++; si ≥ 5 → lockedUntil = now() + 15 min |
| `unlock()` | — | lockedUntil = null, failedAttempts = 0 |
| `softDelete()` | — | deletedAt = now() |

#### Invariantes del User

1. `UNVERIFIED` o `INACTIVE` → nunca puede autenticarse
2. `lockedUntil > now()` → no puede autenticarse aunque esté `ACTIVE`
3. `username` debe ser único en todo el sistema
4. `deletedAt != null` → status debe ser `INACTIVE`; no puede autenticarse
5. `failedLoginAttempts` default = 0; se resetea a 0 al autenticarse exitosamente o resetear contraseña
6. Un `User` solo puede estar vinculado a **un único** `Employee` o `Contact` — nunca huérfano
7. Garantía de dueño: responsabilidad del Application Service (no de la entidad)

#### Desbloqueo (dos vías)

- **Admin / usuario con `user.unlock`**: ejecuta `unlock()` directamente desde la UI
- **Self-service con MFA activo**: usuario ingresa username → "¿Cuenta bloqueada?" → ingresa código TOTP → `unlock()` inmediato
- **Self-service sin MFA**: flujo `UserToken(ACCOUNT_UNLOCK)` enviado al email del dueño

---

### Entidad — `UserToken`

Gestiona tokens temporales de ciclo de vida de la cuenta. Los tokens consumidos o expirados se **eliminan físicamente** — la auditoría queda en `AuthLog`.

| Campo | Tipo | Descripción |
|---|---|---|
| id | ULID | Identificador único |
| userId | ULID | Referencia al User dueño |
| type | enum | `ACTIVATION` / `PASSWORD_RESET` / `ACCOUNT_UNLOCK` |
| tokenHash | string | Hash SHA-256 del token (nunca en texto plano) |
| expiresAt | datetime | Fecha de expiración |

| Tipo | Expira en | Quién lo solicita |
|---|---|---|
| `ACTIVATION` | 48 horas | Al crear un User (Flujo A y B) |
| `PASSWORD_RESET` | 1 hora | El propio usuario desde formulario de recuperación |
| `ACCOUNT_UNLOCK` | 1 hora | Usuario bloqueado desde pantalla de login |

#### Value Objects de UserToken

| Value Object | Reglas |
|---|---|
| `PlainTokenValueObject` | 32 bytes criptográficamente seguros (`random_bytes`), representado como hex (64 chars) |
| `TokenHashValueObject` | Hash SHA-256 del PlainToken. Solo el hash se persiste |

#### Invariantes de UserToken

1. Solo puede existir **un token activo** (no expirado) por `userId` + `type`
2. Al generar nuevo token del mismo tipo → el anterior se **elimina físicamente**
3. Al consumir token válido → el registro se **elimina físicamente**
4. Token expirado (`expiresAt < now()`) → rechazado. Job de limpieza los elimina

#### Comportamientos de UserToken

> ⚠️ La entidad **no puede** llamar a su repositorio. La secuencia correcta es responsabilidad del Application Handler.

| Quién | Paso | Acción |
|---|---|---|
| **Handler** | 1 | Elimina el token anterior del mismo tipo via `UserTokenRepositoryInterface::deleteByUserAndType()` |
| **Entidad** | 2 | `UserToken::create(userId, type)` — genera PlainToken aleatorio, calcula tokenHash, establece expiresAt |
| **Handler** | 3 | Persiste la entidad via repositorio; envía el PlainToken por email |
| **Handler** | 4 | `consume(plainToken)` → verifica hash + expiración; el handler llama a `delete()` en el repositorio |

| Método en la entidad | Guard | Efecto puro (sin I/O) |
|---|---|---|
| `UserToken::create(userId, type)` | — | Genera PlainToken, calcula hash SHA-256, establece expiresAt según tipo |
| `isExpired()` | — | Retorna `expiresAt < now()` |
| `matchesToken(plainToken)` | — | Verifica hash SHA-256 sin consumir |

---

### Entidad — `UserMfaConfig` ✅ Implementado

Configuración TOTP de doble factor. Entidad separada para mantener SRP.

| Campo | Tipo | Descripción |
|---|---|---|
| id | ULID | Identificador único |
| userId | ULID | Referencia al User (1:1) |
| secret | string | Secreto TOTP cifrado en BD (AES-256) |
| isEnabled | bool | `false` = en configuración; `true` = activo |
| confirmedAt | datetime? | Fecha de primera confirmación TOTP válida |
| createdAt | datetime | Fecha de creación |

#### Invariantes de UserMfaConfig

1. Solo puede existir **una** `UserMfaConfig` por `userId`
2. `isEnabled = false` → no protege el login
3. `confirmedAt` se establece una sola vez
4. El `secret` se cifra antes de persistir; se descifra en memoria solo durante verificación
5. No se puede deshabilitar MFA sin ingresar código TOTP válido o de respaldo

#### Comportamientos de UserMfaConfig

> ⚠️ `UserMfaConfig` **no puede** crear/eliminar `UserMfaBackupCode` — son aggregates distintos con repositorios distintos. Los métodos de la entidad son puros; el handler coordina ambos.

| Método en la entidad | Guard | Efecto puro |
|---|---|---|
| `UserMfaConfig::setup(userId)` | — | Genera secret TOTP, isEnabled = false |
| `confirm(totpCode)` | isEnabled = false, código TOTP válido | isEnabled = true, confirmedAt = now() |
| `verify(totpCode)` | isEnabled = true | Retorna bool (válido/inválido, ventana ±30s) |
| `disable()` | isEnabled = true | isEnabled = false |

**Secuencia del handler para `ConfirmMfaCommand`:**
1. `mfaConfig.confirm(totpCode)` — activa el MFA en la entidad
2. `mfaConfigRepository.save(mfaConfig)`
3. Genera 8 `UserMfaBackupCode` (plain codes) → hashea y persiste via `backupCodeRepository`
4. Retorna los 8 plain codes al Controller (solo en este momento)
5. Registra `AuthLog(MFA_SETUP_COMPLETED)`

**Secuencia del handler para `RegenerateBackupCodesCommand`:**
1. `mfaConfig.verify(totpCode)` — valida el TOTP
2. `backupCodeRepository.deleteByUserId(userId)` — elimina todos los anteriores
3. Genera 8 nuevos `UserMfaBackupCode` y los persiste
4. Registra `AuthLog(MFA_BACKUP_CODES_REGENERATED)`

**Secuencia del handler para `DisableMfaCommand`:**
1. `mfaConfig.verify(totpCode)` — valida el TOTP (o admin bypasses)
2. `mfaConfig.disable()`
3. `backupCodeRepository.deleteByUserId(userId)` — elimina todos los backup codes
4. `mfaConfigRepository.save(mfaConfig)`

---

### Entidad — `UserMfaBackupCode` ⚠️ Pendiente de implementar

Códigos de respaldo de un solo uso para recuperación de acceso MFA.

| Campo | Tipo | Descripción |
|---|---|---|
| id | ULID | Identificador único |
| userId | ULID | Referencia al User |
| codeHash | string | Hash SHA-256 del código |
| usedAt | datetime? | `null` = disponible; fecha = ya consumido |

- Se generan **8 códigos** al confirmar MFA
- Cada código es de un solo uso
- Al usar un código → registra en `AuthLog(MFA_BACKUP_CODE_USED)`
- El usuario puede regenerar todos (invalida anteriores)

---

### Aggregate Root — `Role`

Los roles se crean y gestionan desde la interfaz (no son fijos). Cada usuario tiene **exactamente un rol**.

| Campo | Tipo | Descripción |
|---|---|---|
| id | ULID | Identificador único |
| name | string | Nombre del rol |
| slug | string | Identificador único en slug (ej: `TECHNICIAN`) |
| status | enum | `ACTIVE` / `INACTIVE` |
| permissionIds | ULID[] | Referencias a permisos asignados |
| deletedAt | datetime? | Soft delete |

**Roles del sistema (sembrados en BD):**

| Slug | Nombre |
|---|---|
| `ADMIN` | Administrador |
| `SUPERVISOR` | Supervisor |
| `TECHNICIAN` | Técnico de Campo |
| `FINANCIAL` | Financiero |
| `WAREHOUSE` | Bodeguero |
| `CLIENT_PORTAL` | Cliente (Portal) |

---

### Entidad — `Permission`

Los permisos tienen formato `{módulo}.{acción}` y son **fijos/sembrados en BD** — no se crean desde la UI.

| Campo | Tipo | Descripción |
|---|---|---|
| id | ULID | Identificador único |
| key | string | Clave del permiso (`módulo.acción`) — única |
| description | string | Descripción legible |

**Permisos por módulo:**

| Módulo | Acciones |
|---|---|
| `user` | `view`, `create`, `update`, `deactivate`, `reset_password`, `unlock` |
| `employee` | `view`, `create`, `update`, `deactivate` |
| `client` | `view`, `create`, `update`, `deactivate` |
| `contact` | `view`, `create`, `update`, `delete` |
| `ticket` | `view`, `create`, `update`, `assign`, `close`, `approve`, `cancel` |
| `inventory` | `view`, `create`, `update`, `delete`, `move_stock` |
| `expense` | `view`, `create`, `update`, `approve` |
| `purchase_order` | `view`, `create`, `update`, `receive` |
| `report` | `view` |

**Invariantes:** La clave debe ser única. Patrón: `/^[a-z][a-z0-9_]*\.[a-z][a-z0-9_]*$/`, máximo 100 chars. Los permisos no se eliminan del sistema.

---

### Entidad — `AuthLog`

Registro inmutable de auditoría de accesos. **Nunca se modifica ni elimina.**

| Campo | Tipo | Descripción |
|---|---|---|
| id | ULID | Identificador único |
| userId | ULID | Referencia al User |
| event | enum | Tipo de evento (ver tabla abajo) |
| ipAddress | string | IP del cliente |
| userAgent | string | User-agent del cliente |
| occurredAt | datetime | Fecha y hora del evento |

**Eventos registrados:**

| Evento | Descripción |
|---|---|
| `LOGIN_SUCCESS` | Autenticación completa (fase 1 + fase 2 MFA) |
| `LOGIN_FAILED` | Contraseña incorrecta (fase 1 fallida) |
| `MFA_FAILED` | Código TOTP incorrecto (fase 2 fallida) |
| `MFA_SETUP_COMPLETED` | Usuario configuró MFA por primera vez |
| `MFA_BACKUP_CODE_USED` | Se usó un código de respaldo |
| `MFA_BACKUP_CODES_REGENERATED` | Se regeneraron los códigos de respaldo |
| `ACCOUNT_LOCKED` | Cuenta bloqueada por exceso de intentos |
| `ACCOUNT_UNLOCK_REQUESTED` | Usuario solicitó desbloqueo self-service |
| `ACCOUNT_UNLOCKED` | Cuenta desbloqueada |
| `ACTIVATION_COMPLETED` | Usuario activó su cuenta por primera vez |
| `PASSWORD_RESET_REQUESTED` | Se solicitó reset de contraseña |
| `PASSWORD_RESET_COMPLETED` | Contraseña reseteada exitosamente |
| `LOGOUT` | Cierre de sesión |

---

## Flujos de Onboarding

### Flujo A — Crear Empleado con Usuario

> `CreateEmployeeWithUserCommand` — un formulario, una transacción.

```
BEGIN TRANSACTION
  1. Generar username desde firstName + lastName (unicidad garantizada)
  2. Crear User (UNVERIFIED, rol según puesto)
  3. Crear Employee con el userId recién generado
COMMIT
  4. Generar ActivationToken → enviar email al Employee.email  ← fuera de transacción
```

- Si el paso 2 o 3 falla → ROLLBACK completo
- Si el email falla → User y Employee existen; la UI permite "Reenviar invitación"
- El admin puede reenviar la invitación manualmente en cualquier momento

### Flujo B — Invitar Contacto de Cliente al Portal

> `InviteContactToPortalCommand` — disparado manualmente por usuario con `user.create`.

```
BEGIN TRANSACTION
  1. Verificar que Contact existe y no tiene userId asignado
  2. Generar username desde Contact.firstName + Contact.lastName
  3. Crear User (UNVERIFIED, rol CLIENT_PORTAL)
  4. Actualizar Contact.userId = user.id
COMMIT
  5. Generar ActivationToken → enviar email al Contact.email  ← fuera de transacción
```

- Si el contacto ya tiene `userId` → error: "Este contacto ya tiene acceso al sistema"

### Flujo C — Recuperación de Contraseña (self-service)

```
  1. Usuario ingresa su username
  2. Sistema busca User → si ACTIVE, genera PasswordResetToken
  3. Resuelve el email: busca Employee.email o Contact.email donde userId = user.id (cross-BC)
  4. Envía email con link de reset
  5. Usuario ingresa nueva contraseña → resetPassword(token, raw) → failedAttempts = 0
```

- Si el User no existe o no está `ACTIVE` → respuesta genérica (no revelar si existe)
- Token expira en 1 hora

---

## Autenticación con MFA (TOTP) — Flujo de dos fases

**Fase 1 — Credenciales:**
```
1. Usuario ingresa username + password
2. Sistema valida: status = ACTIVE, no bloqueado, password correcto
3. Si falla → recordFailedLogin() → AuthLog(LOGIN_FAILED)
4. Si pasa → challenge MFA temporal (token de sesión parcial, 5 min)
```

**Fase 2 — Verificación TOTP:**
```
5. Usuario ingresa código de 6 dígitos
6. Sistema valida el código TOTP (ventana ±30 segundos)
   → Si válido → autenticación completa, sesión/JWT emitido
   → Si inválido → AuthLog(MFA_FAILED), puede reintentar hasta 3 veces
7. Alternativa: código de respaldo en lugar del TOTP
```

**Primer login (MFA no configurado):**
```
1. Fase 1 exitosa → sistema detecta que no tiene UserMfaConfig activo
2. Redirige obligatoriamente al flujo de configuración de MFA
3. Muestra QR para app autenticadora
4. Usuario ingresa primer código TOTP → confirm() → MFA activo
5. Se muestran los 8 códigos de respaldo (SOLO en este momento)
6. Usuario confirma que los guardó → acceso al sistema
```

> MFA es **obligatorio para todos** los usuarios (empleados y contactos de clientes).

- **Web:** Symfony Security con sesión HTTP
- **API REST (mobile):** JWT — Access Token + Refresh Token tras completar ambas fases

---

## Queries esperadas

- Buscar usuario por id o username
- Listar usuarios con filtro por status, rol
- Buscar rol por slug; listar todos los roles con sus permisos
- Buscar permiso por id o key; listar todos los permisos
- Listar AuthLog por userId con paginación

---

## Dependencias cross-BC

| Referencia | Dirección | Descripción |
|---|---|---|
| `Employee.userId` | Organization → Identity | Employee referencia a User por ULID |
| `Contact.userId?` | Client → Identity | Contact referencia opcionalmente a User |
| `UserEmailResolverInterface` | Identity → Organization/Client | Resuelve email del User buscando Employee o Contact |

> Identity BC es independiente. Los demás BCs dependen de él. Nunca al revés.

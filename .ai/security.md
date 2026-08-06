# .ai/security.md — AGENTE SECURITY-REVIEWER (Revisión de Vulnerabilidades)

Rol: revisas que el código no introduzca vulnerabilidades. Eres la ÚLTIMA revisión antes del commit (después de DEVELOPER y FRONTEND, antes del QA-REVIEWER final). Emites verdict APROBADO/RECHAZADO con severidades.

---

## 1. Arranque obligatorio (CUALQUIER tarea)

```
1. AGENTS.md                    → reglas de código vigentes (Apéndices A, C, D, E)
2. PROJECT_CONTEXT.md           → contexto AI-native + stack (con discrepancias ⚠️ marcadas en él)
3. docs/architecture.md         → arquitectura del sistema (capas, BCs, flujos, persistencia) — verificada 2026-08-06
4. docs/coding-standards.md     → estándares de codificación (Apéndices A/C/E de AGENTS.md verificados)
5. docs/adr/README.md           → índice de ADRs (ADR-001..011) + template obligatorio
6. docs/adr/ (ADR-001..011)     → decisiones vigentes que NO se contradicen
7. spec correspondiente         → specs/{num}-{bc}/spec.md (+ plan.md, tasks.md)
8. docs/history.md, docs/analytics.md → deuda técnica y estrategia (bajo demanda)
9. tasks/ / HANDOFF.md        → tablero de tareas y protocolo de handoff
10. REVISAR (diff del código) — ver §2
11. ACTUALIZAR DOCS si cambió algo arquitectónico → nuevo ADR + PROJECT_CONTEXT.md + HANDOFF.md §12
```

> **Nota:** `docs/architecture.md` y `docs/coding-standards.md` existen desde 2026-08-06 (transición AI-Native) y son la referencia de arquitectura/estándares verificados contra el disco. `PROJECT_CONTEXT.md` conserva discrepancias marcadas ⚠️ — ante conflicto, prevalecen los archivos verificados en disco (`docs/architecture.md`, `docs/coding-standards.md`, `CONTEXT.md`).

---

## 2. Checklist de seguridad (verificado contra el repo real)

### 2.1 Permisos y autorización
- [ ] **Todo endpoint tiene `#[IsGranted('{bc}.{action}')]`** y el permiso existe en `SeedPermissionsCommand` (`src/app/Identity/Infrastructure/Console/SeedPermissionsCommand.php`, se siembra con `php bin/console app:permissions:seed`)
- [ ] **Scoping por perfil**: los recursos sensibles usan `{Resource}ViewScopeResolver` (patrón Apéndice D). Resolvers reales: `StockViewScopeResolver`, `MovementViewScopeResolver` (Inventory), `SparePartViewScopeResolver` (Support) en `Application/Service/`. Técnicos solo ven su mochila; clientes solo su cliente.
- [ ] Firewalls de `config/packages/security.yaml` respetados:
  - `api_public` → solo `^/api/v1/auth/(forgot-password|reset-password)` (`security: false`)
  - `api_jwt` → solo `^/api/v1/auth/jwt/(login|refresh)` (`security: false`)
  - `api` → `^/api/` stateless JWT
  - `main` → form_login para web
  - `access_control`: PUBLIC_ACCESS solo en login/forgot/reset/verify; todo lo demás ROLE_USER
- [ ] Endpoints nuevos no caen accidentalmente en un firewall público

### 2.2 Autenticación (JWT + MFA)
- [ ] **JWT Lexik**: RSA keys en `config/jwt/` (no versionadas), firewalls `api_jwt`/`api` como arriba
- [ ] **MFA TOTP** (`spomky-labs/otphp: ^11.5`): `TotpService`, `MfaSubscriber`, backup codes (`GenerateBackupCodesCommand`), rutas 2fa (setup/check/recovery/backup-codes). Nuevos flujos de login deben integrarse con MFA — ver `MfaSubscriber`
- [ ] **security_version**: incrementa en deactivate, changePassword y assignRole; `EquatableInterface` invalida la sesión en refresh (Key Decision)
- [ ] **RefreshToken**: opaque, hash SHA-256 en BD, rotación en cada refresh, cleanup via `identity:cleanup-refresh-tokens`
- [ ] **UC-04 catch**: captura `EmailNotFoundException | DomainException` (NO `\Throwable`) — sin email enumeration (Key Decision)
- [ ] **RecordFailedLogin**: dos flujos — (1) API JWT → handler inyectado en `JwtApiController::login()`; (2) Web → `LoginFailureSubscriber` escucha `LoginFailureEvent`. Ambos deben existir para cada flujo de login nuevo (Key Decision)
- [ ] Password hasher: bcrypt para `SymfonySecurityUser` (⚠️ issue #42 pendiente: bcrypt vs argon2 — no cambiar sin decisión)

### 2.3 Secrets
- [ ] **`.env` NO está trackeado** (verificado: `git ls-files` solo muestra `.env.example`, `.env.dev.example`, `.env.test.example` — sin valores reales)
- [ ] No hay keys, passwords, tokens ni JWT_PASSPHRASE en código o archivos versionados
- [ ] `.env.example` usa placeholders (`change-me`, `null://null`) — no introducir secretos reales ahí

### 2.4 Validación de input
- [ ] Controllers validan antes de delegar; no hay SQL nativo inyectable (Apéndice C #1/#6: usar repositorios, nunca `$em->getConnection()->fetchAll` en controllers)
- [ ] DQL con parámetros tipados (Apéndice C #4)
- [ ] Uploads (ej. evidence de findings): validar tipo/tamaño

### 2.5 Web (OWASP aplicado)
- [ ] **CSRF**: forms web usan `csrf_token(...)` (ver `templates/security/login.html.twig`) + firewall `main` con form_login. No desactivar CSRF en forms que mutan estado
- [ ] **XSS**: Twig autoescape activo (por defecto en Symfony). No usar `|raw` sin necesidad; no interpolar input de usuario sin escapar
- [ ] **CORS/headers**: configuración por defecto segura; verificar que no se agregaron headers peligrosos
- [ ] **Rate limiting**: ⚠️ NO existe en el proyecto (PROJECT_CONTEXT §7: "No rate limiting on login" — limitación conocida). Si se introduce, requiere ADR
- [ ] Logs: no loguear contraseñas ni tokens

---

## 3. Salida del review

```
Verdict: APROBADO / RECHAZADO

Hallazgos (severidad: crítica/alta/media/baja):
- [CRÍTICA]  archivo:línea — descripción
- [ALTA]     ...

Notas: rate limiting ausente (conocido, PROJECT_CONTEXT §7), etc.
```

Si se encuentra algo crítico, indicar archivo y línea exacta.

---

## 4. Fuentes

- `AGENTS.md` — Apéndice D (Data Scoping), Key Decisions (JWT, RefreshToken, security_version, UC-04, RecordFailedLogin, WhatsApp stub, LoggerForgotPasswordEmailSender)
- `CONTEXT.md` — §7 blockers (#75 MFA backup codes, #42 bcrypt/argon2), §6 handoff
- `PROJECT_CONTEXT.md` — §3.1 security stack, §7 limitaciones conocidas (no rate limiting)
- `config/packages/security.yaml` — firewalls y access_control (verificado 2026-08-06)
- `config/packages/lexik_jwt_authentication.yaml` — configuración JWT (si existe)
- `src/app/Identity/Infrastructure/Security/` — `MfaSubscriber`, `TotpService`, `SymfonyUserProvider`, `SymfonySecurityUser`
- `src/app/Identity/Infrastructure/Console/SeedPermissionsCommand.php` — permisos a sembrar
- `src/app/{Inventory,Support}/Application/Service/*ViewScopeResolver.php` — scoping
- `.env.example` — placeholders (no versionar secretos)
- `docs/history.md` — issues de seguridad conocidos
- `docs/architecture.md` — §5 persistencia, §6 frontend (discrepancias verificadas)

## 5. Cómo verificar el resultado

```powershell
# 1. No hay secrets en el working tree:
git ls-files | Select-String -Pattern "\.env"        # solo .env*.example deben aparecer
Select-String -Path src,templates,config -Pattern "JWT_PASSPHRASE|password.*=.*['\"]" -Recurse -ErrorAction SilentlyContinue | Where-Object { $_.Path -notmatch "\.example|\.env\." }

# 2. Los permisos usados en #[IsGranted] existen en el seeder:
Select-String -Path src -Pattern "#\[IsGranted\('([a-z_\.]+)'\)\]" -Recurse | ForEach-Object { $_.Matches[0].Groups[1].Value } | Sort-Object -Unique
Select-String -Path src/app/Identity/Infrastructure/Console/SeedPermissionsCommand.php -Pattern "'[a-z_\.]+'" | ForEach-Object { $_.Matches[0].Value } | Sort-Object -Unique

# 3. Firewalls cubren los nuevos endpoints:
php bin/console debug:router | Select-String "/api/v1"
Select-String -Path config/packages/security.yaml -Pattern "pattern:|security:"

# 4. Endpoints sensibles usan ViewScopeResolver (buscar resolvers en el diff):
Select-String -Path src -Pattern "ViewScopeResolver" -Recurse | ForEach-Object { $_.Path }

# 5. Validación de integridad:
& .\validate_project.ps1

# 6. Tests (si hubo cambios de código):
php vendor/bin/phpunit --filter {FlujoDeAuth}
```

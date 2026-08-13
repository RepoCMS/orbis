# Checklist de Pruebas UI — Orbis

> **Formato:** cada fila es un caso de prueba. Marca `[x]` al ejecutar. Rellena **Resultado** (✅ Pass / ❌ Fail / ⏸ Blocked / ⏭ Skip) y **Notas** (errores, capturas, datos de prueba).
> **Versión app:** commit `a1dbc78` (T30+T31 aplicados) — T32–T35 en curso.
> **Entorno:** `symfony serve` / `php -S localhost:8000 -t public` + BD con fixtures mínimas.

---

## Metadatos de la sesión de pruebas

| Campo | Valor |
|---|---|
| **Tester** | |
| **Fecha inicio** | |
| **Fecha fin** | |
| **Build / Commit** | `a1dbc78` |
| **Entorno** | (local / staging / docker) |
| **Navegador** | (Chrome / Firefox / Edge + versión) |
| **Resolución probada** | (1920×1080 / 1366×768 / mobile 375×667) |

---

## Leyenda de prioridad
- 🔴 **Crítica** — Bloquea release si falla
- 🟠 **Alta** — Funcionalidad core, debe pasar
- 🟡 **Media** — Importante, tolera known issues documentados
- ⚠️ **Gap conocido** — T32–T35 en curso; documenta comportamiento actual

---

## 1. Identity BC — Auth, Users, Roles, MFA

| # | Área | Caso de prueba | Prioridad | Resultado | Notas | Fecha |
|---|---|---|---|---|---|---|
| 1.1 | Login | Credenciales válidas → dashboard | 🔴 | ☐ Pass / Fail / Blocked / Skip | | |
| 1.2 | Login | Credenciales inválidas → error "Invalid credentials" | 🔴 | ☐ | | |
| 1.3 | Login | Cuenta bloqueada (max attempts) → mensaje bloqueo | 🔴 | ☐ | | |
| 1.4 | Login | Remember-me → persiste sesión tras cerrar navegador | 🟠 | ☐ | | |
| 1.5 | Logout | Botón logout → redirige a `/login` y limpia sesión | 🔴 | ☐ | | |
| 1.6 | MFA Setup | `/mfa/setup` muestra QR + código manual + backup codes | 🔴 | ☐ | | |
| 1.7 | MFA Verify | Código TOTP válido → MFA activado | 🔴 | ☐ | | |
| 1.8 | MFA Verify | Código TOTP inválido → error, reintenta | 🟠 | ☐ | | |
| 1.9 | MFA Disable | Desactivar MFA con código válido → MFA off | 🟠 | ☐ | | |
| 1.10 | MFA Recovery | Usar backup code → login exitoso | 🟡 | ☐ | | |
| 1.11 | Forgot Password | Email válido → "Email sent" (logger en dev) | 🔴 | ☐ | | |
| 1.12 | Forgot Password | Email inexistente → mismo mensaje (no enumera users) | 🟠 | ☐ | | |
| 1.13 | Reset Password | Token válido → formulario nueva password | 🔴 | ☐ | | |
| 1.14 | Reset Password | Token expirado (>1h) → error "Token expired" | 🟠 | ☐ | | |
| 1.15 | Reset Password | Token inválido → error "Invalid token" | 🟠 | ☐ | | |
| 1.16 | Users List | `/users` paginado (25/page), orden username ASC | 🟠 | ☐ | | |
| 1.17 | Users List | Filtro por username (parcial, case-insensitive) | 🟡 | ☐ | | |
| 1.18 | Users List | Filtro por rol (dropdown) | 🟡 | ☐ | | |
| 1.19 | Users List | Filtro por estado (active/inactive) | 🟡 | ☐ | | |
| 1.20 | User Create | `/users/new` → username único, email único, roles multi-select | 🔴 | ☐ | | |
| 1.21 | User Create | Username duplicado → error validación inline | 🟠 | ☐ | | |
| 1.22 | User Edit | `/users/{id}/edit` → cambia email, roles, estado | 🟠 | ☐ | | |
| 1.23 | User Activate | Botón "Activar" en usuario inactivo → estado ACTIVE | 🟠 | ☐ | | |
| 1.24 | User Deactivate | Botón "Desactivar" en usuario activo → estado INACTIVE | 🟠 | ☐ | | |
| 1.25 | User SoftDelete | Botón "Eliminar" → soft delete, no aparece en list | 🟡 | ☐ | | |
| 1.26 | Roles CRUD | `/roles` list/create/edit/delete + permisos | 🟡 | ☐ | | |
| 1.27 | Auth Logs | `/auth-logs` paginado + filtros user/action/date | 🟡 | ☐ | | |
| 1.28 | Auth Log Detail | `/auth-logs/{id}` muestra IP, user-agent, success/fail | 🟡 | ☐ | | |
| 1.29 | Identity Dashboard | `/identity/dashboard` 6 KPIs cargan sin error | 🟡 | ☐ | | |

---

## 2. Client BC — Clients, Contacts, Sites, Terminals, Contracts, Warranties, Import

| # | Área | Caso de prueba | Prioridad | Resultado | Notas | Fecha |
|---|---|---|---|---|---|---|
| 2.1 | Client List | `/clients` paginado, filtros name/ruc/status | 🔴 | ☐ | | |
| 2.2 | Client Create | `/clients/new` → RUC válido único (11-13 dígitos Ecuador) | 🔴 | ☐ | | |
| 2.3 | Client Create | RUC duplicado → error "RUC ya registrado" | 🟠 | ☐ | | |
| 2.4 | Client Create | RUC formato inválido → error validación | 🟠 | ☐ | | |
| 2.5 | Client Show | `/clients/{id}` detalle + tabs contacts/terminals/contracts | 🔴 | ☐ | | |
| 2.6 | Client Edit | `/clients/{id}/edit` → cambia name, address, status | 🟠 | ☐ | | |
| 2.7 | Client Activate | Botón activar → status ACTIVE, flash success | 🔴 | ☐ | | |
| 2.8 | Client Deactivate | Botón desactivar → status INACTIVE, **cascada Contact→User (T31)** | 🔴 | ☐ | | |
| 2.9 | Client SoftDelete | Botón eliminar → soft delete, no en list | 🟡 | ☐ | | |
| 2.10 | Contact List | `/clients/{clientId}/contacts` paginado por cliente | 🔴 | ☐ | | |
| 2.11 | Contact Create | Nuevo contacto vinculado a client, first/last name, email | 🔴 | ☐ | | |
| 2.12 | Contact Show | Detalle + linked User + botón Provision Access | 🟠 | ☐ | | |
| 2.13 | Contact Link User | Vincula User Identity existente → `ContactUserLinked` (T31) | 🟠 | ☐ | | |
| 2.14 | Contact Unlink User | Desvincula User → `ContactUserUnlinked` | 🟡 | ☐ | | |
| 2.15 | Contact Provision Access | Crea User Identity + vincula + email activation (logger) | 🟠 | ☐ | | |
| 2.16 | Contact Deactivate | Desactiva contacto → `ContactDeactivated` → User INACTIVE (T31) | 🔴 | ☐ | | |
| 2.17 | Contact Reactivate | Reactiva contacto → `ContactReactivated` → User ACTIVE (T31) | 🔴 | ☐ | | |
| 2.18 | Contact SoftDelete | Elimina contacto → no en list | 🟡 | ☐ | | |
| 2.19 | Sites CRUD | `/clients/{clientId}/sites` list/create/edit/delete | 🟠 | ☐ | | |
| 2.20 | Terminals CRUD | `/clients/{clientId}/terminals` vendor/func/type/status enums | 🟠 | ☐ | | |
| 2.21 | Terminal Assignment | Asigna terminal a site + client, active/inactive | 🟠 | ☐ | | |
| 2.22 | Contracts CRUD | Vigencia, cliente, terminal assignment | 🟡 | ☐ | | |
| 2.23 | Warranties CRUD | Expiración, terminal, alertas | 🟡 | ☐ | | |
| 2.24 | Client Dashboard | `/clients/{id}/dashboard` 11 KPIs (G‑02) cargan | 🟡 | ☐ | |
| 2.25 | Import Clients | `/client/import` → template CSV/Excel, upload, validación, preview | 🟠 | ☐ | |
| 2.26 | Import Terminals | Template + upload terminals, validación errores | 🟠 | ☐ | |

---

## 3. Organization BC — Employees, Service Zones, Overtime, Special Periods

| # | Área | Caso de prueba | Prioridad | Resultado | Notas | Fecha |
|---|---|---|---|---|---|---|
| 3.1 | Employee List | `/employees` paginado (T30: page/limit), **filtros profile/status/search/serviceZoneId** | 🔴 | ☐ | | |
| 3.2 | Employee List | `limit` null (dropdown) → todos; `limit=20` → 20 items + pagination | 🔴 | ☐ | | |
| 3.3 | Employee List | Filtro `profile=technician` → solo técnicos | 🟠 | ☐ | | |
| 3.4 | Employee List | Filtro `status=active` → solo activos | 🟠 | ☐ | | |
| 3.5 | Employee List | Filtro `search=Juan` → búsqueda en nombre/apellido | 🟠 | ☐ | | |
| 3.6 | Employee List | Filtro `serviceZoneId=...` → solo zona (T30) | 🔴 | ☐ | | |
| 3.7 | Employee List | Paginación page=2, limit=10 → items 11-20 | 🟠 | ☐ | | |
| 3.8 | Employee Create | `/employees/new` → identificationNumber único, hiredAt, profile enum | 🔴 | ☐ | | |
| 3.9 | Employee Show | `/employees/{id}` → linked User, special periods, botones vacation/standby | 🟠 | ☐ | | |
| 3.10 | Employee Edit | Cambia firstName, lastName, email, phone, profile | 🟠 | ☐ | | |
| 3.11 | Employee Link User | Vincula User Identity → ProvisionAccess | 🟠 | ☐ | | |
| 3.12 | Employee Unlink User | Desvincula User | 🟡 | ☐ | | |
| 3.13 | Employee Provision Access | Crea User + vincula + roles | 🟠 | ☐ | | |
| 3.14 | Employee Activate/Deactivate/Reactivate | Botones + flash + cascada (Identity) | 🔴 | ☐ | | |
| 3.15 | Employee SoftDelete | Elimina lógicamente | 🟡 | ☐ | | |
| 3.16 | Vacation Schedule | `/employees/{id}/vacation/schedule` → start/end dates, cancel | 🟡 | ☐ | | |
| 3.17 | Standby Schedule | `/employees/{id}/standby/schedule` → start/end + notes, cancel | 🟡 | ☐ | | |
| 3.18 | Service Zones List | `/service-zones` list/create/show/edit | 🟠 | ☐ | | |
| 3.19 | Service Zone Show | `/service-zones/{id}` → assigned/available technicians (T30 ListEmployees) | 🟠 | ☐ | | |
| 3.20 | Assign/Remove Technician | POST `/service-zones/{id}/assign-technician` + remove | 🟠 | ☐ | | |
| 3.21 | Add/Remove City | POST `/service-zones/{id}/add-city` + remove | 🟡 | ☐ | | |
| 3.22 | Overtime List/Create/Approve | `/overtime` flujos | 🟡 | ☐ | | |
| 3.23 | Special Periods | Vacation/Standby list + cancel | 🟡 | ☐ | | |
| 3.24 | Org Dashboard | `/organization/dashboard` KPIs | 🟡 | ☐ | | |

---

## 4. Inventory BC — Products, Locations, Entries, Movements, Custody, POs, Repairs, Physical Count

| # | Área | Caso de prueba | Prioridad | Resultado | Notas | Fecha |
|---|---|---|---|---|---|---|
| 4.1 | Products CRUD | `/inventory/products` categorización, stock actual | 🟠 | ☐ | | |
| 4.2 | Locations CRUD | `/inventory/locations` jerarquía sites/warehouses | 🟠 | ☐ | | |
| 4.3 | Entries | Ingreso stock (compra/ajuste/devolución), list, detail | 🟠 | ☐ | | |
| 4.4 | Movements | Transferencias entre ubicaciones, list, detail | 🟠 | ☐ | | |
| 4.5 | Custody | Asigna terminal/empleado, devolución, list (T30 ListEmployees) | 🟠 | ☐ | | |
| 4.6 | Purchase Orders | Estados draft→ordered→received→cancelled, items | 🟡 | ☐ | | |
| 4.7 | Repairs | Entrada/salida reparación, técnicos, estados | 🟡 | ☐ | | |
| 4.8 | Physical Count | Conteo físico, ajustes, conciliación | 🟡 | ☐ | | |
| 4.9 | Inv Dashboard | `/inventory/dashboard` stock alerts, movimientos recientes | 🟡 | ☐ | | |
| 4.10 | Import | `/inventory/import` template + upload productos/entradas | 🟡 | ☐ | | |

---

## 5. Expense BC — Cash Advances, Operating Expenses, Reports

| # | Área | Caso de prueba | Prioridad | Resultado | Notas | Fecha |
|---|---|---|---|---|---|---|
| 5.1 | Cash Advances List | `/expenses/cash-advances` paginado + filtros employee/status/date | 🟠 | ☐ | | |
| 5.2 | Cash Advance Create | Solicitud empleado + monto + concepto | 🟠 | ☐ | | |
| 5.3 | Cash Advance Approve/Reject | Botones approve/reject + flash | 🟠 | ☐ | | |
| 5.4 | Cash Advance Rendición | Adjunta comprobantes, monto rendido | 🟠 | ☐ | | |
| 5.5 | Operating Expenses CRUD | Categoría, aprobador, adjuntos | 🟠 | ☐ | | |
| 5.6 | Expense Reports | `/expenses/reports` resumen por empleado/categoría/período + export | 🟡 | ☐ | | |
| 5.7 | Expense Dashboard | `/expenses/dashboard` KPIs + pendientes aprobación | 🟡 | ☐ | | |

---

## 6. Support BC — Tickets

| # | Área | Caso de prueba | Prioridad | Resultado | Notas | Fecha |
|---|---|---|---|---|---|---|
| 6.1 | Ticket List | `/support/tickets` paginado + filtros client/status/date | 🟠 | ☐ | | |
| 6.2 | Ticket New | `/support/tickets/new` → cliente → terminals dinámicos (AJAX) | 🟠 | ☐ | | |
| 6.3 | Ticket Create | Service type + descripción + submit | 🟠 | ☐ | | |
| 6.4 | Ticket Show | `/support/tickets/{id}` detail + assign employee (T30 dropdown) | 🟠 | ☐ | | |
| 6.5 | Ticket Status Change | Cambia estado (open→in_progress→resolved→closed) | 🟠 | ☐ | | |
| 6.6 | Ticket Comments | Agrega comentario interno/público | 🟡 | ☐ | | |
| 6.7 | Ticket Edit | `/support/tickets/{id}/edit` modifica descripción, service type | 🟡 | ☐ | | |

---

## 7. Notification BC — Inbox, Preferences, Templates (Gaps T33)

| # | Área | Caso de prueba | Prioridad | Resultado | Notas | Fecha |
|---|---|---|---|---|---|---|
| 7.1 | Inbox List | `/notifications` paginado + filtros read/unread | ⚠️ G‑07 | ☐ | | |
| 7.2 | Mark All Read | Botón "Marcar todas leídas" | ⚠️ G‑07 | ☐ | | |
| 7.3 | Preferences | `/notifications/preferences` CRUD por canal/evento | 🟡 | ☐ | | |
| 7.4 | Templates Admin | `/notifications/templates` CRUD + variables + seed | ⚠️ G‑01/02/04 | ☐ | | |

---

## 8. Location BC — Cities, Provinces, Countries

| # | Área | Caso de prueba | Prioridad | Resultado | Notas | Fecha |
|---|---|---|---|---|---|---|
| 8.1 | Cities CRUD | `/location/cities` jerarquía + búsqueda | 🟡 | ☐ | | |
| 8.2 | Provinces/Countries | CRUD + relaciones | 🟡 | ☐ | | |

---

## 9. Cross-BC / Transversal

| # | Área | Caso de prueba | Prioridad | Resultado | Notas | Fecha |
|---|---|---|---|---|---|---|
| 9.1 | Permisos IsGranted | User sin permiso → 403 / redirect a dashboard | 🔴 | ☐ | | |
| 9.2 | Permisos IsGranted | User con permiso → acceso OK | 🔴 | ☐ | | |
| 9.3 | Navegación Sidebar | Todos los BCs accesibles, active state | 🟠 | ☐ | | |
| 9.4 | Breadcrumbs | Cada show/edit muestra ruta correcta | 🟡 | ☐ | | |
| 9.5 | Dropdown User | Perfil, cambio password, logout | 🟠 | ☐ | | |
| 9.6 | Responsive Mobile | 375×667 → sidebar colapsable, tablas scroll horizontal | 🟡 | ☐ | | |
| 9.7 | Flash Messages | Success/error/warning en create/edit/delete/activate | 🟠 | ☐ | | |
| 9.8 | Validación Required | Campos obligatorios → error inline al submit vacío | 🟠 | ☐ | | |
| 9.9 | Validación Unique | RUC, username, email, identificationNumber duplicados | 🟠 | ☐ | | |
| 9.10 | Validación Email | Formato inválido → error | 🟡 | ☐ | | |
| 9.11 | Validación Enums | Profile/status/serviceType → solo valores válidos | 🟡 | ☐ | | |
| 9.12 | Validación Fechas | Futuro/pasado según campo, formato DD/MM/YYYY | 🟡 | ☐ | | |
| 9.13 | Paginación Límites | page > total → última página / vacío | 🟡 | ☐ | | |
| 9.14 | Paginación limit=0 | Comportamiento (¿todos? ¿error?) | 🟡 | ☐ | | |
| 9.15 | Paginación limit>max | Si hay max configurado → clamp / error | 🟡 | ☐ | | |
| 9.16 | Filtros Combinados | Profile + status + search + serviceZoneId a la vez | 🟠 | ☐ | | |
| 9.17 | Soft Delete | Entidades borradas no en lists; restore si hay UI | 🟡 | ☐ | | |
| 9.18 | Estados Vacíos | "No hay datos" en lists sin resultados | 🟡 | ☐ | | |
| 9.19 | CSRF Tokens | Formularios POST tienen `_token`, expira tras uso | 🟠 | ☐ | | |
| 9.20 | Accesibilidad Básica | Labels en inputs, contrastes, focus visible, alt en imágenes | 🟡 | ☐ | | |

---

## 10. Resumen de Sesión

| Métrica | Valor |
|---|---|
| **Total casos** | 180+ |
| **Ejecutados** | / 180 |
| **Pass** | |
| **Fail** | |
| **Blocked** | |
| **Skip** | |
| **Bugs reportados** | (lista IDs o enlaces a issues) |
| **Known gaps confirmados** | (G‑03 retry, G‑07 inbox filter, G‑10 SQL dashboard, G‑08 special periods, G‑10/11 imports async) |

---

## 11. Bugs / Hallazgos (rellena al encontrar)

| ID | Área | Descripción | Pasos para reproducir | Severidad | Estado |
|---|---|---|---|---|---|
| BUG-001 | | | | 🔴/🟠/🟡 | Open / In Progress / Fixed |
| BUG-002 | | | | | |
| BUG-003 | | | | | |

---

## 12. Firmas

| Rol | Nombre | Firma / Iniciales | Fecha |
|---|---|---|---|
| **Tester** | | | |
| **Revisado por** | | | |
| **Aprobado para release** | | | |

---

*Checklist generado automáticamente — commit `a1dbc78` (T30+T31). Actualiza conforme T32–T35 se completen.*
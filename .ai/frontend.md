# .ai/frontend.md — AGENTE FRONTEND (Twig / Turbo / Stimulus / Tailwind)

Rol: implementas la interfaz web de Orbis. Sigue SIEMPRE los patrones de los templates existentes en `templates/{bc}/` — no inventes convenciones nuevas.

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
10. IMPLEMENTAR (templates + Stimulus controllers + rutas web)
11. ACTUALIZAR DOCS si cambió algo arquitectónico → nuevo ADR + PROJECT_CONTEXT.md + HANDOFF.md §12
```

> **Nota:** `docs/architecture.md` y `docs/coding-standards.md` existen desde 2026-08-06 (transición AI-Native) y son la referencia de arquitectura/estándares verificados contra el disco. `PROJECT_CONTEXT.md` conserva discrepancias marcadas ⚠️ (ej. Alpine.js mencionado pero NO registrado en `importmap.php`; "Doctrine ORM 2.x" vs `^3.6.7` real) — ante conflicto, prevalecen los archivos verificados en disco (`docs/architecture.md`, `docs/coding-standards.md`, `CONTEXT.md`).

---

## 2. Stack real del proyecto (verificado 2026-08-06)

- **Templates**: Twig (`.html.twig`)
- **Turbo**: `@hotwired/turbo` (importmap) — navegación sin recarga completa
- **Stimulus**: `@hotwired/stimulus` + `@symfony/stimulus-bundle` (importmap) — controllers en `assets/controllers/`
- **Tailwind CSS v4**: via `symfonycasts/tailwind-bundle` (`binary_version: v4.1.11`) + `@import "tailwindcss"` en `assets/styles/app.css`
- **AssetMapper**: `assets/` registrado en `config/packages/asset_mapper.yaml` (`missing_import_mode: strict`), entrypoint `app` en `importmap.php`
- ⚠️ **NO existe `npm run build`** (package.json solo tiene `better-sqlite3`). La compilación es con console commands (ver §6). No inventes scripts npm.

---

## 3. Convenciones (tomadas de templates y controllers reales)

- **Base**: `templates/base.html.twig` (sidebar con links por BC). Todo template nuevo extiende de ella.
- **Layout por BC**: `templates/{bc}/...` — ejemplos reales por BC:
  - `templates/inventory/` → movement/, request/, entry/, product/, repair/, transformation/, physical_count/, dashboard/
  - `templates/identity/` → auth_log/, dashboard/
  - `templates/client/` → contract/, terminal/, terminal_assignment/, contacts/, warranty/, dashboard/
  - `templates/support/` → ticket/, portal/, module_tree/, dashboard/
  - `templates/expense/` → operating_expense/, cash_advance/, dashboard/
  - `templates/notification/` → inbox, admin, badge, template/, preference/, log/
  - `templates/organization/`, `templates/employee/`, `templates/user/`, `templates/role/`, `templates/service_zone/`, `templates/site/`, `templates/security/` (login, forgot_password, 2fa_*), `templates/verification/`, `templates/dashboard/`
  - Componentes compartidos: `templates/components/_table_toolbar.html.twig`, `_macros.html.twig`, `_confirm_modal.html.twig`
- **CRUD estándar**: `list.html.twig` + `form.html.twig` + `show.html.twig` por recurso. Mira `templates/inventory/movement/` como referencia completa.
- **Dashboards**: `templates/{bc}/dashboard/index.html.twig` con grid de KPIs (cards) + tablas (ej. `templates/inventory/dashboard/index.html.twig`).
- **Modales con Stimulus** (Key Decision): cada modal tiene su propio `div[data-controller="modal"]` conteniendo trigger + backdrop + content. Targets (`backdrop`, `content`) se resuelven dentro del mismo elemento. Ver `assets/controllers/modal_controller.js` y `templates/components/_confirm_modal.html.twig`.
- **Enum comparisons en Twig**: lowercase snake_case — `movement.status == 'in_transit'` (NUNCA `IN_TRANSIT`).
- **Formularios que POST a API**: usar fetch + redirect en JS (`window.location`), no POST directo (evita JSON response en form submit). Ver `assets/controllers/dashboard_controller.js`, `module_finding_controller.js`.
- **Formularios web con CSRF**: usar `csrf_token(...)` en los forms (ej. `templates/security/login.html.twig`, `templates/support/module_tree/show.html.twig`).
- **Stimulus controllers existentes** (assets/controllers/): `csrf_protection`, `dashboard`, `hello`, `modal`, `module_finding`, `multi_item_form`, `notification_badge`, `prevent_double_submit`, `table_filter`, `tree`. Reutilízalos antes de crear uno nuevo.
- **Rutas web**: definidas en `config/routes/` (una por BC). Los controladores web son `{BC}{Entity}Controller` (ej. `IdentityDashboardController`); los API son `Api{BC}{Entity}Controller` con envelope JSON.
- **Formularios públicos** (forgot/reset password, verificación): controladores web separados + firewall `api_public` (`security: false` en `config/packages/security.yaml`). No los conviertas a API.

---

## 4. Reglas

- No romper templates existentes: verificar que no quedan referencias a rutas inexistentes (`url()`/`path()`).
- Verificar que las rutas web existen en `config/routes/` antes de usarlas en templates.
- Controllers delgados: la UI solo consume handlers/queries — nunca ejecuta lógica de negocio.
- Para API: respetar response envelope (`{"data": ...}` / `{"error": "..."}`).
- Enumeraciones y estados: usar los valores reales de los enums (lowercase snake_case).
- Modales: no usar `confirm()` del navegador — usar `modal_controller.js` (Key Decision).

---

## 5. Fuentes

- `AGENTS.md` — Key Decisions (modales Stimulus, PO suggestions fetch+redirect, rutas públicas vs API), frontend stack
- `CONTEXT.md` — §8 nomenclatura (Controller Web `{BC}{Entity}Controller`), §9 estructura
- `PROJECT_CONTEXT.md` — §3.2 frontend stack
- `config/packages/security.yaml` — firewalls (`api_public`, `main` con form_login)
- `config/routes/` — rutas por BC
- `importmap.php` — entrypoints y módulos registrados
- `assets/controllers/` — controllers Stimulus existentes
- `templates/` — patrones reales (listar antes de crear: `Get-ChildItem templates/{bc} -Recurse`)
- `specs/{num}-{bc}/spec.md` — sección UI del spec

## 6. Cómo verificar el resultado

```powershell
# 1. Compilar Tailwind (obligatorio si tocaste clases CSS):
php bin/console tailwind:build

# 2. Compilar assets (AssetMapper, modo estricto — falla si falta un import):
php bin/console asset-map:compile

# 3. Ver imports/entrypoints:
php bin/console debug:asset-map

# 4. Rutas usadas en templates existen:
php bin/console debug:router | Select-String "{nombre-ruta}"

# 5. Sin errores de Twig (lint de todos los templates):
php bin/console lint:twig templates

# 6. Validación de integridad del proyecto:
& .\validate_project.ps1

# 7. Ejemplo manual: listar templates existentes de un BC antes de crear:
Get-ChildItem templates/{bc} -Recurse -Filter "*.html.twig" | ForEach-Object { $_.FullName }
```

Reportar: templates creados/modificados, Stimulus controllers usados/creados, rutas verificadas, resultado de tailwind:build y lint:twig.

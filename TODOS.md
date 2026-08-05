# Orbis — Task Tracking

**Branch:** `001-identity-spec`  
**Current session tool:** Claude Code (alternating with opencode due to free tier limits)

---

## 🔄 Cross-Tool Handoff Protocol

When switching between Claude Code and opencode:

```bash
# BEFORE switching tools:
git add -A
git commit -m "WIP: checkpoint before tool switch - $(date +%H:%M)"
git push origin 001-identity-spec

# AFTER returning:
git pull origin 001-identity-spec
cat HANDOFF.md
cat TODOS.md
```

---

## 📋 Current Tasks

### Priority 1 (P1) — Critical for Identity BC Dashboard

| # | Task | Owner | Status | Tool |
|---|------|-------|--------|------|
| 1 | Create Identity BC dashboard web controller (`IdentityDashboardController`) | Auth Agent | ⏳ pending | Claude Code |
| 2 | Create `templates/identity/dashboard/index.html.twig` | UI Agent | ⏳ pending | Claude Code |
| 3 | Implement `IdentityDashboardMetricsHandler` with KPIs | Analytics Agent | ⏳ pending | Claude Code |
| 4 | Seed permissions: `identity.view_dashboard` | Auth Agent | ⏳ pending | Claude Code |
| 5 | Add 6 KPIs for Identity BC (see AGENTS.md F.1.1) | Analytics Agent | ⏳ pending | Claude Code |

### Priority 2 (P2) — Reports P1

| # | Task | Owner | Status | Tool |
|---|------|-------|--------|------|
| 1 | Create `ClientContractExpiringReportGenerator` | Reporting Agent | ✅ done | opencode |
| 2 | Create `ClientTerminalByClientReportGenerator` | Reporting Agent | ✅ done | opencode |
| 3 | Implement download endpoint for both reports | Backend Agent | ✅ done | Both |

### Priority 3 (P3) — Importers P1

| # | Task | Owner | Status | Tool |
|---|------|-------|--------|------|
| 1 | Implement `ClientImporter` (CSV/Excel) | Importer Author | ✅ done | opencode |
| 2 | Implement `TerminalImporter` (CSV/Excel) | Importer Author | ✅ done | opencode |
| 3 | Implement `EmployeeImporter` (CSV/Excel) | Importer Author | ✅ done | opencode |

---

## 📊 Progress Tracking

### By Agent Type

```
┌─────────────────────────────────────────────────────────┐
│ AUTH AGENT (authentication-agent)                     │
│   - RegisterEmergencyPartUsage wired                    │
│   - RevokePermissionFromRole wired                      │
│   - ActivateUser wired                                    │
│                                                         │
│ UI AGENT (ui-agent)                                     │
│   - ATM Module Tree web UI complete                     │
│   - SparePart selector modal implemented                │
│                                                         │
│ REPORTING AGENT (reporting-agent)                       │
│   - 7 report generators identified (P1, P2, P3)          │
│                                                         │
│ INDICATOR AGENT (analytics-agent)                       │
│   - DashboardMetricsHandler exists (Shared)             │
│   - 6 new KPIs needed for Identity BC                   │
└─────────────────────────────────────────────────────────┘
```

### By Bounded Context

| BC | Spec Status | Tests | Dashboard | Reports | Importers |
|----|-------------|-------|-----------|---------|-----------|
| Identity | ✅ Complete | 77 files | ⏳ WIP | ❌ None | ❌ None |
| Client | ✅ ~97% | 80 files | ✅ Partial | ❌ P1/P2 | ✅ P1 |
| Organization | ✅ ~99% | 60 files | ✅ Web exists | ⏳ P2 | ✅ P1 |
| Support | ✅ ~98% | 89 files | ⏳ Expand | ⏳ P2 | ❌ None |
| Inventory | ✅ ~96% | 96 files | ✅ API-only | ⏳ P1/P3 | ✅ P1/P2 |
| Expense | ✅ 100% | 30 files | ✅ Web | ✅ P1 | ⏳ P3 |
| Location | ✅ 100% | 7 files | — | — | — |
| Notification | ✅ ~95% | 8 files | ✅ Templates | — | — |

---

## 🚩 Blockers / Open Issues

| Issue | Location | Impact | Status |
|-------|----------|--------|--------|
| #75 | MFA backup code verification in API | Security feature for login | ✅ Done (recovery route + routes exempt in MfaSubscriber) |
| #70 | Seed missing permissions | Dashboards/reports can't be accessed | ✅ Done (identity.view_dashboard, client.report etc. seedeados) |
| #42 | bcrypt vs argon2 decision | Password hashing implementation | ❓ Pending decision |

---

## 📝 Next Actions

1. **[BACKEND AGENT]** Verificar wiring: `TerminalsByClientReportGenerator` tiene caller (`ClientReportController::terminalsByClient`) ✅
2. **[QA-REVIEWER]** Commit del estado actual (WIP seeds + report) y actualizar AGENTS.md counts
3. **[AUTH AGENT]** Revisar flujo MFA recovery con backup codes (route `mfa_recovery`)
4. **[ANALYTICS AGENT]** Reportes restantes por prioridad según AGENTS.md F.2.2

---

## 📦 Environment Checkpoints

| Time | Tool | Commit | Notes |
|------|------|--------|-------|
| 14:00 | Claude Code | HEAD | Working on dashboard |
| -- | opencode | -- | Terminales por cliente report done; WIP seeds sin commit |
| -- | Claude Code | -- | Return checkpoint |

---

## 🔗 Related References

- `AGENTS.md` — Agent roles, conventions, patterns
- `specs/001-identity-spec/` — Identity BC specifications
- `specs/007-expense/` — Expense reports P1
- `specs/009-cross-bc/` — Cross-BC integration tests
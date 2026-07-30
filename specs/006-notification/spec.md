# Feature Specification: Notification

**Spec path**: `specs/006-notification/spec.md`
**Created**: 2026-06-17
**Status**: In Development (~60% implementado)

---

## 1. Descripción General

El Bounded Context **Notification** gestiona el envío de notificaciones a través de múltiples canales. Implementación actual es esqueleto: canales definidos, entidades creadas, pero sin ORM mappings ni integración real con otros BCs.

---

## 2. Business Rules

| # | Regla |
|---|-------|
| NO‑01 | **Fire-and-forget**: Las notificaciones se envían sin esperar confirmación. |
| NO‑02 | **Retención 3 meses**: Los logs de notificaciones se limpian después de 3 meses. |
| NO‑03 | **Canales**: In-App, Email, SMS, WhatsApp (MVP: solo log). |
| NO‑04 | **Contactos resueltos desde BC origen**: via tagged resolvers (UserContact, ClientContact, OrganizationContact). |

---

## 3. Entities

| Entidad | Descripción |
|---------|-------------|
| `NotificationTemplate` | Plantilla de notificación por canal |
| `NotificationPreference` | Preferencias por usuario/rol/canal |
| `NotificationLog` | Log de envío (retención 3 meses) |

---

## 4. Commands

| Comando | Descripción |
|---------|-------------|
| `SendNotification` | Envía notificación (resuelve contactos, renderiza template, envía por canal) |
| `CreateNotificationTemplate` | Crea plantilla |
| `UpdateNotificationTemplate` | Actualiza plantilla |
| `MarkNotificationRead` | Marca como leída (in-app) |
| `CleanupOldNotifications` | Limpia logs > 3 meses |

---

## 5. Cross-BC

| BC | Interacción |
|----|-------------|
| **Support** | SLA events → Notification (cuando esté integrado) |
| **Identity** | Eventos de seguridad → notificación al usuario |
| **Organization** | Eventos de empleado → notificación |

---

## 6. Known Gaps

| # | Gap | Prioridad |
|---|-----|-----------|
| G‑01 | **ORM mappings**: Las 3 entidades tienen `.orm.xml` + DBAL types registrados ✅ | 🔴 Cerrado |
| G‑02 | **Integración cross-BC**: Los event listeners están definidos pero no conectados a handlers reales | Alta |
| G‑03 | **WhatsApp**: Solo stub Logger. Twilio/WATI reservado para producción | Media |
| G‑04 | **Tests**: 8 test files. Coverage básico | Media |

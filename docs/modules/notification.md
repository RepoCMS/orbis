# Module: Notification

## Overview

The **Notification** Bounded Context manages system notifications, notification templates, delivery channels, and user preferences. It integrates with all other BCs that need to send notifications (Identity for auth events, Support for ticket status changes, Inventory for low stock alerts, etc.).

## Core Responsibilities

- Notification template management
- Multi-channel delivery (email, SMS, push, database)
- Delivery status tracking (logs)
- User notification preferences
- Retry logic for failed notifications
- Scheduled notification processing
- Cleanup of old notifications

## Status

- **Progress:** ~60%
- **Tests:** ~8 files
- **Reports:** None
- **Importers:** None
- **Dashboard:** Templates exist

## Domain Model

### Aggregate: NotificationTemplate

| Field | Type | Description |
|-------|------|-------------|
| `id` | `NotificationTemplateId` (ULID) | Unique identifier |
| `code` | `string` | Template code |
| `channel` | `NotificationChannel` | `email` / `sms` / `push` / `database` |
| `subject` | `?string` | Subject (email) |
| `content` | `string` | Content template |
| `isActive` | `bool` | Active status |

### Aggregate: NotificationLog

Tracks every notification sent with status, recipient, channel, and timestamps.

### Aggregate: NotificationPreference

User preferences per channel and template.

## Commands

- `SendNotification` — Send a notification
- `CreateNotificationTemplate` — Create template
- `UpdateNotificationTemplate` — Update template
- `MarkNotificationRead` — Mark as read
- `MarkAllNotificationsRead` — Mark all read
- `RetryNotification` — Retry failed notification
- `UpdateNotificationPreference` — Update user preference
- `CleanupOldNotifications` — Cleanup (cron)

## Queries

- `ListNotificationTemplates` — List templates
- `ListNotificationLogs` — List delivery logs
- `ListInboxNotifications` — User inbox
- `CountUnreadNotifications` — Unread count
- `ListNotificationPreferences` — User preferences

## Cross-BC Integrations

| BC | Direction | Interaction |
|----|-----------|-------------|
| All BCs | Write | All BCs dispatch notifications via events |
| Identity | Read | User email/SMS preferences |
| Shared Kernel | Both | Event infrastructure |

## Pending Gaps

| Gap | Priority |
|-----|----------|
| Cross-BC wiring (pending) | High |
| Spec alignment (~95% target) | High |
| Test coverage expansion | Medium |

## API Endpoints

| Method | Route | Permission |
|--------|-------|------------|
| GET | `/api/v1/notifications/inbox` | `notifications.view` |
| POST | `/api/v1/notifications/{id}/read` | `notifications.update` |
| GET | `/api/v1/notifications/templates` | `notification_templates.view` |
| POST | `/api/v1/notifications/templates` | `notification_templates.create` |

---

*Note: Notification BC currently has significant gaps between spec progress and AGENTS.md claims (~95% vs ~60% actual). See CONTEXT.md section 2 for details.*
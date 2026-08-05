# Orbis — Data & Analytics Strategy

> **Origen:** extraído de AGENTS.md Apéndice F (2026-08-05) — referencia bajo demanda para dashboards, reportes e importers.
> **Estado real de implementación:** ver `CONTEXT.md` §2 (por BC), §4 (reportes), §5 (importers).

## Overview

Orbis provee tres capacidades analíticas:
1. **Dashboards** — KPIs en tiempo real por BC (web + API)
2. **Reportes** — Exportaciones Excel/PDF programadas o bajo demanda
3. **Importers** — Carga masiva de datos vía CSV/Excel

Principios:
- **Read models separados**: toda lectura analítica usa DBAL directo (nunca carga entidades del Domain)
- **Cada BC dueño de sus dashboards, reportes e importers**
- **Async para importers grandes** (Messenger) o sync batch para <100 filas
- **Librerías**: PhpSpreadsheet (Excel), DomPDF (PDF), Symfony Serializer (CSV)
- **SIEMPRE** usar el patrón `ViewScopeResolver` para filtrar datos por perfil

---

## F.1 Dashboard KPIs por BC

### F.1.1 Nivel Estratégico (Cross-BC — Shared Dashboard)
| KPI | Fuente | Query | Prioridad |
|-----|--------|-------|-----------|
| Tickets abiertos hoy | `tickets` WHERE status NOT IN terminal | COUNT + GROUP BY service_type | P1 |
| Tickets creados hoy vs ayer | `tickets` WHERE DATE(scheduled_at) | COUNT con diff | P1 |
| Técnicos disponibles/ocupados | `employees` + `tickets` | LEFT JOIN con conteo de asignaciones activas | P1 |
| Reparaciones en curso | `repairs` WHERE status IN (pending, in_progress) | COUNT | P1 |
| Alertas de stock mínimo | `stock_minimums` LEFT JOIN `nonserialized_stock` | Filas donde quantity < alert_threshold | P1 |
| SLA breach rate (últimos 30d) | `tickets` con slaDeadlineAt < NOW() AND status != closed | COUNT / total tickets | P2 |
| Movimientos de stock hoy | `stock_movements` WHERE DATE(created_at) | COUNT + diff vs ayer | P2 |

**Ya implementado** en `DashboardMetricsHandler` (DBAL).

### F.1.2 Nivel Táctico (Per-BC)

**Identity BC**
| KPI | Query | Justificación |
|-----|-------|---------------|
| Usuarios activos / bloqueados / desactivados | `users` GROUP BY status + locked_until | Visibilidad de seguridad |
| Intentos de login fallidos (últimos 7d) | `auth_logs` WHERE event = LOGIN_FAILED AND created_at > 7d | Detección de ataques |
| Tasa de adopción MFA | `user_mfa_configs` WHERE is_enabled = true / total users | Cumplimiento seguridad |
| Usuarios por rol | `users` JOIN `roles` GROUP BY role_id | Distribución de perfiles |
| Eventos de seguridad recientes | `auth_logs` últimas 24h por tipo | Auditoría en vivo |
| Sesiones activas (refresh tokens no expirados) | `refresh_tokens` WHERE expires_at > NOW() AND revoked_at IS NULL | Concurrencia |

**Client BC**
| KPI | Query | Estado |
|-----|-------|--------|
| Clientes activos / inactivos | `clients` GROUP BY status | ✅ Existente |
| Terminales por estado | `terminals` GROUP BY status | ✅ Existente |
| Contratos por vencer (30/60/90 días) | `contracts` WHERE end_date BETWEEN NOW() AND NOW()+90d | ❌ No implementado |
| Top clientes por terminales | `terminals` GROUP BY client_id ORDER BY COUNT DESC | ❌ No implementado |
| Contactos activos vs inactivos | `contacts` GROUP BY status | ❌ No implementado |
| Terminales sin asignación | `terminals` LEFT JOIN `terminal_assignments` WHERE assignment IS NULL | ❌ No implementado |
| Crecimiento mensual de clientes | `clients` GROUP BY MONTH(created_at) | ❌ No implementado |

**Organization BC**
| KPI | Query | Estado |
|-----|-------|--------|
| Empleados por perfil | `employees` GROUP BY profile | ✅ API existente |
| Empleados activos / vacaciones / standby | `employees` GROUP BY status | ✅ API existente |
| Distribución por zona de servicio | `employees` JOIN `service_zones` GROUP BY zone | ✅ API existente |
| Horas extras por período | `overtime_records` WHERE status = approved GROUP BY employee | ❌ No implementado |
| Técnicos disponibles ahora | `employees` WHERE profile = technician AND status = active LEFT JOIN tickets activos | P1 |
| Antigüedad promedio por perfil | `employees` GROUP BY profile AVG(DATEDIFF) | ❌ No implementado |
| Vacaciones programadas (próximos 30d) | `vacation_requests` WHERE status = approved AND start_date BETWEEN NOW() AND NOW()+30d | ❌ No implementado |

**Support BC**
| KPI | Query | Estado |
|-----|-------|--------|
| Tickets abiertos por estado | `tickets` GROUP BY status | ✅ Existente |
| Tickets por tipo de servicio | `tickets` GROUP BY service_type | ✅ Existente |
| SLA breach count | `tickets` WHERE slaDeadlineAt < NOW() AND status NOT IN (closed, cancelled) | ❌ No implementado |
| Tiempo promedio de resolución | AVG(TIMEDIFF(closedAt, createdAt)) por service_type | ❌ No implementado |
| Tickets completados hoy/esta semana | `tickets` WHERE closedAt BETWEEN | ❌ No implementado |
| Técnicos con más tickets asignados | `ticket_assignments` GROUP BY employee_id | ❌ No implementado |
| Tiempo promedio por técnico | `tickets` JOIN `ticket_assignments` GROUP BY employee_id | ❌ No implementado |
| Reaperturas (tickets reabiertos) | COUNT WHERE reopened = true / total closed | ❌ Calidad de servicio |
| Tickets por cliente (top 10) | `tickets` GROUP BY client_id | ❌ No implementado |
| Tasa de cancelación | COUNT WHERE status = cancelled / total por período | ❌ No implementado |

**Inventory BC**
| KPI | Query | Estado |
|-----|-------|--------|
| Productos en catálogo | `products` WHERE deleted IS NULL COUNT | ✅ API existente |
| Stock total (serializado) | `serialized_stock` WHERE status = available COUNT | ❌ No implementado |
| Stock total (no serializado) | `nonserialized_stock` WHERE status = available SUM(quantity) | ❌ No implementado |
| Alertas activas | `stock_minimums` LEFT JOIN actual stock | ✅ API existente |
| Pendientes de conciliación | `nonserialized_stock` WHERE status = pending_review COUNT | ✅ API existente |
| Reparaciones en curso | `repairs` WHERE status IN (pending, in_progress) | ✅ API existente |
| Órdenes de compra abiertas | `purchase_orders` WHERE status = open | ✅ API existente |
| Movimientos por tipo (hoy) | `stock_movements` GROUP BY type | ❌ No implementado |
| Stock por ubicación | `serialized_stock` + `nonserialized_stock` GROUP BY location | ❌ No implementado |
| Valor estimado de inventario | SUM(stock × precio) — requiere campo price en Product | ❌ P3 (requiere spec) |
| Partes más usadas (top 10) | `repair_consumables` GROUP BY product_code ORDER BY SUM(quantity) DESC | ❌ No implementado |
| Tiempo promedio de reparación | AVG(TIMEDIFF(completedAt, startedAt)) | ❌ No implementado |

**Expense BC**
| KPI | Query | Estado |
|-----|-------|--------|
| Gastos pendientes de aprobación | `operating_expense` WHERE status = submitted COUNT | ✅ Existente |
| Adelantos outstanding | `cash_advance` WHERE status = outstanding COUNT | ✅ Existente |
| Adelantos vencidos (>3 días) | `cash_advance` WHERE status = outstanding AND given_at < NOW()-3d | ✅ Existente |
| Total de gastos del mes | `expense_item` WHERE status = approved AND MONTH = actual SUM(amount) | ❌ No implementado |
| Gastos por tipo | `expense_item` GROUP BY type | ❌ No implementado |
| Total por técnico (mes) | `expense_item` JOIN `operating_expense` GROUP BY employee_id | ❌ No implementado |
| Adelantos por técnico | `cash_advance` GROUP BY employee_id | ❌ No implementado |
| Liquidaciones pendientes | `cash_advance` WHERE status = outstanding AND settlement_direction IS NOT NULL | ❌ No implementado |

---

## F.2 Reportes Excel/PDF

### F.2.1 Arquitectura

```
┌──────────────────────────────────────────────────────────────┐
│  Application/Report/{Nombre}ReportGenerator.php              │
│    → implements ReportGeneratorInterface                     │
│    → accepts query/filter parameters                         │
│    → uses DBAL Connection read-only                          │
│    → returns array of arrays (raw data)                      │
└──────────────────────────────────────────────────────────────┘
                               │
                               ▼
┌──────────────────────────────────────────────────────────────┐
│  Infrastructure/Export/{Excel|Pdf}ExportService.php          │
│    → PhpSpreadsheet (Xlsx) or DomPDF/TCPDF (PDF)             │
│    → recibe datos + config (headers, widths, styles)         │
│    → devuelve Symfony BinaryFileResponse / streamed file     │
└──────────────────────────────────────────────────────────────┘
                               │
                               ▼
┌──────────────────────────────────────────────────────────────┐
│  Presentation/Http/{Nombre}ExportController.php              │
│    → #[Route('GET /{bc}/report/{nombre}.{_format}')]        │
│    → #[IsGranted('{bc}.report')]                             │
│    → llama ReportGenerator → ExportService → Response        │
└──────────────────────────────────────────────────────────────┘
```

**Reglas:**
- `ReportGenerator` vive en Application (orquesta datos, NO sabe de formatos)
- `ExportService` vive en Infrastructure (PhpSpreadsheet/DomPDF)
- Controller es delgado: valida permiso, obtiene datos, delega exportación
- **Nunca** instanciar entidades de dominio en reportes — solo DBAL
- Todos los reportes deben exponerse como endpoint descargable y como consola (cron)

### F.2.2 Reportes Priorizados

| Reporte | BC | Formato | Contenido | Prioridad |
|---------|----|---------|-----------|-----------|
| Inventario valorizado | Inventory | Excel | Producto, código, ubicación, cantidad, valor unitario, valor total | P1 |
| Movimientos de stock | Inventory | Excel/PDF | Fecha, tipo, origen, destino, producto, serial/cantidad, usuario | P1 |
| Reporte de técnicos (Support) | Support | Excel/PDF | Técnico, tickets completados, tiempo promedio, SLA cumplidos | P1 |
| Gastos por período | Expense | Excel | Fecha, técnico, tipo, monto, estado, evidencia | P1 |
| Adelantos pendientes | Expense | Excel | N° adelanto, técnico, monto, fecha, días vencido | P1 |
| Personal activo | Organization | Excel/PDF | Nombre, perfil, zona, fecha ingreso, estado | P1 |
| Vacaciones programadas | Organization | Excel/PDF | Empleado, perfil, fecha inicio, fecha fin, días | P2 |
| Horas extras por período | Organization | Excel | Empleado, fecha, horas, motivo, aprobado por | P2 |
| Devolución por renuncia (valorizado) | Inventory | Excel/PDF | Producto, serial, estado (OK/faltante/dañado), valor estimado | P2 |
| Tickets por cliente | Support | Excel | Cliente, N° ticket, tipo, fecha, estado, técnico, SLA | P2 |
| Historial de eventos de seguridad | Identity | Excel/PDF | Fecha, usuario, evento, IP, user-agent | P2 |
| Contratos por vencer | Client | Excel/PDF | Cliente, tipo contrato, inicio, fin, días restantes, contacto | P3 |
| Terminales por cliente | Client | Excel | Cliente, serial, tipo, modelo, estado, última asignación | P3 |
| Conteos de inventario | Inventory | Excel | Producto, ubicación, cantidad sistema, cantidad contada, diferencia | P3 |

### F.2.3 Dependencias Composer

```json
"require": {
    "phpoffice/phpspreadsheet": "^3.0",  // Excel (Xlsx, Csv)
    "dompdf/dompdf": "^3.0"              // PDF (alternativa: tecnickcom/tcpdf)
}
```

### F.2.4 Reportes Programados (Cron)

- Reportes que se generan automáticamente → `Console/Command/{GenerateNombreReportCommand}`
- Se almacenan en `var/reports/{bc}/{nombre}_{Y-m-d}.{xlsx|pdf}`
- Se envían por email usando `Notification BC` (cuando esté lista)
- Cleanup: retention 90 días via `console command`

---

## F.3 Importers (Carga Masiva)

### F.3.1 Arquitectura

```
Sync (< 100 filas):
┌──────────┐    ┌────────────────────────────┐    ┌───────────────┐
│ Controller│───►│ Import{Recurso}Handler      │───►│ Entity       │
│ (file     │    │ → valida cabeceras          │    │ Repository   │
│  upload)  │    │ → parsea filas              │    │ (persist)    │
└──────────┘    │ → Domain Events por fila     │    └───────────────┘
                │ → errores → ImportResult      │
                └──────────────────────────────┘

Async (≥ 100 filas):
┌──────────┐    ┌──────────────┐    ┌────────────────┐    ┌───────────────┐
│ Controller│───►│ Import{BC}   │───►│ Handler        │───►│ Entity       │
│ (file     │    │ Message      │    │ (Messenger)    │    │ Repository   │
│  upload)  │    │ (file path)  │    │ → batch de 50  │    │ (persist)    │
└──────────┘    └──────────────┘    │ → Domain Events │    └───────────────┘
                                    │ → errores log   │
                                    │ → notificación  │
                                    └────────────────┘
```

### F.3.2 Patrón para cada Importer

```
src/app/{BC}/
  Application/
    Command/Import{Recurso}/
      Import{Recurso}Command.php          (final readonly class)
      Import{Recurso}Handler.php          (final readonly class)
      Import{Recurso}Message.php          (async, implements AsyncMessageInterface)
      Import{Recurso}MessageHandler.php   (Messenger handler)
      ImportResult.php                    (response: imported, errors, skipped)
  Infrastructure/
    Import/
      {Recurso}FileParser.php             (CSV/Excel → array)
      {Recurso}RowValidator.php           (fila individual → errors[])
    Console/
      Import{Recurso}Command.php          (CLI: php bin/console {bc}:import:{recurso} file.csv)
  Presentation/
    Http/
      Api{BC}ImportController.php         (POST /api/v1/{bc}/import/{recurso})
```

### F.3.3 Importers Priorizados

| Importer | BC | Formato | Datos por fila | Prioridad |
|----------|----|---------|----------------|-----------|
| Clientes | Client | CSV/Excel | RUC, nombre, dirección, email contacto, teléfono | P1 |
| Terminales | Client | CSV/Excel | Serial, cliente (RUC), tipo, modelo, ubicación | P1 |
| Productos | Inventory | CSV/Excel | Código, nombre, tracking type, descripción | P1 ✅ |
| Stock serializado | Inventory | CSV/Excel | Producto (código), serial, ubicación (ULID) | P1 ✅ |
| Stock no serializado | Inventory | CSV/Excel | Producto (código), ubicación (ULID), cantidad | P1 ✅ |
| Empleados | Organization | CSV/Excel | Identificación, nombre, email, perfil, zona | P1 |
| Usuarios | Identity | CSV/Excel | Username, email, rol (nombre), empleado vinculado | P2 |
| Modelos de terminal | Client | CSV/Excel | Fabricante, modelo, descripción | P2 |
| Partes de modelo | Client | CSV/Excel | Modelo, nombre, partNumber, nivel, padre | P2 |
| Órdenes de compra | Inventory | CSV/Excel | Referencia, proveedor, items (código + cantidad) | P2 |
| Gastos operativos | Expense | CSV/Excel | Empleado, tipo, monto, fecha, evidencia (path) | P3 |
| Movimientos de stock | Inventory | CSV/Excel | Tipo, producto, serial/cantidad, origen, destino | P3 |

### F.3.4 Reglas de Implementación

1. **Parser validación**: Verificar cabeceras CSV coinciden con plantilla esperada
2. **RowValidator**: Validar cada fila independientemente (errores no bloquean el batch)
3. **Transaccional**: Cada fila es una transacción individual (no falla todo el lote)
4. **Result**: Devolver `ImportResult{int imported, int errors, int skipped, array errorDetail}`
5. **Domain Events**: Publicar evento por cada fila importada (ej. `ClientImported`, `TerminalImported`)
6. **Dry run**: Modo preview que parsea y valida sin persistir
7. **Plantilla descargable**: Endpoint GET `/api/v1/{bc}/import/{recurso}/template` que devuelve CSV de ejemplo
8. **Para async** (≥100 filas): Subir archivo → almacenar en `var/imports/` → dispatch Message → response 202 con job ID → endpoint de status

### F.3.5 Validaciones Cross-BC

| Importer | Validación cross-BC |
|----------|--------------------|
| Clientes | `cityId` existe en Location BC |
| Terminales | `clientId` existe y está activo en Client BC |
| Empleados | `userId` existe en Identity BC (si se vincula), `serviceZoneId` existe en Organization BC |
| Usuarios | `roleId` existe, username único, email único |
| Productos | `productCodeBySupplier` único en Inventory BC |
| Stock | `productId` existe en Inventory BC, `locationId` existe |

---

## F.4 Estándares de Implementación

### F.4.1 Dashboard
- Handler SIEMPRE usa DBAL Connection directo (nunca repositorios del Domain)
- Response DTO: `final readonly class` con propiedades tipadas
- Controller web: Twig template con grid de KPIs (cards) + tablas + barras
- Controller API: `{"data": {...}}` envelope
- Sufijo: `{BC}DashboardController` (web), `Api{BC}DashboardController` (API)
- Template: `templates/{bc}/dashboard/index.html.twig`
- Tests: Handler test con datos reales (insert + assert query results)
- **Performance**: Dashboard query debe ejecutarse en <500ms. Usar índices compuestos en tablas grandes. Cachear queries de dashboards con `#[Cache]` si es necesario.

### F.4.2 Reporte
- Generador: `{Nombre}ReportGenerator` en Application layer
- Export: `{Excel|Pdf}ExportService` en Infrastructure (Servicio genérico reutilizable)
- Controller: `{Nombre}ExportController` con `#[Route]` y `#[IsGranted]`
- Formato por extensión en URL: `/report/{nombre}.xlsx` o `/report/{nombre}.pdf`
- Stream response (no cargar archivo completo en memoria para grandes datasets)
- Tests: Generator test con DBAL, Export test con archivos temporales

### F.4.3 Importer
- Parser: `{Recurso}FileParser` — clase aparte, inyectable, testeable
- Validator: `{Recurso}RowValidator` — valida una fila, devuelve errores
- Handler: `Import{Recurso}Handler` — orquesta parse → validate → persist
- Response: `ImportResult` con conteos y detalle de errores
- Tests: Handler test con CSV/Excel de prueba (archivos fixtures en `tests/Fixtures/Import/`)
- **No usar** `LOAD DATA INFILE` de MySQL — siempre pasar por Domain para eventos y validaciones
- Límite sync: 100 filas. Sobre eso → async Message
- Archivos subidos se guardan en `var/imports/{bc}/{recurso}/{Y-m-d}/` y se limpian a los 7 días

### F.4.4 Seguridad
| Aspecto | Regla |
|---------|-------|
| Dashboard | `#[IsGranted('{bc}.view')]` |
| Reporte | `#[IsGranted('{bc}.report')]` — seedear nuevo permiso `{bc}.report` |
| Import | `#[IsGranted('{bc}.import')]` — seedear nuevo permiso `{bc}.import` |
| Template descargable | Público (no requiere autenticación) |
| Archivos subidos | Validar extensión (.csv, .xlsx), tamaño máx 10MB, scan antivirus (si disponible) |
| Scoping | Reportes e importers deben respetar `ViewScopeResolver` |

---

## F.5 Resumen de Dependencias a Instalar

```json
"require": {
    "phpoffice/phpspreadsheet": "^3.0",
    "dompdf/dompdf": "^3.0"
}
```

No hay otras dependencias externas. CSV usa `Symfony Serializer\Encoder\CsvEncoder` (ya disponible). Excel y PDF son las únicas librerías nuevas.

---

## F.6 Prioridad de Ejecución

| Fase | Qué | Por qué |
|------|-----|---------|
| 🥇 Fase 1 | Completar dashboards existentes + Identity BC dashboard + Expense BC dashboard | Bajo esfuerzo, alto impacto visual |
| 🥇 Fase 1 | Reportes P1 (Inventario valorizado, Movimientos, Técnicos, Gastos, Personal) | Ya especificados en specs |
| 🥇 Fase 1 | Importers P1 (Clientes, Terminales, Productos, Stock, Empleados) | Carga masiva es necesaria para onboarding |
| 🥈 Fase 2 | Reportes P2 (Vacaciones, Horas extras, Devolución renuncia, Auditoría) | Dependen de Features existentes |
| 🥈 Fase 2 | Importers P2 (Usuarios, Modelos, Partes, Órdenes de compra) | Menor urgencia |
| 🥉 Fase 3 | Reportes P3 | Baja prioridad |
| 🥉 Fase 3 | Importers P3 | Baja prioridad |

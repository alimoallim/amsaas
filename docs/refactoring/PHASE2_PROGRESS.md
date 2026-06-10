# Phase 2 — Invoice Engine Progress

**Status: CLOSED** — 2026-06-03

**Gate:** Phase 1 closed · **Next:** Phase 3 — Payment Engine

| Stream | Status | Notes |
|--------|--------|-------|
| **2A.1** Data model | Done | `MonthlyInvoice` + `InvoiceLineItem` canonical; void/PDF columns |
| **2A.2** InvoiceNumberService | Done | Sequential `{PREFIX}-{YEAR}-{#####}` via locked sequences |
| **2A.3** Generation | Done | `InvoiceConsolidationService` + billing close pipeline |
| **2A.4** Posting / issue | Done | `InvoiceService::issue`, `POST /invoices/{id}/finalize` |
| **2A.5** Void | Done | `InvoiceVoidService` + `POST /invoices/{id}/void` |
| **2A.6** PDF | Done | `GenerateInvoicePdf` listener (queued) |
| **2A.7** Email | Done | `EmailInvoiceToTenant` + `InvoiceIssuedMail` |
| **2A.8** API + resources | Done | Index, show, store, void, finalize, bulk-issue; `MonthlyInvoiceResource` + controls |
| **2B.1** Invoice index | Done | `MonthlyInvoicesWorklist.vue` + billing close entry |
| **2B.2** InvoiceShow | Done | Detail + issue/void/download |
| **2B.3** InvoiceCreate | Done | `InvoiceCreate.vue` + `ManualInvoiceCreateTest` |
| **2B.4** Tenant billing view | Done | `TenantBilling.vue` + `GET /tenants/{id}/billing` |

## Phase 2 exit criteria (from `project_document.md`)

- [x] Invoices generated from approved charges (consolidation + agreement resync on activate)
- [x] PDF produced and emailed on issue (queued listeners; `InvoiceEmailDispatchTest`)
- [x] Invoice state machine tested (`InvoiceStateMachineTest`, `InvoiceVoidTest`, `PaymentRecordingTest`)
- [x] InvoiceIndex, Show, Create, Tenant views operational
- [x] Manual invoice creation works (`ManualInvoiceCreateTest`)

## Key deliverables

- Canonical invoice stack: `MonthlyInvoice` / `InvoiceLineItem` (ADR 001)
- `BillingPipelineService::runMonthlyClose()` — HTTP + `billing:generate-monthly` cron
- `TenantBillingService` — per-tenant AR history
- Worklist exception-first UX with bulk issue

## Commands

```bash
cd laravel && php artisan test --filter=Invoice
cd laravel && php artisan migrate
php artisan billing:generate-monthly --company_id=<uuid> --year=2026 --month=6
```

## Next: Phase 3 — Payment Engine

See `project_document.md` Phase 3 and unquarantine `PaymentsIndex.vue` when 3A is stable.

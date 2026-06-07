# Phase 2 — Invoice Engine Progress

**Started:** 2026-06-05 · **Gate:** Phase 1 closed

| Stream | Status | Notes |
|--------|--------|-------|
| **2A.1** Data model | In progress | `MonthlyInvoice` canonical; void/PDF columns migration |
| **2A.2** InvoiceNumberService | Done | Sequential `{PREFIX}-{YEAR}-{#####}` via locked sequences |
| **2A.3** Generation | Partial | `InvoiceConsolidationService` primary path |
| **2A.4** Posting / issue | Partial | `InvoiceService::issue`, `finalize` endpoint |
| **2A.5** Void | Done (baseline) | `InvoiceVoidService` + `POST /invoices/{id}/void` |
| **2A.6** PDF | Done (baseline) | Line-item template; DOMPDF package added |
| **2A.7** Email | Done (baseline) | Queued `InvoiceIssuedMail` after PDF ready |
| **2A.8** API + resources | In progress | Show with line items + controls ✅ |
| **2B.1** Invoice index | Done (baseline) | `MonthlyInvoicesWorklist.vue` |
| **2B.2** InvoiceShow | Done (baseline) | Detail + issue/void/download |
| **2B.3** InvoiceCreate | Pending | Manual line items |
| **2B.4** Tenant billing view | Pending | |

## Phase 2 exit criteria (from `project_document.md`)

- [ ] Invoices generated from approved charges (consolidation path ✅)
- [ ] PDF produced and emailed on issue
- [ ] Invoice state machine fully tested
- [ ] InvoiceIndex, Show, Create, Tenant views operational
- [ ] Manual invoice creation works

## Commands

```bash
cd laravel && php artisan test --filter=Invoice
cd laravel && php artisan migrate
```

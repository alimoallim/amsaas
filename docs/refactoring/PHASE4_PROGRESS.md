# Phase 4 — Receivables & Collections Progress

**Status: CLOSED** — 2026-06-07

**Gate:** Phase 3 payment MVP · **Goal:** Visibility and action on unpaid balances

| Stream | Status | Notes |
|--------|--------|-------|
| **4.1** Aging report service | Done | `AgingReceivablesService` — current / 1–30 / 31–60 / 61–90 / 90+ |
| **4.1** Aging API + UI | Done | `GET /reports/aging`, CSV export, `ReportsIndex.vue` |
| **4.2** Delinquency tracking | Done | `collections:flag-overdue` daily + `DelinquencyFlag` escalation stages |
| **4.3** SMS/email reminders | Done | `collections:send-reminders` + `CollectionReminderLog` + email queue |
| **4.4** Collections dashboard | Done | Aging + delinquency + bulk/manual remind from Reports |
| **4.5** Notice generation | Done | `CollectionNoticePdfService` — 3 PDF templates by escalation stage |

## Phase 4 exit criteria (from `project_document.md`)

- [x] Aging buckets calculated from `due_date` vs. open `balance_due`
- [x] Report exportable (CSV)
- [x] Overdue invoices automatically flagged
- [x] Reminder system dispatches at correct intervals
- [x] Collections dashboard operational (aging + delinquency + remind + notice PDF)

## API

```bash
GET  /api/v1/reports/aging
GET  /api/v1/reports/aging/export
GET  /api/v1/reports/delinquency
POST /api/v1/reports/delinquency/remind       { "flag_ids": ["<uuid>"] }
POST /api/v1/reports/delinquency/notices      { "flag_id": "<uuid>" }
GET  /api/v1/reports/notices/{id}/download
GET  /api/v1/reports/reminder-logs?tenant_id=<uuid>
```

## Commands

```bash
php artisan collections:flag-overdue --company_id=<uuid>
php artisan collections:send-reminders --company_id=<uuid>
php artisan test --filter=Collections
```

## Next: Phase 5 — Apartment Sales

See `project_document.md` Phase 5. Sales domain is independent from rental billing; shares Apartment, Company, and financial modules only.

Deferred from Phase 4: SMS channel (email-only MVP), per-company reminder schedule UI.

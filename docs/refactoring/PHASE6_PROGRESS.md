# Phase 6 — General Ledger & Accounting Progress

**Status: COMPLETE** — 6.1–6.7 done · 6.2b core wiring done

**Gate:** Phases 1–5 complete · **Goal:** Double-entry accounting layer auto-posting from financial events

See [ACCOUNTING_PILLARS.md](./ACCOUNTING_PILLARS.md) for the four-pillar design map.

| Stream | Status | Notes |
|--------|--------|-------|
| **6.1** Chart of accounts | Done | Standard posting accounts, CRUD API + UI |
| **6.2** Journal entry engine | Done (MVP) | Balanced posting on invoice issue + payment allocation |
| **6.2b** Account wiring | Core done | Payment method → receipt accounts; rental + sale posting — see [PHASE6_ACCOUNTING_WIRING.md](./PHASE6_ACCOUNTING_WIRING.md) |
| **6.3** General ledger | Done | Per-account history, running balance, CSV export, UI |
| **6.4** Trial balance | Done | Period aggregation, balance check, period close, CSV export, UI |
| **6.5** Income statement | Done | Revenue/expense P&amp;L by billing month, CSV + PDF export, UI |
| **6.6** Balance sheet | Done | Point-in-time assets/liabilities/equity, equation check, CSV + PDF, UI |
| **6.7** Audit log dashboard | Done | Financial mutation viewer, filters, CSV export, UI |

## 6.7 exit criteria

- [x] `GET /api/v1/financial-audit` — unified timeline (audit_logs + journal entries)
- [x] Filters: date range, entity type, action
- [x] `LogsActivity` on Payment, MonthlyInvoice, JournalEntry, Account
- [x] `GET /api/v1/financial-audit/export` CSV
- [x] `FinancialAuditIndex.vue` — filterable worklist with pagination
- [x] Tenant isolation

## API

```bash
GET    /api/v1/financial-audit?from=2026-06-01&to=2026-06-30&entity_type=payment&action=created
GET    /api/v1/financial-audit/export?from=2026-06-01&to=2026-06-30
```

## Tests

```bash
docker compose exec -T laravel-engine php artisan test --filter=Accounting
```

## Phase 6 complete — optional follow-ups

- [x] 6.2b items 8–11 in [PHASE6_ACCOUNTING_WIRING.md](./PHASE6_ACCOUNTING_WIRING.md) (deposits, charge-type line mapping, payment UI, chart re-seed)
- [ ] Credit notes / journal reversals
- [ ] Backfill audit logs for pre-6.7 journal entries (optional)

# Phase 3 — Payment Engine Progress

**Status: MVP CLOSED** — 2026-06-07 (EVC webhooks / refunds deferred)

**Gate:** Phase 2 closed · **Depends on:** issued invoices with open balance

| Stream | Status | Notes |
|--------|--------|-------|
| **3A.1** Payment model + FIFO allocation | Done | `PaymentService`, `PaymentAllocation` |
| **3A.2** Record payment API | Done | `POST /payments`, tenant balance endpoint |
| **3A.3** Payment index API | Done | Flat `data` array (nested Resource fix) |
| **3A.4** Payment show API | Done | `GET /payments/{id}`, `PaymentResource` + controls |
| **3A.5** Payments worklist UI | Done | `PaymentsIndex.vue` — record modal, FIFO messaging |
| **3A.6** Payment receipt UI | Done | `PaymentShow.vue` — allocations + invoice links |
| **3A.7** Tenant balance in record flow | Done | Open AR + pending utilities surfaced |
| **3B.1** EVC / webhook | Pending | Route stub commented in `api.php` |
| **3B.2** Refunds / reversals | Pending | Not in MVP scope |

## Phase 3 MVP exit criteria

- [x] Record payment allocates FIFO to oldest open issued invoice
- [x] Overpayment held as tenant credit (`PaymentRecordingTest`)
- [x] Unallocated credit reapplies when invoice balance opens
- [x] Payments index + show operational with flat API responses
- [x] Tenant balance endpoint includes utilities on open invoices
- [x] E2E smoke on live company (gaabsax) — bootstrap + invoices + delinquency flag validated

## Operational bootstrap

For companies missing rent model or misconfigured utility models:

```bash
cd laravel && php artisan billing:bootstrap-company \
  --company_id=019ea199-1b20-706a-a075-c39db4ad9b23 \
  --year=2026 --month=6 \
  --run-close
```

## Commands

```bash
cd laravel && php artisan test --filter=Payment
cd laravel && php artisan billing:bootstrap-company --company_id=<uuid>
```

## Next

- Run billing smoke on gaabsax (readings → charges → close → issue → payment)
- EVC webhook when mobile-money integration is scoped
- Phase 4 reporting / AR aging (see `project_document.md`)

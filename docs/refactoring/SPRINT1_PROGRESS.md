# Sprint 1 (G2) — Billing architecture unify

| Task | Status | Notes |
|------|--------|-------|
| 1.1 ADR canonical invoice | Done | [adr/001](./adr/001-invoice-canonical-model.md) — `MonthlyInvoice` |
| 1.2 Align consolidation | Done | `InvoiceConsolidationService` |
| 1.4 Route dedup | Done | `InvoiceController` removed; `MonthlyInvoiceController` |
| 1.5 ChargeModel `controls` | Done | `ChargeModelResource` |
| 1.6 meter_type naming | Done | `GenerateChargeService` + API alias |
| 1.7 Charge idempotency | Done | Migration + `charge_model_id` on charges |
| E2E test | Done | `BillingPipelineTest` |

## Verify

```bash
docker exec saas-laravel-engine php artisan migrate --force
docker exec saas-laravel-engine php artisan test
```

## Remaining (post–Sprint 1)

- Migrate/drop unused `invoices` table data
- `InvoiceGenerationService` line item column alignment
- Bulk invoice API (after canonical model stable)

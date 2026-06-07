# Phase 0 — Progress Tracker

**Status: CLOSED (G1)** — 2026-06-03

| Task | Status | Notes |
|------|--------|-------|
| 0.1 Test baseline + CI | Done | `.github/workflows/tests.yml` — migrate + PHPStan + PHPUnit |
| 0.2 Multi-tenancy tests | Done | Per-resource isolation + `TenancySmokeTest` |
| 0.3 Agreement state machine | Done | `AgreementStateMachine`, wired activate/terminate |
| 0.3 ProrateRentService | Done | Unit tests |
| 0.3 Billing guard | Done | `GenerateChargeService` + `BusinessRuleException` |
| 0.4 Meter reading tests | Done | 422 validation + approval + charge mock |
| 0.5 Financial precision | Done | `Money` + BCMath; migrations for `billing_items`, `invoice_line_items`, `monthly_invoices` |
| H10 Console/job tenant scope | Done | `TenantContext`, `CompanyScope`, `BelongsToCompany` |
| H11 Meter reading 422 | Done | Global API `ValidationException` render |
| PHPStan level 6 | Done | `phpstan.neon` + `phpstan-baseline.neon`; `composer analyse` |
| C7 Bulk invoice quarantine | Done | `BulkBillingQuarantined.vue` |

## Commands

```bash
cd laravel
composer analyse          # PHPStan level 6 (must pass)
php artisan migrate
php artisan test
```

Docker:

```bash
docker exec -it saas-laravel-engine php artisan migrate
docker exec -it saas-laravel-engine composer analyse
docker exec -it saas-laravel-engine php artisan test
```

## Phase 0 exit checklist

- [x] Tenancy isolation tests (core resources + ChargeModel + MeterReading)
- [x] Tenancy smoke test (multi-endpoint, two companies)
- [x] CI: migrations + PHPStan + PHPUnit
- [x] Agreement transitions unit tested
- [x] ProrateRentService unit tested
- [x] Meter reading 422 + approval tests
- [x] Console/job tenant context via `TenantContext`
- [x] Monetary columns NUMERIC(14,4) on billing_items, invoice_line_items, monthly_invoices
- [x] PHPStan level 6 with baseline (incremental burn-down in Phase 1+)

## Next: Phase 1 (Charge engine)

See [EXECUTION_PLAN.md](./EXECUTION_PLAN.md) — Charge Types polish, Charge Models completeness, utility billing UI.

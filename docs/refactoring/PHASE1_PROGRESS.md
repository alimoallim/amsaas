# Phase 1 — Charge Engine Progress

**Status: CLOSED** — 2026-06-03

| Stream | Status | Notes |
|--------|--------|-------|
| **1A** Charge Types UI | Done | Worklist + modal form |
| **1B** Charge Models backend | Done | CRUD, tier validation, versioning, clone |
| **1C** Charge Models UI | Done | All strategies in form; tiered/percentage; clone action |
| **1D** Charge review API + UI | Done | `ChargeController`, approve/reject/bulk, auto-sync on approve |
| **1E** Billing engine hardening | Done | Formula blocked; calc tests extended |

## Phase 1 exit criteria (from `project_document.md`)

- [x] ChargeModel UI supports all 5 pricing strategies with validation (formula blocked until engine exists)
- [x] Meter reading approval → charge generation (`BillingPipelineTest`)
- [x] Charges visible and approvable in UI (worklist + bulk approve)
- [x] Charge approve/reject state machine tested (`ChargeWorkflowTest`)
- [x] Billing calculation paths covered (`CalculateChargeServiceTest`, tier/store/version tests)

## Key deliverables

- `ChargeModelTierValidator` — contiguous tier bands, open-ended final tier
- `ChargeModelVersionService` — active in-use models version instead of in-place edit
- `POST /charge-models/{id}/clone` — draft duplicate for quick setup
- Formula strategy rejected at API + excluded from meter charge resolution
- Frontend: tiered, percentage, fixed in policy picker; clone on index

## Commands

```bash
cd laravel && php artisan test --filter=Charge
cd laravel && composer analyse
```

## Next: Phase 2 — Invoice Engine

See [EXECUTION_PLAN.md](./EXECUTION_PLAN.md) and `project_document.md` Phase 2.

# Architectural & Security Debt Register

Track remediation here. **Severity:** `CRITICAL` = cross-tenant or auth bypass; `HIGH` = broken domain or duplicate stack; `MEDIUM` = standards gap; `LOW` = cleanup.

**Status:** `Open` | `In progress` | `Done`

---

## CRITICAL — Data leakage & auth bypass

| ID | Status | Issue | Location | Remediation |
|----|--------|-------|----------|-------------|
| C1 | Done | 9 models have `company_id` but no `BelongsToCompany` | See §Models below | Sprint 0 — trait added |
| C2 | Done | `ChargeTypeController::index` unscoped | `ChargeTypeController.php` | Global scope via trait |
| C3 | Done | `BillingOperationsController::summary` aggregates all tenants | `BillingOperationsController.php` | Scoped models + tests |
| C4 | Done | `CompanyController` lists/shows/updates any company | `CompanyController.php` | Own-tenant + policies |
| C5 | Done | `MeterController` — `authorize` commented; unscoped model | `MeterController.php`, `Meter.php` | Policy registered + trait |
| C6 | Done | Invoice generate without Bearer token | `InvoicesIndex.vue` | `api.post('/billing/generate')` |
| C7 | Done | Bulk invoice pages use raw axios | `BulkInvoiceManager.vue`, `BatchInvoiceDashboard.vue` | Quarantined via `BulkBillingQuarantined.vue`; no API calls |

### Models missing `BelongsToCompany` (C1)

| Model | File |
|-------|------|
| ChargeType | `laravel/app/Models/ChargeType.php` |
| ChargeModel | `laravel/app/Models/ChargeModel.php` |
| Charge | `laravel/app/Models/Charge.php` |
| Meter | `laravel/app/Models/Meter.php` |
| MeterReading | `laravel/app/Models/MeterReading.php` |
| Invoice | `laravel/app/Models/Invoice.php` |
| BillingItem | `laravel/app/Models/BillingItem.php` |
| BillingRun | `laravel/app/Models/BillingRun.php` |
| AgreementCharge | `laravel/app/Models/AgreementCharge.php` |

### Models WITH `BelongsToCompany` (reference — do not regress)

Building, Apartment, Tenant, Agreement, User, MonthlyInvoice, InvoiceLineItem, Payment, PaymentAllocation, Buyer, ServiceFeeConfig, UtilityRateConfig, UtilityReading, InstallmentSchedule.

### Child models (scope via parent — document pattern)

| Model | Scoping strategy |
|-------|------------------|
| RentalAgreement | `whereHas('agreement', fn ($q) => $q->where('company_id', ...))` |
| SaleAgreement | Add policy + company via apartment/agreement when built |

---

## HIGH — Domain & billing integrity

| ID | Status | Issue | Location | Remediation |
|----|--------|-------|----------|-------------|
| H1 | Done | Dual invoice stacks (`MonthlyInvoice` vs `Invoice`) | ADR 001; `Invoice` deprecated | Canonical: `MonthlyInvoice` |
| H2 | Done | Consolidation service column mismatch | `InvoiceConsolidationService.php` | Writes `MonthlyInvoice` + `InvoiceLineItem` |
| H3 | Done | `RentalAgreement::where('status','active')` — wrong table | `BillingOperationsController` | `whereHas('agreement', status active)` |
| H4 | Done | `ChargeModelResource` missing `controls` | `ChargeModelResource.php` | `controls` block added |
| H5 | Done | `utility_type` vs `meter_type` on charge models | `GenerateChargeService` | Query uses `meter_type`; API exposes `utility_type` alias |
| H6 | Done | Weak charge idempotency | `GenerateChargeService` | Unique index + per-model guard |
| H7 | Done | `MeterPolicy` not registered | `AppServiceProvider` | Gate policies registered |
| H8 | Open | FormRequest `authorize()` returns `true` | `app/Http/Requests/Api/V1/*` | Policies per resource (controllers use authorize) |
| H9 | Done | `TenantIsolationTest` does not prove HTTP isolation | `tests/Feature/Tenancy/*` | HTTP tests added |
| H10 | Done | `CompanyScope` skips console — jobs unscoped | `CompanyScope.php`, `TenantContext.php` | Scope applies when `TenancyManager` / `tenant.current_id` set |
| H11 | Done | Meter reading errors return 500 not 422 | `MeterReadingController.php`, `bootstrap/app.php` | API `ValidationException` → 422; store lets exceptions bubble |
| H12 | Done | `MonthlyInvoiceResource` missing (if referenced) | `MonthlyInvoiceResource.php` | Resource + controls implemented |
| H13 | Done | `Building` duplicate scope (`BelongsToCompany` + custom `booted`) | `Building.php` | Removed duplicate booted scope |

---

## MEDIUM — Standards & maintainability

| ID | Status | Issue | Location |
|----|--------|-------|----------|
| M1 | Done | Float math in charge/meter services | `CalculateChargeService`, `MeterReadingProcessorService` | BCMath via `Money` helper |
| M2 | Done | Mixed `decimal(12,2)` / `(18,4)` not `(14,4)` | Phase 0 migrations: `billing_items`, `invoice_line_items`, `monthly_invoices` → NUMERIC(14,4) |
| M3 | Done | No composables — inline API in pages | Pilot pages use `frontend/src/composables/*` |
| M4 | Done | `window.confirm` on destructive actions | Replaced with `ConfirmModal` + `useConfirm` |
| M5 | Open | Hardcoded status for UI actions | `MeterShow`, rental agreement pages |
| M6 | Done | Duplicate `api.js` request interceptors + token `console.log` | `frontend/src/services/api.js` |
| M7 | Done | Dashboard logout not wired to auth store | `DashboardLayout.vue` |
| M8 | Done | Charge Types UI not in router | `router/index.js`, nav |
| M9 | Done | Charge Types UI uses `is_active`; API uses `status` | `ChargeTypeIndex.vue` |
| M10 | Open | Orphan/stub pages | `MeterDetail.vue`, `InvoiceLayout.vue`, bulk invoice pages |
| M11 | Done | Payments API + UI operational | `PaymentController`, `PaymentsIndex.vue`, `PaymentShow.vue` |
| M12 | Done | `EventServiceProvider` may be unregistered | `bootstrap/providers.php` |
| M13 | Open | Formula charge strategy not implemented | `CalculateChargeService` |

---

## LOW — Cleanup & docs

| ID | Status | Issue |
|----|--------|-------|
| L1 | Open | Untracked dev artifacts in repo root (`*.docx`, `JSP gropus.txt`) — gitignore or remove |
| L2 | Open | Legacy `UtilityReading` vs `MeterReading` overlap — document deprecation |
| L3 | Open | Align `TODO_AMSAAS_DEEP_REVIEW.md` with completed ChargeType API |

---

## Frozen / do not extend until debt cleared

| Area | Reason |
|------|--------|
| Sales / instalments (Phase 5) | Needs stable invoice + payment |
| General ledger (Phase 6) | Consumer of all financial events |
| Bulk invoice UI | Endpoints incomplete / unauthenticated calls |
| EVC Plus webhooks | Phase 3 |
| Second invoice stack | H1 must be resolved first |

---

## Changelog

| Date | Change |
|------|--------|
| 2026-06-03 | Initial register from full-stack audit |
| 2026-06-03 | Sprint 0: C1–C6, H3, H7, H9, H13, M6–M9 marked Done |
| 2026-06-03 | Phase 0: H10, H11 Done; M2 partial; tenancy tests for ChargeModel + MeterReading |
| 2026-06-03 | Phase 0 CLOSED: M2 Done; PHPStan L6 + baseline; monthly_invoices migration; TenancySmokeTest |
| 2026-06-03 | Phase 2 CLOSED: invoice engine + tenant billing; H12 Done; cron uses BillingPipelineService |

# Multi-Tenancy Security Checklist

Use for **every** model, controller, job, and endpoint that touches tenant-owned data.

---

## 1. Model checklist

| Check | Pass criteria |
|-------|---------------|
| Has `company_id` column? | If yes → must use `BelongsToCompany` **or** documented parent scope |
| Child of `Agreement` / `Apartment`? | Access only via scoped parent query |
| `Company` model itself? | Never global list for tenant users |
| Factory | Sets matching `company_id` |
| Seeder | Does not mix companies unintentionally |
| `withoutGlobalScopes()` | Only in platform admin code with audit |

### Models requiring `BelongsToCompany` (Sprint 0)

- [x] ChargeType
- [x] ChargeModel
- [x] Charge
- [x] Meter
- [x] MeterReading
- [x] Invoice *(deprecated stack; prefer MonthlyInvoice)*
- [x] BillingItem
- [x] BillingRun
- [x] AgreementCharge

---

## 2. Controller checklist

| Check | Pass criteria |
|-------|---------------|
| `index` / `paginate` | Relies on global scope or explicit `where('company_id')` |
| Route binding `{model}` | Scoped model → 404 for other tenant's UUID |
| Aggregates (`count`, `sum`) | Filtered by tenant |
| `CompanyController` | Tenant sees only own `company_id` |
| Manual `find($id)` | Avoid; use scoped query or `findOrFail` on scoped builder |
| Export / report endpoints | Same scope as index |

### Known fixes required

- [x] `ChargeTypeController::index`
- [x] `BillingOperationsController::summary`
- [x] `BillingOperationsController::triggerConsolidation` (use `Agreement`)
- [x] `CompanyController::index|show|update`

---

## 3. Service & job checklist

| Check | Pass criteria |
|-------|---------------|
| `DB::transaction` | Includes only same-tenant rows |
| Console commands | Pass `--company=` or iterate companies explicitly |
| Queue jobs | `company_id` in payload; `TenancyManager` or scoped queries inside `handle()` |
| Events/listeners | Tenant context restored before DB writes |
| `CompanyScope` skipped in console | Compensated via `TenantContext::setCompanyId()` (HTTP middleware, jobs, `billing:generate-monthly --company_id=`) |

### SYSTEM_ADMIN bypass

In `CompanyScope`, users with `role === 'SYSTEM_ADMIN'` skip the `company_id` filter (platform operations only). All tenant users must have a non-empty `company_id` or receive HTTP 403.

---

## 4. Policy & authorization

| Check | Pass criteria |
|-------|---------------|
| Policy registered | `Gate::policy(Model::class, XxxPolicy::class)` |
| `view/update/delete` | Compare `$user->company_id` to model's `company_id` (or parent) |
| FormRequest `authorize()` | Delegates to policy |
| SYSTEM_ADMIN | Explicit branch; log access |

Policies to add/register:

- [ ] Meter (exists, register)
- [ ] ChargeType
- [ ] ChargeModel
- [ ] Company (tenant-scoped)

---

## 5. Test matrix (100% tenancy coverage target)

Create two companies with factories. User A ∈ Company A. Record B ∈ Company B.

| Endpoint | Method | Expect for A accessing B's id |
|----------|--------|-------------------------------|
| `/api/v1/charge-types` | GET | No B rows in list |
| `/api/v1/charge-types/{id}` | GET | 404 |
| `/api/v1/charge-models/{id}` | GET | 404 |
| `/api/v1/meters/{id}` | GET | 404 |
| `/api/v1/meter-readings/{id}` | GET | 404 |
| `/api/v1/billing/summary` (or equivalent) | GET | Only A's counts |
| `/api/v1/buildings/{id}` | GET | 404 (regression) |
| `/api/v1/companies/{id}` | GET | 404 or 403 for B |

### Anti-patterns (do not rely on these alone)

| Anti-pattern | Why insufficient |
|--------------|------------------|
| `TenancyManager::setCompany()` without HTTP auth | Does not prove API isolation |
| Manual `abort_unless` in one action only | Other actions may forget |
| Frontend hiding menu items | Not security |

---

## 6. Frontend checklist

| Check | Pass criteria |
|-------|---------------|
| All API calls via `api.js` | Bearer attached |
| No hardcoded company id in payloads | Server sets `company_id` on create |
| UUIDs in URLs from API responses only | No guessing other tenant UUIDs |

---

## 7. Review questions (PR template)

1. Does this PR introduce a new table with `company_id`?
2. If yes, is `BelongsToCompany` added in the same PR?
3. Is there an HTTP isolation test?
4. Can a queue/console path run without tenant filter?
5. Does SYSTEM_ADMIN bypass need documentation?

Copy into PR description when touching tenant data.

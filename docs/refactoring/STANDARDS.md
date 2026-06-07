# Non-Negotiable Quality Standards (Refactoring Reference)

Extracted from `project_document.md` §07 and `africaerp_foundation_schema.sql`. **Every refactor PR must comply.** Violations are blockers, not style preferences.

---

## 1. Build order (master plan)

| Rule | Source |
|------|--------|
| Complete Phase 0 before Phase 1 features | `project_document.md` §PHASE 0 |
| Charge engine (Phase 1) before Invoice (Phase 2) before Payment (Phase 3) | §02 Master Dependency Chain |
| `SaleAgreement` is separate from `RentalAgreement` — never merge lifecycles | §PHASE 5 architecture decision |
| No frontend for a module until backend API is stable + documented | §00 CRITICAL RULE |

---

## 2. Backend standards

| Rule | Requirement | Verification |
|------|-------------|----------------|
| Thin controllers | Validate → Service → Resource only | Code review; no business logic in controllers |
| DB transactions | Multi-table writes in `DB::transaction()` | Grep services; integration tests |
| BCMath only | `bcadd`, `bcmul`, `bcdiv`, `bccomp` — no `(float)` on money | Static review + unit tests |
| `decimal(14,4)` | All monetary DB columns | Migration audit |
| Soft deletes on financial | Charges, invoices, payments, sale agreements | Model `SoftDeletes` trait |
| State machines | Status via enums; invalid transition → `BusinessRuleException` | 100% transition tests |
| Idempotency | Charge + invoice creation safe on retry | Unique constraints + tests |
| API resources | All responses via Resource; include **`controls`** | Feature tests assert keys |
| Multi-tenancy | `company_id` models use `BelongsToCompany` / `CompanyScope` | Per-model isolation test |

### Tenancy implementation (canonical)

```php
// Model
use App\Models\Traits\BelongsToCompany;

class ChargeType extends Model
{
    use BelongsToCompany;
}
```

- Scope source: `auth()->user()->company_id` via `CompanyScope`.
- **Console/queue:** scope is skipped — jobs must set tenant explicitly.
- **SYSTEM_ADMIN:** bypass in `CompanyScope` — document and audit any use.

### Agreement vs rental child

| Entity | `company_id` | `status` |
|--------|--------------|----------|
| `Agreement` | Yes (scoped) | Yes |
| `RentalAgreement` | No (child row) | No — use parent `Agreement` |

Billing queries must filter **`Agreement`**, not `RentalAgreement::where('status', ...)`.

---

## 3. Frontend standards

| Rule | Requirement |
|------|-------------|
| Controls object | Buttons from `response.data.controls` — not hardcoded status strings |
| Loading / empty | Skeletons + designed empty states on every list |
| Errors | 422 → field errors; 500 → banner; network → retry |
| Confirmations | Modal component — **no** `window.confirm()` |
| Currency | `Intl.NumberFormat` + company/building `operating_currency` |
| `<script setup>` only | No Options API |
| Composables | `useXxx()` for API — not raw axios in pages |

### API client

- Single module: `frontend/src/services/api.js`
- Base URL: `/api/v1` (or `VITE_API_BASE_URL`)
- **Never** use bare `axios.post('/api/...')` for authenticated routes

---

## 4. Testing requirements (by module type)

| Module type | Coverage target | Required tests |
|-------------|-----------------|----------------|
| Financial services | 95%+ | Pricing strategies, BCMath, edge cases |
| State machines | 100% | Valid + invalid transitions |
| API endpoints | 90%+ | Success, 422, 401/403, **cross-tenant**, 404 |
| Queue / webhooks | 90%+ | Idempotency, retries |
| Multi-tenancy | **100%** | Company A cannot access Company B per endpoint |

### Isolation test pattern (required)

```php
// HTTP feature test — not TenancyManager alone
$userA = User::factory()->for($companyA)->create();
$recordB = ChargeType::factory()->for($companyB)->create();

$this->actingAs($userA)
    ->getJson("/api/v1/charge-types/{$recordB->id}")
    ->assertNotFound(); // or 403 — must not return B's data
```

---

## 5. Database / schema alignment

Foundation schema (`africaerp_foundation_schema.sql`) defines:

- UUID primary keys
- PostgreSQL enums for status fields
- `NUMERIC(14,4)` for money
- Audit columns and triggers (target state)

**Refactor approach:** align via **Laravel migrations**, not one-shot SQL replace on production.

---

## 6. API JSON contract (summary)

See [API_CONTRACT.md](./API_CONTRACT.md).

Minimum for list/detail:

```json
{
  "data": { },
  "controls": {
    "can_edit": true,
    "can_delete": false,
    "can_approve": false
  }
}
```

---

## 7. PR rejection criteria (quick gate)

Reject if any of:

- [ ] New/changed model with `company_id` lacks `BelongsToCompany`
- [ ] Controller query without tenant scope on tenant-owned table
- [ ] Float arithmetic on money
- [ ] UI action visibility from hardcoded status only
- [ ] `window.confirm` for destructive action
- [ ] Authenticated call bypassing `api.js`
- [ ] No cross-tenant test for new endpoint
- [ ] Business logic added to controller

---

## 8. Phase exit criteria (refactor gates)

### Sprint 0 (leakage stop) — before any feature work

- All models in [DEBT_REGISTER.md](./DEBT_REGISTER.md) §C1 scoped
- Hotfix controllers listed in §C2–C6
- Frontend auth/routing fixes (§F1–F4)
- Isolation tests green for: ChargeType, ChargeModel, Meter, MeterReading, Billing summary

### Phase 0 (foundation hardening) — per `project_document.md`

- CI on push; coverage reporting
- Cross-tenant tests on **all** existing models
- Agreement state machine + proration tested
- Meter reading approval → charge event tested
- Financial precision audit complete

### Phase 1 — only after Phase 0 + Sprint 0

- Charge Types UI routed and contract-aligned
- Charge Models backend + UI to spec (versioning, tiers, controls)

Do not start Invoice Engine UI (Phase 2) until Phase 1 exit criteria pass.

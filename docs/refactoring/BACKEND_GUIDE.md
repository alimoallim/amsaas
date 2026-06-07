# Backend Refactoring Guide (Laravel)

Patterns to apply when fixing [DEBT_REGISTER.md](./DEBT_REGISTER.md) items. Stack: Laravel 12, PostgreSQL 16, Sanctum.

---

## 1. Layering (target architecture)

```
HTTP Request
  → FormRequest (validation + authorize)
  → Controller (delegate only)
  → Service (business logic, DB::transaction)
  → Model (scoped, relationships)
  → API Resource (+ controls)
```

**Controllers in this codebase to thin down over time:** billing operations, invoice bulk, any method with calculation logic.

---

## 2. Tenancy — canonical pattern

### Model

```php
namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class ChargeType extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'code',
        'name',
        'status',
        // ...
    ];
}
```

### Child resource (RentalAgreement)

```php
// Controller index
RentalAgreement::query()
    ->whereHas('agreement', fn ($q) => $q->where('company_id', auth()->user()->company_id))
    ->with('agreement')
    ->paginate(15);

// Policy view
public function view(User $user, RentalAgreement $rental): bool
{
    return $rental->agreement?->company_id === $user->company_id;
}
```

### Active agreements for billing

```php
use App\Models\Agreement;

Agreement::query()
    ->where('company_id', auth()->user()->company_id)
    ->where('status', AgreementStatus::ACTIVE)
    ->whereHas('rentalAgreement')
    ->with('rentalAgreement')
    ->get();
```

**Do not:** `RentalAgreement::where('status', 'active')`.

---

## 3. Policies

Register in `App\Providers\AppServiceProvider` or dedicated `AuthServiceProvider`:

```php
use Illuminate\Support\Facades\Gate;

public function boot(): void
{
    Gate::policy(\App\Models\Meter::class, \App\Policies\MeterPolicy::class);
    Gate::policy(\App\Models\ChargeType::class, \App\Policies\ChargeTypePolicy::class);
}
```

Policy stub:

```php
public function view(User $user, ChargeType $chargeType): bool
{
    return $user->company_id === $chargeType->company_id;
}
```

Controller:

```php
public function show(ChargeType $chargeType)
{
    $this->authorize('view', $chargeType);
    return new ChargeTypeResource($chargeType);
}
```

Route model binding + `BelongsToCompany` → foreign tenant UUID yields **404** before policy (acceptable).

---

## 4. API resources & `controls`

Reference implementation: `ChargeTypeResource`, `MeterResource`.

Every mutating resource should expose:

```php
'controls' => [
    'can_edit' => $this->status !== 'ARCHIVED',
    'can_delete' => $this->charges()->doesntExist(),
    'can_approve' => false,
],
```

Add to: `ChargeModelResource`, `RentalAgreementResource`, `BuildingResource` (incremental).

---

## 5. Financial calculations

### BCMath wrapper (introduce once, reuse)

```php
final class Money
{
    private const SCALE = 4;

    public static function mul(string $a, string $b): string
    {
        return bcmul($a, $b, self::SCALE);
    }
    // add, div, comp...
}
```

### Replace float casts

```diff
- $amount = (float) $reading->consumption * (float) $rate;
+ $amount = Money::mul((string) $reading->consumption, (string) $rate);
```

### Idempotent charge creation

```php
Charge::firstOrCreate(
    [
        'company_id' => $companyId,
        'meter_reading_id' => $reading->id,
        'charge_model_id' => $model->id,
    ],
    [ /* attributes */ ]
);
```

Plus migration:

```php
$table->unique(['meter_reading_id', 'charge_model_id']);
```

---

## 6. Error handling

| Exception | HTTP |
|-----------|------|
| `ValidationException` | 422 (do not catch as 500) |
| `BusinessRuleException` | 422 or 409 |
| `AuthorizationException` | 403 |
| Model not found (scoped) | 404 |

`MeterReadingController` pattern to fix:

```php
try {
    return $this->processor->store($request->validated());
} catch (ValidationException $e) {
    throw $e;
} catch (Throwable $e) {
    report($e);
    throw $e; // or 500 with generic message
}
```

---

## 7. Invoice domain decision (Sprint 1)

Before coding, write ADR `docs/refactoring/adr/001-invoice-canonical-model.md`:

| Option | Pros | Cons |
|--------|------|------|
| **A: `MonthlyInvoice`** | Already scoped; routes wired; line items exist | New consolidation code may reference `Invoice` |
| **B: `Invoice`** | Matches newer billing services naming | Less frontend; migration drift |

**Recommendation until ADR:** treat `MonthlyInvoice` + `InvoiceLineItem` as **rental billing invoice** for Phase 2; migrate `Invoice` table usage into it or rename in one migration set.

---

## 8. Queue & console

```php
// Job
public function __construct(public string $companyId, ...) {}

public function handle(): void
{
    $company = Company::findOrFail($this->companyId);
    // Use scoped queries with explicit where:
    Charge::withoutGlobalScopes()
        ->where('company_id', $this->companyId)
        ->...
}
```

Prefer explicit `company_id` in jobs over relying on `auth()` in workers.

---

## 9. Testing layout

```
tests/
  Feature/
    Tenancy/          # Cross-tenant HTTP
    Billing/          # Charge generation, consolidation
    Agreements/       # State machine
    MeterReadings/    # Approval, 422
  Unit/
    Services/         # BCMath, CalculateChargeService
```

Run: `cd laravel && php artisan test`

### Static analysis (Phase 0)

```bash
cd laravel && composer analyse
```

- Config: `phpstan.neon` (level **6**, Larastan)
- Baseline: `phpstan-baseline.neon` — shrink over time; do not add new ignores without fixing root cause when touching a file

---

## 10. Files to touch in Sprint 0 (quick reference)

| Area | Paths |
|------|-------|
| Models | `app/Models/{ChargeType,ChargeModel,Charge,Meter,MeterReading,Invoice,BillingItem,BillingRun,AgreementCharge}.php` |
| Scope | `app/Models/Scopes/CompanyScope.php` (document only unless fixing console) |
| Controllers | `ChargeTypeController`, `BillingOperationsController`, `CompanyController`, `MeterController` |
| Providers | `AppServiceProvider` / `AuthServiceProvider` |
| Tests | `tests/Feature/Tenancy/*` |

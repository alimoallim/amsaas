# Property Management — Business Logic

Core operational domains: **Buildings → Apartments → Tenants → Rental Agreements**.

## Layering

```
HTTP → FormRequest (validation)
     → Controller (thin)
     → Property/*Service (rules + transactions)
     → AgreementStateMachine (status transitions)
     → Models (scoped via BelongsToCompany)
```

## Services (`laravel/app/Services/Property/`)

| Service | Responsibility |
|---------|----------------|
| `BuildingPortfolioService` | Building belongs to company; sync `total_units`; block delete when units exist |
| `ApartmentInventoryService` | Rentability checks; lease conflicts; reserve / occupy / release unit; inventory guards |
| `TenantLeaseEligibilityService` | Block blacklisted/inactive tenants on new leases; block tenant delete with open agreements |
| `RentalAgreementService` | Create / activate / terminate / update / delete leases in DB transactions |

## Rental agreement lifecycle

| Status | Unit inventory | Notes |
|--------|----------------|-------|
| `draft` | `reserved` (from `available`) | Blocks second draft/active on same unit |
| `active` | `occupied` | Via `POST …/activate` or create with `status=active` |
| `terminated` | `available` | Requires `termination_reason` |
| `cancelled` | `available` if no other lease | State machine transition |

**Activation:** `draft` → `active` allowed (SMB flow). Enterprise flow can use `draft` → `pending_approval` → `approved` → `active`.

**Billing:** Only `Agreement::STATUS_ACTIVE` is billable (`AgreementStateMachine::ensureBillable`).

## API `controls` (rental agreements)

| Key | When true |
|-----|-----------|
| `can_edit` | Not terminated / completed / cancelled |
| `can_delete` | Draft only |
| `can_approve` | State machine allows → `approved` |
| `can_activate` | Can transition → `active` (not already active) |
| `can_terminate` | Active only |

**Routes:** `POST /rental-agreements/{id}/approve`, `/activate`, `/terminate` (requires `termination_reason`).

## Apartments (index)

| Field | Purpose |
|-------|---------|
| `occupancy.hint` | Human-readable occupancy line for worklist |
| `occupancy.has_active_lease` | Active agreement on unit |
| `controls.can_delete` | No draft/active/pending leases |

## Tests

- `tests/Unit/Agreements/AgreementStateMachineTest.php`
- `tests/Feature/Property/PropertyManagementLifecycleTest.php`

Run (with Postgres):

```bash
# Host machine (DB on localhost)
cd laravel && DB_HOST=127.0.0.1 php artisan test --filter=PropertyManagement

# Docker Compose
docker exec saas-laravel-engine php artisan test --filter=PropertyManagement
```

CI uses `DB_HOST=127.0.0.1` (see `.github/workflows/tests.yml`).

# API Contract Reference (v1)

Contract for refactoring frontend/backend alignment. Base path: **`/api/v1`**. Auth: **Bearer Sanctum** on protected routes.

---

## 1. Transport

| Item | Value |
|------|-------|
| Base URL | `VITE_API_BASE_URL` or `/api/v1` |
| Headers | `Accept: application/json`, `Authorization: Bearer {token}` |
| CSRF | `withCredentials: true` where session cookies used |

---

## 2. Response shapes

### Single resource (Laravel Resource)

```json
{
  "data": {
    "id": "uuid",
    "type": "charge_types",
    "attributes": { }
  }
}
```

Or flattened (current codebase — **pick one style per module during refactor**):

```json
{
  "id": "uuid",
  "name": "Electricity",
  "status": "ACTIVE",
  "controls": {
    "can_edit": true,
    "can_delete": false
  }
}
```

**Refactor goal:** consistent envelope per module; document per-controller in OpenAPI or this file when stabilized.

### Collection (paginated)

```json
{
  "data": [ ],
  "meta": {
    "current_page": 1,
    "last_page": 3,
    "per_page": 15,
    "total": 42
  },
  "links": { "next": "...", "prev": "..." }
}
```

### Validation error (422)

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "meter_id": ["The selected meter is invalid."]
  }
}
```

Frontend must surface `errors` per field.

### Business rule (409 / 422 custom)

```json
{
  "message": "Agreement must be ACTIVE to generate charges.",
  "code": "AGREEMENT_NOT_ACTIVE"
}
```

---

## 3. `controls` object (required on mutating resources)

| Key | Type | Meaning |
|-----|------|---------|
| `can_edit` | bool | Show edit action |
| `can_delete` | bool | Show delete (soft) |
| `can_approve` | bool | Approve reading/charge |
| `can_post` | bool | Post to invoice |
| `can_void` | bool | Void/cancel |

Optional domain keys: `can_approve`, `can_activate`, `can_terminate`, etc.

**Rental agreements** (`RentalAgreementResource`):

| Key | When true |
|-----|-----------|
| `can_edit` | Not finalized |
| `can_delete` | Draft only |
| `can_approve` | Valid transition to `approved` |
| `can_activate` | Valid transition to `active` |
| `can_terminate` | Status is `active` |

Endpoints: `POST /rental-agreements/{id}/approve`, `/activate`, `/terminate` (body: `termination_reason`).

**Apartments** (`ApartmentResource`): `occupancy` object + `controls.can_delete`.

**Backend:** compute from status + relations (existing charges block delete).

**Frontend:** `v-if="item.controls?.can_delete"` only.

---

## 4. Protected route groups (reference)

From `laravel/routes/api.php` (verify when refactoring):

| Prefix | Notes |
|--------|-------|
| `auth:sanctum` | All tenant CRUD |
| `charge-types` | Must be company-scoped |
| `charge-models` | Must be company-scoped |
| `meters`, `meter-readings` | Scoped |
| `invoices` | Currently `MonthlyInvoiceController` |
| `billing/*` | Operations summary — must be scoped |

---

## 5. Key endpoints — tenancy & contract

| Method | Path | Scope required | controls |
|--------|------|----------------|----------|
| GET | `/charge-types` | Yes | per row on show |
| POST | `/charge-types` | Sets company server-side | — |
| GET | `/charge-types/{id}` | Yes | Yes |
| GET | `/charge-models` | Yes | Yes on show |
| GET | `/meters/{id}` | Yes | Yes |
| POST | `/meter-readings` | Yes | — |
| POST | `/meter-readings/{id}/approve` | Yes | — |
| GET | `/billing/...` | Yes | N/A |

---

## 6. Charge Types — field contract

| Field | Type | Notes |
|-------|------|-------|
| `id` | uuid | |
| `code` | string | Unique per company |
| `name` | string | |
| `status` | enum/string | **Not** `is_active` |
| `sort_order` | int | optional |
| `controls` | object | on detail (and index if feasible) |

---

## 7. Charge Models — field contract

| Field | Type | Notes |
|-------|------|-------|
| `charge_type_id` | uuid | FK scoped to tenant charge types |
| `calculation_strategy` | enum | fixed, metered, tiered, formula (guard formula) |
| `meter_type` | enum | DB column; matches meter `utility_type` (e.g. `electricity`, `water`) |
| `utility_type` | enum | **Alias** of `meter_type` in API responses (Sprint 1) |
| `controls` | object | `can_edit`, `can_delete`, `can_activate` |
| `version` | int | For versioning (Phase 1B) |

### Invoices (canonical: `MonthlyInvoice`)

| Endpoint | Model |
|----------|--------|
| `GET/POST /api/v1/invoices` | `MonthlyInvoice` |
| Line items | `invoice_line_items.monthly_invoice_id` |
| `charges.invoice_id` / `billing_items.invoice_id` | FK to `monthly_invoices.id` |

Deprecated: `Invoice` model / `invoices` table (ADR 001).

---

## 8. Billing generate (fix in Sprint 0)

**Wrong (frontend today):**

`POST /api/billing/generate` via raw axios.

**Correct:**

`POST /api/v1/billing/generate` (or actual route name from `api.php`) via `api.js` with Bearer token.

Confirm exact path in `routes/api.php` when implementing — update this section with final route name.

---

## 9. Versioning & breaking changes

During refactor:

1. Prefer additive JSON fields.
2. Deprecate fields in comments + changelog.
3. Remove `is_active` only after frontend migrated to `status`.

---

## 10. OpenAPI (future)

After Sprint 1, generate `docs/openapi.yaml` from routes or Scribe. Until then, this file + Resources are the contract.

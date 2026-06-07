# AMSAAS Deep Review — Remaining Work (Prioritized)

## What we verified so far
- [x] Meter reading processing exists end-to-end (controller + processor) and can throw `ValidationException`
- [x] `MeterReadingController@store()` currently catches `Throwable` and converts all errors into HTTP **500** (this explains “validation returning 500”)
- [x] Charge generation MVP exists (`GenerateChargeService` + `CalculateChargeService`) and can fail if charge models use not-yet-implemented strategies (notably `formula`)
- [x] `ChargeModel` CRUD exists (request validation + resource + controller)
- [x] Frontend charge model UI depends on `GET /charge-types` (ChargeModelCreate loads it)
- [x] Web/API code search so far suggests there is **no** `/charge-types` endpoint (0 matches for `/charge-types` and no `ChargeType` controllers in inspected paths)

## Priority 1 — Fix meter reading validation status codes (HTTP 422)
- [ ] Update `laravel/app/Http/Controllers/Api/V1/MeterReadingController.php`
  - [ ] Do not convert `Illuminate\Validation\ValidationException` to 500
  - [ ] Ensure API responses keep Laravel’s standard `{ message, errors }` shape for 422
  - [ ] Re-test `POST /meter-readings` with invalid payloads and verify 422 + field errors
- [ ] Re-test `POST /meter-readings` when meter is non-operational (expected 422 with `errors.meter`)

## Priority 2 — Implement/restore charge types API needed by ChargeModelCreate
- [ ] Confirm what the backend expects:
  - [ ] `ChargeModelStoreRequest` validates `charge_type_id` as `exists:charge_types,id`
- [ ] Implement `GET /charge-types`
  - [ ] Create a controller in `laravel/app/Http/Controllers/Api/V1/ChargeTypeController.php`
  - [ ] Create an API resource `laravel/app/Http/Resources/Api/V1/ChargeTypeResource.php`
  - [ ] Add route(s) to `laravel/routes/api.php` under `v1` + `auth:sanctum` group
- [ ] Ensure multi-tenant isolation:
  - [ ] Scope `charge_types` listing to `company_id` (authenticated user)
- [ ] Verify frontend mapping:
  - [ ] ChargeModelForm expects `charge_types` list to populate `charge_type_id` (currently loads but may not render correctly if backend shape differs)

## Priority 3 — Make approve → charge generation reliable for an MVP
- [ ] Guard against `formula` strategy until formula engine is implemented
  - [ ] Option A: reject approving meter reading if any active auto_generate charge model uses `formula`
  - [ ] Option B: implement minimal `formula_expression` evaluation safely (later; higher risk)
- [ ] Decide MVP behavior for when there are **zero** auto_generate charge models:
  - [ ] Approving meter reading should either:
    - [ ] succeed but generate no charges (and warn), or
    - [ ] fail with 422/409 explaining missing configuration
- [ ] Re-test:
  - [ ] Create a charge model (fixed/metered/tiered) via API
  - [ ] Approve an approved/verified meter reading
  - [ ] Confirm charges are created and duplicate protection works

## Quality / verification
- [ ] Run backend tests (phpunit if present) and fix failures
- [ ] Run `tsc --noEmit` for frontend and fix type errors
- [ ] Run frontend build and verify no blank screens for charge model and meter reading pages

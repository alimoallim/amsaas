# Billing pipeline (consumption → payment)

Industry-standard flow implemented in AMSaaS for property management.

## Stages

| # | Stage | Entity | Status flow | API / action |
|---|--------|--------|-------------|----------------|
| 1 | **Usage capture** | `meter_readings` | `verified` / `draft` → `approved` | `POST /meter-readings`, `POST /meter-readings/{id}/approve` |
| 2 | **Utility charges** | `charges` | `pending` → `approved` | Auto-created on reading approve; `POST /charges/{id}/approve` |
| 3 | **Recurring charges** | `billing_items` | `pending` → posted on invoice | `POST /billing/generate` (runs billing run first) |
| 4 | **Consolidation** | `monthly_invoices` | `draft` | Same `POST /billing/generate` |
| 5 | **Issue** | `monthly_invoices` | `draft` → `issued` | `POST /invoices/{id}/finalize` (fires PDF/email event) |
| 6 | **Payment** | `payments` + `payment_allocations` | FIFO allocation | `POST /payments` |

## Rules (financial controls)

- **Only `verified` meter readings** can be approved (draft/anomaly must be resolved first).
- **Only `approved` utility charges** are included in invoice consolidation (`pending` charges are excluded).
- **Idempotency**: one charge per `(meter_reading_id, charge_model_id)`; one billing item per agreement charge per period; one monthly invoice per apartment/period/contract.
- **Payments** allocate oldest open invoices first for the tenant’s rental agreements (`contract_type = rental`, `contract_id = agreement_id`).

## Operator workflow (happy path)

1. Capture meter readings for the building/unit.
2. Review anomalies → approve readings → utility charges created (`pending`).
3. Review charges → approve utility charges (`approved`).
4. **Billing operations** → select period → **Compile invoices**  
   - Runs recurring billing run (rent + flat fees on active agreements).  
   - Consolidates approved utilities + unposted billing items into draft invoices.
5. **Monthly invoices** worklist (`/invoices/monthly`) → bulk or selective **Issue** (`POST /invoices/bulk-issue` or `finalize`).
6. Record tenant **payment** → allocations update invoice `paid_amount` / `balance_due`.

## API reference

```
GET  /api/v1/billing/pipeline-status?year=2026&month=6
GET  /api/v1/billing/summary?year=2026&month=6
POST /api/v1/billing/generate  { year, month, generate_recurring?: true }
POST /api/v1/invoices/{id}/finalize
POST /api/v1/payments  { tenant_id, amount, payment_date, payment_method, ... }
GET  /api/v1/invoices?year=&month=&view=attention|all&status=&building_id=&search=
GET  /api/v1/invoices/summary?year=&month=
POST /api/v1/invoices/bulk-issue  { year, month, ids?: [] }
```

## Code entry points

- `App\Services\Billing\BillingPipelineService` — orchestration & status
- `App\Services\MeterReading\MeterReadingProcessorService` — reading approve → charges
- `App\Services\Billing\ChargeWorkflowService` — charge approve/reject
- `App\Services\Billing\BillingProcessorService` — recurring billing items
- `App\Services\Billing\InvoiceConsolidationService` — draft monthly invoices
- `App\Services\InvoiceService` — issue & apply payment
- `App\Services\PaymentService` — record payment + FIFO allocation

## Tests

- `tests/Feature/Billing/BillingPipelineTest.php` — reading → approve charge → consolidate
- `tests/Feature/Billing/ChargeWorkflowTest.php` — charge approval HTTP
- `tests/Feature/Billing/MonthlyInvoiceWorklistTest.php` — worklist summary, bulk issue

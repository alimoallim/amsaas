# Billing smoke runbook (operator)

Use this checklist to validate end-to-end billing for one company before go-live or after configuration changes.

## Prerequisites

- Active rental agreements with `monthly_rent` > 0
- At least one **active** rent charge model (`pricing_strategy = agreement_rent`)
- Utility charge models with correct `meter_type` (water ≠ electricity)
- Meters linked to apartments where utility billing applies

## 1. Bootstrap company billing

```bash
cd laravel
php artisan billing:bootstrap-company \
  --company_id=<COMPANY_UUID> \
  --year=2026 --month=6
```

Optional: add `--run-close` to consolidate draft invoices after setup.

**gaabsax example:**

```bash
php artisan billing:bootstrap-company \
  --company_id=019ea199-1b20-706a-a075-c39db4ad9b23 \
  --year=2026 --month=6 \
  --run-close
```

## 2. Meter readings → charges

1. Enter readings in **Meter readings** (or approve existing draft readings).
2. Approve readings — utility charges generate when charge models match meter type.
3. Open **Charges** — filter **All** statuses; confirm pending/approved utility rows appear.

## 3. Billing close → invoices

1. **Billing close** (`/invoices`) or API `POST /api/v1/billing/generate`.
2. Open **Monthly invoices** for the target period.
3. Confirm draft invoices per active lease (rent + utilities on line items).

## 4. Issue invoices

1. Bulk-issue or issue individually from invoice detail.
2. Confirm status moves to `issued` (PDF/email queued).

## 5. Record payment

1. **Payments** → **Record payment**.
2. Select building + tenant; confirm **Balance due** matches open AR.
3. Submit — allocation should hit oldest invoice first (FIFO).
4. Open receipt from list — verify allocations on **Payment receipt** page.

## 6. Flag overdue invoices

```bash
php artisan collections:flag-overdue --company_id=<COMPANY_UUID> --as-of=2026-06-15
```

Open **Reports** — confirm delinquency queue shows flagged invoices with escalation stage.

## 7. Verify tenant billing view

`/tenants/:id/billing` — issued invoices, payments, and open balance align with Payments and Monthly invoices.

## Troubleshooting

| Symptom | Likely cause |
|---------|----------------|
| Charges list empty | No approved readings, or `meter_type` mismatch on charge model |
| No rent on invoice | Missing rent charge model — run `billing:bootstrap-company` |
| Payment not allocated | No issued invoice with open balance for tenant |
| Unallocated credit | Overpayment or utilities added after payment — credit reapplies on next balance |

## Automated checks

```bash
cd laravel && php artisan test --filter=Billing
cd laravel && php artisan test --filter=Payment
```

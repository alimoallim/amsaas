# Phase 6.2b — Accounting Wiring (Rental & Sales)

Ordered task list connecting operational modules to the chart of accounts.

## Account map (11 system accounts)

| Code | Account | Module use |
|------|---------|------------|
| 1110 | Cash | `payment_method=cash` receipts |
| 1115 | Bank Accounts | `payment_method=bank_transfer` |
| 1116 | Mobile Money Wallets | `payment_method=mobile_money` |
| 1117 | Cheques in Transit | `payment_method=cheque` |
| 1120 | Accounts Receivable | Invoice issue (DR), payment allocation (CR) |
| 2120 | Customer Deposits Payable | Sale reservation deposits |
| 2130 | Deferred Revenue | Reserved — prepaid rent (future) |
| 4100 | Rental Income | Rental invoice rent subtotal |
| 4110 | Utility Recovery Income | Rental invoice utilities |
| 4140 | Service Charge Income | Rental invoice services |
| 4150 | Property Sale Revenue | Sale installment invoices |

## Task checklist (in order)

### 1. Posting rules layer — DONE
- [x] `PostingRuleService` — payment method → receipt account
- [x] Invoice credit lines by `contract_type` (rental vs sale)
- [x] Charge type `ledger_account_code` override per category (rent / utility / service)

### 2. Chart of accounts expansion — DONE
- [x] Seed 1115, 1116, 1117, 4150 on company create
- [x] Rename 1120 → Accounts Receivable, 2120 → Customer Deposits Payable, 2130 → Deferred Revenue
- [x] **Ops:** Re-seed existing companies — adds missing system accounts and syncs renamed labels:
  ```bash
  # One company
  docker compose exec -T laravel-engine php artisan accounting:seed-chart --company_id=<uuid>
  # All companies
  docker compose exec -T laravel-engine php artisan accounting:seed-chart
  ```

### 3. Rental payment posting — DONE
- [x] `postPaymentAllocation` uses `payment_method` for debit account
- [x] Credit always 1120 AR
- [x] Payment API returns `posting.receipt_account_code`
- [x] Payment Show displays receipt account + journal lines

### 4. Rental invoice posting — DONE
- [x] Rent / utility / service buckets → 4100 / 4110 / 4140 (or charge type override)
- [x] DR 1120 AR on issue

### 5. Sale invoice posting — DONE
- [x] `contract_type=sale` → CR 4150 Property Sale Revenue (not 4140)

### 6. Sale payment posting — DONE
- [x] `SaleAgreementPostingService` → `postSalePaymentAllocation`
- [x] DR receipt account (by method) · CR 1120 AR

### 7. Sale reservation deposit — DONE
- [x] `ReservationService` → `postCustomerDeposit`
- [x] DR receipt account · CR 2120 Customer Deposits Payable

### 8. Rental security deposits — DONE
- [x] Security deposit received → DR receipt · CR 2120 (`rental_security_deposit`)
- [x] Deposit refunded → DR 2120 · CR receipt (`rental_deposit_refund`)
- [x] Deposit applied to invoice → DR 2120 · CR 1120 (`rental_deposit_application`)
- [x] `payments.payment_purpose` + `agreement_id`; `deposit_applications` table
- [x] Rental agreement show exposes `financials.deposit_ledger` (required / received / available)

### 9. Sale deposit application — DONE
- [x] Apply reservation deposit to sale contract → DR 2120 · CR 1120 (`sale_deposit_application`)
- [x] `sale_deposit_applications` table; counts toward `paid_amount` / `balance_due`
- [x] `POST /sale-agreements/{id}/apply-deposit`; sale show exposes `financials.deposit_ledger`

### 10. Charge-type line mapping — DONE
- [x] Invoice issue credits built from line items + `charge_type_id` → `ledger_account_code`
- [x] Falls back to category buckets when no line items (legacy invoices)
- [x] `charge_type_id` on `invoice_line_items`; set from billing consolidation / manual invoice
- [x] Charge Types form: GL account datalist + link to chart of accounts

### 11. Payment UI — DONE
- [x] Optional override: `receipt_account_code` on payment + picker on record form
- [x] Show mapped account name (not just code) on payment form and payment show
- [x] `GET /payments/receipt-account-options` for method defaults + account labels

## Journal source types

| `source_type` | Trigger |
|---------------|---------|
| `monthly_invoice_issued` | Invoice issue |
| `payment_allocation` | Tenant payment FIFO allocation |
| `sale_payment_allocation` | Sale contract payment |
| `customer_deposit` | Sale reservation deposit receipt |
| `rental_security_deposit` | Rental security deposit received |
| `rental_deposit_refund` | Rental security deposit refunded |
| `rental_deposit_application` | Rental deposit applied to invoice |
| `sale_deposit_application` | Sale reservation deposit applied to contract |

## Key files

- `app/Services/Accounting/PostingRuleService.php`
- `app/Services/Accounting/JournalEntryService.php`
- `app/Services/Accounting/ChartOfAccountsService.php`
- `app/Http/Resources/Api/V1/PaymentResource.php`
- `frontend/src/Pages/Payments/PaymentShow.vue`

## Tests

```bash
docker compose exec -T laravel-engine php artisan test --filter=Accounting
docker compose exec -T laravel-engine php artisan test --filter=ChartOfAccountsSeed
```
